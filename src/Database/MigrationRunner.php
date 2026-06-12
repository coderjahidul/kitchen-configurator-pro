<?php
/**
 * Executes versioned database migrations.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Database;

use KitchenConfiguratorPro\Contracts\MigrationInterface;
use KitchenConfiguratorPro\Support\Helpers;
use wpdb;

/**
 * Runs pending migrations and records execution history.
 */
final class MigrationRunner {

	/**
	 * WordPress database instance.
	 *
	 * @var wpdb
	 */
	private wpdb $wpdb;

	/**
	 * Constructor.
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Run all pending migrations.
	 *
	 * @return array<int, string> Executed migration versions.
	 */
	public function run(): array {
		$this->ensure_migrations_table_exists();

		$executed = array();

		foreach ( Helpers::migration_classes() as $class_name ) {
			if ( ! is_subclass_of( $class_name, MigrationInterface::class ) ) {
				continue;
			}

			/** @var class-string<MigrationInterface&AbstractMigration> $class_name */
			$version = $class_name::version();

			if ( $this->is_executed( $version ) ) {
				continue;
			}

			if ( is_subclass_of( $class_name, AbstractMigration::class ) ) {
				$class_name::set_db( $this->wpdb );
			}

			$class_name::up();
			$this->record_execution( $version, $class_name );
			$executed[] = $version;
		}

		if ( ! empty( $executed ) ) {
			$latest = end( $executed );
			update_option( 'kcp_db_version', $latest, false );
		}

		return $executed;
	}

	/**
	 * Roll back to a specific version (exclusive).
	 *
	 * @param string $target_version Target version to roll back to.
	 * @return array<int, string> Rolled back migration versions.
	 */
	public function rollback( string $target_version ): array {
		$this->ensure_migrations_table_exists();

		$rolled_back = array();
		$classes     = array_reverse( Helpers::migration_classes() );

		foreach ( $classes as $class_name ) {
			if ( ! is_subclass_of( $class_name, MigrationInterface::class ) ) {
				continue;
			}

			$version = $class_name::version();

			if ( version_compare( $version, $target_version, '<=' ) ) {
				break;
			}

			if ( ! $this->is_executed( $version ) ) {
				continue;
			}

			if ( is_subclass_of( $class_name, AbstractMigration::class ) ) {
				$class_name::set_db( $this->wpdb );
			}

			$class_name::down();
			$this->remove_execution( $version );
			$rolled_back[] = $version;
		}

		update_option( 'kcp_db_version', $target_version, false );

		return $rolled_back;
	}

	/**
	 * Get the latest executed migration version.
	 *
	 * @return string|null
	 */
	public function get_latest_executed_version(): ?string {
		$this->ensure_migrations_table_exists();

		$table = Helpers::table_name( 'kcp_migrations' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from trusted helper.
		$version = $this->wpdb->get_var( "SELECT version FROM {$table} ORDER BY executed_at DESC, id DESC LIMIT 1" );

		return is_string( $version ) && '' !== $version ? $version : null;
	}

	/**
	 * Ensure the migrations tracking table exists.
	 *
	 * @return void
	 */
	private function ensure_migrations_table_exists(): void {
		$table = Helpers::table_name( 'kcp_migrations' );

		if ( $this->table_exists( $table ) ) {
			return;
		}

		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			version VARCHAR(20) NOT NULL,
			class_name VARCHAR(191) NOT NULL,
			executed_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uk_version (version)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Check whether a migration version has been executed.
	 *
	 * @param string $version Migration version.
	 * @return bool
	 */
	private function is_executed( string $version ): bool {
		$table = Helpers::table_name( 'kcp_migrations' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from trusted helper.
		$count = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE version = %s",
				$version
			)
		);

		return $count > 0;
	}

	/**
	 * Record a successful migration execution.
	 *
	 * @param string $version    Migration version.
	 * @param string $class_name Migration class name.
	 * @return void
	 */
	private function record_execution( string $version, string $class_name ): void {
		$this->wpdb->insert(
			Helpers::table_name( 'kcp_migrations' ),
			array(
				'version'     => $version,
				'class_name'  => $class_name,
				'executed_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%s' )
		);
	}

	/**
	 * Remove a migration execution record.
	 *
	 * @param string $version Migration version.
	 * @return void
	 */
	private function remove_execution( string $version ): void {
		$this->wpdb->delete(
			Helpers::table_name( 'kcp_migrations' ),
			array( 'version' => $version ),
			array( '%s' )
		);
	}

	/**
	 * Check if a database table exists.
	 *
	 * @param string $table Full table name.
	 * @return bool
	 */
	private function table_exists( string $table ): bool {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from trusted helper.
		$result = $this->wpdb->get_var( $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		return $result === $table;
	}
}
