<?php
/**
 * Align layouts table with current schema.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;
use KitchenConfiguratorPro\Support\Helpers;

/**
 * Recreates kcp_layouts when an older schema is detected (v1.2.0).
 */
final class Migration_1_2_0 extends AbstractMigration {

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.2.0';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
		if ( self::has_current_layouts_schema() ) {
			return;
		}

		$table = self::table( 'kcp_layouts' );
		$rows  = self::$wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		self::disable_foreign_key_checks();
		self::exec( "DROP TABLE IF EXISTS {$table}" );
		self::create_layouts_table();
		self::enable_foreign_key_checks();

		if ( ! is_array( $rows ) ) {
			return;
		}

		$now = Helpers::now();

		foreach ( $rows as $row ) {
			$config = array();

			foreach ( array( 'base_price', 'width_mm', 'depth_mm', 'height_mm', 'gallery', 'badges' ) as $key ) {
				if ( isset( $row[ $key ] ) && '' !== (string) $row[ $key ] ) {
					$config[ $key ] = $row[ $key ];
				}
			}

			$config_json = empty( $config )
				? ''
				: wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

			self::$wpdb->insert(
				$table,
				array(
					'slug'          => (string) ( $row['slug'] ?? '' ),
					'name'          => (string) ( $row['name'] ?? '' ),
					'description'   => (string) ( $row['description'] ?? '' ),
					'thumbnail_url' => (string) ( $row['image_url'] ?? $row['thumbnail_url'] ?? '' ),
					'config_json'   => is_string( $config_json ) ? $config_json : '',
					'sort_order'    => (int) ( $row['sort_order'] ?? 0 ),
					'is_active'     => self::resolve_is_active( $row ),
					'created_at'    => (string) ( $row['created_at'] ?? $now ),
					'updated_at'    => (string) ( $row['updated_at'] ?? $now ),
				)
			);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public static function down(): void {
		// Irreversible: legacy schema is not restored.
	}

	/**
	 * Whether the layouts table already matches the current schema.
	 *
	 * @return bool
	 */
	private static function has_current_layouts_schema(): bool {
		$table = self::table( 'kcp_layouts' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$column = self::$wpdb->get_var( "SHOW COLUMNS FROM {$table} LIKE 'is_active'" );

		return null !== $column;
	}

	/**
	 * Create layouts table with the current schema.
	 *
	 * @return void
	 */
	private static function create_layouts_table(): void {
		$table = self::table( 'kcp_layouts' );

		self::exec(
			"CREATE TABLE {$table} (
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
}
