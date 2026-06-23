<?php
/**
 * Cabinet select step shortcode.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

/**
 * Renders [kcp_cabinet_select] shortcode.
 */
final class CabinetSelectShortcode {

	private static bool $rendered = false;

	public function register(): void {
		add_shortcode( 'kcp_cabinet_select', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function render( array|string $atts = array() ): string {
		unset( $atts );
		self::$rendered = true;
		ob_start();
		include KCP_PLUGIN_DIR . 'templates/frontend/cabinet-select-step.php';
		return (string) ob_get_clean();
	}

	public static function is_rendered(): bool {
		return self::$rendered;
	}

	public static function post_has_shortcode(): bool {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_post();
		return $post instanceof \WP_Post && has_shortcode( $post->post_content, 'kcp_cabinet_select' );
	}
}
