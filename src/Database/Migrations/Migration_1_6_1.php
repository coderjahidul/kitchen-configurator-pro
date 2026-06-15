<?php
/**
 * Deactivate legacy Oak colors after storefront seed migration.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;
use KitchenConfiguratorPro\Support\Helpers;

/**
 * Keeps only reference oak decor colors active (v1.6.1).
 */
final class Migration_1_6_1 extends AbstractMigration {

	/**
	 * Reference oak color slugs shown on the single product page.
	 *
	 * @var array<int, string>
	 */
	private const OAK_COLOR_SLUGS = array(
		'licht-gerookt-eiken',
		'enkel-gerookt-eiken',
		'dubbel-gerookt-eiken',
	);

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.6.1';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
		$materials = self::table( 'kcp_materials' );
		$colors    = self::table( 'kcp_colors' );
		$now       = Helpers::now();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$oak_id = (int) self::$wpdb->get_var(
			"SELECT id FROM {$materials} WHERE material_type = 'front' AND slug = 'oak' LIMIT 1"
		);

		if ( $oak_id <= 0 ) {
			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( self::OAK_COLOR_SLUGS ), '%s' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built safely.
		self::$wpdb->query(
			self::$wpdb->prepare(
				"UPDATE {$colors} SET is_active = 0, updated_at = %s WHERE material_id = %d AND slug NOT IN ({$placeholders})",
				$now,
				$oak_id,
				...self::OAK_COLOR_SLUGS
			)
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function down(): void {
		// Data-only migration; no rollback.
	}
}
