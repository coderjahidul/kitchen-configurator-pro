<?php
/**
 * Cabinet select step frontend assets.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

use KitchenConfiguratorPro\Services\CabinetSelectStepService;

final class CabinetSelectAssets {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'script_loader_tag', array( $this, 'add_module_type' ), 10, 3 );
	}

	/**
	 * @param array<int, string> $classes
	 * @return array<int, string>
	 */
	public function body_class( array $classes ): array {
		if ( CabinetSelectShortcode::post_has_shortcode() ) {
			$classes[] = 'kcp-cabinet-select-active';
		}
		return $classes;
	}

	public function enqueue(): void {
		if ( ! CabinetSelectShortcode::is_rendered() && ! CabinetSelectShortcode::post_has_shortcode() ) {
			return;
		}

		wp_enqueue_style(
			'kcp-cabinet-select',
			KCP_PLUGIN_URL . 'assets/frontend/css/cabinet-select.css',
			array(),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/css/cabinet-select.css' )
		);

		wp_enqueue_style(
			'kcp-cabinet-select-overlays',
			KCP_PLUGIN_URL . 'assets/frontend/css/cabinet-select-overlays.css',
			array( 'kcp-cabinet-select' ),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/css/cabinet-select-overlays.css' )
		);

		wp_enqueue_script(
			'kcp-cabinet-select',
			KCP_PLUGIN_URL . 'assets/frontend/js/cabinet-select/main.js',
			array(),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/js/cabinet-select/main.js' ),
			true
		);

		wp_script_add_data( 'kcp-cabinet-select', 'type', 'module' );

		wp_localize_script(
			'kcp-cabinet-select',
			'kcpCabinetSelect',
			CabinetSelectStepService::get_public_config()
		);
	}

	public function add_module_type( string $tag, string $handle, string $src ): string {
		if ( 'kcp-cabinet-select' !== $handle ) {
			return $tag;
		}
		return sprintf(
			'<script type="module" src="%s" id="%s-js"></script>',
			esc_url( $src ),
			esc_attr( $handle )
		);
	}
}
