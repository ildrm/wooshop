<?php
declare(strict_types=1);
namespace WooShop\Commerce\Merchandising;

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Catalog\Products;
use WooShop\Commerce\Catalog\Specifications;
use WooShop\Commerce\Customer\Records;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

final class Merchandising
{
    private Database $db; private EntityStore $store; private Products $products; private Specifications $specs;
    public function __construct(Database $db, EntityStore $store, Products $products, Specifications $specs) { $this->db = $db; $this->store = $store; $this->products = $products; $this->specs = $specs; }

    private function manage(): void { if (!current_user_can('commerce.settings.manage') && !current_user_can('manage_woocommerce')) { throw new DomainError('forbidden', 'Commerce settings permission is required.', 403); } }

    public function placement(array $input, int $revision): array
    {
        Specifications::requirePublisher();
        Input::keys($input, ['placement_key', 'curated_product_ids', 'sponsored'], ['placement_key', 'curated_product_ids', 'sponsored']);
        $input['placement_key'] = Input::text($input['placement_key'], 1, 64);
        if (!preg_match('/^[a-z][a-z0-9_.-]*$/D', $input['placement_key']) || !is_array($input['curated_product_ids']) || !array_is_list($input['curated_product_ids']) || count($input['curated_product_ids']) > 20 || count(array_unique($input['curated_product_ids'], SORT_REGULAR)) !== count($input['curated_product_ids'])) { throw new DomainError('invalid_placement', 'Use a stable placement key and at most 20 unique products.', 422); }
        Input::bool($input['sponsored']);
        foreach ($input['curated_product_ids'] as $id) { $product = $this->products->get(Input::id($id)); if (!$this->products->capabilities($product)['supported'] || !$product->is_visible()) { throw new DomainError('ineligible_product', 'Only visible products with a supported native capability may be placed.', 422); } }
        $id = Input::stableId('placement:' . $input['placement_key']); $existing = $this->store->get('placement', $id);
        if ($existing) { Records::expect($existing, $revision); return $this->store->update('placement', $id, $revision, $input); }
        if ($revision !== 1) { throw new DomainError('stale_revision', 'Initial placement revision is 1.', 412); }
        return $this->store->create('placement', $input, 'catalog', $id);
    }

    public function recommendations(string $placement, int $limit = 10): array
    {
        $placement = Input::text($placement, 1, 64); if ($limit < 1 || $limit > 20) { throw new DomainError('invalid_limit', 'Placement limit is 1–20.', 422); }
        $record = $this->store->get('placement', Input::stableId('placement:' . $placement));
        if (!$record) { return []; }
        $cards = [];
        foreach ($record['data']['curated_product_ids'] as $id) {
            try { $product = $this->products->get($id); } catch (DomainError $error) { if ($error->status === 404) { continue; } throw $error; }
            if (!$product->is_visible() || !$this->products->capabilities($product)['supported']) { continue; }
            $cards[] = $this->products->publicCard($product) + ['source' => 'curated', 'sponsored' => $record['data']['sponsored']];
            if (count($cards) >= $limit) { break; }
        }
        return $cards;
    }

    public function relationship(array $input, int $revision): array
    {
        Specifications::requirePublisher();
        Input::keys($input, ['source_product_id', 'target_product_id', 'type', 'evidence_ref', 'valid_from', 'valid_to'], ['source_product_id', 'target_product_id', 'type', 'valid_from', 'valid_to']);
        foreach (['source_product_id', 'target_product_id'] as $field) { $this->products->get(Input::id($input[$field])); }
        if ($input['source_product_id'] === $input['target_product_id'] || !in_array($input['type'], ['accessory', 'replacement', 'complement', 'compatible'], true)) { throw new DomainError('invalid_relationship', 'Choose two distinct products and a supported relationship.', 422); }
        $this->dates($input['valid_from'], $input['valid_to']);
        if (in_array($input['type'], ['compatible', 'replacement'], true) && (!$this->specs->approvedEvidence($input['evidence_ref'] ?? null, $input['source_product_id']) || !$this->specs->approvedEvidence($input['evidence_ref'] ?? null, $input['target_product_id']) || !current_user_can('commerce.evidence.approve'))) { throw new DomainError('evidence_required', 'Specialist-reviewed evidence covering both products is required.', 422); }
        $id = Input::stableId('relation:' . $input['source_product_id'] . ':' . $input['target_product_id'] . ':' . $input['type']); $existing = $this->store->get('product_relationship', $id);
        if ($existing) { Records::expect($existing, $revision); return $this->store->update('product_relationship', $id, $revision, $input); }
        if ($revision !== 1) { throw new DomainError('stale_revision', 'Initial relation revision is 1.', 412); }
        return $this->store->create('product_relationship', $input, 'product:' . $input['source_product_id'], $id);
    }

    public function relationships(string $product): array
    {
        $this->products->get(Input::id($product)); $items = [];
        foreach ($this->store->find('product_relationship', 'product:' . $product, 100) as $record) {
            $data = $record['data'];
            if (strtotime($data['valid_from']) > time() || strtotime($data['valid_to']) <= time()) { continue; }
            if (in_array($data['type'], ['compatible', 'replacement'], true) && (!$this->specs->approvedEvidence($data['evidence_ref'], $product) || !$this->specs->approvedEvidence($data['evidence_ref'], $data['target_product_id']))) { continue; }
            try { $target = $this->products->get($data['target_product_id']); } catch (DomainError $error) { if ($error->status === 404) { continue; } throw $error; }
            $items[] = $this->products->publicCard($target) + ['relationship' => $data['type']];
        }
        return $items;
    }

    /** Creates terms only. Independent funding approval and a reviewed activation policy are required. */
    public function campaign(array $input, int $revision): array
    {
        $this->manage();
        Input::keys($input, ['key', 'starts_at', 'ends_at', 'timezone', 'product_ids', 'native_coupon_refs', 'badge_text'], ['key', 'starts_at', 'ends_at', 'timezone', 'product_ids', 'native_coupon_refs', 'badge_text']);
        $input['key'] = Input::text($input['key'], 1, 64); $input['badge_text'] = Input::text($input['badge_text'], 1, 40);
        $this->dates($input['starts_at'], $input['ends_at']);
        if (!is_string($input['timezone']) || !in_array($input['timezone'], \DateTimeZone::listIdentifiers(), true)) { throw new DomainError('invalid_timezone', 'Choose a recognized IANA timezone.', 422); }
        foreach (['product_ids', 'native_coupon_refs'] as $field) { if (!is_array($input[$field]) || !array_is_list($input[$field]) || count($input[$field]) > 100 || count(array_unique($input[$field], SORT_REGULAR)) !== count($input[$field])) { throw new DomainError('invalid_campaign', 'Campaign references must be unique bounded lists.', 422); } }
        foreach ($input['product_ids'] as $id) { $this->products->get(Input::id($id)); }
        foreach ($input['native_coupon_refs'] as $id) { Input::id($id); $coupon = new \WC_Coupon((int) $id); if (!$coupon->get_id() || get_post_status((int) $id) !== 'publish') { throw new DomainError('coupon_unavailable', 'A published native WooCommerce coupon is required.', 422); } }
        $id = Input::stableId('campaign:' . $input['key']); $existing = $this->store->get('campaign', $id);
        $data = $input + ['state' => 'draft', 'prepared_by' => (string) get_current_user_id(), 'approved_by' => null];
        if ($existing) { Records::expect($existing, $revision); $this->store->create('campaign_snapshot', $existing['data'] + ['source_revision' => $revision], 'catalog'); return $this->store->update('campaign', $id, $revision, $data); }
        if ($revision !== 1) { throw new DomainError('stale_revision', 'Initial campaign revision is 1.', 412); }
        return $this->store->create('campaign', $data, 'catalog', $id);
    }

    public function approveCampaign(string $id, int $revision): array
    {
        if (!current_user_can('commerce.promotions.approve')) { throw new DomainError('forbidden', 'Independent promotion funding permission is required.', 403); }
        $record = $this->store->get('campaign', Input::uuid($id)); if (!$record) { throw new DomainError('not_found', 'Campaign unavailable.', 404); }
        Records::expect($record, $revision);
        if ($record['data']['prepared_by'] === (string) get_current_user_id()) { throw new DomainError('self_approval', 'A different funding reviewer must approve this campaign.', 403); }
        $data = $record['data']; $data['approved_by'] = (string) get_current_user_id(); $data['state'] = 'scheduled';
        return $this->store->update('campaign', $id, $revision, $data);
    }

    public static function campaignState(array $data, int $now): string
    {
        if (($data['state'] ?? '') === 'draft' || empty($data['approved_by'])) { return 'draft'; }
        if (strtotime($data['ends_at']) <= $now) { return 'ended'; }
        return strtotime($data['starts_at']) > $now ? 'scheduled' : 'active';
    }

    public function campaigns(?string $product = null): array
    {
        $items = [];
        foreach ($this->store->find('campaign', 'catalog', 100) as $record) {
            $data = $record['data']; $state = self::campaignState($data, time());
            if ($state !== 'active' || ($product !== null && !in_array($product, $data['product_ids'], true))) { continue; }
            // No marketing activation is inferred from saving a campaign or approving its funding.
            $approved = function_exists('wooshop_commerce_setting') ? wooshop_commerce_setting('campaign.activation_approved', false) : false;
            if ($approved !== true) { continue; }
            $items[] = ['id' => $record['id'], 'badge_text' => $data['badge_text'], 'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'], 'timezone' => $data['timezone'], 'state' => $state];
        }
        return $items;
    }

    private function dates($from, $to): void
    {
        foreach ([$from, $to] as $date) { if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $date) || strtotime($date) === false) { throw new DomainError('invalid_date', 'Use UTC ISO8601 dates.', 422); } }
        if (strtotime($to) <= strtotime($from)) { throw new DomainError('invalid_schedule', 'The end must follow the start.', 422); }
    }

    public function register(): void
    {
        add_shortcode('commerce_recommendations', function ($attributes): string {
            $attributes = shortcode_atts(['placement' => 'home', 'limit' => 10], $attributes);
            try { $cards = $this->recommendations((string) $attributes['placement'], (int) $attributes['limit']); } catch (DomainError $error) { return '<p>' . esc_html($error->getMessage()) . '</p>'; }
            if (!$cards) { return ''; }
            $html = '<section class="commerce-panel"><h2>' . esc_html__('Recommended products', 'wooshop-commerce') . '</h2><ul class="commerce-products">';
            foreach ($cards as $card) { $html .= '<li>' . ($card['sponsored'] ? '<p>' . esc_html__('Sponsored', 'wooshop-commerce') . '</p>' : '') . '<a href="' . esc_url($card['url']) . '">' . esc_html($card['name']) . '</a><p>' . wp_kses_post($card['price_html']) . '</p></li>'; }
            return $html . '</ul></section>';
        });
        add_action('woocommerce_single_product_summary', function (): void {
            global $product; if (!$product) { return; }
            foreach ($this->campaigns((string) $product->get_id()) as $campaign) { echo '<p class="commerce-campaign"><strong>' . esc_html($campaign['badge_text']) . '</strong> <time datetime="' . esc_attr($campaign['ends_at']) . '">' . esc_html($campaign['ends_at'] . ' UTC') . '</time></p>'; }
        }, 9);
        add_action('woocommerce_after_single_product_summary', function (): void {
            global $product; if (!$product) { return; }
            $relations = $this->relationships((string) $product->get_id()); if (!$relations) { return; }
            echo '<section class="commerce-panel"><h2>' . esc_html__('Related products and accessories', 'wooshop-commerce') . '</h2><ul>';
            foreach ($relations as $relation) { echo '<li><span>' . esc_html($relation['relationship']) . '</span>: <a href="' . esc_url($relation['url']) . '">' . esc_html($relation['name']) . '</a></li>'; } echo '</ul></section>';
        }, 25);
    }
}
