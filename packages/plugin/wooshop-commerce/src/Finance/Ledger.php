<?php
declare(strict_types=1);

namespace WooShop\Commerce\Finance;

use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Marketplace\Records;
use WooShop\Commerce\Support\DomainError;

/** Append-only commerce subledger; posting never implies verified external money movement. */
final class Ledger
{
    private Records $records;
    private const ACCOUNTS = ['provider_clearing', 'seller_payable', 'tax_payable', 'platform_commission', 'gateway_fee', 'promotion_expense', 'seller_receivable', 'dispute_suspense'];

    public function __construct(private Database $db) { $this->records = new Records($db); }

    public static function validate(array $entries): Money
    {
        if (count($entries) < 2 || count($entries) > 1000) {
            throw new DomainError('invalid_journal', 'A journal requires 2–1000 entries.', 422);
        }
        $unit = null;
        $debits = $credits = 0;
        foreach ($entries as $entry) {
            if (!in_array($entry['account'] ?? '', self::ACCOUNTS, true) || !in_array($entry['side'] ?? '', ['D', 'C'], true)) {
                throw new DomainError('invalid_journal', 'Unknown account or entry side.', 422);
            }
            $money = Money::from($entry['amount'] ?? []);
            if ($money->minor <= 0) {
                throw new DomainError('invalid_journal', 'Entries must be positive; reverse the side for corrections.', 422);
            }
            if (str_starts_with($entry['account'], 'seller_') && !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $entry['seller_id'] ?? '')) {
                throw new DomainError('invalid_journal', 'Seller accounts require seller identity.', 422);
            }
            $unit ? $unit->sameUnit($money) : $unit = $money;
            if ($entry['side'] === 'D') { $debits += $money->minor; } else { $credits += $money->minor; }
            if ($debits > Money::MAX || $credits > Money::MAX) {
                throw new DomainError('amount_overflow', 'Journal total exceeds monetary range.', 422);
            }
        }
        if ($debits !== $credits) {
            throw new DomainError('unbalanced_journal', 'Journal debits and credits must balance exactly.', 422);
        }
        return new Money((string) $debits, $unit->currency, $unit->exponent);
    }

    public function post(string $commandKey, string $source, array $entries, int $actor, ?string $reversalOf = null): array
    {
        $total = self::validate($entries);
        if (!$source || strlen($source) > 200) {
            throw new DomainError('invalid_source', 'A durable source reference is required.', 422);
        }
        return $this->records->command('journal', $commandKey, compact('source', 'entries', 'reversalOf'), function () use ($commandKey, $source, $entries, $actor, $reversalOf, $total): array {
            if ($reversalOf !== null) {
                $original = $this->records->one('journals', $reversalOf);
                if ($original['currency'] !== $total->currency || (int) $original['exponent'] !== $total->exponent) {
                    throw new DomainError('currency_mismatch', 'Reversal must preserve original units.', 422);
                }
                $w = $this->db->wpdb();
                $old = $w->get_results($w->prepare('SELECT account,seller_id,side,amount_minor FROM '.$this->db->table('journal_entries').' WHERE journal_id=%s ORDER BY sequence', $reversalOf), ARRAY_A);
                $expected = array_map(static fn (array $e): string => Records::canonical(['account' => $e['account'], 'seller_id' => $e['seller_id'], 'side' => $e['side'] === 'D' ? 'C' : 'D', 'amount_minor' => (string) $e['amount_minor']]), $old);
                $actual = array_map(static fn (array $e): string => Records::canonical(['account' => $e['account'], 'seller_id' => $e['seller_id'] ?? '', 'side' => $e['side'], 'amount_minor' => $e['amount']['amount_minor']]), $entries);
                sort($expected, SORT_STRING); sort($actual, SORT_STRING);
                if ($expected !== $actual) { throw new DomainError('invalid_reversal', 'A reversal must invert every original entry exactly once.', 422); }
            }
            $id = Records::id();
            $this->records->insert('journals', ['id' => $id, 'command_key' => hash('sha256', $commandKey), 'source_ref' => $source, 'reversal_of' => $reversalOf, 'currency' => $total->currency, 'exponent' => $total->exponent, 'digest' => hash('sha256', Records::canonical($entries)), 'actor_id' => $actor, 'created_at' => $this->db->now()]);
            foreach ($entries as $entry) {
                $this->records->insert('journal_entries', ['journal_id' => $id, 'account' => $entry['account'], 'seller_id' => $entry['seller_id'] ?? '', 'side' => $entry['side'], 'amount_minor' => $entry['amount']['amount_minor']]);
            }
            return ['id' => $id, 'source_ref' => $source, 'total' => $total->jsonSerialize(), 'reversal_of' => $reversalOf];
        });
    }

    public function sellerStatement(string $seller, string $currency): array
    {
        if (!preg_match('/^[A-Z]{3}$/D', $currency)) { throw new DomainError('invalid_currency', 'Invalid currency.', 400); }
        $w = $this->db->wpdb();
        $rows = $w->get_results($w->prepare('SELECT e.account,e.side,e.amount_minor,j.exponent FROM '.$this->db->table('journal_entries').' e JOIN '.$this->db->table('journals').' j ON j.id=e.journal_id WHERE e.seller_id=%s AND j.currency=%s ORDER BY e.sequence', $seller, $currency), ARRAY_A);
        $balances = [];
        foreach ($rows as $row) {
            $account = $row['account'];
            $value = new Money((string) (($row['side'] === 'C' ? 1 : -1) * (int) $row['amount_minor']), $currency, (int) $row['exponent']);
            $balances[$account] = isset($balances[$account]) ? $balances[$account]->add($value) : $value;
        }
        return ['seller_id' => $seller, 'currency' => $currency, 'balances' => array_map(static fn (Money $m): array => $m->jsonSerialize(), $balances), 'available_payable' => null, 'payout_state' => 'blocked', 'reason' => 'Provider, legal model, reconciliation and independent approval are not configured.'];
    }
}
