<?php
/**
 * Cabinet group step shortcode.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

use KitchenConfiguratorPro\Services\CabinetGroupStepService;

/**
 * Renders [kcp_cabinet_group] shortcode.
 */
final class CabinetGroupShortcode {

	private static bool $rendered = false;

	/** @var string */
	private static string $current_slug = '';

	public function register(): void {
		add_shortcode( 'kcp_cabinet_group', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function render( array|string $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'slug' => '',
			),
			is_array( $atts ) ? $atts : array(),
			'kcp_cabinet_group'
		);

		$slug = sanitize_title( (string) ( $atts['slug'] ?? '' ) );

		if ( '' === $slug && is_singular() ) {
			$post = get_post();
			if ( $post instanceof \WP_Post ) {
				$slug = (string) $post->post_name;
			}
		}

		self::$rendered     = true;
		self::$current_slug = $slug;

		ob_start();
		include KCP_PLUGIN_DIR . 'templates/frontend/cabinet-group-step.php';

		return (string) ob_get_clean();
	}

	public static function is_rendered(): bool {
		return self::$rendered;
	}

	public static function current_slug(): string {
		return self::$current_slug;
	}

	public static function post_has_shortcode(): bool {
		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post();

		return $post instanceof \WP_Post && has_shortcode( $post->post_content, 'kcp_cabinet_group' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function current_config(): array {
		$slug = self::$current_slug;

		if ( '' === $slug && is_singular() ) {
			$post = get_post();
			if ( $post instanceof \WP_Post ) {
				$slug = (string) $post->post_name;
			}
		}

		return CabinetGroupStepService::get_public_config( $slug );
	}
}
