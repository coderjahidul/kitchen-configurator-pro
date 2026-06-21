<?php
/**
 * Resolves design step zone options from catalog repositories.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Domain\Entities\Cabinet;
use KitchenConfiguratorPro\Domain\Entities\Color;
use KitchenConfiguratorPro\Domain\Entities\Handle;
use KitchenConfiguratorPro\Domain\Entities\Plinth;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Repositories\ColorRepository;
use KitchenConfiguratorPro\Repositories\HandleRepository;
use KitchenConfiguratorPro\Repositories\PlinthRepository;

/**
 * Maps design zones to active catalog items for the frontend picker.
 */
final class DesignZoneCatalogService {

	/**
	 * Zone ID to catalog source key.
	 */
	private const ZONE_SOURCES = array(
		'front'        => 'colors',
		'handle_strip' => 'handles',
		'cabinet'      => 'cabinets',
		'plinth'       => 'plinths',
	);

	/**
	 * Zone ID to admin page slug.
	 */
	private const ZONE_ADMIN_PAGES = array(
		'front'        => 'kcp-colors',
		'handle_strip' => 'kcp-handles',
		'cabinet'      => 'kcp-cabinets',
		'plinth'       => 'kcp-plinths',
	);

	/**
	 * @param ColorRepository   $colors   Color repository.
	 * @param HandleRepository  $handles  Handle repository.
	 * @param CabinetRepository $cabinets Cabinet repository.
	 * @param PlinthRepository  $plinths  Plinth repository.
	 */
	public function __construct(
		private readonly ColorRepository $colors,
		private readonly HandleRepository $handles,
		private readonly CabinetRepository $cabinets,
		private readonly PlinthRepository $plinths
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

			$zone_id           = sanitize_key( (string) ( $zone['id'] ?? '' ) );
			$zone['colors']      = $this->get_options_for_zone( $zone_id );
			$hydrated[]        = $zone;
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
		$source = self::ZONE_SOURCES[ $zone_id ] ?? '';

		if ( '' === $source ) {
			return array();
		}

		$active = array( 'is_active' => '1' );

		return match ( $source ) {
			'colors'   => array_map( array( $this, 'map_color' ), $this->colors->find_all( $active ) ),
			'handles'  => array_map( array( $this, 'map_handle' ), $this->handles->find_all( $active ) ),
			'cabinets' => array_map( array( $this, 'map_cabinet' ), $this->cabinets->find_all( $active ) ),
			'plinths'  => array_map( array( $this, 'map_plinth' ), $this->plinths->find_all( $active ) ),
			default    => array(),
		};
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

	/**
	 * @param Cabinet $cabinet Cabinet entity.
	 * @return array<string, mixed>
	 */
	private function map_cabinet( Cabinet $cabinet ): array {
		return array(
			'id'             => $cabinet->id,
			'name'           => $cabinet->name,
			'hex'            => '',
			'image_url'      => $cabinet->image_url,
			'description'    => $cabinet->description,
			'price'          => (float) $cabinet->base_price,
			'price_modifier' => (float) $cabinet->base_price,
		);
	}

	/**
	 * @param Plinth $plinth Plinth entity.
	 * @return array<string, mixed>
	 */
	private function map_plinth( Plinth $plinth ): array {
		return array(
			'id'             => $plinth->id,
			'name'           => $plinth->name,
			'hex'            => '',
			'image_url'      => $plinth->thumbnail_url,
			'description'    => $plinth->description,
			'price'          => (float) $plinth->base_price,
			'price_modifier' => (float) $plinth->base_price,
		);
	}
}
