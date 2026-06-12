<?php
/**
 * Public facade for database migrations.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database;

use wpdb;

/**
 * Facade wrapping MigrationRunner for container injection.
 */
final class Migrator {

	/**
	 * Migration runner instance.
	 *
	 * @var MigrationRunner
	 */
	private MigrationRunner $runner;

	/**
	 * Constructor.
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->runner = new MigrationRunner( $wpdb );
	}

	/**
	 * Run pending migrations.
	 *
	 * @return array<int, string>
	 */
	public function run(): array {
		return $this->runner->run();
	}

	/**
	 * Roll back migrations above the target version.
	 *
	 * @param string $target_version Version to roll back to.
	 * @return array<int, string>
	 */
	public function rollback( string $target_version ): array {
		return $this->runner->rollback( $target_version );
	}

	/**
	 * Get latest executed migration version.
	 *
	 * @return string|null
	 */
	public function get_latest_version(): ?string {
		return $this->runner->get_latest_executed_version();
	}
}
