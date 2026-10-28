<?php
/** @var \WooShop\Commerce\Marketplace\SellerPortal $this */
if (!defined('ABSPATH')) { exit; }
?>
<section class="wooshop-seller-portal" aria-labelledby="wooshop-seller-title">
    <header><p class="wooshop-portal-eyebrow"><?php esc_html_e('Seller workspace', 'wooshop-commerce'); ?></p><h2 id="wooshop-seller-title"><?php esc_html_e('Your organizations', 'wooshop-commerce'); ?></h2></header>
    <div class="wooshop-portal-notice" role="note"><strong><?php esc_html_e('Marketplace sales and payouts are unavailable', 'wooshop-commerce'); ?></strong><p><?php esc_html_e('Required legal approval, payment provider verification and operational safeguards are pending. Existing purchase support and fulfillment duties remain.', 'wooshop-commerce'); ?></p></div>
    <?php if ($memberships): ?>
        <nav aria-label="<?php esc_attr_e('Seller organization', 'wooshop-commerce'); ?>"><ul class="wooshop-organization-list">
        <?php foreach ($memberships as $membership): ?>
            <li><a href="<?php echo esc_url(add_query_arg('seller', $membership['seller_id'])); ?>" <?php echo $membership['seller_id'] === $selected ? 'aria-current="page"' : ''; ?>><?php echo esc_html($membership['display_name']); ?></a> <span><?php echo esc_html($membership['seller_state']); ?></span>
            <?php if ($membership['state'] === 'invited'): ?><form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post"><input type="hidden" name="action" value="wooshop_accept_invitation"><input type="hidden" name="membership_id" value="<?php echo esc_attr($membership['id']); ?>"><?php wp_nonce_field('wooshop_accept_invitation_'.$membership['id']); ?><button type="submit"><?php esc_html_e('Accept invitation', 'wooshop-commerce'); ?></button></form><?php endif; ?></li>
        <?php endforeach; ?>
        </ul></nav>
    <?php else: ?><p><?php esc_html_e('You have no seller organizations yet.', 'wooshop-commerce'); ?></p><?php endif; ?>
    <?php if ($current): ?>
        <h3><?php echo esc_html($current['display_name']); ?></h3><p><?php echo esc_html(sprintf(__('Organization status: %s', 'wooshop-commerce'), $current['seller_state'])); ?></p>
        <div class="wooshop-portal-columns">
            <section><h4><?php esc_html_e('Offers', 'wooshop-commerce'); ?></h4>
            <?php if (!$offers): ?><p><?php esc_html_e('No editable offers. Published products and approved seller access are required.', 'wooshop-commerce'); ?></p><?php endif; ?>
            <?php foreach ($offers as $offer): ?><article class="wooshop-portal-card"><h5><?php echo esc_html($offer['seller_sku']); ?></h5><p><?php echo esc_html(sprintf(__('State: %1$s · Version: %2$d', 'wooshop-commerce'), $offer['state'], $offer['version'])); ?></p><form data-marketplace-route="<?php echo esc_attr('offers/'.$offer['id']); ?>" data-method="PATCH" data-version="<?php echo esc_attr($offer['version']); ?>"><label><?php esc_html_e('Seller SKU', 'wooshop-commerce'); ?><input name="seller_sku" value="<?php echo esc_attr($offer['seller_sku']); ?>" maxlength="64" required></label><button type="submit"><?php esc_html_e('Save SKU', 'wooshop-commerce'); ?></button><p data-result role="status" aria-live="polite"></p></form></article><?php endforeach; ?></section>
            <section><h4><?php esc_html_e('Fulfillment tasks', 'wooshop-commerce'); ?></h4>
            <?php if (!$allocations): ?><p><?php esc_html_e('No assigned allocations. New paid allocations will appear here.', 'wooshop-commerce'); ?></p><?php endif; ?>
            <?php foreach ($allocations as $allocation): $data = json_decode($allocation['data'], true); ?><article class="wooshop-portal-card"><h5><?php echo esc_html(sprintf(__('Order #%s', 'wooshop-commerce'), $allocation['order_id'])); ?></h5><p><?php echo esc_html($allocation['fulfillment_state']); ?></p><ul><?php foreach ($data['lines'] as $line): ?><li><?php echo esc_html(sprintf(__('Item %1$s · Quantity %2$d', 'wooshop-commerce'), $line['order_item_id'], $line['quantity'])); ?></li><?php endforeach; ?></ul></article><?php endforeach; ?></section>
            <section><h4><?php esc_html_e('Statement', 'wooshop-commerce'); ?></h4>
            <?php if (!$statement): ?><p><?php esc_html_e('Your membership does not include statement access.', 'wooshop-commerce'); ?></p><?php else: ?>
                <?php if (!$statement['balances']): ?><p><?php esc_html_e('No posted financial movements.', 'wooshop-commerce'); ?></p><?php else: ?><dl><?php foreach ($statement['balances'] as $account => $amount): ?><dt><?php echo esc_html(str_replace('_', ' ', $account)); ?></dt><dd><bdi><?php echo esc_html($amount['amount_minor'].' '.$amount['currency'].' '.__('minor units', 'wooshop-commerce')); ?></bdi></dd><?php endforeach; ?></dl><?php endif; ?>
                <p><?php esc_html_e('Available payout is undetermined until approved provider reconciliation and holds are established.', 'wooshop-commerce'); ?></p>
            <?php endif; ?></section>
        </div>
    <?php endif; ?>
    <?php if (!$memberships && $termsVersion > 0): ?><details><summary><?php esc_html_e('Apply for a seller organization', 'wooshop-commerce'); ?></summary><form data-marketplace-route="seller-applications" data-method="POST"><label><?php esc_html_e('Public organization name', 'wooshop-commerce'); ?><input name="display_name" maxlength="120" required></label><label><?php esc_html_e('Public slug', 'wooshop-commerce'); ?><input name="public_slug" pattern="[a-z0-9-]{1,80}" maxlength="80" required></label><label><?php esc_html_e('Private business profile reference', 'wooshop-commerce'); ?><input name="business_profile_ref" maxlength="200" required></label><label><input type="checkbox" name="accepted_terms_version" value="<?php echo esc_attr($termsVersion); ?>" data-integer required> <?php echo esc_html(sprintf(__('I accept the published seller terms, version %d.', 'wooshop-commerce'), $termsVersion)); ?></label><button type="submit"><?php esc_html_e('Submit for review', 'wooshop-commerce'); ?></button><p data-result role="status" aria-live="polite"></p></form></details><?php elseif (!$memberships): ?><p><?php esc_html_e('Seller applications will open after current seller terms are published.', 'wooshop-commerce'); ?></p><?php endif; ?>
</section>
