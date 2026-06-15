<?php
/**
 * Reference storefront colors and preset scope migration.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;
use KitchenConfiguratorPro\Support\Helpers;

/**
 * Seeds oak front colors and scopes linked product presets (v1.6.0).
 */
final class Migration_1_6_0 extends AbstractMigration {

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.6.0';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
		self::seed_oak_front_colors();
		self::patch_popular_layout_storefront_meta();
		self::scope_linked_product_presets();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function down(): void {
		// Data-only migration; no rollback.
	}

	/**
	 * Ensure reference oak decor colors exist on the Oak front material.
	 *
	 * @return void
	 */
	private static function seed_oak_front_colors(): void {
		$materials = self::table( 'kcp_materials' );
		$colors    = self::table( 'kcp_colors' );
		$now       = Helpers::now();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$oak_id = (int) self::$wpdb->get_var(
			"SELECT id FROM {$materials} WHERE material_type = 'front' AND slug = 'oak' LIMIT 1"
		);

		if ( $oak_id <= 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
			$oak_id = (int) self::$wpdb->get_var(
				"SELECT id FROM {$materials} WHERE material_type = 'front' ORDER BY id ASC LIMIT 1"
			);
		}

		if ( $oak_id <= 0 ) {
			return;
		}

		$base_url = home_url( '/wp-content/uploads/2024/03/' );
		$rows     = array(
			array(
				'slug'           => 'licht-gerookt-eiken',
				'name'           => 'licht gerookt eiken decor',
				'thumbnail_url'  => $base_url . 'img-03-a.jpg',
				'price_modifier' => '0.00',
				'sort_order'     => 1,
			),
			array(
				'slug'           => 'enkel-gerookt-eiken',
				'name'           => 'enkel gerookt eiken decor',
				'thumbnail_url'  => $base_url . 'img-03-c.jpg',
				'price_modifier' => '0.00',
				'sort_order'     => 2,
			),
			array(
				'slug'           => 'dubbel-gerookt-eiken',
				'name'           => 'dubbel gerookt eiken decor',
				'thumbnail_url'  => $base_url . 'img-02-c.jpg',
				'price_modifier' => '0.00',
				'sort_order'     => 3,
			),
		);

		foreach ( $rows as $row ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
			$existing_id = (int) self::$wpdb->get_var(
				self::$wpdb->prepare(
					"SELECT id FROM {$colors} WHERE slug = %s LIMIT 1",
					$row['slug']
				)
			);

			$data = array(
				'material_id'    => $oak_id,
				'slug'           => $row['slug'],
				'name'           => $row['name'],
				'hex_code'       => '',
				'price_modifier' => $row['price_modifier'],
				'thumbnail_url'  => $row['thumbnail_url'],
				'sort_order'     => $row['sort_order'],
				'is_active'      => 1,
				'updated_at'     => $now,
			);

			if ( $existing_id > 0 ) {
				self::$wpdb->update(
					$colors,
					$data,
					array( 'id' => $existing_id ),
					array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' ),
					array( '%d' )
				);
				continue;
			}

			$data['created_at'] = $now;
			self::$wpdb->insert(
				$colors,
				$data,
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
			);
		}
	}

	/**
	 * Add layout-level storefront defaults for popular layouts.
	 *
	 * @return void
	 */
	private static function patch_popular_layout_storefront_meta(): void {
		$table = self::table( 'kcp_layouts' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$rows = self::$wpdb->get_results( "SELECT id, config_json FROM {$table}", ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$config = json_decode( (string) ( $row['config_json'] ?? '' ), true );

			if ( ! is_array( $config ) || ( $config['type'] ?? '' ) !== 'popular_layout' ) {
				continue;
			}

			$changed = false;

			if ( empty( $config['default_color'] ) ) {
				$config['default_color'] = 'licht-gerookt-eiken';
				$changed                 = true;
			}

			if ( empty( $config['color_notes'] ) || ! is_array( $config['color_notes'] ) ) {
				$config['color_notes'] = array(
					'dubbel-gerookt-eiken' => 'in winkelwagen te personaliseren',
				);
				$changed = true;
			}

			if ( ! $changed ) {
				continue;
			}

			self::$wpdb->update(
				$table,
				array(
					'config_json' => wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '',
					'updated_at'  => Helpers::now(),
				),
				array( 'id' => (int) $row['id'] ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Limit linked presets to Oak front colors when scope is empty.
	 *
	 * @return void
	 */
	private static function scope_linked_product_presets(): void {
		$materials = self::table( 'kcp_materials' );
		$presets   = self::table( 'kcp_product_presets' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$oak_id = (int) self::$wpdb->get_var(
			"SELECT id FROM {$materials} WHERE material_type = 'front' AND slug = 'oak' LIMIT 1"
		);

		if ( $oak_id <= 0 ) {
			return;
		}

		$scope = wp_json_encode(
			array(
				'material_ids' => array( $oak_id ),
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		self::$wpdb->query(
			self::$wpdb->prepare(
				"UPDATE {$presets} SET catalog_scope_json = %s, updated_at = %s WHERE catalog_scope_json IS NULL OR catalog_scope_json = '' OR catalog_scope_json = '[]' OR catalog_scope_json = '{}'",
				$scope,
				Helpers::now()
			)
		);
	}
}
