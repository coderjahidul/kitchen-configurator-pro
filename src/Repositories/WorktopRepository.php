<?php
/**
 * Worktop repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\Worktop;

/**
 * @extends AbstractRepository<Worktop>
 */
final class WorktopRepository extends AbstractRepository {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_worktops';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): Worktop {
		return Worktop::from_row( $row );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		return array(
			'slug'                   => $this->resolve_slug( $data ),
			'name'                   => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'description'            => wp_kses_post( (string) ( $data['description'] ?? '' ) ),
			'default_length'         => max( 0, (int) ( $data['default_length'] ?? 3000 ) ),
			'default_depth'          => max( 0, (int) ( $data['default_depth'] ?? 600 ) ),
			'min_length'             => max( 0, (int) ( $data['min_length'] ?? 600 ) ),
			'max_length'             => max( 0, (int) ( $data['max_length'] ?? 5000 ) ),
			'min_depth'              => max( 0, (int) ( $data['min_depth'] ?? 400 ) ),
			'max_depth'              => max( 0, (int) ( $data['max_depth'] ?? 1200 ) ),
			'length_step'            => max( 1, (int) ( $data['length_step'] ?? 10 ) ),
			'depth_step'             => max( 1, (int) ( $data['depth_step'] ?? 10 ) ),
			'base_price'             => $this->sanitize_decimal( $data['base_price'] ?? '0' ),
			'price_per_sqm'          => $this->sanitize_optional_decimal( $data['price_per_sqm'] ?? null, 4 ),
			'price_per_linear_meter' => $this->sanitize_optional_decimal( $data['price_per_linear_meter'] ?? null, 4 ),
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

	/**
	 * Sanitize optional decimal value.
	 *
	 * @param mixed $value Input value.
	 * @param int   $scale Decimal places.
	 * @return string|null
	 */
	private function sanitize_optional_decimal( mixed $value, int $scale ): ?string {
		if ( null === $value || '' === (string) $value ) {
			return null;
		}

		return number_format( (float) $value, $scale, '.', '' );
	}
}
