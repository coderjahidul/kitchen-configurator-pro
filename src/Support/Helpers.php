<?php
/**
 * Shared helper functions.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Support;

/**
 * Utility helpers for the plugin.
 */
final class Helpers {

	/**
	 * Get a fully qualified custom table name.
	 *
	 * @param string $suffix Table suffix without prefix (e.g. "kcp_cabinets").
	 * @return string
	 */
	public static function table_name( string $suffix ): string {
		global $wpdb;

		return $wpdb->prefix . $suffix;
	}

	/**
	 * Current UTC datetime string for MySQL.
	 *
	 * @return string
	 */
	public static function now(): string {
		return gmdate( 'Y-m-d H:i:s' );
	}

	/**
	 * Get the list of registered migration class names in version order.
	 *
	 * @return array<int, class-string<\KitchenConfiguratorPro\Contracts\MigrationInterface>>
	 */
	public static function migration_classes(): array {
		return array(
			\KitchenConfiguratorPro\Database\Migrations\Migration_1_0_0::class,
			\KitchenConfiguratorPro\Database\Migrations\Migration_1_1_0::class,
			\KitchenConfiguratorPro\Database\Migrations\Migration_1_2_0::class,
			\KitchenConfiguratorPro\Database\Migrations\Migration_1_3_0::class,
		);
	}

	/**
	 * Bump catalog cache version to invalidate transients.
	 *
	 * @return void
	 */
	public static function bump_catalog_cache_version(): void {
		$version = (int) get_option( 'kcp_catalog_cache_version', 1 );
		update_option( 'kcp_catalog_cache_version', $version + 1, false );
	}
}
