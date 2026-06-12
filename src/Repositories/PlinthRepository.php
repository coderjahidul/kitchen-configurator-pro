<?php
/**
 * Plinth repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\Plinth;

/**
 * @extends AbstractRepository<Plinth>
 */
final class PlinthRepository extends AbstractRepository {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_plinths';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): Plinth {
		return Plinth::from_row( $row );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		return array(
			'slug'                   => $this->resolve_slug( $data ),
			'name'                   => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'description'            => wp_kses_post( (string) ( $data['description'] ?? '' ) ),
			'default_height'         => max( 0, (int) ( $data['default_height'] ?? 150 ) ),
			'min_height'             => max( 0, (int) ( $data['min_height'] ?? 100 ) ),
			'max_height'             => max( 0, (int) ( $data['max_height'] ?? 200 ) ),
			'height_step'            => max( 1, (int) ( $data['height_step'] ?? 10 ) ),
			'default_length'         => max( 0, (int) ( $data['default_length'] ?? 3000 ) ),
			'min_length'             => max( 0, (int) ( $data['min_length'] ?? 600 ) ),
			'max_length'             => max( 0, (int) ( $data['max_length'] ?? 10000 ) ),
			'length_step'            => max( 1, (int) ( $data['length_step'] ?? 10 ) ),
			'base_price'             => $this->sanitize_decimal( $data['base_price'] ?? '0' ),
			'price_per_linear_meter' => number_format( (float) ( $data['price_per_linear_meter'] ?? 0 ), 4, '.', '' ),
			'thumbnail_url'          => esc_url_raw( (string) ( $data['thumbnail_url'] ?? '' ) ),
			'sort_order'             => (int) ( $data['sort_order'] ?? 0 ),
			'is_active'              => $this->to_bool_int( $data['is_active'] ?? 1 ),
		);
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
}
