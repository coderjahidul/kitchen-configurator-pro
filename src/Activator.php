<?php
/**
 * Plugin activation handler.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro;

use KitchenConfiguratorPro\Database\Migrator;
use KitchenConfiguratorPro\Security\CapabilityManager;

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
					'currency'           => 'EUR',
					'quote_validity_days' => 30,
				),
				'',
				false
			);
		}

		if ( false === get_option( 'kcp_catalog_cache_version', false ) ) {
			add_option( 'kcp_catalog_cache_version', 1, '', false );
		}
	}
}
