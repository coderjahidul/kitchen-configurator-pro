<?php
/**
 * Cabinet repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\Cabinet;

/**
 * @extends AbstractRepository<Cabinet>
 */
final class CabinetRepository extends AbstractRepository {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_cabinets';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): Cabinet {
		return Cabinet::from_row( $row );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		$sanitized = array(
			'category_id'          => (int) ( $data['category_id'] ?? 0 ),
			'slug'                 => $this->resolve_slug( $data ),
			'name'                 => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'description'          => wp_kses_post( (string) ( $data['description'] ?? '' ) ),
			'sku_prefix'           => sanitize_text_field( (string) ( $data['sku_prefix'] ?? '' ) ),
			'default_width'        => max( 0, (int) ( $data['default_width'] ?? 0 ) ),
			'default_height'       => max( 0, (int) ( $data['default_height'] ?? 0 ) ),
			'default_depth'        => max( 0, (int) ( $data['default_depth'] ?? 0 ) ),
			'min_width'            => max( 0, (int) ( $data['min_width'] ?? 0 ) ),
			'max_width'            => max( 0, (int) ( $data['max_width'] ?? 0 ) ),
			'min_height'           => max( 0, (int) ( $data['min_height'] ?? 0 ) ),
			'max_height'           => max( 0, (int) ( $data['max_height'] ?? 0 ) ),
			'min_depth'            => max( 0, (int) ( $data['min_depth'] ?? 0 ) ),
			'max_depth'            => max( 0, (int) ( $data['max_depth'] ?? 0 ) ),
			'width_step'           => max( 1, (int) ( $data['width_step'] ?? 10 ) ),
			'height_step'          => max( 1, (int) ( $data['height_step'] ?? 10 ) ),
			'depth_step'           => max( 1, (int) ( $data['depth_step'] ?? 10 ) ),
			'base_price'           => $this->sanitize_decimal( $data['base_price'] ?? '0' ),
			'dimension_price_json' => $this->sanitize_json( (string) ( $data['dimension_price_json'] ?? '' ) ),
			'image_url'            => esc_url_raw( (string) ( $data['image_url'] ?? '' ) ),
			'sort_order'           => (int) ( $data['sort_order'] ?? 0 ),
			'is_active'            => $this->to_bool_int( $data['is_active'] ?? 1 ),
		);

		return $sanitized;
	}

	/**
	 * Sanitize decimal value.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	private function sanitize_decimal( mixed $value ): string {
		return number_format( (float) $value, 2, '.', '' );
	}

	/**
	 * Sanitize JSON field.
	 *
	 * @param string $json Raw JSON.
	 * @return string
	 */
	private function sanitize_json( string $json ): string {
		$json = trim( $json );

		if ( '' === $json ) {
			return '';
		}

		json_decode( $json );

		return JSON_ERROR_NONE === json_last_error() ? $json : '';
	}
}
