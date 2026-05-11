<?php
/**
 * Start-Theme (stylesheet: start-theme) bootstrap.
 *
 * @package Start_Theme
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/plugin-dependency.php';
require_once get_template_directory() . '/inc/editor-query-strip.php';

/**
 * Load translations.
 */
function start_theme_setup(): void {
	load_theme_textdomain( 'start-theme', get_template_directory() . '/languages' );

	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	// Same strip / mosaic rules as the front so Query + post-template layout matches in the block canvas and pattern previews.
	add_editor_style( 'assets/start-theme-pattern.css' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );

	// Theme-owned pattern category (patterns in /patterns/ should use slug "start-theme" in Categories:).
	register_block_pattern_category(
		'start-theme',
		array(
			'label' => __( 'Start Theme', 'start-theme' ),
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary', 'start-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'start_theme_setup' );

/**
 * Disable WordPress bundled core block patterns (wp-includes/block-patterns).
 * Runs late so default theme support has already been added for block themes.
 */
function start_theme_disable_core_block_patterns(): void {
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'after_setup_theme', 'start_theme_disable_core_block_patterns', 100 );

/**
 * Do not fetch block patterns from the WordPress.org Pattern Directory API.
 */
add_filter( 'should_load_remote_block_patterns', '__return_false' );

/**
 * Pattern strip front CSS (built from assets/scss via npm run build:css).
 */
function start_theme_enqueue_pattern_styles(): void {
	$deps = array( 'wp-block-library' );
	if ( wp_style_is( 'global-styles', 'registered' ) ) {
		$deps[] = 'global-styles';
	}
	wp_enqueue_style(
		'start-theme-pattern',
		get_template_directory_uri() . '/assets/start-theme-pattern.css',
		$deps,
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'start_theme_enqueue_pattern_styles', 20 );

/**
 * Ensure strip Query blocks persist picks into `query.include` so inner blocks receive them via `providesContext`.
 *
 * `build_query_vars_from_query_block()` receives `core/post-template` (or pagination), not `core/query` — picks must live on `context['query']`.
 *
 * @param array         $parsed       Parsed block.
 * @param array         $source_block Source parsed block.
 * @param WP_Block|null $parent_block Parent block instance.
 * @return array
 */
function start_theme_normalize_strip_query_block_data( array $parsed, array $source_block, $parent_block ): array {
	if ( 'core/query' !== ( $parsed['blockName'] ?? '' ) ) {
		return $parsed;
	}
	$attrs = &$parsed['attrs'];
	$class = isset( $attrs['className'] ) ? (string) $attrs['className'] : '';
	if ( false === strpos( $class, 'st-query-mosaic' ) ) {
		return $parsed;
	}
	$raw = $attrs['stStripPostIds'] ?? array();
	$ids = array_values( array_filter( array_map( 'intval', (array) $raw ) ) );
	if ( empty( $ids ) ) {
		return $parsed;
	}
	$q              = isset( $attrs['query'] ) && is_array( $attrs['query'] ) ? $attrs['query'] : array();
	$q['include']   = $ids;
	$q['orderBy']   = 'include';
	$q['perPage']   = count( $ids );
	$attrs['query'] = $q;
	return $parsed;
}
add_filter( 'render_block_data', 'start_theme_normalize_strip_query_block_data', 4, 3 );

/**
 * Apply ordered post picks for the mosaic Query block (`st-query-mosaic` + stStripPostIds / `query.include`).
 * When picks are set, `posts_per_page` matches the number of picked IDs.
 *
 * @param array    $query Query vars.
 * @param WP_Block $block Block instance.
 * @return array
 */
function start_theme_query_strip_apply_picked_posts( array $query, WP_Block $block ): array {
	$attrs = array();
	if ( isset( $block->attributes ) && is_array( $block->attributes ) ) {
		$attrs = $block->attributes;
	} elseif ( ! empty( $block->parsed_block['attrs'] ) && is_array( $block->parsed_block['attrs'] ) ) {
		$attrs = $block->parsed_block['attrs'];
	}

	$class = isset( $attrs['className'] ) ? (string) $attrs['className'] : '';

	// Inner Query Loop blocks only see the parent's `query` via context (not `stStripPostIds`).
	$ctx_query = array();
	if ( isset( $block->context['query'] ) && is_array( $block->context['query'] ) ) {
		$ctx_query = $block->context['query'];
	}

	$raw_strip = $attrs['stStripPostIds'] ?? array();
	$ids       = array_values( array_filter( array_map( 'intval', (array) $raw_strip ) ) );

	$query_include_ids = array();
	if ( ! empty( $attrs['query']['include'] ) && is_array( $attrs['query']['include'] ) ) {
		$query_include_ids = array_values( array_filter( array_map( 'intval', $attrs['query']['include'] ) ) );
	}
	if ( empty( $query_include_ids ) && ! empty( $ctx_query['include'] ) && is_array( $ctx_query['include'] ) ) {
		$query_include_ids = array_values( array_filter( array_map( 'intval', $ctx_query['include'] ) ) );
	}

	$is_target_query = false !== strpos( $class, 'st-query-mosaic' ) || ! empty( $ids ) || ! empty( $query_include_ids );
	if ( ! $is_target_query ) {
		return $query;
	}

	if ( empty( $ids ) && ! empty( $query_include_ids ) ) {
		$ids = $query_include_ids;
	}

	if ( empty( $ids ) ) {
		return $query;
	}

	$query['post__in']            = $ids;
	$query['orderby']             = 'post__in';
	$query['posts_per_page']      = count( $ids );
	$query['ignore_sticky_posts'] = true;
	unset( $query['offset'] );

	return $query;
}

/**
 * Bridge for `query_loop_block_query_vars` (third argument unused; required by hook arity).
 *
 * @param array    $query Query vars.
 * @param WP_Block $block Block instance.
 * @param int      $page  Current page number from the filter (unused here).
 * @return array
 */
function start_theme_query_loop_block_query_vars( array $query, WP_Block $block, int $page ): array {
	return start_theme_query_strip_apply_picked_posts( $query, $block );
}
add_filter( 'query_loop_block_query_vars', 'start_theme_query_loop_block_query_vars', 5, 3 );
