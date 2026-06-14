<?php
/**
 * Align legacy catalog tables with the current schema.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;
use KitchenConfiguratorPro\Support\Helpers;

/**
 * Recreates catalog tables when an older schema is detected (v1.3.0).
 */
final class Migration_1_3_0 extends AbstractMigration {

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.3.0';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
		self::disable_foreign_key_checks();

		self::migrate_materials_table();
		self::migrate_colors_table();
		self::migrate_handles_table();
		self::migrate_accessories_table();
		self::migrate_pricing_rules_table();
		self::migrate_cabinets_table();

		self::enable_foreign_key_checks();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function down(): void {
		// Irreversible: legacy schema is not restored.
	}

	/**
	 * Migrate materials table when needed.
	 *
	 * @return void
	 */
	private static function migrate_materials_table(): void {
		if ( self::has_column( 'kcp_materials', 'is_active' ) ) {
			return;
		}

		$table = self::table( 'kcp_materials' );
		$rows  = self::$wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$now   = Helpers::now();

		self::exec( "DROP TABLE IF EXISTS {$table}" );
		self::exec(
			"CREATE TABLE {$table} (
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

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			self::$wpdb->insert(
				$table,
				array(
					'id'               => (int) $row['id'],
					'slug'             => (string) ( $row['slug'] ?? '' ),
					'name'             => (string) ( $row['name'] ?? '' ),
					'material_type'    => 'front',
					'description'      => '',
					'price_modifier'   => number_format( (float) ( $row['price_modifier'] ?? 0 ), 2, '.', '' ),
					'price_per_sqm'    => null,
					'price_multiplier' => '1.0000',
					'thumbnail_url'    => '',
					'sort_order'       => (int) ( $row['sort_order'] ?? 0 ),
					'is_active'        => self::resolve_is_active( $row ),
					'created_at'       => $now,
					'updated_at'       => $now,
				)
			);
		}
	}

	/**
	 * Migrate colors table when needed.
	 *
	 * @return void
	 */
	private static function migrate_colors_table(): void {
		if ( self::has_column( 'kcp_colors', 'is_active' ) ) {
			return;
		}

		$table         = self::table( 'kcp_colors' );
		$materials     = self::table( 'kcp_materials' );
		$rows          = self::$wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$now           = Helpers::now();
		$fallback_material_id = (int) self::$wpdb->get_var( "SELECT id FROM {$materials} ORDER BY id ASC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		self::exec( "DROP TABLE IF EXISTS {$table}" );
		self::exec(
			"CREATE TABLE {$table} (
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
					FOREIGN KEY (material_id) REFERENCES {$materials} (id)
					ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$material_id = (int) ( $row['material_id'] ?? 0 );

			if ( $material_id <= 0 ) {
				$material_id = max( 1, $fallback_material_id );
			}

			self::$wpdb->insert(
				$table,
				array(
					'id'             => (int) $row['id'],
					'material_id'    => $material_id,
					'slug'           => (string) ( $row['slug'] ?? '' ),
					'name'           => (string) ( $row['name'] ?? '' ),
					'hex_code'       => (string) ( $row['hex_code'] ?? '' ),
					'price_modifier' => number_format( (float) ( $row['price_modifier'] ?? 0 ), 2, '.', '' ),
					'thumbnail_url'  => (string) ( $row['image_url'] ?? $row['thumbnail_url'] ?? '' ),
					'sort_order'     => (int) ( $row['sort_order'] ?? 0 ),
					'is_active'      => self::resolve_is_active( $row ),
					'created_at'     => $now,
					'updated_at'     => $now,
				)
			);
		}
	}

	/**
	 * Migrate handles table when needed.
	 *
	 * @return void
	 */
	private static function migrate_handles_table(): void {
		if ( self::has_column( 'kcp_handles', 'is_active' ) ) {
			return;
		}

		$table = self::table( 'kcp_handles' );
		$rows  = self::$wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$now   = Helpers::now();

		self::exec( "DROP TABLE IF EXISTS {$table}" );
		self::exec(
			"CREATE TABLE {$table} (
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

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$price = $row['price'] ?? $row['price_modifier'] ?? 0;

			self::$wpdb->insert(
				$table,
				array(
					'id'            => (int) $row['id'],
					'slug'          => (string) ( $row['slug'] ?? '' ),
					'name'          => (string) ( $row['name'] ?? '' ),
					'description'   => '',
					'price'         => number_format( (float) $price, 2, '.', '' ),
					'thumbnail_url' => '',
					'sort_order'    => (int) ( $row['sort_order'] ?? 0 ),
					'is_active'     => self::resolve_is_active( $row ),
					'created_at'    => $now,
					'updated_at'    => $now,
				)
			);
		}
	}

	/**
	 * Migrate accessories table when needed.
	 *
	 * @return void
	 */
	private static function migrate_accessories_table(): void {
		if ( self::has_column( 'kcp_accessories', 'is_active' ) ) {
			return;
		}

		$table = self::table( 'kcp_accessories' );
		$rows  = self::$wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$now   = Helpers::now();

		self::exec( "DROP TABLE IF EXISTS {$table}" );
		self::exec(
			"CREATE TABLE {$table} (
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

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			self::$wpdb->insert(
				$table,
				array(
					'id'             => (int) $row['id'],
					'slug'           => (string) ( $row['slug'] ?? '' ),
					'name'           => (string) ( $row['name'] ?? '' ),
					'category'       => 'general',
					'description'    => '',
					'price'          => number_format( (float) ( $row['price'] ?? 0 ), 2, '.', '' ),
					'is_per_cabinet' => 1,
					'thumbnail_url'  => '',
					'sort_order'     => (int) ( $row['sort_order'] ?? 0 ),
					'is_active'      => self::resolve_is_active( $row ),
					'created_at'     => $now,
					'updated_at'     => $now,
				)
			);
		}
	}

	/**
	 * Migrate pricing rules table when needed.
	 *
	 * @return void
	 */
	private static function migrate_pricing_rules_table(): void {
		if ( self::has_column( 'kcp_pricing_rules', 'is_active' ) ) {
			return;
		}

		$table = self::table( 'kcp_pricing_rules' );
		$rows  = self::$wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$now   = Helpers::now();

		self::exec( "DROP TABLE IF EXISTS {$table}" );
		self::exec(
			"CREATE TABLE {$table} (
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

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$target      = (string) ( $row['target'] ?? '' );
			$legacy_type = (string) ( $row['rule_type'] ?? 'surcharge' );
			$rule_type   = in_array( $legacy_type, array( 'surcharge', 'discount', 'multiplier', 'fixed' ), true )
				? $legacy_type
				: 'surcharge';

			$conditions = wp_json_encode(
				array(
					'legacy_target' => $target,
					'legacy_type'   => $legacy_type,
				),
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

			$calculation = wp_json_encode(
				array(
					'type'   => 'fixed',
					'amount' => (float) ( $row['extra_price'] ?? 0 ),
				),
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

			self::$wpdb->insert(
				$table,
				array(
					'id'               => (int) $row['id'],
					'name'             => '' !== $target ? $target : (string) ( $row['name'] ?? 'Legacy Rule' ),
					'rule_type'        => $rule_type,
					'entity_type'      => null,
					'entity_id'        => null,
					'conditions_json'  => is_string( $conditions ) ? $conditions : '{}',
					'calculation_json' => is_string( $calculation ) ? $calculation : '{}',
					'priority'         => (int) ( $row['priority'] ?? 100 ),
					'is_active'        => self::resolve_is_active( $row ),
					'valid_from'       => null,
					'valid_until'      => null,
					'created_at'       => $now,
					'updated_at'       => $now,
				)
			);
		}
	}

	/**
	 * Migrate cabinets table when needed.
	 *
	 * @return void
	 */
	private static function migrate_cabinets_table(): void {
		if ( self::has_column( 'kcp_cabinets', 'category_id' ) ) {
			return;
		}

		$table    = self::table( 'kcp_cabinets' );
		$category = self::table( 'kcp_cabinet_categories' );
		$rows     = self::$wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$now      = Helpers::now();

		self::exec( "DROP TABLE IF EXISTS {$table}" );
		self::exec(
			"CREATE TABLE {$table} (
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

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$category_id = self::resolve_category_id( (string) ( $row['category'] ?? '' ) );
			$min_width   = max( 0, (int) ( $row['min_width'] ?? 300 ) );
			$max_width   = max( $min_width, (int) ( $row['max_width'] ?? 1200 ) );
			$min_height  = max( 0, (int) ( $row['min_height'] ?? 720 ) );
			$max_height  = max( $min_height, (int) ( $row['max_height'] ?? 900 ) );
			$min_depth   = max( 0, (int) ( $row['min_depth'] ?? 560 ) );
			$max_depth   = max( $min_depth, (int) ( $row['max_depth'] ?? 650 ) );

			self::$wpdb->insert(
				$table,
				array(
					'id'                   => (int) $row['id'],
					'category_id'          => $category_id,
					'slug'                 => (string) ( $row['slug'] ?? '' ),
					'name'                 => (string) ( $row['name'] ?? '' ),
					'description'          => '',
					'sku_prefix'           => '',
					'default_width'        => (int) round( ( $min_width + $max_width ) / 2 ),
					'default_height'       => (int) round( ( $min_height + $max_height ) / 2 ),
					'default_depth'        => (int) round( ( $min_depth + $max_depth ) / 2 ),
					'min_width'            => $min_width,
					'max_width'            => $max_width,
					'min_height'           => $min_height,
					'max_height'           => $max_height,
					'min_depth'            => $min_depth,
					'max_depth'            => $max_depth,
					'width_step'           => 10,
					'height_step'          => 10,
					'depth_step'           => 10,
					'base_price'           => number_format( (float) ( $row['base_price'] ?? 0 ), 2, '.', '' ),
					'dimension_price_json' => '',
					'image_url'            => '',
					'sort_order'           => (int) ( $row['sort_order'] ?? 0 ),
					'is_active'            => self::resolve_is_active( $row ),
					'created_at'           => $now,
					'updated_at'           => $now,
				)
			);
		}
	}

	/**
	 * Check whether a table has a column.
	 *
	 * @param string $suffix Table suffix.
	 * @param string $column Column name.
	 * @return bool
	 */
	private static function has_column( string $suffix, string $column ): bool {
		$table = self::table( $suffix );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		return null !== self::$wpdb->get_var( "SHOW COLUMNS FROM {$table} LIKE '{$column}'" );
	}

	/**
	 * Resolve active flag from legacy or current row data.
	 *
	 * @param array<string, mixed> $row Database row.
	 * @return int
	 */
	private static function resolve_is_active( array $row ): int {
		if ( isset( $row['is_active'] ) ) {
			return (int) ( (bool) $row['is_active'] );
		}

		$status = strtolower( (string) ( $row['status'] ?? 'active' ) );

		return 'active' === $status ? 1 : 0;
	}

	/**
	 * Resolve cabinet category ID from legacy category slug.
	 *
	 * @param string $legacy_category Legacy category slug.
	 * @return int
	 */
	private static function resolve_category_id( string $legacy_category ): int {
		$table = self::table( 'kcp_cabinet_categories' );
		$slug  = sanitize_title( $legacy_category );

		if ( '' === $slug ) {
			$slug = 'base-cabinet';
		}

		$candidates = array_unique(
			array(
				$slug,
				$slug . '-cabinet',
				'base-cabinet',
			)
		);

		foreach ( $candidates as $candidate ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
			$id = self::$wpdb->get_var(
				self::$wpdb->prepare(
					"SELECT id FROM {$table} WHERE slug = %s LIMIT 1",
					$candidate
				)
			);

			if ( null !== $id ) {
				return (int) $id;
			}
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$fallback = self::$wpdb->get_var( "SELECT id FROM {$table} ORDER BY id ASC LIMIT 1" );

		return max( 1, (int) $fallback );
	}
}
