<?php
/**
 * Resolves design step zone options from catalog repositories.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Domain\Entities\Color;
use KitchenConfiguratorPro\Domain\Entities\Handle;
use KitchenConfiguratorPro\Domain\Entities\Material;
use KitchenConfiguratorPro\Domain\Enums\MaterialType;
use KitchenConfiguratorPro\Repositories\ColorRepository;
use KitchenConfiguratorPro\Repositories\HandleRepository;
use KitchenConfiguratorPro\Repositories\MaterialRepository;

/**
 * Maps design zones to active catalog items for the frontend picker.
 */
final class DesignZoneCatalogService {

	/**
	 * Zone ID to linked material type for color-based zones.
	 */
	private const ZONE_MATERIAL_TYPES = array(
		'front'   => MaterialType::FRONT,
		'cabinet' => MaterialType::CARCASS,
		'plinth'  => MaterialType::PLINTH,
	);

	/**
	 * Zone ID to admin page slug.
	 */
	private const ZONE_ADMIN_PAGES = array(
		'front'        => 'kcp-colors',
		'handle_strip' => 'kcp-handles',
		'cabinet'      => 'kcp-colors',
		'plinth'       => 'kcp-colors',
	);

	/**
	 * Active material IDs keyed by material type value.
	 *
	 * @var array<string, array<int, int>>|null
	 */
	private ?array $material_ids_by_type = null;

	/**
	 * @param ColorRepository    $colors    Color repository.
	 * @param HandleRepository   $handles   Handle repository.
	 * @param MaterialRepository $materials Material repository.
	 */
	public function __construct(
		private readonly ColorRepository $colors,
		private readonly HandleRepository $handles,
		private readonly MaterialRepository $materials
	) {
	}

	/**
	 * Attach catalog options to each zone.
	 *
	 * @param array<int, array<string, mixed>> $zones Zone definitions.
	 * @return array<int, array<string, mixed>>
	 */
	public function hydrate_zones( array $zones ): array {
		$hydrated = array();

		foreach ( $zones as $zone ) {
			if ( ! is_array( $zone ) ) {
				continue;
			}

			$zone_id        = sanitize_key( (string) ( $zone['id'] ?? '' ) );
			$zone['colors'] = $this->get_options_for_zone( $zone_id );
			$hydrated[]     = $zone;
		}

		return $hydrated;
	}

	/**
	 * Admin page slug for a zone catalog.
	 *
	 * @param string $zone_id Zone identifier.
	 * @return string
	 */
	public static function admin_page_for_zone( string $zone_id ): string {
		return self::ZONE_ADMIN_PAGES[ $zone_id ] ?? '';
	}

	/**
	 * Load active catalog items for a zone.
	 *
	 * @param string $zone_id Zone identifier.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_options_for_zone( string $zone_id ): array {
		if ( 'handle_strip' === $zone_id ) {
			return array_map(
				array( $this, 'map_handle' ),
				$this->handles->find_all( array( 'is_active' => '1' ) )
			);
		}

		$material_type = self::ZONE_MATERIAL_TYPES[ $zone_id ] ?? null;

		if ( ! $material_type instanceof MaterialType ) {
			return array();
		}

		return array_map(
			array( $this, 'map_color' ),
			$this->colors_for_material_type( $material_type )
		);
	}

	/**
	 * Active colors whose material matches the given type.
	 *
	 * @param MaterialType $material_type Material type.
	 * @return array<int, Color>
	 */
	private function colors_for_material_type( MaterialType $material_type ): array {
		$allowed_material_ids = $this->material_ids_by_type()[ $material_type->value ] ?? array();

		if ( empty( $allowed_material_ids ) ) {
			return array();
		}

		$colors = array();

		foreach ( $this->colors->find_all( array( 'is_active' => '1' ), 'sort_order', 'ASC' ) as $color ) {
			if ( ! $color instanceof Color ) {
				continue;
			}

			if ( ! isset( $allowed_material_ids[ $color->material_id ] ) ) {
				continue;
			}

			$colors[] = $color;
		}

		return $colors;
	}

	/**
	 * Build and cache active material IDs grouped by type.
	 *
	 * @return array<string, array<int, int>>
	 */
	private function material_ids_by_type(): array {
		if ( null !== $this->material_ids_by_type ) {
			return $this->material_ids_by_type;
		}

		$this->material_ids_by_type = array();

		foreach ( MaterialType::cases() as $material_type ) {
			$this->material_ids_by_type[ $material_type->value ] = array();
		}

		foreach ( $this->materials->find_all( array( 'is_active' => '1' ) ) as $material ) {
			if ( ! $material instanceof Material ) {
				continue;
			}

			$this->material_ids_by_type[ $material->material_type ][ $material->id ] = $material->id;
		}

		return $this->material_ids_by_type;
	}

	/**
	 * @param Color $color Color entity.
	 * @return array<string, mixed>
	 */
	private function map_color( Color $color ): array {
		return array(
			'id'             => $color->id,
			'name'           => $color->name,
			'hex'            => $color->hex_code ?: '#ffffff',
			'image_url'      => $color->thumbnail_url,
			'description'    => '',
			'price'          => (float) $color->price_modifier,
			'price_modifier' => (float) $color->price_modifier,
		);
	}

	/**
	 * @param Handle $handle Handle entity.
	 * @return array<string, mixed>
	 */
	private function map_handle( Handle $handle ): array {
		return array(
			'id'             => $handle->id,
			'name'           => $handle->name,
			'hex'            => '',
			'image_url'      => $handle->thumbnail_url,
			'description'    => $handle->description,
			'price'          => (float) $handle->price,
			'price_modifier' => (float) $handle->price,
		);
	}
}
