<?php
/**
 * Title: Published guides
 * Slug: wooshop/published-guides
 * Categories: wooshop
 * Description: Real published WordPress posts; no fabricated authors or expertise claims.
 */
?>
<!-- wp:group {"className":"wooshop-guide-section","layout":{"type":"constrained"}} --><div class="wp-block-group wooshop-guide-section">
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e('Ideas and guides', 'wooshop'); ?></h2><!-- /wp:heading -->
<!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false}} --><div class="wp-block-query">
<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3"} /--><!-- wp:post-title {"isLink":true,"level":3} /--><!-- wp:post-excerpt {"moreText":""} /-->
<!-- /wp:post-template --></div><!-- /wp:query --></div><!-- /wp:group -->
