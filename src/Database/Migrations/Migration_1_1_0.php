<?php
/**
 * Worktop and plinth catalog tables migration.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;

/**
 * Adds kcp_worktops and kcp_plinths tables (v1.1.0).
 */
final class Migration_1_1_0 extends AbstractMigration {

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.1.0';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
		self::create_worktops_table();
		self::create_plinths_table();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function down(): void {
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_plinths' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_worktops' ) );
	}

	/**
	 * Create worktops table.
	 *
	 * @return void
	 */
	private static function create_worktops_table(): void {
		$table = self::table( 'kcp_worktops' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				slug VARCHAR(100) NOT NULL,
				name VARCHAR(191) NOT NULL,
				description TEXT NULL,
				default_length INT UNSIGNED NOT NULL DEFAULT 3000,
				default_depth INT UNSIGNED NOT NULL DEFAULT 600,
				min_length INT UNSIGNED NOT NULL DEFAULT 600,
				max_length INT UNSIGNED NOT NULL DEFAULT 5000,
				min_depth INT UNSIGNED NOT NULL DEFAULT 400,
				max_depth INT UNSIGNED NOT NULL DEFAULT 1200,
				length_step INT UNSIGNED NOT NULL DEFAULT 10,
				depth_step INT UNSIGNED NOT NULL DEFAULT 10,
				base_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
				price_per_sqm DECIMAL(12,4) NULL,
				price_per_linear_meter DECIMAL(12,4) NULL,
				thumbnail_url VARCHAR(500) NULL,
				sort_order INT NOT NULL DEFAULT 0,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uk_slug (slug),
				KEY idx_active_sort (is_active, sort_order)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * Create plinths table.
	 *
	 * @return void
	 */
	private static function create_plinths_table(): void {
		$table = self::table( 'kcp_plinths' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				slug VARCHAR(100) NOT NULL,
				name VARCHAR(191) NOT NULL,
				description TEXT NULL,
				default_height INT UNSIGNED NOT NULL DEFAULT 150,
				min_height INT UNSIGNED NOT NULL DEFAULT 100,
				max_height INT UNSIGNED NOT NULL DEFAULT 200,
				height_step INT UNSIGNED NOT NULL DEFAULT 10,
				default_length INT UNSIGNED NOT NULL DEFAULT 3000,
				min_length INT UNSIGNED NOT NULL DEFAULT 600,
				max_length INT UNSIGNED NOT NULL DEFAULT 10000,
				length_step INT UNSIGNED NOT NULL DEFAULT 10,
				base_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
				price_per_linear_meter DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
				thumbnail_url VARCHAR(500) NULL,
				sort_order INT NOT NULL DEFAULT 0,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uk_slug (slug),
				KEY idx_active_sort (is_active, sort_order)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}
}
