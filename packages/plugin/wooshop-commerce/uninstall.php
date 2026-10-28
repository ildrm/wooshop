<?php
/** Explicitly retain catalog, consent, audit and obligation records on uninstall. */
defined('WP_UNINSTALL_PLUGIN') || exit;
// No table, core product, customer, order or media deletion is authorized implicitly.
update_option('wooshop_commerce_uninstall_retention', [
    'state' => 'retained_pending_approved_export_and_retention_review',
    'recorded_at' => gmdate('c'),
], false);
