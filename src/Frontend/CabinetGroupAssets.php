<?php
/**
 * Cabinet group step frontend assets.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

final class CabinetGroupAssets {

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
		if ( CabinetGroupShortcode::post_has_shortcode() ) {
			$classes[] = 'kcp-cabinet-select-active';
			$classes[] = 'kcp-cabinet-group-active';
		}

		return $classes;
	}

	public function enqueue(): void {
		if ( ! CabinetGroupShortcode::is_rendered() && ! CabinetGroupShortcode::post_has_shortcode() ) {
			return;
		}

		$config = self::resolve_enqueue_config();

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

		wp_enqueue_script(
			'kcp-cabinet-group',
			KCP_PLUGIN_URL . 'assets/frontend/js/cabinet-group/main.js',
			array(),
			(string) filemtime( KCP_PLUGIN_DIR . 'assets/frontend/js/cabinet-group/main.js' ),
			true
		);

		wp_script_add_data( 'kcp-cabinet-group', 'type', 'module' );

		wp_localize_script(
			'kcp-cabinet-group',
			'kcpCabinetGroup',
			$config
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function resolve_enqueue_config(): array {
		$slug = CabinetGroupShortcode::current_slug();

		if ( '' === $slug && is_singular() ) {
			$post = get_post();
			if ( $post instanceof \WP_Post ) {
				$slug = self::slug_from_post( $post );
			}
		}

		return \KitchenConfiguratorPro\Services\CabinetGroupStepService::get_public_config( $slug );
	}

	private static function slug_from_post( \WP_Post $post ): string {
		if ( preg_match( '/\[kcp_cabinet_group[^\]]*slug=["\']([^"\']+)["\']/', $post->post_content, $matches ) ) {
			return sanitize_title( (string) ( $matches[1] ?? '' ) );
		}

		return sanitize_title( (string) $post->post_name );
	}

	public function add_module_type( string $tag, string $handle, string $src ): string {
		if ( 'kcp-cabinet-group' !== $handle ) {
			return $tag;
		}

		return sprintf(
			'<script type="module" src="%s" id="%s-js"></script>',
			esc_url( $src ),
			esc_attr( $handle )
		);
	}
}
