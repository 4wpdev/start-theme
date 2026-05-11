<?php
/**
 * Title: Posts feed with Weather
 * Slug: start-theme/posts-with-weather
 * Categories: start-theme
 * Description: Query Loop mosaic strip with Cover, meta group, and weather slot.
 * Keywords: start-theme, weather, query, posts, core
 *
 * @package Start_Theme
 */

?>
<!-- wp:group {"metadata":{"categories":["start-theme"],"patternName":"start-theme/posts-with-weather","name":"Posts feed with Weather"},"align":"wide","className":"st-featured-strip st-featured-strip\u002d\u002dmosaic-weather","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide st-featured-strip st-featured-strip--mosaic-weather"><!-- wp:query {"queryId":880,"query":{"perPage":2,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"include","inherit":false,"include":[84,81]},"stStripPostIds":[84,81],"className":"st-query-mosaic"} -->
<div class="wp-block-query st-query-mosaic"><!-- wp:post-template {"className":"st-query-mosaic__template","style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}}},"textColor":"contrast","fontFamily":"archivo","layout":{"type":"default"}} -->
<!-- wp:cover {"useFeaturedImage":true,"dimRatio":0,"isUserOverlayColor":true,"isDark":false,"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","justifyContent":"left"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)"><!-- wp:post-terms {"term":"category","style":{"color":{"text":"#537a50"},"elements":{"link":{"color":{"text":"#537a50"}}},"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"600","letterSpacing":"1px","textTransform":"uppercase"}},"fontFamily":"archivo-narrow"} /-->

<!-- wp:post-title {"isLink":true} /-->

<!-- wp:post-excerpt {"moreText":"","excerptLength":26,"style":{"elements":{"link":{"color":{"text":"var:preset|color|custom-dark-gray"}}}},"textColor":"custom-dark-gray"} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->

<!-- wp:group {"metadata":{"name":"Weather Widget"},"className":"st-strip-weather-slot","style":{"border":{"width":"1px"}},"fontSize":"small","fontFamily":"archivo","borderColor":"cyan-bluish-gray","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"center"}} -->
<div class="wp-block-group st-strip-weather-slot has-border-color has-cyan-bluish-gray-border-color has-archivo-font-family has-small-font-size" style="border-width:1px"><!-- wp:forwp/weather {"latitude":40.7128,"longitude":-74.006,"showLocationSearch":true} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->