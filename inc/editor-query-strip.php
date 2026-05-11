<?php
/**
 * Core Query Loop: extra block attribute + editor script (strip post picker).
 *
 * @package Start_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Allow the strip pattern to persist hand-picked post IDs on core/query.
 *
 * @param array  $args       Block type args.
 * @param string $block_name Block name.
 * @return array
 */
function start_theme_register_query_strip_attributes( array $args, string $block_name ): array {
	if ( 'core/query' !== $block_name ) {
		return $args;
	}
	$args['attributes'] = array_merge(
		isset( $args['attributes'] ) && is_array( $args['attributes'] ) ? $args['attributes'] : array(),
		array(
			'stStripPostIds' => array(
				'type'    => 'array',
				'default' => array(),
				'items'   => array( 'type' => 'number' ),
			),
		)
	);
	return $args;
}
add_filter( 'register_block_type_args', 'start_theme_register_query_strip_attributes', 10, 2 );

/**
 * Enqueue block editor integration (inspector for Query blocks inside the strip pattern).
 */
function start_theme_enqueue_query_strip_editor(): void {
	if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! method_exists( $screen, 'is_block_editor' ) || ! $screen->is_block_editor() ) {
		return;
	}

	$handle = 'start-theme-editor-query-strip';
	wp_enqueue_script(
		$handle,
		get_template_directory_uri() . '/assets/js/editor-query-strip.js',
		array(
			'wp-hooks',
			'wp-element',
			'wp-components',
			'wp-data',
			'wp-i18n',
			'wp-blocks',
			'wp-block-editor',
			'wp-core-data',
			'wp-api-fetch',
			'wp-compose',
			'wp-html-entities',
		),
		wp_get_theme()->get( 'Version' ),
		true
	);

	wp_set_script_translations( $handle, 'start-theme', get_template_directory() . '/languages' );
}
add_action( 'enqueue_block_editor_assets', 'start_theme_enqueue_query_strip_editor' );
