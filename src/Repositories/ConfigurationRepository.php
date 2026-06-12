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
