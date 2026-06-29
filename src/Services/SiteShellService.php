<?php
/**
 * Site header/footer view model for the KKF storefront shell.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Frontend\CabinetDetailShortcode;
use KitchenConfiguratorPro\Frontend\CabinetGroupShortcode;
use KitchenConfiguratorPro\Frontend\CabinetListShortcode;
use KitchenConfiguratorPro\Frontend\CabinetRouter;
use KitchenConfiguratorPro\Frontend\CabinetSelectShortcode;
use KitchenConfiguratorPro\Frontend\ConfiguratorLandingShortcode;
use KitchenConfiguratorPro\Frontend\DesignShortcode;
use KitchenConfiguratorPro\Frontend\Shortcode;

/**
 * Builds navigation data and detects when the custom shell should replace the theme header/footer.
 */
final class SiteShellService {

	/**
	 * Whether the KKF shell should replace the theme header and footer.
	 */
	public static function is_active(): bool {
		if ( is_admin() ) {
			return false;
		}

		if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() ) ) {
			return true;
		}

		if (
			ConfiguratorLandingShortcode::post_has_shortcode()
			|| DesignShortcode::post_has_shortcode()
			|| CabinetSelectShortcode::post_has_shortcode()
			|| CabinetGroupShortcode::post_has_shortcode()
			|| CabinetListShortcode::post_has_shortcode()
			|| CabinetDetailShortcode::post_has_shortcode()
			|| Shortcode::post_has_shortcode()
		) {
			return true;
		}

		if ( CabinetRouter::is_child_list_route() || CabinetRouter::is_detail_route() ) {
			return true;
		}

		return false;
	}

	/**
	 * View model for header and footer templates.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_view_model(): array {
		$settings         = SiteShellSettingsService::get_settings();
		$shop_url         = function_exists( 'wc_get_page_permalink' )
			? (string) wc_get_page_permalink( 'shop' )
			: home_url( '/shop/' );
		$cart_url         = function_exists( 'wc_get_cart_url' )
			? (string) wc_get_cart_url()
			: home_url( '/cart/' );
		$configurator_url = ConfiguratorLandingService::get_page_url();

		return array(
			'home_url'              => home_url( '/' ),
			'configurator_url'      => $configurator_url,
			'shop_url'              => $shop_url,
			'cart_url'              => $cart_url,
			'cart_count'            => self::get_cart_count(),
			'announcement_enabled'  => ! empty( $settings['announcement_enabled'] ),
			'announcement_text'     => (string) ( $settings['announcement_text'] ?? '' ),
			'announcement_cta'      => (string) ( $settings['announcement_cta'] ?? '' ),
			'showroom_url'          => (string) ( $settings['announcement_url'] ?? '' ),
			'corporate_url'         => (string) ( $settings['corporate_url'] ?? home_url( '/' ) ),
			'logo_url'              => self::resolve_logo_url( (string) ( $settings['logo_url'] ?? '' ) ),
			'show_theme_toggle'     => ! empty( $settings['show_theme_toggle'] ),
			'primary_nav'           => SiteShellMenuBuilder::get_primary_nav( $configurator_url, $shop_url ),
			'mobile_primary_nav'    => SiteShellMenuBuilder::get_primary_nav( $configurator_url, $shop_url, false ),
			'mobile_links'          => SiteShellMenuBuilder::get_secondary_nav( $shop_url, false ),
			'mobile_sections'       => SiteShellMenuBuilder::get_desktop_nav( false ),
			'desktop_nav'           => SiteShellMenuBuilder::get_desktop_nav(),
			'footer_columns'        => SiteShellMenuBuilder::get_footer_columns(),
			'trust_badges'          => is_array( $settings['trust_badges'] ?? null ) ? $settings['trust_badges'] : array(),
			'legal_links'           => SiteShellMenuBuilder::get_legal_links(),
			'contact'               => is_array( $settings['contact_links'] ?? null ) ? $settings['contact_links'] : array(),
			'payment_icons'         => is_array( $settings['payment_icons'] ?? null ) ? $settings['payment_icons'] : array(),
			'breadcrumbs'           => self::get_breadcrumbs(),
		);
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	public static function get_breadcrumbs(): array {
		if ( class_exists( ShopBrandLandingService::class ) && ShopBrandLandingService::is_active() ) {
			return ShopBrandLandingService::get_breadcrumbs();
		}

		if ( ConfiguratorLandingShortcode::post_has_shortcode() || ConfiguratorLandingShortcode::is_rendered() ) {
			return array(
				array(
					'label' => __( 'configurator', 'kitchen-configurator-pro' ),
					'url'   => '',
				),
			);
		}

		if ( DesignShortcode::post_has_shortcode() || DesignShortcode::is_rendered() ) {
			$design = DesignStepService::get_settings();

			return array(
				array(
					'label' => (string) ( $design['breadcrumb'] ?? $design['heading'] ?? '' ),
					'url'   => '',
				),
			);
		}

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return array(
				array(
					'label' => __( 'configurator', 'kitchen-configurator-pro' ),
					'url'   => '',
				),
			);
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return array(
				array(
					'label' => __( 'winkelwagen', 'kitchen-configurator-pro' ),
					'url'   => '',
				),
			);
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
			return array(
				array(
					'label' => __( 'afrekenen', 'kitchen-configurator-pro' ),
					'url'   => '',
				),
			);
		}

		return array();
	}

	/**
	 * Plugin logo URL overrides the theme Site Identity logo when set.
	 */
	private static function resolve_logo_url( string $override_url ): string {
		if ( '' !== $override_url ) {
			return $override_url;
		}

		$logo_id = (int) get_theme_mod( 'custom_logo', 0 );
		if ( $logo_id <= 0 ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $logo_id, 'full' );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * @return int
	 */
	private static function get_cart_count(): int {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return 0;
		}

		return (int) WC()->cart->get_cart_contents_count();
	}
}
