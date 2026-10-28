<?php
/**
 * Title: Page not found
 * Slug: wooshop/not-found
 * Categories: wooshop
 * Inserter: false
 */
?>
<!-- wp:group {"className":"wooshop-state","layout":{"type":"constrained"}} --><div class="wp-block-group wooshop-state"><!-- wp:paragraph {"className":"wooshop-eyebrow"} --><p class="wooshop-eyebrow">404</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1} --><h1 class="wp-block-heading"><?php esc_html_e('This page could not be found', 'wooshop'); ?></h1><!-- /wp:heading -->
<!-- wp:paragraph --><p><?php esc_html_e('The address may have changed. Search the site or explore the catalog.', 'wooshop'); ?></p><!-- /wp:paragraph -->
<!-- wp:search {"label":"<?php esc_attr_e('Search the site', 'wooshop'); ?>","buttonText":"<?php esc_attr_e('Search', 'wooshop'); ?>"} /-->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(wooshop_shop_url()); ?>"><?php esc_html_e('Browse products', 'wooshop'); ?></a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->
