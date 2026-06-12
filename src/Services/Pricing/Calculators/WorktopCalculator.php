<?php
/**
 * Worktop price calculator.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing\Calculators;

use KitchenConfiguratorPro\Domain\ValueObjects\Money;
use KitchenConfiguratorPro\Services\Pricing\AbstractCalculator;
use KitchenConfiguratorPro\Services\Pricing\CalculationContext;
/**
 * Applies worktop catalog pricing plus optional finish material/color.
 */
final class WorktopCalculator extends AbstractCalculator {

	/**
	 * {@inheritDoc}
	 */
	public function priority(): int {
		return 60;
	}

	/**
	 * {@inheritDoc}
	 */
	public function calculate( CalculationContext $context ): void {
		$worktop_id = (int) $context->global_option( 'worktop_id', 0 );

		if ( $worktop_id <= 0 || null === $context->worktop ) {
			return;
		}

		$worktop = $context->worktop;
		$length  = (int) $context->global_option( 'worktop_length', $worktop->default_length );
		$depth   = (int) $context->global_option( 'worktop_depth', $worktop->default_depth );
		$breakdown = array();
		$total     = Money::from( $worktop->base_price, $context->currency );
		$breakdown = $this->breakdown_entry( $breakdown, 'worktop_base', $total );

		if ( null !== $worktop->price_per_sqm && (float) $worktop->price_per_sqm > 0 ) {
			$sqm       = number_format( ( $length * $depth ) / 1_000_000, 4, '.', '' );
			$area_cost = Money::from( $worktop->price_per_sqm, $context->currency )->multiply( $sqm );
			$total     = $total->add( $area_cost );
			$breakdown = $this->breakdown_entry( $breakdown, 'worktop_per_sqm', $area_cost );
		}

		if ( null !== $worktop->price_per_linear_meter && (float) $worktop->price_per_linear_meter > 0 ) {
			$meters    = number_format( $length / 1000, 4, '.', '' );
			$linear    = Money::from( $worktop->price_per_linear_meter, $context->currency )->multiply( $meters );
			$total     = $total->add( $linear );
			$breakdown = $this->breakdown_entry( $breakdown, 'worktop_per_linear_meter', $linear );
		}

		$material_id = (int) $context->global_option( 'worktop_material_id', 0 );
		$material    = $context->materials[ $material_id ] ?? null;

		if ( null !== $material ) {
			$modifier = Money::from( $material->price_modifier, $context->currency );

			if ( $modifier->is_positive() ) {
				$total     = $total->add( $modifier );
				$breakdown = $this->breakdown_entry( $breakdown, 'worktop_material_modifier', $modifier );
			}

			if ( null !== $material->price_per_sqm && (float) $material->price_per_sqm > 0 ) {
				$sqm       = number_format( ( $length * $depth ) / 1_000_000, 4, '.', '' );
				$area_cost = Money::from( $material->price_per_sqm, $context->currency )->multiply( $sqm );
				$total     = $total->add( $area_cost );
				$breakdown = $this->breakdown_entry( $breakdown, 'worktop_material_per_sqm', $area_cost );
			}
		}

		$color_id = (int) $context->global_option( 'worktop_color_id', 0 );
		$color    = $context->colors[ $color_id ] ?? null;

		if ( null !== $color ) {
			$color_mod = Money::from( $color->price_modifier, $context->currency );

			if ( $color_mod->is_positive() ) {
				$total     = $total->add( $color_mod );
				$breakdown = $this->breakdown_entry( $breakdown, 'worktop_color_modifier', $color_mod );
			}
		}

		$this->add_line(
			$context,
			'worktop',
			$worktop->id,
			$worktop->name,
			$total,
			1,
			$breakdown
		);
	}
}
