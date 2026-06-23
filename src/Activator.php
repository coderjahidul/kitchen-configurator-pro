<?php
/**
 * Plugin activation handler.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro;

use KitchenConfiguratorPro\Database\Migrator;
use KitchenConfiguratorPro\Integration\WooCommerce\ProductManager;
use KitchenConfiguratorPro\Security\CapabilityManager;
use KitchenConfiguratorPro\Services\CabinetSelectStepService;
use KitchenConfiguratorPro\Services\DesignStepService;
use KitchenConfiguratorPro\Services\ShopHeroService;

/**
 * Handles plugin activation.
 */
final class Activator {

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::ensure_php_version();
		self::run_migrations();
		self::register_capabilities();
		self::set_default_options();
		self::ensure_woocommerce_product();
		flush_rewrite_rules();
	}

	/**
	 * Ensure the server meets the minimum PHP version.
	 *
	 * @return void
	 */
	private static function ensure_php_version(): void {
		if ( version_compare( PHP_VERSION, '8.2.0', '>=' ) ) {
			return;
		}

		deactivate_plugins( KCP_PLUGIN_BASENAME );

		wp_die(
			esc_html__(
				'Kitchen Configurator Pro requires PHP 8.2 or higher.',
				'kitchen-configurator-pro'
			),
			esc_html__( 'Plugin Activation Error', 'kitchen-configurator-pro' ),
			array( 'back_link' => true )
		);
	}

	/**
	 * Run database migrations.
	 *
	 * @return void
	 */
	private static function run_migrations(): void {
		global $wpdb;

		$migrator = new Migrator( $wpdb );
		$migrator->run();
	}

	/**
	 * Register plugin capabilities.
	 *
	 * @return void
	 */
	private static function register_capabilities(): void {
		CapabilityManager::register();
	}

	/**
	 * Set default plugin options.
	 *
	 * @return void
	 */
	private static function set_default_options(): void {
		if ( false === get_option( 'kcp_settings', false ) ) {
			add_option(
				'kcp_settings',
				array(
					'currency'            => 'EUR',
					'quote_validity_days' => 30,
					'design_check_price'  => 75,
					'shop_hero'           => ShopHeroService::defaults(),
					'design_step'         => DesignStepService::defaults(),
					'cabinet_select_step' => CabinetSelectStepService::defaults(),
				),
				'',
				false
			);
		}

		if ( false === get_option( 'kcp_catalog_cache_version', false ) ) {
			add_option( 'kcp_catalog_cache_version', 1, '', false );
		}
	}

	/**
	 * Create the WooCommerce container product on activation when WooCommerce is available.
	 *
	 * @return void
	 */
	private static function ensure_woocommerce_product(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		try {
			( new ProductManager() )->ensure_container_product();
		} catch ( \Throwable $exception ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'KCP container product creation failed: ' . $exception->getMessage() );
			}
		}
	}
}
