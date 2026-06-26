<?php
/**
 * Cabinet child-list step shortcode.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

use KitchenConfiguratorPro\Services\CabinetListStepService;

/**
 * Renders [kcp_cabinet_list] shortcode for parent cabinet child listings.
 */
final class CabinetListShortcode {

	private static bool $rendered = false;

	/** @var string */
	private static string $category_slug = '';

	/** @var string */
	private static string $parent_cabinet_slug = '';

	public function register(): void {
		add_shortcode( 'kcp_cabinet_list', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function render( array|string $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'category' => '',
				'parent'   => '',
			),
			is_array( $atts ) ? $atts : array(),
			'kcp_cabinet_list'
		);

		$category_slug = sanitize_title( (string) ( $atts['category'] ?? '' ) );
		$parent_slug   = sanitize_title( (string) ( $atts['parent'] ?? '' ) );

		if ( '' === $category_slug ) {
			$category_slug = sanitize_title( (string) get_query_var( 'kcp_category_slug', '' ) );
		}

		if ( '' === $parent_slug ) {
			$parent_slug = sanitize_title( (string) get_query_var( 'kcp_parent_cabinet_slug', '' ) );
		}

		self::$rendered            = true;
		self::$category_slug       = $category_slug;
		self::$parent_cabinet_slug = $parent_slug;

		ob_start();
		include KCP_PLUGIN_DIR . 'templates/frontend/cabinet-list-step.php';

		return (string) ob_get_clean();
	}

	public static function is_rendered(): bool {
		return self::$rendered;
	}

	public static function category_slug(): string {
		return self::$category_slug;
	}

	public static function parent_cabinet_slug(): string {
		return self::$parent_cabinet_slug;
	}

	public static function post_has_shortcode(): bool {
		if ( CabinetRouter::is_child_list_route() ) {
			return true;
		}

		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post();

		return $post instanceof \WP_Post && has_shortcode( $post->post_content, 'kcp_cabinet_list' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function current_config(): array {
		$category_slug = self::$category_slug;
		$parent_slug   = self::$parent_cabinet_slug;

		if ( '' === $category_slug ) {
			$category_slug = sanitize_title( (string) get_query_var( 'kcp_category_slug', '' ) );
		}

		if ( '' === $parent_slug ) {
			$parent_slug = sanitize_title( (string) get_query_var( 'kcp_parent_cabinet_slug', '' ) );
		}

		return CabinetListStepService::get_public_config( $category_slug, $parent_slug );
	}
}
