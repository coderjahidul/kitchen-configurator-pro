<?php
/**
 * Custom pricing rule engine calculator.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing\Calculators;

use KitchenConfiguratorPro\Domain\Enums\PricingRuleType;
use KitchenConfiguratorPro\Domain\ValueObjects\Money;
use KitchenConfiguratorPro\Repositories\PricingRuleRepository;
use KitchenConfiguratorPro\Services\Pricing\AbstractCalculator;
use KitchenConfiguratorPro\Services\Pricing\CalculationContext;
use KitchenConfiguratorPro\Services\Pricing\ConditionEvaluator;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Applies admin-defined pricing rules after base calculators.
 */
final class RuleEngineCalculator extends AbstractCalculator {

	/**
	 * @param PricingRuleRepository $rules      Pricing rule repository.
	 * @param ConditionEvaluator    $evaluator  Condition evaluator.
	 */
	public function __construct(
		private readonly PricingRuleRepository $rules,
		private readonly ConditionEvaluator $evaluator
	) {
	}

	/**
	 * {@inheritDoc}
	 */
	public function priority(): int {
		return 100;
	}

	/**
	 * {@inheritDoc}
	 */
	public function calculate( CalculationContext $context ): void {
		$active_rules = $this->rules->find_active_ordered();

		foreach ( $active_rules as $rule ) {
			$this->apply_rule( $rule, $context );
		}
	}

	/**
	 * Apply a single pricing rule.
	 *
	 * @param \KitchenConfiguratorPro\Domain\Entities\PricingRule $rule    Rule.
	 * @param CalculationContext                                    $context Context.
	 * @return void
	 */
	private function apply_rule( $rule, CalculationContext $context ): void {
		$calculation = json_decode( $rule->calculation_json, true );

		if ( ! is_array( $calculation ) ) {
			return;
		}

		$scope = (string) Arr::get( $calculation, 'scope', 'configuration' );

		if ( 'cabinet' === $scope ) {
			foreach ( array_keys( $context->input->cabinets ) as $index ) {
				if ( $this->evaluator->rule_applies( $rule, $context, $index ) ) {
					$this->apply_calculation( $rule, $calculation, $context, $index );
				}
			}

			return;
		}

		if ( $this->evaluator->rule_applies( $rule, $context ) ) {
			$this->apply_calculation( $rule, $calculation, $context );
		}
	}

	/**
	 * Apply rule calculation and add line item.
	 *
	 * @param \KitchenConfiguratorPro\Domain\Entities\PricingRule $rule          Rule.
	 * @param array<string, mixed>                                $calculation   Calculation config.
	 * @param CalculationContext                                  $context       Context.
	 * @param int|null                                            $cabinet_index Cabinet index.
	 * @return void
	 */
	private function apply_calculation( $rule, array $calculation, CalculationContext $context, ?int $cabinet_index = null ): void {
		$amount = $this->resolve_amount( $calculation, $context, $cabinet_index );

		if ( null === $amount || $amount->is_zero() ) {
			return;
		}

		$rule_type = PricingRuleType::tryFrom( $rule->rule_type ) ?? PricingRuleType::SURCHARGE;

		$final = match ( $rule_type ) {
			PricingRuleType::DISCOUNT   => Money::from( 0, $context->currency )->subtract( $amount ),
			PricingRuleType::SURCHARGE,
			PricingRuleType::FIXED      => $amount,
			PricingRuleType::MULTIPLIER => $context->subtotal->multiply( (float) Arr::get( $calculation, 'factor', 1 ) - 1 ),
		};

		$label = (string) Arr::get( $calculation, 'label', $rule->name );

		$this->add_line(
			$context,
			'pricing_rule',
			$rule->id,
			$label,
			$final,
			1,
			array(
				array(
					'rule'   => 'pricing_rule_' . $rule->id,
					'amount' => (float) $final->amount,
					'label'  => $label,
				),
			)
		);
	}

	/**
	 * Resolve monetary amount from calculation JSON.
	 *
	 * @param array<string, mixed> $calculation   Calculation config.
	 * @param CalculationContext   $context       Context.
	 * @param int|null             $cabinet_index Cabinet index.
	 * @return Money|null
	 */
	private function resolve_amount( array $calculation, CalculationContext $context, ?int $cabinet_index ): ?Money {
		$type = (string) Arr::get( $calculation, 'type', 'fixed' );

		return match ( $type ) {
			'fixed'   => Money::from( (string) Arr::get( $calculation, 'amount', 0 ), $context->currency ),
			'percent' => $this->percent_amount( $calculation, $context ),
			'per_mm'  => $this->per_mm_amount( $calculation, $context, $cabinet_index ),
			default   => null,
		};
	}

	/**
	 * Calculate percentage-based amount.
	 *
	 * @param array<string, mixed> $calculation Calculation config.
	 * @param CalculationContext   $context     Context.
	 * @return Money
	 */
	private function percent_amount( array $calculation, CalculationContext $context ): Money {
		$percent = (float) Arr::get( $calculation, 'percent', 0 );
		$of      = (string) Arr::get( $calculation, 'of', 'subtotal' );
		$base    = 'subtotal' === $of ? $context->subtotal : Money::zero( $context->currency );

		return $base->percentage( $percent );
	}

	/**
	 * Calculate per-mm amount from a dimension field.
	 *
	 * @param array<string, mixed> $calculation   Calculation config.
	 * @param CalculationContext   $context       Context.
	 * @param int|null             $cabinet_index Cabinet index.
	 * @return Money
	 */
	private function per_mm_amount( array $calculation, CalculationContext $context, ?int $cabinet_index ): Money {
		$field = (string) Arr::get( $calculation, 'field', 'cabinet.width' );
		$rate  = (float) Arr::get( $calculation, 'rate', 0 );
		$base  = (int) Arr::get( $calculation, 'base', 0 );
		$value = 0;

		if ( str_starts_with( $field, 'cabinet.' ) && null !== $cabinet_index ) {
			$axis  = substr( $field, strlen( 'cabinet.' ) );
			$dims  = $context->cabinet_dimensions( $cabinet_index );
			$value = match ( $axis ) {
				'width'  => $dims->width,
				'height' => $dims->height,
				'depth'  => $dims->depth,
				default  => 0,
			};
		}

		$diff = max( 0, $value - $base );

		return Money::from( $diff * $rate, $context->currency );
	}
}
