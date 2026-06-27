<?php
/**
 * Widen price_hash column for sha256-prefixed hashes.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;

/**
 * Expands price_hash storage (v1.7.2).
 */
final class Migration_1_7_2 extends AbstractMigration {

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.7.2';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
		$table = self::table( 'kcp_configurations' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		self::exec( "ALTER TABLE {$table} MODIFY COLUMN price_hash VARCHAR(96) NULL" );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function down(): void {
		$table = self::table( 'kcp_configurations' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		self::exec( "ALTER TABLE {$table} MODIFY COLUMN price_hash VARCHAR(64) NULL" );
	}
}
