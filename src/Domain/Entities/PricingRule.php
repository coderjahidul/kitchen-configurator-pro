<?php
/**
 * Pricing rule entity.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Entities;

/**
 * Pricing rule catalog entity.
 */
final class PricingRule {

	/**
	 * @param int         $id               Primary key.
	 * @param string      $name             Rule name.
	 * @param string      $rule_type        Rule type.
	 * @param string|null $entity_type      Entity type.
	 * @param int|null    $entity_id        Entity ID.
	 * @param string      $conditions_json  Conditions JSON.
	 * @param string      $calculation_json Calculation JSON.
	 * @param int         $priority         Priority.
	 * @param bool        $is_active        Active flag.
	 * @param string|null $valid_from       Valid from datetime.
	 * @param string|null $valid_until      Valid until datetime.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $rule_type,
		public readonly ?string $entity_type,
		public readonly ?int $entity_id,
		public readonly string $conditions_json,
		public readonly string $calculation_json,
		public readonly int $priority,
		public readonly bool $is_active,
		public readonly ?string $valid_from,
		public readonly ?string $valid_until
	) {
	}

	/**
	 * Create from database row.
	 *
	 * @param array<string, mixed> $row Database row.
	 * @return self
	 */
	public static function from_row( array $row ): self {
		return new self(
			(int) $row['id'],
			(string) $row['name'],
			(string) $row['rule_type'],
			isset( $row['entity_type'] ) ? (string) $row['entity_type'] : null,
			isset( $row['entity_id'] ) ? (int) $row['entity_id'] : null,
			(string) $row['conditions_json'],
			(string) $row['calculation_json'],
			(int) $row['priority'],
			(bool) (int) $row['is_active'],
			isset( $row['valid_from'] ) ? (string) $row['valid_from'] : null,
			isset( $row['valid_until'] ) ? (string) $row['valid_until'] : null
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'               => $this->id,
			'name'             => $this->name,
			'rule_type'        => $this->rule_type,
			'entity_type'      => $this->entity_type,
			'entity_id'        => $this->entity_id,
			'conditions_json'  => $this->conditions_json,
			'calculation_json' => $this->calculation_json,
			'priority'         => $this->priority,
			'is_active'        => $this->is_active,
			'valid_from'       => $this->valid_from,
			'valid_until'      => $this->valid_until,
		);
	}
}
