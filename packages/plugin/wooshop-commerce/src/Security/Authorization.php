<?php
declare(strict_types=1);
namespace WooShop\Commerce\Security;

use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Support\DomainError;

final class Authorization
{
    public function __construct(private readonly Database $db) {}

    public function can(string $capability, ?string $sellerId = null): bool
    {
        if (!is_user_logged_in()) { return false; }
        // Privileged approval requires a verified identity integration, not an administrator shortcut.
        if (in_array($capability, ['commerce.payout.approve', 'commerce.refund.approve'], true)) {
            return false;
        }
        if (current_user_can('manage_options') || current_user_can('manage_woocommerce')) { return true; }
        if ($sellerId === null) { return current_user_can($capability); }
        $sql = $this->db->wpdb()->prepare(
            'SELECT m.capabilities,s.state AS seller_state FROM ' . $this->db->table('memberships') . ' m INNER JOIN ' . $this->db->table('sellers') . ' s ON s.id=m.seller_id WHERE m.seller_id=%s AND m.user_id=%d AND m.state=%s',
            $sellerId, get_current_user_id(), 'active'
        );
        $row = $this->db->wpdb()->get_row($sql, ARRAY_A);
        if (!$row) { return false; }
        $continuing = preg_match('/(read|fulfill|shipment|return|dispute|finance\.view)/', $capability) === 1;
        if ($row['seller_state'] !== 'approved' && !$continuing) { return false; }
        $caps = json_decode($row['capabilities'], true);
        if (!is_array($caps)) { return false; }
        $implied = ['commerce.allocation.read.own' => 'commerce.allocation.fulfill', 'commerce.statement.read' => 'commerce.finance.read.own'];
        return in_array($capability, $caps, true) || (isset($implied[$capability]) && in_array($implied[$capability], $caps, true));
    }

    public function require(string $capability, ?string $sellerId = null): void
    {
        if (!$this->can($capability, $sellerId)) {
            throw new DomainError('forbidden', 'You cannot perform this action in the selected resource scope.', is_user_logged_in() ? 403 : 401);
        }
    }
}
