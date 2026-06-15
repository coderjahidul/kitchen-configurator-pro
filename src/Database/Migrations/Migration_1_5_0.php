<?php
/**
 * Layout storefront config migration.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;
use KitchenConfiguratorPro\Support\Helpers;

/**
 * Adds storefront includes/heights to popular layout configs (v1.5.0).
 */
final class Migration_1_5_0 extends AbstractMigration {

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.5.0';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
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
			$height  = isset( $config['height'] ) ? (float) $config['height'] : 0.0;

			if ( empty( $config['includes'] ) && ! empty( $config['storefront_includes'] ) ) {
				$config['includes'] = $config['storefront_includes'];
				$changed            = true;
			}

			if ( empty( $config['includes'] ) ) {
				$config['includes'] = array(
					'1x oven 60 cm hoog',
					'1x koelkast 178 cm hoog',
				);
				$changed = true;
			}

			if ( empty( $config['heights'] ) && $height > 0 ) {
				$config['heights'] = array(
					array(
						'value'          => $height,
						'price_modifier' => 0,
					),
					array(
						'value'          => round( $height + 12.9, 1 ),
						'price_modifier' => 195,
					),
					array(
						'value'          => round( $height + 25.9, 1 ),
						'price_modifier' => 314,
					),
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
	 * {@inheritDoc}
	 */
	public static function down(): void {
		// Data-only migration; no rollback.
	}
}
