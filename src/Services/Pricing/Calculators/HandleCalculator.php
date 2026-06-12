<?php
/**
 * Handle price calculator.
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
 * Applies handle price per cabinet.
 */
final class HandleCalculator extends AbstractCalculator {

	/**
	 * {@inheritDoc}
	 */
	public function priority(): int {
		return 40;
	}

	/**
	 * {@inheritDoc}
	 */
	public function calculate( CalculationContext $context ): void {
		foreach ( $context->input->cabinets as $item ) {
			$handle_id = (int) Arr::get( $item, 'handle_id', 0 );

			if ( $handle_id <= 0 ) {
				continue;
			}

			$handle = $context->handles[ $handle_id ] ?? null;

			if ( null === $handle ) {
				continue;
			}

			$price = Money::from( $handle->price, $context->currency );

			$this->add_line(
				$context,
				'handle',
				$handle->id,
				$handle->name,
				$price,
				1,
				$this->breakdown_entry( array(), 'handle_price', $price )
			);
		}
	}
}
