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
			\KitchenConfiguratorPro\Database\Migrations\Migration_1_7_0::class,
			\KitchenConfiguratorPro\Database\Migrations\Migration_1_7_1::class,
			\KitchenConfiguratorPro\Database\Migrations\Migration_1_7_2::class,
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
		self::flush_route_transients();
	}

	/**
	 * Clear cached cabinet selection page URLs.
	 *
	 * @return void
	 */
	public static function flush_route_transients(): void {
		global $wpdb;

		delete_transient( 'kcp_cabinet_select_page_url' );
		delete_transient( 'kcp_cabinet_select_page_path' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_kcp_cabinet_%'
			OR option_name LIKE '_transient_timeout_kcp_cabinet_%'"
		);
	}

	/**
	 * Flush rewrite rules after route structure changes.
	 *
	 * @return void
	 */
	public static function flush_rewrite_rules(): void {
		flush_rewrite_rules( false );
	}

	/**
	 * Convert a permalink URL to a site-relative path for rewrite rules.
	 *
	 * Strips the WordPress home path so subdirectory installs work correctly.
	 */
	public static function relative_site_path_from_url( string $url ): string {
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );

		if ( '' === $path ) {
			return '';
		}

		$home_path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );

		if ( '' !== $home_path ) {
			if ( $path === $home_path ) {
				return '';
			}

			if ( str_starts_with( $path, $home_path . '/' ) ) {
				return substr( $path, strlen( $home_path ) + 1 );
			}
		}

		return $path;
	}
}
