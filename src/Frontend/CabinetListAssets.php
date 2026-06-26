<?php
/**
 * Cabinet child-list step frontend assets.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

final class CabinetListAssets {

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
		if ( CabinetListShortcode::post_has_shortcode() ) {
			$classes[] = 'kcp-cabinet-select-active';
			$classes[] = 'kcp-cabinet-group-active';
			$classes[] = 'kcp-cabinet-list-active';
		}

		return $classes;
	}

	public function enqueue(): void {
		if ( ! CabinetListShortcode::is_rendered() && ! CabinetListShortcode::post_has_shortcode() ) {
			return;
		}

		$config = CabinetListShortcode::current_config();

		wp_enqueue_style(
			'kcp-cabinet-select',
			KCP_PLUGIN_URL . 'assets/frontend/css/cabinet-select.css',
			array(),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/css/cabinet-select.css' )
		);

		wp_enqueue_style(
			'kcp-cabinet-group',
			KCP_PLUGIN_URL . 'assets/frontend/css/cabinet-group.css',
			array( 'kcp-cabinet-select' ),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/css/cabinet-group.css' )
		);

		$list_css = KCP_PLUGIN_DIR . 'assets/frontend/css/cabinet-list.css';
		if ( is_readable( $list_css ) ) {
			wp_enqueue_style(
				'kcp-cabinet-list',
				KCP_PLUGIN_URL . 'assets/frontend/css/cabinet-list.css',
				array( 'kcp-cabinet-group' ),
				(string) filemtime( $list_css )
			);
		}

		wp_enqueue_script(
			'kcp-cabinet-list',
			KCP_PLUGIN_URL . 'assets/frontend/js/cabinet-list/main.js',
			array(),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/js/cabinet-list/main.js' ),
			true
		);

		wp_script_add_data( 'kcp-cabinet-list', 'type', 'module' );

		wp_localize_script(
			'kcp-cabinet-list',
			'kcpCabinetList',
			$config
		);
	}

	public function add_module_type( string $tag, string $handle, string $src ): string {
		if ( 'kcp-cabinet-list' !== $handle ) {
			return $tag;
		}

		return sprintf(
			'<script type="module" src="%s" id="%s-js"></script>',
			esc_url( $src ),
			esc_attr( $handle )
		);
	}
}
