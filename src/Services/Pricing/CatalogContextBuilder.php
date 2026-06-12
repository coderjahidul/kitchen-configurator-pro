<?php
/**
 * Builds a hydrated calculation context from catalog repositories.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing;

use KitchenConfiguratorPro\Domain\DTO\ConfigurationInput;
use KitchenConfiguratorPro\Domain\Exceptions\PricingException;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Repositories\ColorRepository;
use KitchenConfiguratorPro\Repositories\HandleRepository;
use KitchenConfiguratorPro\Repositories\LayoutRepository;
use KitchenConfiguratorPro\Repositories\MaterialRepository;
use KitchenConfiguratorPro\Repositories\PlinthRepository;
use KitchenConfiguratorPro\Repositories\WorktopRepository;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Resolves catalog entities referenced by a configuration input.
 */
final class CatalogContextBuilder {

	/**
	 * @param LayoutRepository   $layouts   Layout repository.
	 * @param CabinetRepository  $cabinets  Cabinet repository.
	 * @param MaterialRepository $materials Material repository.
	 * @param ColorRepository    $colors    Color repository.
	 * @param HandleRepository   $handles   Handle repository.
	 * @param WorktopRepository  $worktops  Worktop repository.
	 * @param PlinthRepository   $plinths   Plinth repository.
	 */
	public function __construct(
		private readonly LayoutRepository $layouts,
		private readonly CabinetRepository $cabinets,
		private readonly MaterialRepository $materials,
		private readonly ColorRepository $colors,
		private readonly HandleRepository $handles,
		private readonly WorktopRepository $worktops,
		private readonly PlinthRepository $plinths
	) {
	}

	/**
	 * Build calculation context with resolved catalog entities.
	 *
	 * @param ConfigurationInput $input    Configuration input.
	 * @param string             $currency Currency code.
	 * @return CalculationContext
	 *
	 * @throws PricingException When required catalog entities are missing.
	 */
	public function build( ConfigurationInput $input, string $currency ): CalculationContext {
		$layout = $this->layouts->find( $input->layout_id );

		if ( null === $layout ) {
			throw new PricingException( __( 'Layout not found for pricing.', 'kitchen-configurator-pro' ) );
		}

		$context = new CalculationContext( $input, $currency, $layout );

		foreach ( $input->cabinets as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$cabinet_id = (int) Arr::get( $item, 'cabinet_id', 0 );

			if ( $cabinet_id > 0 && ! isset( $context->cabinets[ $cabinet_id ] ) ) {
				$cabinet = $this->cabinets->find( $cabinet_id );

				if ( null === $cabinet ) {
					throw new PricingException(
						sprintf(
							/* translators: %d: cabinet ID */
							__( 'Cabinet %d not found for pricing.', 'kitchen-configurator-pro' ),
							$cabinet_id
						)
					);
				}

				$context->cabinets[ $cabinet_id ] = $cabinet;
			}

			$material_id = (int) Arr::get( $item, 'material_id', 0 );

			if ( $material_id > 0 && ! isset( $context->materials[ $material_id ] ) ) {
				$material = $this->materials->find( $material_id );

				if ( null !== $material ) {
					$context->materials[ $material_id ] = $material;
				}
			}

			$color_id = (int) Arr::get( $item, 'color_id', 0 );

			if ( $color_id > 0 && ! isset( $context->colors[ $color_id ] ) ) {
				$color = $this->colors->find( $color_id );

				if ( null !== $color ) {
					$context->colors[ $color_id ] = $color;
				}
			}

			$handle_id = (int) Arr::get( $item, 'handle_id', 0 );

			if ( $handle_id > 0 && ! isset( $context->handles[ $handle_id ] ) ) {
				$handle = $this->handles->find( $handle_id );

				if ( null !== $handle ) {
					$context->handles[ $handle_id ] = $handle;
				}
			}
		}

		$worktop_id = (int) Arr::get( $input->global_options, 'worktop_id', 0 );

		if ( $worktop_id > 0 ) {
			$context->worktop = $this->worktops->find( $worktop_id );
		}

		$worktop_material_id = (int) Arr::get( $input->global_options, 'worktop_material_id', 0 );

		if ( $worktop_material_id > 0 && ! isset( $context->materials[ $worktop_material_id ] ) ) {
			$material = $this->materials->find( $worktop_material_id );

			if ( null !== $material ) {
				$context->materials[ $worktop_material_id ] = $material;
			}
		}

		$worktop_color_id = (int) Arr::get( $input->global_options, 'worktop_color_id', 0 );

		if ( $worktop_color_id > 0 && ! isset( $context->colors[ $worktop_color_id ] ) ) {
			$color = $this->colors->find( $worktop_color_id );

			if ( null !== $color ) {
				$context->colors[ $worktop_color_id ] = $color;
			}
		}

		$plinth_id = (int) Arr::get( $input->global_options, 'plinth_id', 0 );

		if ( $plinth_id > 0 ) {
			$context->plinth = $this->plinths->find( $plinth_id );
		}

		return $context;
	}
}
