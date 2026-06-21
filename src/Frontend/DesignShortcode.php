<?php
/**
 * Design step shortcode.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

/**
 * Renders [kcp_design_step] shortcode.
 */
final class DesignShortcode {

	/**
	 * Whether the shortcode was rendered on this request.
	 *
	 * @var bool
	 */
	private static bool $rendered = false;

	/**
	 * Register shortcode.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( 'kcp_design_step', array( $this, 'render' ) );
	}

	/**
	 * Render design step mount point.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( array|string $atts = array() ): string {
		unset( $atts );

		self::$rendered = true;

		ob_start();
		include KCP_PLUGIN_DIR . 'templates/frontend/design-step.php';

		return (string) ob_get_clean();
	}

	/**
	 * Check if shortcode was rendered.
	 *
	 * @return bool
	 */
	public static function is_rendered(): bool {
		return self::$rendered;
	}

	/**
	 * Check if current post contains the shortcode.
	 *
	 * @return bool
	 */
	public static function post_has_shortcode(): bool {
		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		return has_shortcode( $post->post_content, 'kcp_design_step' );
	}
}
