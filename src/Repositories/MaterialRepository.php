<?php
/**
 * Material repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\Material;
use KitchenConfiguratorPro\Domain\Enums\MaterialType;

/**
 * @extends AbstractRepository<Material>
 */
final class MaterialRepository extends AbstractRepository {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_materials';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): Material {
		return Material::from_row( $row );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		$type = (string) ( $data['material_type'] ?? MaterialType::FRONT->value );

		$valid_types = array_map(
			static fn ( MaterialType $material_type ): string => $material_type->value,
			MaterialType::cases()
		);

		if ( ! in_array( $type, $valid_types, true ) ) {
			$type = MaterialType::FRONT->value;
		}

		$price_per_sqm = $data['price_per_sqm'] ?? null;

		return array(
			'slug'             => $this->resolve_slug( $data ),
			'name'             => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'material_type'    => $type,
			'description'      => wp_kses_post( (string) ( $data['description'] ?? '' ) ),
			'price_modifier'   => number_format( (float) ( $data['price_modifier'] ?? 0 ), 2, '.', '' ),
			'price_per_sqm'    => ( '' === (string) $price_per_sqm || null === $price_per_sqm )
				? null
				: number_format( (float) $price_per_sqm, 4, '.', '' ),
			'price_multiplier' => number_format( (float) ( $data['price_multiplier'] ?? 1 ), 4, '.', '' ),
			'thumbnail_url'    => esc_url_raw( (string) ( $data['thumbnail_url'] ?? '' ) ),
			'sort_order'       => (int) ( $data['sort_order'] ?? 0 ),
			'is_active'        => $this->to_bool_int( $data['is_active'] ?? 1 ),
		);
	}
}
