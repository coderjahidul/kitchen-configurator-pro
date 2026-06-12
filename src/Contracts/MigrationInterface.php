<?php
/**
 * Database migration contract.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Contracts;

/**
 * Defines a versioned database migration.
 */
interface MigrationInterface {

	/**
	 * Apply the migration.
	 *
	 * @return void
	 */
	public static function up(): void;

	/**
	 * Revert the migration.
	 *
	 * @return void
	 */
	public static function down(): void;

	/**
	 * Semantic version for this migration.
	 *
	 * @return string
	 */
	public static function version(): string;
}
