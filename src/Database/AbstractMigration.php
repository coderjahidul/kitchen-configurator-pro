<?php
/**
 * Base migration with shared database helpers.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database;

use KitchenConfiguratorPro\Contracts\MigrationInterface;
use KitchenConfiguratorPro\Support\Helpers;
use wpdb;

/**
 * Abstract migration providing table name and query helpers.
 */
abstract class AbstractMigration implements MigrationInterface {

	/**
	 * WordPress database instance.
	 *
	 * @var wpdb
	 */
	protected static wpdb $wpdb;

	/**
	 * Set the database instance for static migration methods.
	 *
	 * @param wpdb $wpdb WordPress database object.
	 * @return void
	 */
	public static function set_db( wpdb $wpdb ): void {
		self::$wpdb = $wpdb;
	}

	/**
	 * Get prefixed table name.
	 *
	 * @param string $suffix Table suffix (e.g. kcp_layouts).
	 * @return string
	 */
	protected static function table( string $suffix ): string {
		return Helpers::table_name( $suffix );
	}

	/**
	 * Execute a SQL statement.
	 *
	 * @param string $sql SQL query.
	 * @return void
	 *
	 * @throws \RuntimeException When the query fails.
	 */
	protected static function exec( string $sql ): void {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- DDL statements from controlled migration classes.
		$result = self::$wpdb->query( $sql );

		if ( false === $result && ! empty( self::$wpdb->last_error ) ) {
			throw new \RuntimeException(
				sprintf(
					'Migration %s failed: %s',
					static::version(),
					self::$wpdb->last_error
				)
			);
		}
	}

	/**
	 * Disable foreign key checks for table creation.
	 *
	 * @return void
	 */
	protected static function disable_foreign_key_checks(): void {
		self::exec( 'SET FOREIGN_KEY_CHECKS = 0' );
	}

	/**
	 * Re-enable foreign key checks.
	 *
	 * @return void
	 */
	protected static function enable_foreign_key_checks(): void {
		self::exec( 'SET FOREIGN_KEY_CHECKS = 1' );
	}
}
