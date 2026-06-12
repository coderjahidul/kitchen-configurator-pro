<?php
/**
 * Server-side pricing engine.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing;

use KitchenConfiguratorPro\Contracts\PricingCalculatorInterface;
use KitchenConfiguratorPro\Domain\DTO\ConfigurationInput;
use KitchenConfiguratorPro\Domain\DTO\PricingSnapshot;
use KitchenConfiguratorPro\Domain\ValueObjects\Money;
use KitchenConfiguratorPro\Services\ValidationService;

/**
 * Orchestrates validation, catalog resolution, and calculator pipeline.
 */
final class PricingEngine {

	/**
	 * @param ValidationService              $validator       Configuration validator.
	 * @param CatalogContextBuilder          $context_builder Catalog context builder.
	 * @param PriceHashGenerator             $hash_generator  Hash generator.
	 * @param array<int, PricingCalculatorInterface> $calculators     Pricing calculators.
	 */
	public function __construct(
		private readonly ValidationService $validator,
		private readonly CatalogContextBuilder $context_builder,
		private readonly PriceHashGenerator $hash_generator,
		private readonly array $calculators
	) {
	}

	/**
	 * Calculate pricing for a configuration.
	 *
	 * @param ConfigurationInput $input Configuration input.
	 * @return PricingSnapshot
	 */
	public function calculate( ConfigurationInput $input ): PricingSnapshot {
		$this->validator->validate_configuration( $input );

		$settings = get_option(
			'kcp_settings',
			array(
				'currency' => 'EUR',
				'vat_rate' => 0,
			)
		);

		$currency = (string) ( $settings['currency'] ?? 'EUR' );
		$vat_rate = (float) ( $settings['vat_rate'] ?? 0 );

		$context = $this->context_builder->build( $input, $currency );

		$calculators = $this->calculators;

		usort(
			$calculators,
			static fn ( PricingCalculatorInterface $a, PricingCalculatorInterface $b ): int => $a->priority() <=> $b->priority()
		);

		foreach ( $calculators as $calculator ) {
			$calculator->calculate( $context );
		}

		$subtotal = $context->subtotal;
		$tax      = $subtotal->percentage( $vat_rate );
		$total    = $subtotal->add( $tax );

		$calculated_at = gmdate( 'c' );

		$snapshot_data = array(
			'calculated_at' => $calculated_at,
			'currency'      => $currency,
			'line_items'    => array_map(
				static fn ( $item ) => $item->to_array(),
				$context->line_items
			),
			'subtotal'      => (float) $subtotal->amount,
			'tax'           => (float) $tax->amount,
			'total'         => (float) $total->amount,
		);

		$price_hash = $this->hash_generator->generate( $snapshot_data );

		return new PricingSnapshot(
			$calculated_at,
			$currency,
			$context->line_items,
			$subtotal,
			$tax,
			$total,
			$price_hash
		);
	}

	/**
	 * Calculate from raw array payload.
	 *
	 * @param array<string, mixed> $data Configuration data.
	 * @return PricingSnapshot
	 */
	public function calculate_from_array( array $data ): PricingSnapshot {
		return $this->calculate( ConfigurationInput::from_array( $data ) );
	}

	/**
	 * Verify a price hash against a configuration.
	 *
	 * @param ConfigurationInput $input      Configuration input.
	 * @param string             $price_hash Expected hash string.
	 * @return bool
	 */
	public function verify_price_hash( ConfigurationInput $input, string $price_hash ): bool {
		$snapshot = $this->calculate( $input );

		return hash_equals( $snapshot->price_hash->to_string(), $price_hash );
	}
}
