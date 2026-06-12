<?php
/**
 * Handle repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\Handle;

/**
 * @extends AbstractRepository<Handle>
 */
final class HandleRepository extends AbstractRepository {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_handles';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): Handle {
		return Handle::from_row( $row );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		return array(
			'slug'          => $this->resolve_slug( $data ),
			'name'          => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'description'   => wp_kses_post( (string) ( $data['description'] ?? '' ) ),
			'price'         => number_format( (float) ( $data['price'] ?? 0 ), 2, '.', '' ),
			'thumbnail_url' => esc_url_raw( (string) ( $data['thumbnail_url'] ?? '' ) ),
			'sort_order'    => (int) ( $data['sort_order'] ?? 0 ),
			'is_active'     => $this->to_bool_int( $data['is_active'] ?? 1 ),
		);
	}
}
