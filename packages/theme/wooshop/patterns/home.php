<?php
/**
 * Title: Storefront discovery
 * Slug: wooshop/home
 * Categories: wooshop
 * Description: Original editorial hero, live native catalog collections and published guides.
 * Inserter: true
 */
?>
<!-- wp:group {"className":"wooshop-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group wooshop-hero"><!-- wp:group {"className":"wooshop-hero-content","layout":{"type":"constrained"}} -->
<div class="wp-block-group wooshop-hero-content"><!-- wp:paragraph {"className":"wooshop-eyebrow"} --><p class="wooshop-eyebrow"><?php esc_html_e('A closer look. A better choice.', 'wooshop'); ?></p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1} --><h1 class="wp-block-heading"><?php esc_html_e('Find what fits your everyday.', 'wooshop'); ?></h1><!-- /wp:heading -->
<!-- wp:paragraph --><p><?php esc_html_e('Explore the catalog, compare the details and choose at your own pace.', 'wooshop'); ?></p><!-- /wp:paragraph -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(wooshop_shop_url()); ?>"><?php esc_html_e('Explore the catalog', 'wooshop'); ?></a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->
<!-- wp:html --><div class="wooshop-hero-art" aria-hidden="true"></div><!-- /wp:html --></div><!-- /wp:group -->
<!-- wp:wooshop/category-shortcuts {"count":8,"shape":"square"} /-->
<!-- wp:wooshop/product-collection {"source":"new","count":10} /-->
<!-- wp:wooshop/product-collection {"source":"sale","count":10,"layout":"rail"} /-->
<!-- wp:pattern {"slug":"wooshop/published-guides"} /-->
