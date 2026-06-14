<?php
/**
 * Pricing rule repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

use KitchenConfiguratorPro\Domain\Entities\PricingRule;
use KitchenConfiguratorPro\Domain\Enums\PricingRuleType;

/**
 * @extends AbstractRepository<PricingRule>
 */
final class PricingRuleRepository extends AbstractRepository {

	/**
	 * Allowed ORDER BY columns.
	 *
	 * @var array<int, string>
	 */
	protected array $orderable_columns = array( 'id', 'name', 'priority', 'created_at', 'updated_at' );

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_pricing_rules';
	}

	/**
	 * {@inheritDoc}
	 */
	public function find_all( array $criteria = array(), string $order_by = 'priority', string $order = 'ASC' ): array {
		return parent::find_all( $criteria, $order_by, $order );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): PricingRule {
		return PricingRule::from_row( $row );
	}

	/**
	 * Find active rules ordered by priority.
	 *
	 * @return array<int, PricingRule>
	 */
	public function find_active_ordered(): array {
		return $this->find_all( array( 'is_active' => '1' ), 'priority', 'ASC' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		$rule_type = (string) ( $data['rule_type'] ?? PricingRuleType::SURCHARGE->value );

		$valid_types = array_map(
			static fn ( PricingRuleType $type ): string => $type->value,
			PricingRuleType::cases()
		);

		if ( ! in_array( $rule_type, $valid_types, true ) ) {
			$rule_type = PricingRuleType::SURCHARGE->value;
		}

		$entity_type = isset( $data['entity_type'] ) ? sanitize_key( (string) $data['entity_type'] ) : '';
		$entity_id   = isset( $data['entity_id'] ) && '' !== (string) $data['entity_id']
			? (int) $data['entity_id']
			: null;

		return array(
			'name'             => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'rule_type'        => $rule_type,
			'entity_type'      => '' === $entity_type ? null : $entity_type,
			'entity_id'        => $entity_id,
			'conditions_json'  => $this->require_json( (string) ( $data['conditions_json'] ?? '{}' ) ),
			'calculation_json' => $this->require_json( (string) ( $data['calculation_json'] ?? '{}' ) ),
			'priority'         => (int) ( $data['priority'] ?? 100 ),
			'is_active'        => $this->to_bool_int( $data['is_active'] ?? 1 ),
			'valid_from'       => $this->sanitize_datetime( $data['valid_from'] ?? null ),
			'valid_until'      => $this->sanitize_datetime( $data['valid_until'] ?? null ),
		);
	}

	/**
	 * Validate and return JSON string.
	 *
	 * @param string $json Raw JSON.
	 * @return string
	 */
	private function require_json( string $json ): string {
		$json = trim( $json );

		if ( '' === $json ) {
			return '{}';
		}

		json_decode( $json );

		return JSON_ERROR_NONE === json_last_error() ? $json : '{}';
	}

	/**
	 * Sanitize optional datetime.
	 *
	 * @param mixed $value Input value.
	 * @return string|null
	 */
	private function sanitize_datetime( mixed $value ): ?string {
		if ( null === $value || '' === (string) $value ) {
			return null;
		}

		$timestamp = strtotime( (string) $value );

		return false === $timestamp ? null : gmdate( 'Y-m-d H:i:s', $timestamp );
	}
}
