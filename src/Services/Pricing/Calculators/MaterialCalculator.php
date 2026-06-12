<?php
/**
 * Material and color price calculator.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing\Calculators;

use KitchenConfiguratorPro\Domain\ValueObjects\Money;
use KitchenConfiguratorPro\Services\Pricing\AbstractCalculator;
use KitchenConfiguratorPro\Services\Pricing\CalculationContext;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Applies material modifiers, multipliers, and per-sqm pricing per cabinet.
 */
final class MaterialCalculator extends AbstractCalculator {

	/**
	 * {@inheritDoc}
	 */
	public function priority(): int {
		return 30;
	}

	/**
	 * {@inheritDoc}
	 */
	public function calculate( CalculationContext $context ): void {
		foreach ( $context->input->cabinets as $index => $item ) {
			$cabinet_id  = (int) Arr::get( $item, 'cabinet_id', 0 );
			$material_id = (int) Arr::get( $item, 'material_id', 0 );
			$color_id    = (int) Arr::get( $item, 'color_id', 0 );
			$cabinet     = $context->cabinets[ $cabinet_id ] ?? null;
			$material    = $context->materials[ $material_id ] ?? null;

			if ( null === $cabinet || null === $material ) {
				continue;
			}

			$breakdown = array();
			$total     = Money::zero( $context->currency );

			$modifier = Money::from( $material->price_modifier, $context->currency );

			if ( $modifier->is_positive() ) {
				$total     = $total->add( $modifier );
				$breakdown = $this->breakdown_entry( $breakdown, 'material_modifier', $modifier );
			}

			if ( null !== $material->price_per_sqm && (float) $material->price_per_sqm > 0 ) {
				$sqm       = $context->cabinet_dimensions( $index )->front_area_sqm();
				$area_cost = Money::from( $material->price_per_sqm, $context->currency )->multiply( $sqm );
				$total     = $total->add( $area_cost );
				$breakdown = $this->breakdown_entry( $breakdown, 'material_per_sqm', $area_cost );
			}

			$multiplier = (float) $material->price_multiplier;

			if ( $multiplier > 0 && 1.0 !== $multiplier ) {
				$base_ref  = Money::from( $cabinet->base_price, $context->currency );
				$multi_amt = $base_ref->multiply( $multiplier - 1 );
				$total     = $total->add( $multi_amt );
				$breakdown = $this->breakdown_entry( $breakdown, 'material_multiplier', $multi_amt );
			}

			if ( $color_id > 0 ) {
				$color = $context->colors[ $color_id ] ?? null;

				if ( null !== $color ) {
					$color_mod = Money::from( $color->price_modifier, $context->currency );

					if ( $color_mod->is_positive() ) {
						$total     = $total->add( $color_mod );
						$breakdown = $this->breakdown_entry( $breakdown, 'color_modifier', $color_mod );
					}
				}
			}

			if ( ! $total->is_zero() ) {
				$this->add_line(
					$context,
					'material',
					$material->id,
					sprintf(
						/* translators: %s: material name */
						__( '%s — material', 'kitchen-configurator-pro' ),
						$material->name
					),
					$total,
					1,
					$breakdown
				);
			}
		}
	}
}
