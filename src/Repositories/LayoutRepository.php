<?php
/**
 * Layout repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\Layout;

/**
 * @extends AbstractRepository<Layout>
 */
final class LayoutRepository extends AbstractRepository {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_layouts';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): Layout {
		return Layout::from_row( $row );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		return array(
			'slug'          => $this->resolve_slug( $data ),
			'name'          => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'description'   => wp_kses_post( (string) ( $data['description'] ?? '' ) ),
			'thumbnail_url' => esc_url_raw( (string) ( $data['thumbnail_url'] ?? '' ) ),
			'config_json'   => $this->sanitize_json( (string) ( $data['config_json'] ?? '' ) ),
			'sort_order'    => (int) ( $data['sort_order'] ?? 0 ),
			'is_active'     => $this->to_bool_int( $data['is_active'] ?? 1 ),
		);
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
