<?php
/**
 * Catalog service with transient caching.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Domain\DTO\CatalogResponse;
use KitchenConfiguratorPro\Repositories\AccessoryRepository;
use KitchenConfiguratorPro\Repositories\CabinetCategoryRepository;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Repositories\ColorRepository;
use KitchenConfiguratorPro\Repositories\HandleRepository;
use KitchenConfiguratorPro\Repositories\LayoutRepository;
use KitchenConfiguratorPro\Repositories\MaterialRepository;
use KitchenConfiguratorPro\Repositories\PlinthRepository;
use KitchenConfiguratorPro\Repositories\WorktopRepository;
use KitchenConfiguratorPro\Support\Arr;
use KitchenConfiguratorPro\Support\Helpers;

/**
 * Provides cached read-only catalog data for the configurator frontend.
 */
final class CatalogService {

	/**
	 * Transient key prefix.
	 */
	private const CACHE_PREFIX = 'kcp_catalog_';

	/**
	 * Cache TTL in seconds (1 hour).
	 */
	private const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * @param LayoutRepository          $layouts            Layout repository.
	 * @param CabinetCategoryRepository $cabinet_categories Category repository.
	 * @param CabinetRepository         $cabinets           Cabinet repository.
	 * @param MaterialRepository        $materials          Material repository.
	 * @param ColorRepository           $colors             Color repository.
	 * @param HandleRepository          $handles            Handle repository.
	 * @param AccessoryRepository       $accessories        Accessory repository.
	 * @param WorktopRepository         $worktops           Worktop repository.
	 * @param PlinthRepository          $plinths            Plinth repository.
	 */
	public function __construct(
		private readonly LayoutRepository $layouts,
		private readonly CabinetCategoryRepository $cabinet_categories,
		private readonly CabinetRepository $cabinets,
		private readonly MaterialRepository $materials,
		private readonly ColorRepository $colors,
		private readonly HandleRepository $handles,
		private readonly AccessoryRepository $accessories,
		private readonly WorktopRepository $worktops,
		private readonly PlinthRepository $plinths
	) {
	}

	/**
	 * Get the full active catalog (cached).
	 *
	 * @return CatalogResponse
	 */
	public function get_full_catalog(): CatalogResponse {
		$cache_key = self::CACHE_PREFIX . (int) get_option( 'kcp_catalog_cache_version', 1 );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return new CatalogResponse(
				$cached['layouts'] ?? array(),
				$cached['cabinet_categories'] ?? array(),
				$cached['cabinets'] ?? array(),
				$cached['materials'] ?? array(),
				$cached['colors'] ?? array(),
				$cached['handles'] ?? array(),
				$cached['accessories'] ?? array(),
				$cached['worktops'] ?? array(),
				$cached['plinths'] ?? array()
			);
		}

		$response = $this->build_catalog();
		$data     = $response->to_array();

		set_transient( $cache_key, $data, self::CACHE_TTL );

		return $response;
	}

	/**
	 * Invalidate catalog cache (called on admin save).
	 *
	 * @return void
	 */
	public function invalidate_cache(): void {
		Helpers::bump_catalog_cache_version();
	}

	/**
	 * Build catalog from repositories.
	 *
	 * @return CatalogResponse
	 */
	private function build_catalog(): CatalogResponse {
		$active = array( 'is_active' => '1' );

		return new CatalogResponse(
			$this->map_entities( $this->layouts->find_all( $active ) ),
			$this->map_entities( $this->cabinet_categories->find_all( $active ) ),
			$this->map_entities( $this->cabinets->find_all( $active ) ),
			$this->map_entities( $this->materials->find_all( $active ) ),
			$this->map_entities( $this->colors->find_all( $active ) ),
			$this->map_entities( $this->handles->find_all( $active ) ),
			$this->map_entities( $this->accessories->find_all( $active ) ),
			$this->map_entities( $this->worktops->find_all( $active ) ),
			$this->map_entities( $this->plinths->find_all( $active ) )
		);
	}

	/**
	 * Map entities to arrays.
	 *
	 * @param array<int, mixed> $entities Entity list.
	 * @return array<int, array<string, mixed>>
	 */
	private function map_entities( array $entities ): array {
		return array_values(
			array_map(
				static fn ( mixed $entity ): array => Arr::to_array( $entity ),
				$entities
			)
		);
	}
}
