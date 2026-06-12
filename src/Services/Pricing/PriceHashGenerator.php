<?php
/**
 * Price hash generator.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing;

use KitchenConfiguratorPro\Domain\DTO\PricingSnapshot;
use KitchenConfiguratorPro\Domain\ValueObjects\PriceHash;

/**
 * Generates and verifies pricing snapshot integrity hashes.
 */
final class PriceHashGenerator {

	/**
	 * Generate hash from snapshot data array (without price_hash key).
	 *
	 * @param array<string, mixed> $snapshot_data Snapshot data.
	 * @return PriceHash
	 */
	public function generate( array $snapshot_data ): PriceHash {
		return PriceHash::generate( $snapshot_data );
	}

	/**
	 * Verify a snapshot's price hash.
	 *
	 * @param PricingSnapshot $snapshot Snapshot to verify.
	 * @return bool
	 */
	public function verify( PricingSnapshot $snapshot ): bool {
		$data = $snapshot->to_array();
		$hash = self::generate( $data );

		return $snapshot->price_hash->equals( $hash );
	}
}
