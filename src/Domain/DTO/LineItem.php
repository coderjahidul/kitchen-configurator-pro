<?php
/**
 * Pricing line item DTO.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\DTO;

use KitchenConfiguratorPro\Domain\ValueObjects\Money;

/**
 * Single pricing line item with optional breakdown.
 */
final class LineItem {

	/**
	 * @param string               $type         Line type (cabinet, worktop, rule, etc.).
	 * @param int|null             $reference_id Related catalog entity ID.
	 * @param string               $label        Display label.
	 * @param int                  $quantity     Quantity.
	 * @param Money                $unit_price   Unit price.
	 * @param Money                $subtotal     Line subtotal.
	 * @param array<int, array<string, mixed>> $breakdown    Price breakdown entries.
	 */
	public function __construct(
		public readonly string $type,
		public readonly ?int $reference_id,
		public readonly string $label,
		public readonly int $quantity,
		public readonly Money $unit_price,
		public readonly Money $subtotal,
		public readonly array $breakdown = array()
	) {
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'type'         => $this->type,
			'reference_id' => $this->reference_id,
			'label'        => $this->label,
			'quantity'     => $this->quantity,
			'unit_price'   => (float) $this->unit_price->amount,
			'subtotal'     => (float) $this->subtotal->amount,
			'breakdown'    => $this->breakdown,
		);
	}
}
