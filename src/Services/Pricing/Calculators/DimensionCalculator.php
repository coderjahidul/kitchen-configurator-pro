<?php
/**
 * Dimension surcharge calculator.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing\Calculators;

use KitchenConfiguratorPro\Domain\ValueObjects\Dimensions;
use KitchenConfiguratorPro\Domain\ValueObjects\Money;
use KitchenConfiguratorPro\Services\Pricing\AbstractCalculator;
use KitchenConfiguratorPro\Services\Pricing\CalculationContext;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Applies dimension-based surcharges from cabinet dimension_price_json.
 */
final class DimensionCalculator extends AbstractCalculator {

	/**
	 * {@inheritDoc}
	 */
	public function priority(): int {
		return 20;
	}

	/**
	 * {@inheritDoc}
	 */
	public function calculate( CalculationContext $context ): void {
		foreach ( $context->input->cabinets as $index => $item ) {
			$cabinet_id = (int) Arr::get( $item, 'cabinet_id', 0 );
			$cabinet    = $context->cabinets[ $cabinet_id ] ?? null;

			if ( null === $cabinet || '' === trim( $cabinet->dimension_price_json ) ) {
				continue;
			}

			$rules = json_decode( $cabinet->dimension_price_json, true );

			if ( ! is_array( $rules ) || empty( $rules ) ) {
				continue;
			}

			$defaults   = new Dimensions( $cabinet->default_width, $cabinet->default_height, $cabinet->default_depth );
			$dimensions = $context->cabinet_dimensions( $index );
			$breakdown  = array();
			$total      = Money::zero( $context->currency );

			foreach ( array( 'width', 'height', 'depth' ) as $axis ) {
				if ( ! isset( $rules[ $axis ] ) || ! is_array( $rules[ $axis ] ) ) {
					continue;
				}

				$axis_rule   = $rules[ $axis ];
				$rate        = (float) ( $axis_rule['rate_per_mm'] ?? 0 );
				$base        = (int) ( $axis_rule['base'] ?? $defaults->{$axis} );
				$actual      = $dimensions->{$axis};
				$diff        = max( 0, $actual - $base );
				$surcharge   = Money::from( $diff * $rate, $context->currency );
				$total       = $total->add( $surcharge );
				$breakdown   = $this->breakdown_entry(
					$breakdown,
					'dimension_' . $axis,
					$surcharge,
					sprintf(
						/* translators: %s: dimension axis */
						__( '%s surcharge', 'kitchen-configurator-pro' ),
						ucfirst( $axis )
					)
				);
			}

			if ( ! $total->is_zero() ) {
				$this->add_line(
					$context,
					'dimension_surcharge',
					$cabinet->id,
					sprintf(
						/* translators: %s: cabinet name */
						__( '%s — dimension surcharge', 'kitchen-configurator-pro' ),
						$cabinet->name
					),
					$total,
					1,
					$breakdown
				);
			}
		}
	}
}
