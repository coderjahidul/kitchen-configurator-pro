<?php
/**
 * Configuration repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\Configuration;

/**
 * @extends AbstractRepository<Configuration>
 */
final class ConfigurationRepository extends AbstractRepository {

	/**
	 * Allowed sort columns.
	 *
	 * @var array<int, string>
	 */
	protected array $orderable_columns = array( 'id', 'title', 'status', 'total_price', 'created_at', 'updated_at' );

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_configurations';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): Configuration {
		return Configuration::from_row( $row );
	}

	/**
	 * Find configuration by UUID.
	 *
	 * @param string $uuid Configuration UUID.
	 * @return Configuration|null
	 */
	public function find_by_uuid( string $uuid ): ?Configuration {
		$table = $this->table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$row = $this->db->get_row(
			$this->db->prepare( "SELECT * FROM {$table} WHERE uuid = %s", sanitize_text_field( $uuid ) ),
			ARRAY_A
		);

		return is_array( $row ) ? $this->map_row( $row ) : null;
	}

	/**
	 * Find configurations for a logged-in user.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $limit   Max rows.
	 * @param int    $offset  Offset.
	 * @return array<int, Configuration>
	 */
	public function find_by_user( int $user_id, int $limit = 20, int $offset = 0 ): array {
		$table = $this->table_name();
		$limit = max( 1, min( 100, $limit ) );
		$offset = max( 0, $offset );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC, id DESC LIMIT %d OFFSET %d",
				$user_id,
				$limit,
				$offset
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( array( $this, 'map_row' ), $rows );
	}

	/**
	 * Find configurations for a guest session.
	 *
	 * @param string $session_id Session ID.
	 * @param int    $limit      Max rows.
	 * @param int    $offset     Offset.
	 * @return array<int, Configuration>
	 */
	public function find_by_session( string $session_id, int $limit = 20, int $offset = 0 ): array {
		$table = $this->table_name();
		$limit = max( 1, min( 100, $limit ) );
		$offset = max( 0, $offset );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$table} WHERE session_id = %s AND user_id IS NULL ORDER BY updated_at DESC, id DESC LIMIT %d OFFSET %d",
				sanitize_text_field( $session_id ),
				$limit,
				$offset
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( array( $this, 'map_row' ), $rows );
	}

	/**
	 * Count configurations for a user or session.
	 *
	 * @param int|null    $user_id    User ID.
	 * @param string|null $session_id Session ID.
	 * @return int
	 */
	public function count_for_owner( ?int $user_id, ?string $session_id = null ): int {
		if ( null !== $user_id && $user_id > 0 ) {
			return $this->count( array( 'user_id' => (string) $user_id ) );
		}

		if ( null !== $session_id && '' !== $session_id ) {
			return $this->count(
				array(
					'session_id' => $session_id,
					'user_id'    => null,
				)
			);
		}

		return 0;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		return array(
			'uuid'                  => sanitize_text_field( (string) ( $data['uuid'] ?? '' ) ),
			'project_id'            => isset( $data['project_id'] ) ? (int) $data['project_id'] : null,
			'layout_id'             => (int) ( $data['layout_id'] ?? 0 ),
			'user_id'               => isset( $data['user_id'] ) ? (int) $data['user_id'] : null,
			'session_id'            => isset( $data['session_id'] ) ? sanitize_text_field( (string) $data['session_id'] ) : null,
			'title'                 => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'configuration_json'    => (string) ( $data['configuration_json'] ?? '{}' ),
			'pricing_snapshot_json' => (string) ( $data['pricing_snapshot_json'] ?? '' ),
			'total_price'           => number_format( (float) ( $data['total_price'] ?? 0 ), 2, '.', '' ),
			'price_hash'            => isset( $data['price_hash'] ) ? sanitize_text_field( (string) $data['price_hash'] ) : null,
			'status'                => sanitize_key( (string) ( $data['status'] ?? 'draft' ) ),
			'wc_order_id'           => isset( $data['wc_order_id'] ) ? (int) $data['wc_order_id'] : null,
			'wc_cart_item_key'      => isset( $data['wc_cart_item_key'] ) ? sanitize_text_field( (string) $data['wc_cart_item_key'] ) : null,
			'quoted_at'             => isset( $data['quoted_at'] ) ? sanitize_text_field( (string) $data['quoted_at'] ) : null,
		);
	}
}
