<?php
/**
 * Base repository implementation.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Contracts\RepositoryInterface;
use KitchenConfiguratorPro\Support\Helpers;
use wpdb;

/**
 * Shared CRUD operations using $wpdb.
 *
 * @implements RepositoryInterface<mixed>
 */
abstract class AbstractRepository implements RepositoryInterface {

	/**
	 * WordPress database instance.
	 *
	 * @var wpdb
	 */
	protected wpdb $db;

	/**
	 * Table suffix without prefix (e.g. kcp_layouts).
	 *
	 * @var string
	 */
	protected string $table_suffix;

	/**
	 * Allowed ORDER BY columns.
	 *
	 * @var array<int, string>
	 */
	protected array $orderable_columns = array( 'id', 'name', 'slug', 'sort_order', 'created_at', 'updated_at' );

	/**
	 * Constructor.
	 *
	 * @param wpdb $db WordPress database object.
	 */
	public function __construct( wpdb $db ) {
		$this->db           = $db;
		$this->table_suffix = $this->table();
	}

	/**
	 * Table suffix without prefix.
	 *
	 * @return string
	 */
	abstract protected function table(): string;

	/**
	 * Map a database row to an entity or array.
	 *
	 * @param array<string, mixed> $row Database row.
	 * @return mixed
	 */
	abstract protected function map_row( array $row ): mixed;

	/**
	 * Sanitize data before insert/update.
	 *
	 * @param array<string, mixed> $data Raw input.
	 * @return array<string, mixed>
	 */
	abstract protected function sanitize( array $data ): array;

	/**
	 * {@inheritDoc}
	 */
	public function find( int $id ): mixed {
		$table = $this->table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$row = $this->db->get_row(
			$this->db->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);

		return is_array( $row ) ? $this->map_row( $row ) : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function find_by_slug( string $slug ): mixed {
		$table = $this->table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$row = $this->db->get_row(
			$this->db->prepare( "SELECT * FROM {$table} WHERE slug = %s", sanitize_title( $slug ) ),
			ARRAY_A
		);

		return is_array( $row ) ? $this->map_row( $row ) : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function find_all( array $criteria = array(), string $order_by = 'sort_order', string $order = 'ASC' ): array {
		$table = $this->table_name();
		$where = $this->build_where_clause( $criteria );
		$order = $this->sanitize_order( $order_by, $order );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- dynamic parts sanitized.
		$sql = "SELECT * FROM {$table}{$where['sql']}{$order}";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared via build_where_clause.
		$rows = empty( $where['values'] )
			? $this->db->get_results( $sql, ARRAY_A )
			: $this->db->get_results( $this->db->prepare( $sql, $where['values'] ), ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( array( $this, 'map_row' ), $rows );
	}

	/**
	 * {@inheritDoc}
	 */
	public function count( array $criteria = array() ): int {
		$table = $this->table_name();
		$where = $this->build_where_clause( $criteria );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- dynamic parts sanitized.
		$sql = "SELECT COUNT(*) FROM {$table}{$where['sql']}";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared via build_where_clause.
		$count = empty( $where['values'] )
			? $this->db->get_var( $sql )
			: $this->db->get_var( $this->db->prepare( $sql, $where['values'] ) );

		return (int) $count;
	}

	/**
	 * {@inheritDoc}
	 */
	public function create( array $data ): mixed {
		$data = $this->sanitize( $data );

		if ( empty( $data['created_at'] ) ) {
			$data['created_at'] = Helpers::now();
		}

		if ( empty( $data['updated_at'] ) ) {
			$data['updated_at'] = Helpers::now();
		}

		$result = $this->db->insert( $this->table_name(), $data );

		if ( false === $result ) {
			return null;
		}

		return $this->find( (int) $this->db->insert_id );
	}

	/**
	 * {@inheritDoc}
	 */
	public function update( int $id, array $data ): mixed {
		$data = $this->sanitize( $data );
		unset( $data['id'], $data['created_at'] );
		$data['updated_at'] = Helpers::now();

		$result = $this->db->update(
			$this->table_name(),
			$data,
			array( 'id' => $id )
		);

		if ( false === $result ) {
			return null;
		}

		return $this->find( $id );
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete( int $id ): bool {
		$result = $this->db->delete(
			$this->table_name(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Get full table name.
	 *
	 * @return string
	 */
	protected function table_name(): string {
		return Helpers::table_name( $this->table_suffix );
	}

	/**
	 * Build WHERE clause from criteria.
	 *
	 * @param array<string, mixed> $criteria Search criteria.
	 * @return array{sql: string, values: array<int, mixed>}
	 */
	protected function build_where_clause( array $criteria ): array {
		if ( empty( $criteria ) ) {
			return array(
				'sql'    => '',
				'values' => array(),
			);
		}

		$parts  = array();
		$values = array();

		foreach ( $criteria as $column => $value ) {
			if ( ! $this->is_allowed_column( $column ) ) {
				continue;
			}

			if ( null === $value ) {
				$parts[] = "`{$column}` IS NULL";
				continue;
			}

			$parts[]  = "`{$column}` = %s";
			$values[] = $value;
		}

		return array(
			'sql'    => ' WHERE ' . implode( ' AND ', $parts ),
			'values' => $values,
		);
	}

	/**
	 * Sanitize ORDER BY clause.
	 *
	 * @param string $order_by Column name.
	 * @param string $order    Sort direction.
	 * @return string
	 */
	protected function sanitize_order( string $order_by, string $order ): string {
		if ( ! in_array( $order_by, $this->orderable_columns, true ) ) {
			$order_by = 'sort_order';
		}

		$order = strtoupper( $order ) === 'DESC' ? 'DESC' : 'ASC';

		return " ORDER BY `{$order_by}` {$order}, `id` ASC";
	}

	/**
	 * Check if a column is allowed in queries.
	 *
	 * @param string $column Column name.
	 * @return bool
	 */
	protected function is_allowed_column( string $column ): bool {
		return (bool) preg_match( '/^[a-z0-9_]+$/', $column );
	}

	/**
	 * Sanitize slug from input or name.
	 *
	 * @param array<string, mixed> $data Input data.
	 * @return string
	 */
	protected function resolve_slug( array $data ): string {
		$slug = isset( $data['slug'] ) ? sanitize_title( (string) $data['slug'] ) : '';

		if ( '' === $slug && isset( $data['name'] ) ) {
			$slug = sanitize_title( (string) $data['name'] );
		}

		return $slug;
	}

	/**
	 * Cast value to boolean for storage.
	 *
	 * @param mixed $value Input value.
	 * @return int
	 */
	protected function to_bool_int( mixed $value ): int {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ? 1 : 0;
	}
}
