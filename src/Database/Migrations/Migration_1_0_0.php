<?php
/**
 * Initial database schema migration.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;

/**
 * Creates all core KCP tables (v1.0.0).
 */
final class Migration_1_0_0 extends AbstractMigration {

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.0.0';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
		self::disable_foreign_key_checks();

		self::create_layouts_table();
		self::create_cabinet_categories_table();
		self::create_cabinets_table();
		self::create_materials_table();
		self::create_colors_table();
		self::create_handles_table();
		self::create_accessories_table();
		self::create_pricing_rules_table();
		self::create_projects_table();
		self::create_configurations_table();
		self::create_configuration_history_table();

		self::enable_foreign_key_checks();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function down(): void {
		self::disable_foreign_key_checks();

		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_configuration_history' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_configurations' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_projects' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_pricing_rules' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_accessories' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_handles' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_colors' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_materials' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_cabinets' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_cabinet_categories' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_layouts' ) );
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_migrations' ) );

		self::enable_foreign_key_checks();
	}

	/**
	 * Create layouts table.
	 *
	 * @return void
	 */
	private static function create_layouts_table(): void {
		$table = self::table( 'kcp_layouts' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				slug VARCHAR(100) NOT NULL,
				name VARCHAR(191) NOT NULL,
				description TEXT NULL,
				thumbnail_url VARCHAR(500) NULL,
				config_json LONGTEXT NULL,
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
	 * Create cabinet categories table.
	 *
	 * @return void
	 */
	private static function create_cabinet_categories_table(): void {
		$table = self::table( 'kcp_cabinet_categories' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				slug VARCHAR(100) NOT NULL,
				name VARCHAR(191) NOT NULL,
				description TEXT NULL,
				sort_order INT NOT NULL DEFAULT 0,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uk_slug (slug)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * Create cabinets table.
	 *
	 * @return void
	 */
	private static function create_cabinets_table(): void {
		$table    = self::table( 'kcp_cabinets' );
		$category = self::table( 'kcp_cabinet_categories' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				category_id BIGINT UNSIGNED NOT NULL,
				slug VARCHAR(100) NOT NULL,
				name VARCHAR(191) NOT NULL,
				description TEXT NULL,
				sku_prefix VARCHAR(50) NULL,
				default_width INT UNSIGNED NOT NULL,
				default_height INT UNSIGNED NOT NULL,
				default_depth INT UNSIGNED NOT NULL,
				min_width INT UNSIGNED NOT NULL,
				max_width INT UNSIGNED NOT NULL,
				min_height INT UNSIGNED NOT NULL,
				max_height INT UNSIGNED NOT NULL,
				min_depth INT UNSIGNED NOT NULL,
				max_depth INT UNSIGNED NOT NULL,
				width_step INT UNSIGNED NOT NULL DEFAULT 10,
				height_step INT UNSIGNED NOT NULL DEFAULT 10,
				depth_step INT UNSIGNED NOT NULL DEFAULT 10,
				base_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
				dimension_price_json LONGTEXT NULL,
				image_url VARCHAR(500) NULL,
				sort_order INT NOT NULL DEFAULT 0,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uk_slug (slug),
				KEY idx_category_active (category_id, is_active),
				KEY idx_active_sort (is_active, sort_order),
				CONSTRAINT fk_cabinets_category
					FOREIGN KEY (category_id) REFERENCES {$category} (id)
					ON DELETE RESTRICT ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * Create materials table.
	 *
	 * @return void
	 */
	private static function create_materials_table(): void {
		$table = self::table( 'kcp_materials' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				slug VARCHAR(100) NOT NULL,
				name VARCHAR(191) NOT NULL,
				material_type VARCHAR(50) NOT NULL,
				description TEXT NULL,
				price_modifier DECIMAL(12,2) NOT NULL DEFAULT 0.00,
				price_per_sqm DECIMAL(12,4) NULL,
				price_multiplier DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
				thumbnail_url VARCHAR(500) NULL,
				sort_order INT NOT NULL DEFAULT 0,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uk_slug (slug),
				KEY idx_type_active (material_type, is_active)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * Create colors table.
	 *
	 * @return void
	 */
	private static function create_colors_table(): void {
		$table    = self::table( 'kcp_colors' );
		$material = self::table( 'kcp_materials' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				material_id BIGINT UNSIGNED NOT NULL,
				slug VARCHAR(100) NOT NULL,
				name VARCHAR(191) NOT NULL,
				hex_code CHAR(7) NULL,
				price_modifier DECIMAL(12,2) NOT NULL DEFAULT 0.00,
				thumbnail_url VARCHAR(500) NULL,
				sort_order INT NOT NULL DEFAULT 0,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uk_material_slug (material_id, slug),
				KEY idx_material_active (material_id, is_active),
				CONSTRAINT fk_colors_material
					FOREIGN KEY (material_id) REFERENCES {$material} (id)
					ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * Create handles table.
	 *
	 * @return void
	 */
	private static function create_handles_table(): void {
		$table = self::table( 'kcp_handles' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				slug VARCHAR(100) NOT NULL,
				name VARCHAR(191) NOT NULL,
				description TEXT NULL,
				price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
				thumbnail_url VARCHAR(500) NULL,
				sort_order INT NOT NULL DEFAULT 0,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uk_slug (slug)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * Create accessories table.
	 *
	 * @return void
	 */
	private static function create_accessories_table(): void {
		$table = self::table( 'kcp_accessories' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				slug VARCHAR(100) NOT NULL,
				name VARCHAR(191) NOT NULL,
				category VARCHAR(50) NOT NULL DEFAULT 'general',
				description TEXT NULL,
				price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
				is_per_cabinet TINYINT(1) NOT NULL DEFAULT 1,
				thumbnail_url VARCHAR(500) NULL,
				sort_order INT NOT NULL DEFAULT 0,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uk_slug (slug),
				KEY idx_category_active (category, is_active)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * Create pricing rules table.
	 *
	 * @return void
	 */
	private static function create_pricing_rules_table(): void {
		$table = self::table( 'kcp_pricing_rules' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(191) NOT NULL,
				rule_type VARCHAR(50) NOT NULL,
				entity_type VARCHAR(50) NULL,
				entity_id BIGINT UNSIGNED NULL,
				conditions_json LONGTEXT NOT NULL,
				calculation_json LONGTEXT NOT NULL,
				priority INT NOT NULL DEFAULT 100,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				valid_from DATETIME NULL,
				valid_until DATETIME NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY idx_active_priority (is_active, priority),
				KEY idx_entity (entity_type, entity_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * Create projects table.
	 *
	 * @return void
	 */
	private static function create_projects_table(): void {
		$table = self::table( 'kcp_projects' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				uuid CHAR(36) NOT NULL,
				user_id BIGINT UNSIGNED NULL,
				session_id VARCHAR(64) NULL,
				name VARCHAR(191) NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uk_uuid (uuid),
				KEY idx_user (user_id),
				KEY idx_session (session_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * Create configurations table.
	 *
	 * @return void
	 */
	private static function create_configurations_table(): void {
		$table   = self::table( 'kcp_configurations' );
		$project = self::table( 'kcp_projects' );
		$layout  = self::table( 'kcp_layouts' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				uuid CHAR(36) NOT NULL,
				project_id BIGINT UNSIGNED NULL,
				layout_id BIGINT UNSIGNED NOT NULL,
				user_id BIGINT UNSIGNED NULL,
				session_id VARCHAR(64) NULL,
				title VARCHAR(191) NOT NULL,
				configuration_json LONGTEXT NOT NULL,
				pricing_snapshot_json LONGTEXT NULL,
				total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
				price_hash VARCHAR(64) NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'draft',
				wc_order_id BIGINT UNSIGNED NULL,
				wc_cart_item_key VARCHAR(64) NULL,
				quoted_at DATETIME NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uk_uuid (uuid),
				KEY idx_user_status (user_id, status),
				KEY idx_session (session_id),
				KEY idx_project (project_id),
				KEY idx_status_updated (status, updated_at),
				KEY idx_wc_order (wc_order_id),
				CONSTRAINT fk_configurations_project
					FOREIGN KEY (project_id) REFERENCES {$project} (id)
					ON DELETE SET NULL ON UPDATE CASCADE,
				CONSTRAINT fk_configurations_layout
					FOREIGN KEY (layout_id) REFERENCES {$layout} (id)
					ON DELETE RESTRICT ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * Create configuration history table.
	 *
	 * @return void
	 */
	private static function create_configuration_history_table(): void {
		$table         = self::table( 'kcp_configuration_history' );
		$configurations = self::table( 'kcp_configurations' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				configuration_id BIGINT UNSIGNED NOT NULL,
				configuration_json LONGTEXT NOT NULL,
				pricing_snapshot_json LONGTEXT NULL,
				action VARCHAR(50) NOT NULL,
				actor_user_id BIGINT UNSIGNED NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY idx_configuration_created (configuration_id, created_at),
				CONSTRAINT fk_history_configuration
					FOREIGN KEY (configuration_id) REFERENCES {$configurations} (id)
					ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}
}
