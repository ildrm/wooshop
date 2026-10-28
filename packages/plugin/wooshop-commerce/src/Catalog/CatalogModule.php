<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Customer\CustomerService;
use WooShop\Commerce\Customer\Notifications;
use WooShop\Commerce\Customer\Records;
use WooShop\Commerce\Customer\Session;
use WooShop\Commerce\Customer\Privacy;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Seo\Policy;
use WooShop\Commerce\Merchandising\Merchandising;
use WooShop\Commerce\Merchandising\PriceHistory;
use WooShop\Commerce\Merchandising\Loyalty;
use WooShop\Commerce\Merchandising\LoyaltyPolicy;
use WooShop\Commerce\Merchandising\Incentives;

final class CatalogModule
{
    private Database $db; private EntityStore $store;
    public function __construct(Database $db, EntityStore $store) { $this->db = $db; $this->store = $store; }

    public function register(): void
    {
        $session = new Session(); $products = new Products();
        $specs = new Specifications($this->db, $this->store, $products);
        $search = new Search($products, $specs);
        $customer = new CustomerService($this->db, $this->store, $session, $products, $specs);
        $community = new Community($this->db, $this->store, $session, $products, $specs);
        $compatibility = new Compatibility($this->store, $products, $specs);
        $notifications = new Notifications($this->db, $this->store, $session, $products, $customer);
        $merchandising = new Merchandising($this->db, $this->store, $products, $specs);
        $priceHistory = new PriceHistory($this->db, $this->store, $products);
        $reviewedPolicy = apply_filters('wooshop_commerce_reviewed_loyalty_policy', null);
        $loyalty = new Loyalty($this->db, $this->store, $reviewedPolicy instanceof LoyaltyPolicy ? $reviewedPolicy : null, Options::get('loyalty.enabled', false) === true);
        $reviewedIncentives = apply_filters('wooshop_commerce_reviewed_incentive_rules', []);
        $incentives = new Incentives($this->store, is_array($reviewedIncentives) ? $reviewedIncentives : []);
        $rate = new RateLimit($this->store);
        $controller = new RestController($specs, $search, $customer, $community, $compatibility, $notifications, $session, new Records($this->db, $this->store), $rate);
        add_action('rest_api_init', [$controller, 'register']);
        (new Views($session, $products, $specs, $customer, $community, $notifications, $search, $rate, $loyalty, $incentives))->register();
        (new Admin($this->store, $specs, $compatibility, $community, $merchandising))->register();
        (new Policy($search))->register();
        $merchandising->register(); $priceHistory->register();
        $loyalty->register();
        $incentives->register();
        (new Privacy($this->db, $this->store))->register();
        // Trusted in-process adapters have explicit typed services; no arbitrary REST method dispatch.
        add_filter('wooshop_commerce_catalog_services', static fn (): array => ['products' => $products, 'specifications' => $specs, 'customer' => $customer, 'community' => $community, 'compatibility' => $compatibility, 'notifications' => $notifications, 'search' => $search, 'merchandising' => $merchandising, 'price_history' => $priceHistory, 'session' => $session, 'loyalty' => $loyalty, 'incentives' => $incentives]);
    }

    public function capabilities(): array
    {
        return ['MOD-U001' => ['state' => 'partial', 'features' => ['immutable_schemas', 'typed_values', 'dimension_safe_conversion', 'evidence_review', 'approved_public_projection'], 'unfinished' => ['seller_scoped_specification_proposal_adapter', 'field_based_editor_controls', 'profile_composite_value_schemas']],
            'MOD-U002' => ['state' => 'partial', 'features' => ['native_bounded_search', 'persian_normalization', 'exact_sku_ranking', 'typed_facets', 'public_suggestions'], 'unfinished' => ['external_index', 'synonym_configuration', 'unbounded_catalog_index']],
            'MOD-U003' => ['state' => 'partial', 'features' => ['private_guest_account_lists', 'union_merge', 'session_rotation', 'idempotency', 'capacity', 'tombstones', 'lifecycle_read_new_cleanup', 'retained_reenable'], 'unfinished' => ['revocable_sharing_adapter', 'configuration_identity_adapter', 'configured_capacity_above_100']],
            'MOD-U004' => ['state' => 'partial', 'features' => ['private_saved_comparison', 'two_to_four_products', 'normalized_units', 'missing_not_zero', 'semantic_table', 'owner_revision_clear', 'lifecycle_read_new_cleanup', 'retained_reenable'], 'unfinished' => ['differences_only_control', 'display_unit_preference_control']],
            'MOD-U005' => ['state' => 'partial', 'features' => ['consent_gate', 'device_history_rail', 'internal_record_read_clear', 'deletion_revision', 'bounded_expiry_cron', 'lifecycle_read_new_cleanup', 'device_disable_cleanup', 'consent_module_revision_tombstones'], 'unfinished' => ['explicit_account_sync_choice_ui']],
            'MOD-U006' => ['state' => 'partial', 'features' => ['verified_account_requests', 'pending_persistence', 'send_time_eligibility', 'suppression'], 'blocked' => ['approved_email_sms_provider', 'recipient_verification_provider', 'trigger_evaluation_jobs']],
            'MOD-U007' => ['state' => 'partial', 'features' => ['moderated_submission', 'rate_limit', 'independent_answer_review', 'public_approved_archive'], 'unfinished' => ['author_pending_queue_ui', 'seller_answer_scope_adapter', 'abuse_report_queue', 'moderation_notification_provider']],
            'MOD-U008' => ['state' => 'partial', 'features' => ['native_review_authority', 'verified_purchase_projection', 'deduplicated_helpful_vote'], 'unfinished' => ['media_scan_provider', 'helpful_vote_ui', 'abuse_review_queue']],
            'MOD-U009' => ['state' => 'partial', 'features' => ['persistent_drafts', 'independent_funding_review', 'request_time_expiry'], 'blocked' => ['commercial_legal_activation_policy']],
            'MOD-U010' => ['state' => 'partial', 'features' => ['curated_placements', 'public_eligibility_recheck', 'sponsored_label', 'accessible_product_links'], 'unfinished' => ['native_aggregate_strategy_adapters', 'external_recommender_provider']],
            'MOD-U011' => ['state' => 'partial', 'features' => ['typed_directional_relationships', 'evidence_required_compatibility', 'expiry_revocation_recheck', 'bounded_one_hop'], 'unfinished' => ['seller_proposal_moderation_adapter']],
            'MOD-U012' => ['state' => 'partial', 'features' => ['approved_scoped_evidence', 'unknown_by_default', 'conflict_quarantine', 'numeric_dotted_firmware'], 'unfinished' => ['manufacturer_specific_version_comparators', 'expiry_review_jobs']],
            'MOD-U016' => ['state' => 'partial', 'features' => ['private_preferences', 'immediate_withdrawal', 'signed_scoped_unsubscribe', 'pending_operation'], 'blocked' => ['approved_message_provider', 'delivery_callbacks', 'reconciliation']],
            'MOD-U017' => ['state' => 'partial', 'features' => ['immutable_native_price_observations', 'deduplicated_source_revisions', 'currency_tax_separation', 'table_projection'], 'blocked' => ['legal_public_history_policy'], 'unfinished' => ['marketplace_offer_observation_adapter', 'correction_workflow']],
            'MOD-U018' => ['state' => 'partial', 'features' => ['immutable_point_journal', 'source_order_refund_dedup', 'original_rule_refund_reversal', 'restricted_negative_points', 'atomic_redemption_reservations', 'owner_bound_native_coupons', 'pending_release_expiry_drain', 'private_reward_shortcode'], 'blocked' => ['production_finance_reviewed_policy_adapter', 'expiry_notice_provider_when_required'], 'unfinished' => ['dual_review_manual_adjustment_adapter', 'marketplace_reward_funding', 'scalable_journal_projection']],
            'MOD-U019' => ['state' => 'partial', 'features' => ['immutable_reviewed_rules', 'server_native_cart_basis', 'after_shared_coupon_threshold', 'exclusive_stacking_groups', 'native_coupon_apply_once', 'native_cart_recalculation_removal', 'classic_store_api_checkout_validation'], 'blocked' => ['production_finance_reviewed_funding_policy'], 'unfinished' => ['marketplace_funding_allocation', 'product_scoped_threshold_adapter', 'gift_shipping_adapters']],
            'provider_product_types' => ['state' => 'unavailable', 'blocked' => ['verified_extension_adapters']]];
    }
}
