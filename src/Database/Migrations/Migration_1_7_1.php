<?php
/**
 * Ensure cabinet relations table exists.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;
use KitchenConfiguratorPro\Support\Helpers;

/**
 * Repairs missing kcp_cabinet_relations table (v1.7.1).
 */
final class Migration_1_7_1 extends AbstractMigration {

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.7.1';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
		$table = self::table( 'kcp_cabinet_relations' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$exists = self::$wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );

		if ( $exists === $table ) {
			return;
		}

		self::exec(
			"CREATE TABLE {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				parent_cabinet_id BIGINT UNSIGNED NOT NULL,
				child_cabinet_id BIGINT UNSIGNED NOT NULL,
				PRIMARY KEY (id),
				KEY parent_cabinet_id (parent_cabinet_id),
				KEY child_cabinet_id (child_cabinet_id),
				UNIQUE KEY uk_parent_child (parent_cabinet_id, child_cabinet_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);

		Helpers::flush_rewrite_rules();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function down(): void {
		// Keep table — shared with 1.7.0.
	}
}
