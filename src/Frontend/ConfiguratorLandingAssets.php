<?php
/**
 * Configurator landing page frontend assets.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

use KitchenConfiguratorPro\Services\ShopHeroService;
use KitchenConfiguratorPro\Services\ShopPromoService;

/**
 * Enqueues shop landing styles and scripts on the configurator entry page.
 */
final class ConfiguratorLandingAssets {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * @param array<int, string> $classes Body classes.
	 * @return array<int, string>
	 */
	public function body_class( array $classes ): array {
		if ( ! ConfiguratorLandingShortcode::post_has_shortcode() ) {
			return $classes;
		}

		$classes[] = 'kcp-shop-active';
		$classes[] = 'kcp-configurator-landing-active';

		return $classes;
	}

	/**
	 * Enqueue landing page assets.
	 */
	public function enqueue(): void {
		if ( ! ConfiguratorLandingShortcode::is_rendered() && ! ConfiguratorLandingShortcode::post_has_shortcode() ) {
			return;
		}

		wp_enqueue_style(
			'kcp-shop',
			KCP_PLUGIN_URL . 'assets/frontend/css/shop.css',
			array(),
			KCP_VERSION
		);

		$hero_images = ShopHeroService::get_settings()['image_urls'] ?? array();

		if ( is_array( $hero_images ) && count( $hero_images ) > 1 ) {
			wp_enqueue_script(
				'kcp-shop-hero',
				KCP_PLUGIN_URL . 'assets/frontend/js/shop-hero.js',
				array(),
				KCP_VERSION,
				true
			);
		}

		if ( ShopPromoService::is_enabled() ) {
			wp_enqueue_script(
				'kcp-shop-promo',
				KCP_PLUGIN_URL . 'assets/frontend/js/shop-promo.js',
				array(),
				KCP_VERSION,
				true
			);
		}
	}
}
