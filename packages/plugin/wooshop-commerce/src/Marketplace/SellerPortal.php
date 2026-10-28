<?php
declare(strict_types=1);
namespace WooShop\Commerce\Marketplace;

use WooShop\Commerce\Finance\Ledger;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Security\Authorization;
use WooShop\Commerce\Support\DomainError;

final class SellerPortal
{
    public function __construct(private Database $db, private EntityStore $entities) {}

    public function register(): void
    {
        add_shortcode('wooshop_seller_portal', [$this, 'render']);
        add_shortcode('commerce_seller_dashboard', [$this, 'render']);
        add_action('admin_post_wooshop_accept_invitation', [$this, 'accept']);
    }

    public function accept(): void
    {
        if (!is_user_logged_in()) { auth_redirect(); return; }
        $id = isset($_POST['membership_id']) ? sanitize_text_field(wp_unslash($_POST['membership_id'])) : '';
        check_admin_referer('wooshop_accept_invitation_'.$id);
        try {
            (new SellerService($this->db, $this->entities))->acceptInvitation($id, get_current_user_id());
            wp_safe_redirect(wp_get_referer() ?: home_url('/')); exit;
        } catch (DomainError $e) { wp_die(esc_html($e->getMessage()), '', ['response' => $e->status]); }
    }

    public function render(): string
    {
        if (!is_user_logged_in()) { return '<p>'.esc_html__('Sign in to view your seller organizations and tasks.', 'wooshop-commerce').' <a href="'.esc_url(wp_login_url(get_permalink())).'">'.esc_html__('Sign in', 'wooshop-commerce').'</a></p>'; }
        wp_enqueue_style('wooshop-marketplace', plugins_url('assets/marketplace.css', dirname(__DIR__, 2).'/wooshop-commerce.php'), [], '0.1.0');
        wp_enqueue_script('wooshop-marketplace', plugins_url('assets/marketplace.js', dirname(__DIR__, 2).'/wooshop-commerce.php'), [], '0.1.0', true);
        wp_localize_script('wooshop-marketplace', 'WooShopMarketplace', ['root' => esc_url_raw(rest_url('commerce/v1/')), 'nonce' => wp_create_nonce('wp_rest'), 'working' => __('Saving…', 'wooshop-commerce'), 'saved' => __('Saved. Reload to see the current version.', 'wooshop-commerce')]);
        $w = $this->db->wpdb();
        $memberships = $w->get_results($w->prepare('SELECT m.*,s.display_name,s.state AS seller_state FROM '.$this->db->table('memberships').' m JOIN '.$this->db->table('sellers').' s ON s.id=m.seller_id WHERE m.user_id=%d AND m.state IN (%s,%s) ORDER BY s.display_name', get_current_user_id(), 'active', 'invited'), ARRAY_A);
        $selected = isset($_GET['seller']) ? sanitize_text_field(wp_unslash($_GET['seller'])) : ($memberships[0]['seller_id'] ?? null);
        $current = null;
        foreach ($memberships as $membership) { if ($membership['seller_id'] === $selected && $membership['state'] === 'active') { $current = $membership; } }
        $offers = $allocations = $statement = [];
        $auth = new Authorization($this->db);
        if ($current) {
            if ($auth->can('commerce.offer.write', $selected)) { $offers = $w->get_results($w->prepare('SELECT * FROM '.$this->db->table('offers').' WHERE seller_id=%s ORDER BY updated_at DESC LIMIT 50', $selected), ARRAY_A); }
            if ($auth->can('commerce.allocation.read.own', $selected) || $auth->can('commerce.allocation.fulfill', $selected)) { $allocations = $w->get_results($w->prepare('SELECT * FROM '.$this->db->table('allocations').' WHERE seller_id=%s ORDER BY created_at DESC LIMIT 50', $selected), ARRAY_A); }
            if ($auth->can('commerce.finance.read.own', $selected) || $auth->can('commerce.statement.read', $selected)) { $statement = (new Ledger($this->db))->sellerStatement($selected, get_woocommerce_currency()); }
        }
        $termsVersion = (int) get_option('wooshop_seller_terms_version', 0);
        ob_start();
        require dirname(__DIR__, 2).'/views/marketplace/portal.php';
        return (string) ob_get_clean();
    }
}
