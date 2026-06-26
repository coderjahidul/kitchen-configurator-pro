<?php
/**
 * Cabinet detail step shortcode.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

use KitchenConfiguratorPro\Services\CabinetDetailStepService;

/**
 * Renders [kcp_cabinet_detail] shortcode for leaf cabinet pages.
 */
final class CabinetDetailShortcode {

	private static bool $rendered = false;

	private static string $category_slug = '';

	private static string $parent_cabinet_slug = '';

	private static string $cabinet_slug = '';

	public function register(): void {
		add_shortcode( 'kcp_cabinet_detail', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function render( array|string $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'category' => '',
				'parent'   => '',
				'cabinet'  => '',
			),
			is_array( $atts ) ? $atts : array(),
			'kcp_cabinet_detail'
		);

		$category_slug = sanitize_title( (string) ( $atts['category'] ?? '' ) );
		$parent_slug   = sanitize_title( (string) ( $atts['parent'] ?? '' ) );
		$cabinet_slug  = sanitize_title( (string) ( $atts['cabinet'] ?? '' ) );

		if ( '' === $category_slug ) {
			$category_slug = sanitize_title( (string) get_query_var( 'kcp_category_slug', '' ) );
		}

		if ( '' === $parent_slug ) {
			$parent_slug = sanitize_title( (string) get_query_var( 'kcp_parent_cabinet_slug', '' ) );
		}

		if ( '' === $cabinet_slug ) {
			$cabinet_slug = sanitize_title( (string) get_query_var( 'kcp_cabinet_slug', '' ) );
		}

		self::$rendered            = true;
		self::$category_slug       = $category_slug;
		self::$parent_cabinet_slug = $parent_slug;
		self::$cabinet_slug        = $cabinet_slug;

		ob_start();
		include KCP_PLUGIN_DIR . 'templates/frontend/cabinet-detail-step.php';

		return (string) ob_get_clean();
	}

	public static function is_rendered(): bool {
		return self::$rendered;
	}

	public static function post_has_shortcode(): bool {
		if ( CabinetRouter::is_detail_route() ) {
			return true;
		}

		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post();

		return $post instanceof \WP_Post && has_shortcode( $post->post_content, 'kcp_cabinet_detail' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function current_config(): array {
		$category_slug = self::$category_slug;
		$parent_slug   = self::$parent_cabinet_slug;
		$cabinet_slug  = self::$cabinet_slug;

		if ( '' === $category_slug ) {
			$category_slug = sanitize_title( (string) get_query_var( 'kcp_category_slug', '' ) );
		}

		if ( '' === $parent_slug ) {
			$parent_slug = sanitize_title( (string) get_query_var( 'kcp_parent_cabinet_slug', '' ) );
		}

		if ( '' === $cabinet_slug ) {
			$cabinet_slug = sanitize_title( (string) get_query_var( 'kcp_cabinet_slug', '' ) );
		}

		return CabinetDetailStepService::get_public_config( $category_slug, $parent_slug, $cabinet_slug );
	}
}
