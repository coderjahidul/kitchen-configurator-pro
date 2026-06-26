<?php
/**
 * Cabinet parent-child relation repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\Cabinet;
use KitchenConfiguratorPro\Support\Helpers;
use wpdb;

/**
 * Manages parent-child cabinet relationships.
 */
final class CabinetRelationRepository {

	/**
	 * WordPress database instance.
	 *
	 * @var wpdb
	 */
	private wpdb $db;

	/**
	 * Constructor.
	 *
	 * @param wpdb $db WordPress database object.
	 */
	public function __construct( wpdb $db ) {
		$this->db = $db;
	}

	/**
	 * Get child cabinet IDs for a parent.
	 *
	 * @param int $parent_id Parent cabinet ID.
	 * @return array<int, int>
	 */
	public function get_child_ids( int $parent_id ): array {
		if ( $parent_id <= 0 ) {
			return array();
		}

		$table = $this->table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$rows = $this->db->get_col(
			$this->db->prepare(
				"SELECT child_cabinet_id FROM {$table} WHERE parent_cabinet_id = %d ORDER BY id ASC",
				$parent_id
			)
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( 'intval', $rows );
	}

	/**
	 * Check whether a cabinet has assigned child cabinets.
	 *
	 * @param int $parent_id Parent cabinet ID.
	 * @return bool
	 */
	public function has_children( int $parent_id ): bool {
		if ( $parent_id <= 0 ) {
			return false;
		}

		$table = $this->table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$count = (int) $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE parent_cabinet_id = %d",
				$parent_id
			)
		);

		return $count > 0;
	}

	/**
	 * Load active child cabinets for a parent, preserving relation order.
	 *
	 * @param int $parent_id Parent cabinet ID.
	 * @return array<int, Cabinet>
	 */
	public function get_children( int $parent_id ): array {
		$child_ids = $this->get_child_ids( $parent_id );

		if ( empty( $child_ids ) ) {
			return array();
		}

		$cabinets_table = Helpers::table_name( 'kcp_cabinets' );
		$relations_table = $this->table_name();
		$placeholders    = implode( ',', array_fill( 0, count( $child_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- dynamic IN list.
		$sql = $this->db->prepare(
			"SELECT c.* FROM {$cabinets_table} c
			INNER JOIN {$relations_table} r ON r.child_cabinet_id = c.id
			WHERE r.parent_cabinet_id = %d
			AND c.is_active = 1
			ORDER BY r.id ASC",
			$parent_id
		);

		$rows = $this->db->get_results( $sql, ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static fn ( array $row ): Cabinet => Cabinet::from_row( $row ),
			$rows
		);
	}

	/**
	 * Load all child cabinets for a parent, including inactive rows.
	 *
	 * @param int $parent_id Parent cabinet ID.
	 * @return array<int, Cabinet>
	 */
	public function get_children_for_admin( int $parent_id ): array {
		if ( $parent_id <= 0 ) {
			return array();
		}

		$cabinets_table  = Helpers::table_name( 'kcp_cabinets' );
		$relations_table = $this->table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names from trusted helper.
		$sql = $this->db->prepare(
			"SELECT c.* FROM {$cabinets_table} c
			INNER JOIN {$relations_table} r ON r.child_cabinet_id = c.id
			WHERE r.parent_cabinet_id = %d
			ORDER BY r.id ASC",
			$parent_id
		);

		$rows = $this->db->get_results( $sql, ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static fn ( array $row ): Cabinet => Cabinet::from_row( $row ),
			$rows
		);
	}

	/**
	 * Get all cabinet IDs that are assigned as children.
	 *
	 * @return array<int, int>
	 */
	public function get_all_child_cabinet_ids(): array {
		$table = $this->table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$rows = $this->db->get_col( "SELECT DISTINCT child_cabinet_id FROM {$table} ORDER BY child_cabinet_id ASC" );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( 'intval', $rows );
	}

	/**
	 * Replace all child relations for a parent cabinet.
	 *
	 * @param int           $parent_id Parent cabinet ID.
	 * @param array<int>    $child_ids Child cabinet IDs.
	 * @return void
	 */
	public function sync_children( int $parent_id, array $child_ids ): void {
		if ( $parent_id <= 0 ) {
			return;
		}

		$child_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'intval', $child_ids ),
					static fn ( int $id ): bool => $id > 0 && $id !== $parent_id
				)
			)
		);

		$table = $this->table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->db->delete(
			$table,
			array( 'parent_cabinet_id' => $parent_id ),
			array( '%d' )
		);

		foreach ( $child_ids as $child_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->db->insert(
				$table,
				array(
					'parent_cabinet_id' => $parent_id,
					'child_cabinet_id'  => $child_id,
				),
				array( '%d', '%d' )
			);
		}
	}

	/**
	 * Get the parent cabinet ID for a child cabinet.
	 *
	 * @param int $child_id Child cabinet ID.
	 * @return int Parent cabinet ID or 0 when none.
	 */
	public function get_parent_id( int $child_id ): int {
		if ( $child_id <= 0 ) {
			return 0;
		}

		$table = $this->table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$parent_id = (int) $this->db->get_var(
			$this->db->prepare(
				"SELECT parent_cabinet_id FROM {$table} WHERE child_cabinet_id = %d LIMIT 1",
				$child_id
			)
		);

		return max( 0, $parent_id );
	}

	/**
	 * Remove all relations involving a cabinet.
	 *
	 * @param int $cabinet_id Cabinet ID.
	 * @return void
	 */
	public function delete_by_cabinet( int $cabinet_id ): void {
		if ( $cabinet_id <= 0 ) {
			return;
		}

		$table = $this->table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->db->query(
			$this->db->prepare(
				"DELETE FROM {$table} WHERE parent_cabinet_id = %d OR child_cabinet_id = %d",
				$cabinet_id,
				$cabinet_id
			)
		);
	}

	/**
	 * Search cabinets for admin AJAX multi-select.
	 *
	 * @param string $query      Search term.
	 * @param int    $exclude_id Cabinet ID to exclude (typically the parent being edited).
	 * @param int    $limit      Maximum results.
	 * @return array<int, array{id: int, name: string, slug: string, category: string}>
	 */
	public function search_cabinets( string $query, int $exclude_id = 0, int $limit = 20 ): array {
		$query = trim( $query );
		$limit = max( 1, min( 100, $limit ) );

		$cabinets_table   = Helpers::table_name( 'kcp_cabinets' );
		$categories_table = Helpers::table_name( 'kcp_cabinet_categories' );

		$like = '%' . $this->db->esc_like( $query ) . '%';

		if ( '' === $query ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names from trusted helper.
			$sql = $this->db->prepare(
				"SELECT c.id, c.name, c.slug, cat.name AS category_name
				FROM {$cabinets_table} c
				LEFT JOIN {$categories_table} cat ON cat.id = c.category_id
				WHERE c.id != %d
				ORDER BY c.name ASC
				LIMIT %d",
				$exclude_id,
				$limit
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names from trusted helper.
			$sql = $this->db->prepare(
				"SELECT c.id, c.name, c.slug, cat.name AS category_name
				FROM {$cabinets_table} c
				LEFT JOIN {$categories_table} cat ON cat.id = c.category_id
				WHERE c.id != %d
				AND (c.name LIKE %s OR c.slug LIKE %s)
				ORDER BY c.name ASC
				LIMIT %d",
				$exclude_id,
				$like,
				$like,
				$limit
			);
		}

		$rows = $this->db->get_results( $sql, ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$results = array();

		foreach ( $rows as $row ) {
			$results[] = array(
				'id'       => (int) ( $row['id'] ?? 0 ),
				'name'     => (string) ( $row['name'] ?? '' ),
				'slug'     => (string) ( $row['slug'] ?? '' ),
				'category' => (string) ( $row['category_name'] ?? '' ),
			);
		}

		return $results;
	}

	/**
	 * Get full table name.
	 *
	 * @return string
	 */
	private function table_name(): string {
		return Helpers::table_name( 'kcp_cabinet_relations' );
	}
}
