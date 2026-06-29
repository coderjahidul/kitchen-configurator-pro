<?php
/**
 * Configurator landing page helpers.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Frontend\ConfiguratorLandingShortcode;

/**
 * Resolves landing page URLs and product queries for the configurator entry page.
 */
final class ConfiguratorLandingService {

	/**
	 * Whether the configurator landing shortcode is active on the current request.
	 */
	public static function is_active(): bool {
		return ConfiguratorLandingShortcode::is_rendered()
			|| ConfiguratorLandingShortcode::post_has_shortcode();
	}

	/**
	 * Published page permalink for the configurator landing shortcode.
	 */
	public static function get_page_url(): string {
		$page_id = self::resolve_page_id();

		if ( $page_id <= 0 ) {
			return function_exists( 'wc_get_page_permalink' )
				? (string) wc_get_page_permalink( 'shop' )
				: home_url( '/' );
		}

		$url = get_permalink( $page_id );

		return is_string( $url ) ? $url : home_url( '/' );
	}

	/**
	 * Query arguments for popular arrangement products on the landing page.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_products_query_args(): array {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		);

		/**
		 * Filter product query args for the configurator landing page.
		 *
		 * @param array<string, mixed> $args Query arguments.
		 */
		return apply_filters( 'kcp_configurator_landing_products_query', $args );
	}

	/**
	 * Published page ID that renders the configurator landing shortcode.
	 */
	public static function resolve_page_id(): int {
		$cached = get_transient( 'kcp_configurator_landing_page_id' );
		if ( is_numeric( $cached ) && (int) $cached > 0 ) {
			return (int) $cached;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		foreach ( $posts as $post_id ) {
			$post = get_post( (int) $post_id );
			if ( ! $post instanceof \WP_Post || ! has_shortcode( $post->post_content, 'kcp_configurator_landing' ) ) {
				continue;
			}

			set_transient( 'kcp_configurator_landing_page_id', (int) $post_id, DAY_IN_SECONDS );
			return (int) $post_id;
		}

		return 0;
	}
}
