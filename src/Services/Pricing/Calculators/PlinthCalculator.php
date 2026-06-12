<?php
/**
 * Plinth price calculator.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing\Calculators;

use KitchenConfiguratorPro\Domain\ValueObjects\Money;
use KitchenConfiguratorPro\Services\Pricing\AbstractCalculator;
use KitchenConfiguratorPro\Services\Pricing\CalculationContext;

/**
 * Applies plinth catalog pricing based on run length.
 */
final class PlinthCalculator extends AbstractCalculator {

	/**
	 * {@inheritDoc}
	 */
	public function priority(): int {
		return 70;
	}

	/**
	 * {@inheritDoc}
	 */
	public function calculate( CalculationContext $context ): void {
		$plinth_id = (int) $context->global_option( 'plinth_id', 0 );

		if ( $plinth_id <= 0 || null === $context->plinth ) {
			return;
		}

		$plinth = $context->plinth;
		$length = (int) $context->global_option( 'plinth_length', $plinth->default_length );
		$breakdown = array();
		$total     = Money::from( $plinth->base_price, $context->currency );
		$breakdown = $this->breakdown_entry( $breakdown, 'plinth_base', $total );

		if ( (float) $plinth->price_per_linear_meter > 0 ) {
			$meters    = number_format( $length / 1000, 4, '.', '' );
			$linear    = Money::from( $plinth->price_per_linear_meter, $context->currency )->multiply( $meters );
			$total     = $total->add( $linear );
			$breakdown = $this->breakdown_entry( $breakdown, 'plinth_per_linear_meter', $linear );
		}

		$this->add_line(
			$context,
			'plinth',
			$plinth->id,
			$plinth->name,
			$total,
			1,
			$breakdown
		);
	}
}
