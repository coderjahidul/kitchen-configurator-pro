<?php
/**
 * Accessory repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\Accessory;

/**
 * @extends AbstractRepository<Accessory>
 */
final class AccessoryRepository extends AbstractRepository {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_accessories';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): Accessory {
		return Accessory::from_row( $row );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		return array(
			'slug'           => $this->resolve_slug( $data ),
			'name'           => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'category'       => sanitize_key( (string) ( $data['category'] ?? 'general' ) ),
			'description'    => wp_kses_post( (string) ( $data['description'] ?? '' ) ),
			'price'          => number_format( (float) ( $data['price'] ?? 0 ), 2, '.', '' ),
			'is_per_cabinet' => $this->to_bool_int( $data['is_per_cabinet'] ?? 1 ),
			'thumbnail_url'  => esc_url_raw( (string) ( $data['thumbnail_url'] ?? '' ) ),
			'sort_order'     => (int) ( $data['sort_order'] ?? 0 ),
			'is_active'      => $this->to_bool_int( $data['is_active'] ?? 1 ),
		);
	}
}
