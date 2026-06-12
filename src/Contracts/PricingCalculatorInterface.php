<?php
/**
 * Pricing calculator contract.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Contracts;

use KitchenConfiguratorPro\Services\Pricing\CalculationContext;

/**
 * Single-responsibility pricing calculator.
 */
interface PricingCalculatorInterface {

	/**
	 * Calculator priority (lower runs earlier).
	 *
	 * @return int
	 */
	public function priority(): int;

	/**
	 * Apply pricing logic and append line items to context.
	 *
	 * @param CalculationContext $context Mutable calculation context.
	 * @return void
	 */
	public function calculate( CalculationContext $context ): void;
}
