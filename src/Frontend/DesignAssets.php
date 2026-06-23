<?php
/**
 * Design step frontend assets.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

use KitchenConfiguratorPro\Services\DesignStepService;

/**
 * Enqueues design step CSS and JavaScript.
 */
final class DesignAssets {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'script_loader_tag', array( $this, 'add_module_type' ), 10, 3 );
	}

	/**
	 * Add body class when the design step shortcode is on the page.
	 *
	 * @param array<int, string> $classes Body classes.
	 * @return array<int, string>
	 */
	public function body_class( array $classes ): array {
		if ( DesignShortcode::post_has_shortcode() ) {
			$classes[] = 'kcp-design-active';
		}

		return $classes;
	}

	/**
	 * Enqueue assets when the design shortcode is present.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! DesignShortcode::is_rendered() && ! DesignShortcode::post_has_shortcode() ) {
			return;
		}

		$css_path = KCP_PLUGIN_DIR . 'assets/frontend/css/design.css';
		$js_path  = KCP_PLUGIN_DIR . 'assets/frontend/js/design/main.js';
		$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : KCP_VERSION;
		$js_ver   = file_exists( $js_path ) ? (string) filemtime( $js_path ) : KCP_VERSION;

		wp_enqueue_style(
			'kcp-design',
			KCP_PLUGIN_URL . 'assets/frontend/css/design.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'kcp-design',
			KCP_PLUGIN_URL . 'assets/frontend/js/design/main.js',
			array(),
			$js_ver,
			true
		);

		wp_script_add_data( 'kcp-design', 'type', 'module' );

		wp_localize_script(
			'kcp-design',
			'kcpDesignStep',
			DesignStepService::get_public_config()
		);
	}

	/**
	 * Add type="module" to the design script tag.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script source.
	 * @return string
	 */
	public function add_module_type( string $tag, string $handle, string $src ): string {
		if ( 'kcp-design' !== $handle ) {
			return $tag;
		}

		return sprintf(
			'<script type="module" src="%s" id="%s-js"></script>',
			esc_url( $src ),
			esc_attr( $handle )
		);
	}
}
