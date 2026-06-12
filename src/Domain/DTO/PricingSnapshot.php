<?php
/**
 * Pricing snapshot DTO.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\DTO;

use KitchenConfiguratorPro\Domain\ValueObjects\Money;
use KitchenConfiguratorPro\Domain\ValueObjects\PriceHash;

/**
 * Server-calculated pricing result.
 */
final class PricingSnapshot {

	/**
	 * @param string               $calculated_at ISO 8601 timestamp.
	 * @param string               $currency      Currency code.
	 * @param array<int, LineItem> $line_items    Line items.
	 * @param Money                $subtotal      Subtotal before tax.
	 * @param Money                $tax           Tax amount.
	 * @param Money                $total         Total including tax.
	 * @param PriceHash            $price_hash    Integrity hash.
	 */
	public function __construct(
		public readonly string $calculated_at,
		public readonly string $currency,
		public readonly array $line_items,
		public readonly Money $subtotal,
		public readonly Money $tax,
		public readonly Money $total,
		public readonly PriceHash $price_hash
	) {
	}

	/**
	 * Convert to array (includes price_hash).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'calculated_at' => $this->calculated_at,
			'currency'      => $this->currency,
			'line_items'    => array_map(
				static fn ( LineItem $item ): array => $item->to_array(),
				$this->line_items
			),
			'subtotal'      => (float) $this->subtotal->amount,
			'tax'           => (float) $this->tax->amount,
			'total'         => (float) $this->total->amount,
			'price_hash'    => $this->price_hash->to_string(),
		);
	}

	/**
	 * Encode as JSON.
	 *
	 * @return string
	 */
	public function to_json(): string {
		$json = wp_json_encode( $this->to_array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		return false !== $json ? $json : '{}';
	}
}
