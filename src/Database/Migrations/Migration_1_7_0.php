<?php
/**
 * Parent-child cabinet relations table migration.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;
use KitchenConfiguratorPro\Support\Helpers;

/**
 * Adds kcp_cabinet_relations table (v1.7.0).
 */
final class Migration_1_7_0 extends AbstractMigration {

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.7.0';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
		self::create_relations_table();
		Helpers::flush_rewrite_rules();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function down(): void {
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_cabinet_relations' ) );
	}

	/**
	 * Create parent-child cabinet relations table.
	 *
	 * @return void
	 */
	private static function create_relations_table(): void {
		$table = self::table( 'kcp_cabinet_relations' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				parent_cabinet_id BIGINT UNSIGNED NOT NULL,
				child_cabinet_id BIGINT UNSIGNED NOT NULL,
				PRIMARY KEY (id),
				KEY parent_cabinet_id (parent_cabinet_id),
				KEY child_cabinet_id (child_cabinet_id),
				UNIQUE KEY uk_parent_child (parent_cabinet_id, child_cabinet_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}
}
