<?php
/**
 * Cabinet detail step frontend assets.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

final class CabinetDetailAssets {

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
		if ( CabinetDetailShortcode::post_has_shortcode() ) {
			$classes[] = 'kcp-cabinet-select-active';
			$classes[] = 'kcp-cabinet-group-active';
			$classes[] = 'kcp-cabinet-detail-active';
		}

		return $classes;
	}

	public function enqueue(): void {
		if ( ! CabinetDetailShortcode::is_rendered() && ! CabinetDetailShortcode::post_has_shortcode() ) {
			return;
		}

		$config = CabinetDetailShortcode::current_config();

		wp_enqueue_style(
			'kcp-cabinet-select',
			KCP_PLUGIN_URL . 'assets/frontend/css/cabinet-select.css',
			array(),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/css/cabinet-select.css' )
		);

		$detail_css = KCP_PLUGIN_DIR . 'assets/frontend/css/cabinet-detail.css';
		if ( is_readable( $detail_css ) ) {
			wp_enqueue_style(
				'kcp-cabinet-detail',
				KCP_PLUGIN_URL . 'assets/frontend/css/cabinet-detail.css',
				array( 'kcp-cabinet-select' ),
				(string) filemtime( $detail_css )
			);
		}

		wp_enqueue_script(
			'kcp-cabinet-detail',
			KCP_PLUGIN_URL . 'assets/frontend/js/cabinet-detail/main.js',
			array(),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/js/cabinet-detail/main.js' ),
			true
		);

		wp_script_add_data( 'kcp-cabinet-detail', 'type', 'module' );

		wp_localize_script(
			'kcp-cabinet-detail',
			'kcpCabinetDetail',
			$config
		);
	}

	public function add_module_type( string $tag, string $handle, string $src ): string {
		if ( 'kcp-cabinet-detail' !== $handle ) {
			return $tag;
		}

		return sprintf(
			'<script type="module" src="%s" id="%s-js"></script>',
			esc_url( $src ),
			esc_attr( $handle )
		);
	}
}
