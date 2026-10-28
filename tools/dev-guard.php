<?php
/** Local development transport guard. Never included in release packages. */
defined('ABSPATH') || exit;
if (wp_get_environment_type() !== 'local') {
    return;
}
// A blocked transport must report failure, not a simulated delivery.
add_filter('pre_wp_mail', static fn () => false);
add_filter('pre_http_request', static function ($result, $args, $url) {
    $host = wp_parse_url($url, PHP_URL_HOST);
    if (!in_array($host, ['localhost', '127.0.0.1', 'outdoor', 'smart-home'], true)) {
        return new WP_Error('wooshop_local_transport_blocked', 'External HTTP is blocked in this isolated fixture environment.');
    }
    return $result;
}, 10, 3);
