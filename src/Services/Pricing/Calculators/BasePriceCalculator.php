<?php
/**
 * Base cabinet price calculator.
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
 * Applies cabinet base_price per configured cabinet.
 */
final class BasePriceCalculator extends AbstractCalculator {

	/**
	 * {@inheritDoc}
	 */
	public function priority(): int {
		return 10;
	}

	/**
	 * {@inheritDoc}
	 */
	public function calculate( CalculationContext $context ): void {
		foreach ( $context->input->cabinets as $index => $item ) {
			$cabinet_id = (int) Arr::get( $item, 'cabinet_id', 0 );
			$cabinet    = $context->cabinets[ $cabinet_id ] ?? null;

			if ( null === $cabinet ) {
				continue;
			}

			$base = Money::from( $cabinet->base_price, $context->currency );

			$this->add_line(
				$context,
				'cabinet',
				$cabinet->id,
				$cabinet->name,
				$base,
				1,
				$this->breakdown_entry( array(), 'base_price', $base, __( 'Base price', 'kitchen-configurator-pro' ) )
			);
		}
	}
}
