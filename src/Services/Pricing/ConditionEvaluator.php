<?php
/**
 * Pricing rule condition evaluator.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing;

use KitchenConfiguratorPro\Domain\Entities\Cabinet;
use KitchenConfiguratorPro\Domain\Entities\Material;
use KitchenConfiguratorPro\Domain\Entities\PricingRule;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Evaluates pricing rule conditions_json against calculation context.
 */
final class ConditionEvaluator {

	/**
	 * Check if a rule applies to the current context.
	 *
	 * @param PricingRule        $rule    Pricing rule.
	 * @param CalculationContext $context Calculation context.
	 * @param int|null           $cabinet_index Optional cabinet index for per-cabinet rules.
	 * @return bool
	 */
	public function rule_applies( PricingRule $rule, CalculationContext $context, ?int $cabinet_index = null ): bool {
		if ( ! $rule->is_active ) {
			return false;
		}

		$now = gmdate( 'Y-m-d H:i:s' );

		if ( null !== $rule->valid_from && $now < $rule->valid_from ) {
			return false;
		}

		if ( null !== $rule->valid_until && $now > $rule->valid_until ) {
			return false;
		}

		if ( null !== $rule->entity_type && null !== $rule->entity_id ) {
			if ( ! $this->entity_matches( $rule, $context, $cabinet_index ) ) {
				return false;
			}
		}

		$conditions = json_decode( $rule->conditions_json, true );

		if ( ! is_array( $conditions ) || empty( $conditions ) ) {
			return true;
		}

		return $this->evaluate_group( $conditions, $context, $cabinet_index );
	}

	/**
	 * Evaluate a condition group.
	 *
	 * @param array<string, mixed> $group         Condition group.
	 * @param CalculationContext   $context       Context.
	 * @param int|null             $cabinet_index Cabinet index.
	 * @return bool
	 */
	private function evaluate_group( array $group, CalculationContext $context, ?int $cabinet_index ): bool {
		if ( isset( $group['all'] ) && is_array( $group['all'] ) ) {
			foreach ( $group['all'] as $condition ) {
				if ( ! is_array( $condition ) || ! $this->evaluate_condition( $condition, $context, $cabinet_index ) ) {
					return false;
				}
			}

			return true;
		}

		if ( isset( $group['any'] ) && is_array( $group['any'] ) ) {
			foreach ( $group['any'] as $condition ) {
				if ( is_array( $condition ) && $this->evaluate_condition( $condition, $context, $cabinet_index ) ) {
					return true;
				}
			}

			return false;
		}

		return $this->evaluate_condition( $group, $context, $cabinet_index );
	}

	/**
	 * Evaluate a single condition.
	 *
	 * @param array<string, mixed> $condition     Condition.
	 * @param CalculationContext   $context       Context.
	 * @param int|null             $cabinet_index Cabinet index.
	 * @return bool
	 */
	private function evaluate_condition( array $condition, CalculationContext $context, ?int $cabinet_index ): bool {
		$field    = (string) Arr::get( $condition, 'field', '' );
		$operator = (string) Arr::get( $condition, 'operator', '=' );
		$expected = Arr::get( $condition, 'value' );
		$actual   = $this->resolve_field_value( $field, $context, $cabinet_index );

		return match ( $operator ) {
			'='       => $actual == $expected,
			'!='      => $actual != $expected,
			'>'       => (float) $actual > (float) $expected,
			'<'       => (float) $actual < (float) $expected,
			'>='      => (float) $actual >= (float) $expected,
			'<='      => (float) $actual <= (float) $expected,
			'in'      => is_array( $expected ) && in_array( $actual, $expected, false ),
			default   => false,
		};
	}

	/**
	 * Resolve a dotted field path to a value.
	 *
	 * @param string             $field         Field path.
	 * @param CalculationContext $context       Context.
	 * @param int|null           $cabinet_index Cabinet index.
	 * @return mixed
	 */
	private function resolve_field_value( string $field, CalculationContext $context, ?int $cabinet_index ): mixed {
		if ( str_starts_with( $field, 'configuration.' ) ) {
			$key = substr( $field, strlen( 'configuration.' ) );

			return match ( $key ) {
				'layout_id'     => $context->input->layout_id,
				'cabinet_count' => $context->cabinet_count(),
				default         => null,
			};
		}

		if ( str_starts_with( $field, 'global.' ) ) {
			$key = substr( $field, strlen( 'global.' ) );

			return $context->global_option( $key );
		}

		if ( str_starts_with( $field, 'cabinet.' ) && null !== $cabinet_index ) {
			$key  = substr( $field, strlen( 'cabinet.' ) );
			$item = $context->cabinet_item( $cabinet_index );
			$cabinet_id = (int) Arr::get( $item, 'cabinet_id', 0 );
			$cabinet    = $context->cabinets[ $cabinet_id ] ?? null;

			return match ( $key ) {
				'width'       => $context->cabinet_dimensions( $cabinet_index )->width,
				'height'      => $context->cabinet_dimensions( $cabinet_index )->height,
				'depth'       => $context->cabinet_dimensions( $cabinet_index )->depth,
				'category_id' => $cabinet instanceof Cabinet ? $cabinet->category_id : null,
				'id'          => $cabinet_id,
				default       => Arr::get( $item, $key ),
			};
		}

		if ( str_starts_with( $field, 'material.' ) && null !== $cabinet_index ) {
			$key         = substr( $field, strlen( 'material.' ) );
			$material_id = (int) Arr::get( $context->cabinet_item( $cabinet_index ), 'material_id', 0 );
			$material    = $context->materials[ $material_id ] ?? null;

			if ( ! $material instanceof Material ) {
				return null;
			}

			return match ( $key ) {
				'type' => $material->material_type,
				'id'   => $material->id,
				default => null,
			};
		}

		return null;
	}

	/**
	 * Check polymorphic entity scope on a rule.
	 *
	 * @param PricingRule        $rule          Rule.
	 * @param CalculationContext $context       Context.
	 * @param int|null           $cabinet_index Cabinet index.
	 * @return bool
	 */
	private function entity_matches( PricingRule $rule, CalculationContext $context, ?int $cabinet_index ): bool {
		$entity_type = (string) $rule->entity_type;
		$entity_id   = (int) $rule->entity_id;

		return match ( $entity_type ) {
			'layout'  => $context->input->layout_id === $entity_id,
			'cabinet' => null !== $cabinet_index
				&& (int) Arr::get( $context->cabinet_item( $cabinet_index ), 'cabinet_id', 0 ) === $entity_id,
			'material' => null !== $cabinet_index
				&& (int) Arr::get( $context->cabinet_item( $cabinet_index ), 'material_id', 0 ) === $entity_id,
			'worktop' => (int) $context->global_option( 'worktop_id', 0 ) === $entity_id,
			'plinth'  => (int) $context->global_option( 'plinth_id', 0 ) === $entity_id,
			'global'  => true,
			default   => true,
		};
	}
}
