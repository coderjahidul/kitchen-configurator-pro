<?php
/**
 * Configurator shortcode.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

/**
 * Renders [kitchen_configurator] shortcode.
 */
final class Shortcode {

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
		add_shortcode( 'kitchen_configurator', array( $this, 'render' ) );
	}

	/**
	 * Render configurator mount point.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( array|string $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'uuid'  => '',
				'title' => '',
			),
			is_array( $atts ) ? $atts : array(),
			'kitchen_configurator'
		);

		self::$rendered = true;

		$uuid = sanitize_text_field( (string) $atts['uuid'] );

		if ( '' === $uuid && isset( $_GET['kcp_config'] ) ) {
			$uuid = sanitize_text_field( wp_unslash( (string) $_GET['kcp_config'] ) );
		}

		$title = sanitize_text_field( (string) $atts['title'] );

		ob_start();
		include KCP_PLUGIN_DIR . 'templates/frontend/configurator.php';

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

		return has_shortcode( $post->post_content, 'kitchen_configurator' );
	}
}
