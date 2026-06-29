<?php
/**
 * Configurator landing page shortcode.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

/**
 * Renders [kcp_configurator_landing] — hero, popular arrangements, and promo tiles.
 */
final class ConfiguratorLandingShortcode {

	/**
	 * Whether the shortcode was rendered on this request.
	 */
	private static bool $rendered = false;

	/**
	 * Register shortcode.
	 */
	public function register(): void {
		add_shortcode( 'kcp_configurator_landing', array( $this, 'render' ) );
	}

	/**
	 * Render the configurator landing page sections.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function render( array|string $atts = array() ): string {
		unset( $atts );

		self::$rendered = true;

		ob_start();
		include KCP_PLUGIN_DIR . 'templates/frontend/configurator-landing.php';

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

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		return has_shortcode( $post->post_content, 'kcp_configurator_landing' );
	}
}
