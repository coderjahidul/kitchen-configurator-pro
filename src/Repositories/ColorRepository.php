<?php
/**
 * Color repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\Color;

/**
 * @extends AbstractRepository<Color>
 */
final class ColorRepository extends AbstractRepository {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_colors';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): Color {
		return Color::from_row( $row );
	}

	/**
	 * Find colors by material ID.
	 *
	 * @param int $material_id Material ID.
	 * @return array<int, Color>
	 */
	public function find_by_material( int $material_id ): array {
		return $this->find_all( array( 'material_id' => (string) $material_id ) );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		$hex = strtoupper( sanitize_text_field( (string) ( $data['hex_code'] ?? '' ) ) );

		if ( '' !== $hex && ! preg_match( '/^#[0-9A-F]{6}$/', $hex ) ) {
			$hex = '';
		}

		return array(
			'material_id'    => (int) ( $data['material_id'] ?? 0 ),
			'slug'           => $this->resolve_slug( $data ),
			'name'           => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'hex_code'       => $hex,
			'price_modifier' => number_format( (float) ( $data['price_modifier'] ?? 0 ), 2, '.', '' ),
			'thumbnail_url'  => esc_url_raw( (string) ( $data['thumbnail_url'] ?? '' ) ),
			'sort_order'     => (int) ( $data['sort_order'] ?? 0 ),
			'is_active'      => $this->to_bool_int( $data['is_active'] ?? 1 ),
		);
	}
}
