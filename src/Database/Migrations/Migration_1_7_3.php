<?php
/**
 * Cabinet category select-step preview images.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;

/**
 * Adds greep/greeploos preview image URLs to cabinet categories (v1.7.3).
 */
final class Migration_1_7_3 extends AbstractMigration {

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.7.3';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
		$table = self::table( 'kcp_cabinet_categories' );

		if ( ! self::has_column( $table, 'image_url_greep' ) ) {
			self::exec( "ALTER TABLE {$table} ADD COLUMN image_url_greep VARCHAR(500) NULL AFTER description" );
		}

		if ( ! self::has_column( $table, 'image_url_greeploos' ) ) {
			self::exec( "ALTER TABLE {$table} ADD COLUMN image_url_greeploos VARCHAR(500) NULL AFTER image_url_greep" );
		}

		self::seed_default_category_images();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function down(): void {
		$table = self::table( 'kcp_cabinet_categories' );

		if ( self::has_column( $table, 'image_url_greeploos' ) ) {
			self::exec( "ALTER TABLE {$table} DROP COLUMN image_url_greeploos" );
		}

		if ( self::has_column( $table, 'image_url_greep' ) ) {
			self::exec( "ALTER TABLE {$table} DROP COLUMN image_url_greep" );
		}
	}

	/**
	 * Populate bundled preview images for known category slugs.
	 *
	 * @return void
	 */
	private static function seed_default_category_images(): void {
		$table = self::table( 'kcp_cabinet_categories' );
		$base  = KCP_PLUGIN_URL . 'assets/frontend/images/cabinet-select/';
		$map   = array(
			'onderkasten' => array(
				'image_url_greep'      => $base . 'greep-onderkasten.png',
				'image_url_greeploos'  => $base . 'greeploos-onderkasten.png',
			),
			'bovenkasten' => array(
				'image_url_greep'      => $base . 'greep-bovenkasten.png',
				'image_url_greeploos'  => $base . 'greeploos-bovenkasten.png',
			),
			'hoge-kasten' => array(
				'image_url_greep'      => $base . 'greep-hogekast2deuren.png',
				'image_url_greeploos'  => $base . 'greeploos-hogekast2deuren.png',
			),
		);

		foreach ( $map as $slug => $images ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
			$row = self::$wpdb->get_row(
				self::$wpdb->prepare(
					"SELECT id, image_url_greep, image_url_greeploos FROM {$table} WHERE slug = %s LIMIT 1",
					$slug
				),
				ARRAY_A
			);

			if ( ! is_array( $row ) ) {
				continue;
			}

			$update = array();

			if ( '' === (string) ( $row['image_url_greep'] ?? '' ) ) {
				$update['image_url_greep'] = $images['image_url_greep'];
			}

			if ( '' === (string) ( $row['image_url_greeploos'] ?? '' ) ) {
				$update['image_url_greeploos'] = $images['image_url_greeploos'];
			}

			if ( empty( $update ) ) {
				continue;
			}

			self::$wpdb->update(
				$table,
				$update,
				array( 'id' => (int) $row['id'] ),
				array_fill( 0, count( $update ), '%s' ),
				array( '%d' )
			);
		}
	}

	/**
	 * @param string $table Full table name.
	 * @param string $column Column name.
	 */
	private static function has_column( string $table, string $column ): bool {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$columns = self::$wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 );

		return is_array( $columns ) && in_array( $column, $columns, true );
	}
}
