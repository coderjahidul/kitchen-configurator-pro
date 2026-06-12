<?php
/**
 * Base pricing calculator helpers.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing;

use KitchenConfiguratorPro\Contracts\PricingCalculatorInterface;
use KitchenConfiguratorPro\Domain\DTO\LineItem;
use KitchenConfiguratorPro\Domain\ValueObjects\Money;

/**
 * Shared helpers for pricing calculators.
 */
abstract class AbstractCalculator implements PricingCalculatorInterface {

	/**
	 * Add a priced line to the context.
	 *
	 * @param CalculationContext             $context     Context.
	 * @param string                         $type        Line type.
	 * @param int|null                       $reference_id Reference ID.
	 * @param string                         $label       Label.
	 * @param Money                          $amount      Line amount.
	 * @param int                            $quantity    Quantity.
	 * @param array<int, array<string, mixed>> $breakdown Breakdown entries.
	 * @return void
	 */
	protected function add_line(
		CalculationContext $context,
		string $type,
		?int $reference_id,
		string $label,
		Money $amount,
		int $quantity = 1,
		array $breakdown = array()
	): void {
		if ( $amount->is_zero() && empty( $breakdown ) ) {
			return;
		}

		$context->add_line_item(
			new LineItem(
				$type,
				$reference_id,
				$label,
				$quantity,
				$amount,
				$amount,
				$breakdown
			)
		);
	}

	/**
	 * Add breakdown entry.
	 *
	 * @param array<int, array<string, mixed>> $breakdown Breakdown array.
	 * @param string                           $rule      Rule key.
	 * @param Money                            $amount    Amount.
	 * @param string                           $label     Optional label.
	 * @return array<int, array<string, mixed>>
	 */
	protected function breakdown_entry( array $breakdown, string $rule, Money $amount, string $label = '' ): array {
		$breakdown[] = array(
			'rule'   => $rule,
			'amount' => (float) $amount->amount,
			'label'  => $label,
		);

		return $breakdown;
	}
}
