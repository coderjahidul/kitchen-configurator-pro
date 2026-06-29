<?php
/**
 * Renders the KKF site header and footer shell.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

use KitchenConfiguratorPro\Services\SiteShellService;

/**
 * Replaces the theme header/footer with the KeukenKastenFabriek configurator shell.
 */
final class SiteShellPresenter {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		( new SiteShellMenuRegistry() )->register();
		( new SiteShellMenuItemFields() )->register();

		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 5 );
		add_action( 'wp_body_open', array( $this, 'render_header' ), 0 );
		add_action( 'wp_footer', array( $this, 'render_footer' ), 1 );
		add_filter( 'astra_the_header_enabled', array( $this, 'disable_astra_header' ) );
		add_filter( 'astra_footer_bar_display', array( $this, 'disable_astra_footer' ) );
	}

	/**
	 * @param array<int, string> $classes Body classes.
	 * @return array<int, string>
	 */
	public function body_class( array $classes ): array {
		if ( ! SiteShellService::is_active() ) {
			return $classes;
		}

		$classes[] = 'kcp-shell-active';
		$classes[] = 'light-mode';

		return $classes;
	}

	/**
	 * Enqueue shell assets.
	 */
	public function enqueue_assets(): void {
		if ( ! SiteShellService::is_active() ) {
			return;
		}

		wp_enqueue_style(
			'kcp-shell-fonts',
			'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;1,9..40,400&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'kcp-tokens',
			KCP_PLUGIN_URL . 'assets/frontend/css/tokens.css',
			array( 'kcp-shell-fonts' ),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/css/tokens.css' )
		);

		wp_enqueue_style(
			'kcp-shell',
			KCP_PLUGIN_URL . 'assets/frontend/css/shell.css',
			array( 'kcp-tokens' ),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/css/shell.css' )
		);

		wp_enqueue_script(
			'kcp-shell',
			KCP_PLUGIN_URL . 'assets/frontend/js/shell.js',
			array(),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/js/shell.js' ),
			true
		);
	}

	/**
	 * Render the site header.
	 */
	public function render_header(): void {
		if ( ! SiteShellService::is_active() ) {
			return;
		}

		$model = SiteShellService::get_view_model();
		$path  = KCP_PLUGIN_DIR . 'templates/frontend/partials/site-header.php';

		if ( ! is_readable( $path ) ) {
			return;
		}

		include $path;
	}

	/**
	 * Render the site footer.
	 */
	public function render_footer(): void {
		if ( ! SiteShellService::is_active() ) {
			return;
		}

		$model = SiteShellService::get_view_model();
		$path  = KCP_PLUGIN_DIR . 'templates/frontend/partials/site-footer.php';

		if ( ! is_readable( $path ) ) {
			return;
		}

		include $path;
	}

	/**
	 * @param bool $enabled Whether the Astra header is enabled.
	 */
	public function disable_astra_header( bool $enabled ): bool {
		return SiteShellService::is_active() ? false : $enabled;
	}

	/**
	 * @param bool $display Whether the Astra footer bar is displayed.
	 */
	public function disable_astra_footer( bool $display ): bool {
		return SiteShellService::is_active() ? false : $display;
	}
}
