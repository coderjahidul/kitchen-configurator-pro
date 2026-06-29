<?php
/**
 * Cabinet category repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\CabinetCategory;

/**
 * @extends AbstractRepository<CabinetCategory>
 */
final class CabinetCategoryRepository extends AbstractRepository {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_cabinet_categories';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): CabinetCategory {
		return CabinetCategory::from_row( $row );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		return array(
			'slug'                => $this->resolve_slug( $data ),
			'name'                => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'description'         => wp_kses_post( (string) ( $data['description'] ?? '' ) ),
			'image_url_greep'     => esc_url_raw( (string) ( $data['image_url_greep'] ?? '' ) ),
			'image_url_greeploos' => esc_url_raw( (string) ( $data['image_url_greeploos'] ?? '' ) ),
			'sort_order'          => (int) ( $data['sort_order'] ?? 0 ),
			'is_active'           => $this->to_bool_int( $data['is_active'] ?? 1 ),
		);
	}
}
