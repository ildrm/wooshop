<?php
declare(strict_types=1);
namespace WooShop\Commerce\Seo;

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Catalog\Search;
use WooShop\Commerce\Support\DomainError;

/** Coordinates native head output; it emits no competing Product, Offer, Review or FAQ graph. */
final class Policy
{
    private Search $search;
    public function __construct(Search $search) { $this->search = $search; }
    private function privatePage(): bool
    {
        global $post;
        if (!$post || !is_string($post->post_content)) { return false; }
        foreach (['commerce_wishlist', 'commerce_compare', 'commerce_preferences', 'commerce_alerts', 'commerce_unsubscribe', 'commerce_history', 'commerce_rewards', 'commerce_incentives'] as $shortcode) { if (has_shortcode($post->post_content, $shortcode)) { return true; } }
        return false;
    }

    private function searchPage(): bool { global $post; return $post && is_string($post->post_content) && has_shortcode($post->post_content, 'commerce_search'); }

    public function register(): void
    {
        add_filter('wp_robots', function (array $robots): array {
            if ($this->privatePage() || $this->searchPage() || is_search()) { unset($robots['index']); $robots['noindex'] = true; $robots['follow'] = true; }
            return $robots;
        });
        add_action('template_redirect', function (): void {
            if ($this->privatePage()) { nocache_headers(); header('X-Robots-Tag: noindex, follow'); }
            if (!$this->searchPage()) { return; }
            $seen = []; $invalid = false;
            foreach (explode('&', (string) ($_SERVER['QUERY_STRING'] ?? '')) as $part) {
                if ($part === '') { continue; }
                $key = urldecode(explode('=', $part, 2)[0]);
                if (str_starts_with($key, 'utm_') || in_array($key, ['gclid', 'fbclid'], true)) { continue; }
                if (isset($seen[$key]) || (!in_array($key, ['cq', 'cp', 'page_id', 'p'], true) && !preg_match('/^cf\[[a-z][a-z0-9_.-]{0,79}\]$/D', $key))) { $invalid = true; }
                $seen[$key] = true;
            }
            try {
                if (isset($_GET['cq']) && !is_string($_GET['cq'])) { $invalid = true; }
                if (isset($_GET['cf']) && !is_array($_GET['cf'])) { $invalid = true; }
                if (isset($_GET['cp']) && (!is_string($_GET['cp']) || !ctype_digit($_GET['cp']) || (int) $_GET['cp'] < 1)) { $invalid = true; }
                if (!$invalid) {
                    $filters = isset($_GET['cf']) ? array_filter(wp_unslash($_GET['cf']), static fn ($value): bool => $value !== '') : [];
                    $category = null; global $post;
                    if (preg_match('/\[commerce_search\b([^\]]*)\]/', $post->post_content, $shortcode)) { $attributes = shortcode_parse_atts($shortcode[1]); if (is_array($attributes) && isset($attributes['category_id'])) { $category = Input::id((string) $attributes['category_id']); } }
                    $this->search->find(isset($_GET['cq']) ? wp_unslash($_GET['cq']) : '', $filters, 20, isset($_GET['cp']) ? (int) $_GET['cp'] : 1, $category);
                }
            } catch (DomainError $error) { $invalid = true; }
            if ($invalid) { status_header(404); nocache_headers(); }
        });
        add_filter('wooshop_commerce_seo_manifest', static function (): array {
            $conflict = defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION');
            return ['state' => $conflict ? 'unverified_provider' : 'native', 'canonical' => 'wordpress_core', 'robots' => 'wordpress_core', 'commerce_graph' => 'woocommerce_native', 'sitemap' => 'wordpress_core', 'custom_product_graph' => false];
        });
        add_action('admin_notices', static function (): void {
            if (current_user_can('manage_woocommerce') && (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION'))) { echo '<div class="notice notice-warning"><p>' . esc_html__('An SEO provider needs a verified commerce adapter before coordinated canonical, sitemap and schema ownership can be confirmed.', 'wooshop-commerce') . '</p></div>'; }
        });
        add_filter('get_canonical_url', function (string $canonical): string {
            if (!$this->searchPage()) { return $canonical; }
            $args = [];
            if (isset($_GET['cq']) && is_string($_GET['cq']) && trim($_GET['cq']) !== '') { $args['cq'] = trim(wp_unslash($_GET['cq'])); }
            if (isset($_GET['cf']) && is_array($_GET['cf'])) { $facets = wp_unslash($_GET['cf']); $facets = array_filter($facets, static fn ($value): bool => is_string($value) && $value !== ''); ksort($facets, SORT_STRING); if ($facets) { $args['cf'] = $facets; } }
            if (isset($_GET['cp']) && is_scalar($_GET['cp']) && ctype_digit((string) $_GET['cp']) && (int) $_GET['cp'] > 1) { $args['cp'] = (int) $_GET['cp']; }
            return $args ? add_query_arg($args, $canonical) : $canonical;
        });
        add_filter('wp_sitemaps_posts_query_args', function (array $args, string $postType): array {
            if ($postType !== 'page') { return $args; }
            $private = [];
            foreach (get_posts(['post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 200, 'fields' => 'ids', 'no_found_rows' => true]) as $id) {
                $content = (string) get_post_field('post_content', $id);
                foreach (['commerce_wishlist', 'commerce_compare', 'commerce_preferences', 'commerce_alerts', 'commerce_search', 'commerce_unsubscribe', 'commerce_history', 'commerce_rewards', 'commerce_incentives'] as $shortcode) { if (has_shortcode($content, $shortcode)) { $private[] = $id; break; } }
            }
            $args['post__not_in'] = array_unique(array_merge($args['post__not_in'] ?? [], $private));
            return $args;
        }, 10, 2);
    }
}
