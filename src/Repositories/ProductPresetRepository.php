<?php
/**
 * Product preset repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\ProductPreset;

/**
 * @extends AbstractRepository<ProductPreset>
 */
final class ProductPresetRepository extends AbstractRepository {

	/**
	 * Allowed ORDER BY columns.
	 *
	 * @var array<int, string>
	 */
	protected array $orderable_columns = array( 'id', 'name', 'wc_product_id', 'layout_id', 'created_at', 'updated_at' );

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_product_presets';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): ProductPreset {
		return ProductPreset::from_row( $row );
	}

	/**
	 * {@inheritDoc}
	 */
	public function find_all( array $criteria = array(), string $order_by = 'id', string $order = 'DESC' ): array {
		return parent::find_all( $criteria, $order_by, $order );
	}

	/**
	 * Find preset by WooCommerce product ID.
	 *
	 * @param int $wc_product_id WooCommerce product ID.
	 * @return ProductPreset|null
	 */
	public function find_by_wc_product_id( int $wc_product_id ): ?ProductPreset {
		$table = $this->table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$row = $this->db->get_row(
			$this->db->prepare( "SELECT * FROM {$table} WHERE wc_product_id = %d", $wc_product_id ),
			ARRAY_A
		);

		return is_array( $row ) ? $this->map_row( $row ) : null;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		$layout_id     = (int) ( $data['layout_id'] ?? 0 );
		$config        = $this->sanitize_configuration_json( (string) ( $data['configuration_json'] ?? '{}' ), $layout_id );
		$scope         = $this->sanitize_json_field( (string) ( $data['catalog_scope_json'] ?? '' ) );
		$wc_product_id = (int) ( $data['wc_product_id'] ?? 0 );
		$name          = sanitize_text_field( (string) ( $data['name'] ?? '' ) );

		if ( '' === $name && $wc_product_id > 0 ) {
			$product = wc_get_product( $wc_product_id );
			$name    = $product ? sanitize_text_field( $product->get_name() ) : '';
		}

		return array(
			'wc_product_id'      => $wc_product_id,
			'layout_id'          => $layout_id,
			'name'               => $name,
			'configuration_json' => $config,
			'catalog_scope_json' => $scope,
			'is_active'          => $this->to_bool_int( $data['is_active'] ?? 1 ),
		);
	}

	/**
	 * Validate and normalize configuration JSON.
	 *
	 * @param string $json      Raw JSON.
	 * @param int    $layout_id Layout ID to enforce in config.
	 * @return string
	 */
	private function sanitize_configuration_json( string $json, int $layout_id ): string {
		$config = json_decode( trim( $json ), true );

		if ( ! is_array( $config ) ) {
			$config = array();
		}

		$config['schema_version'] = (string) ( $config['schema_version'] ?? '1.0' );
		$config['layout_id']      = $layout_id;
		$config['title']          = sanitize_text_field( (string) ( $config['title'] ?? '' ) );
		$config['cabinets']       = is_array( $config['cabinets'] ?? null ) ? $config['cabinets'] : array();
		$config['global_options'] = is_array( $config['global_options'] ?? null ) ? $config['global_options'] : array();

		if ( isset( $config['product_options'] ) && is_array( $config['product_options'] ) ) {
			$config['product_options'] = $config['product_options'];
		} else {
			unset( $config['product_options'] );
		}

		return wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '{}';
	}

	/**
	 * Sanitize optional JSON field.
	 *
	 * @param string $json Raw JSON.
	 * @return string
	 */
	private function sanitize_json_field( string $json ): string {
		$json = trim( $json );

		if ( '' === $json ) {
			return '';
		}

		json_decode( $json );

		return JSON_ERROR_NONE === json_last_error() ? $json : '';
	}
}
