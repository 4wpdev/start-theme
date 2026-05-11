<?php
/**
 * Require 4WP Weather: admin guidance + optional one-click install from the GitHub release zip.
 *
 * @package Start_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * GitHub release asset (must unpack to folder `4wp-weather` with `4wp-weather.php` inside).
 *
 * @link https://github.com/4wpdev/4wp-weather/releases/tag/v1.0.0
 */
function start_theme_forwp_weather_zip_url(): string {
	$url = 'https://github.com/4wpdev/4wp-weather/releases/download/v1.0.0/4wp-weather.zip';

	/**
	 * Filters the zip URL used by the theme installer action.
	 *
	 * @param string $url Default release download URL.
	 */
	return (string) apply_filters( 'start_theme_forwp_weather_zip_url', $url );
}

/**
 * Whether 4WP Weather is loaded (defined after its bootstrap runs).
 */
function start_theme_is_forwp_weather_active(): bool {
	return defined( 'FORWP_WEATHER_VERSION' );
}

/**
 * Path to the main plugin file relative to WP_PLUGIN_DIR.
 */
function start_theme_forwp_weather_plugin_file(): string {
	return '4wp-weather/4wp-weather.php';
}

/**
 * Remember to surface the dependency notice after theme switch.
 */
function start_theme_flag_forwp_weather_dependency(): void {
	if ( start_theme_is_forwp_weather_active() ) {
		return;
	}
	set_transient( 'start_theme_need_forwp_weather', '1', WEEK_IN_SECONDS );
}
add_action( 'after_switch_theme', 'start_theme_flag_forwp_weather_dependency' );

/**
 * Admin notice until 4WP Weather is active.
 */
function start_theme_admin_notice_forwp_weather(): void {
	if ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( start_theme_is_forwp_weather_active() ) {
		delete_transient( 'start_theme_need_forwp_weather' );
		return;
	}

	$plugin_file = start_theme_forwp_weather_plugin_file();
	$install_url = '';
	if ( current_user_can( 'install_plugins' ) ) {
		$install_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=start_theme_install_forwp_weather' ),
			'start_theme_install_forwp_weather'
		);
	}

	$activate_url = '';
	if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) && current_user_can( 'activate_plugin', $plugin_file ) ) {
		$activate_url = wp_nonce_url(
			self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $plugin_file ) ),
			'activate-plugin_' . $plugin_file
		);
	}

	$release = 'https://github.com/4wpdev/4wp-weather/releases/tag/v1.0.0';
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'Start-Theme', 'start-theme' ); ?>:</strong>
			<?php esc_html_e( 'This theme needs the 4WP Weather plugin (block forwp/weather). Install or activate it to use the bundled patterns.', 'start-theme' ); ?>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( $release ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Open release v1.0.0 on GitHub', 'start-theme' ); ?>
			</a>
			<?php if ( '' !== $install_url ) : ?>
				<a class="button" href="<?php echo esc_url( $install_url ); ?>">
					<?php esc_html_e( 'Install zip from theme (same host)', 'start-theme' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( '' !== $activate_url ) : ?>
				<a class="button" href="<?php echo esc_url( $activate_url ); ?>">
					<?php esc_html_e( 'Activate 4WP Weather', 'start-theme' ); ?>
				</a>
			<?php endif; ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'start_theme_admin_notice_forwp_weather' );

/**
 * Download + install plugin from the configured zip URL, then try to activate.
 */
function start_theme_handle_install_forwp_weather(): void {
	if ( ! isset( $_GET['action'] ) || 'start_theme_install_forwp_weather' !== $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_die( esc_html__( 'You are not allowed to install plugins.', 'start-theme' ) );
	}

	check_admin_referer( 'start_theme_install_forwp_weather' );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$url  = start_theme_forwp_weather_zip_url();
	$skin = new Automatic_Upgrader_Skin();
	$up   = new Plugin_Upgrader( $skin );
	$ok   = $up->install( $url );

	if ( is_wp_error( $ok ) || false === $ok ) {
		wp_safe_redirect(
			add_query_arg(
				'start_theme_weather_install',
				'fail',
				admin_url( 'themes.php' )
			)
		);
		exit;
	}

	$plugin_file = start_theme_forwp_weather_plugin_file();
	if ( current_user_can( 'activate_plugin', $plugin_file ) && file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
		activate_plugin( $plugin_file, '', false, true );
	}

	wp_safe_redirect(
		add_query_arg(
			'start_theme_weather_install',
			'ok',
			admin_url( 'themes.php' )
		)
	);
	exit;
}
add_action( 'admin_post_start_theme_install_forwp_weather', 'start_theme_handle_install_forwp_weather' );

/**
 * Short feedback after install attempt.
 */
function start_theme_admin_notice_weather_install_result(): void {
	if ( ! is_admin() || ! current_user_can( 'install_plugins' ) ) {
		return;
	}
	if ( empty( $_GET['start_theme_weather_install'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$st = sanitize_key( wp_unslash( $_GET['start_theme_weather_install'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'ok' === $st ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( '4WP Weather install finished. If the plugin is not active, activate it on the Plugins screen.', 'start-theme' ) . '</p></div>';
	}
	if ( 'fail' === $st ) {
		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Automatic install failed. Upload the zip from the GitHub release manually (Plugins → Add New → Upload).', 'start-theme' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'start_theme_admin_notice_weather_install_result', 20 );
