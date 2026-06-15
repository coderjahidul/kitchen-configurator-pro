<?php
/**
 * Product preset table migration.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database\Migrations;

use KitchenConfiguratorPro\Database\AbstractMigration;

/**
 * Adds kcp_product_presets for WooCommerce product configurator mapping (v1.4.0).
 */
final class Migration_1_4_0 extends AbstractMigration {

	/**
	 * {@inheritDoc}
	 */
	public static function version(): string {
		return '1.4.0';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function up(): void {
		$table  = self::table( 'kcp_product_presets' );
		$layout = self::table( 'kcp_layouts' );

		self::exec(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				wc_product_id BIGINT UNSIGNED NOT NULL,
				layout_id BIGINT UNSIGNED NOT NULL,
				name VARCHAR(191) NOT NULL,
				configuration_json LONGTEXT NOT NULL,
				catalog_scope_json LONGTEXT NULL,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uk_wc_product (wc_product_id),
				KEY idx_layout_active (layout_id, is_active),
				CONSTRAINT fk_product_presets_layout
					FOREIGN KEY (layout_id) REFERENCES {$layout} (id)
					ON DELETE RESTRICT ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function down(): void {
		self::exec( 'DROP TABLE IF EXISTS ' . self::table( 'kcp_product_presets' ) );
	}
}
