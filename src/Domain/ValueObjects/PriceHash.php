<?php
/**
 * Price hash value object.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\ValueObjects;

/**
 * SHA-256 integrity hash for pricing snapshots.
 */
final class PriceHash {

	/**
	 * @param string $value Hash value prefixed with algorithm.
	 */
	public function __construct(
		public readonly string $value
	) {
	}

	/**
	 * Generate hash from canonical snapshot data.
	 *
	 * @param array<string, mixed> $snapshot_data Snapshot without price_hash.
	 * @return self
	 */
	public static function generate( array $snapshot_data ): self {
		unset( $snapshot_data['price_hash'] );

		$canonical = wp_json_encode( $snapshot_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		if ( false === $canonical ) {
			$canonical = '';
		}

		return new self( 'sha256:' . hash( 'sha256', $canonical ) );
	}

	/**
	 * Check equality.
	 *
	 * @param self $other Other hash.
	 * @return bool
	 */
	public function equals( self $other ): bool {
		return hash_equals( $this->value, $other->value );
	}

	/**
	 * Convert to string.
	 *
	 * @return string
	 */
	public function to_string(): string {
		return $this->value;
	}
}
