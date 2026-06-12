<?php
/**
 * Repository contract.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Contracts;

/**
 * Defines standard persistence operations for catalog entities.
 *
 * @template T
 */
interface RepositoryInterface {

	/**
	 * Find a record by primary key.
	 *
	 * @param int $id Record ID.
	 * @return mixed|null
	 */
	public function find( int $id ): mixed;

	/**
	 * Find a record by slug.
	 *
	 * @param string $slug Entity slug.
	 * @return mixed|null
	 */
	public function find_by_slug( string $slug ): mixed;

	/**
	 * Find all records matching criteria.
	 *
	 * @param array<string, mixed> $criteria Search criteria.
	 * @param string               $order_by ORDER BY clause (sanitized internally).
	 * @param string               $order    ASC or DESC.
	 * @return array<int, mixed>
	 */
	public function find_all( array $criteria = array(), string $order_by = 'sort_order', string $order = 'ASC' ): array;

	/**
	 * Count records matching criteria.
	 *
	 * @param array<string, mixed> $criteria Search criteria.
	 * @return int
	 */
	public function count( array $criteria = array() ): int;

	/**
	 * Create a new record.
	 *
	 * @param array<string, mixed> $data Record data.
	 * @return mixed
	 */
	public function create( array $data ): mixed;

	/**
	 * Update an existing record.
	 *
	 * @param int                  $id   Record ID.
	 * @param array<string, mixed> $data Record data.
	 * @return mixed
	 */
	public function update( int $id, array $data ): mixed;

	/**
	 * Delete a record.
	 *
	 * @param int $id Record ID.
	 * @return bool
	 */
	public function delete( int $id ): bool;
}
