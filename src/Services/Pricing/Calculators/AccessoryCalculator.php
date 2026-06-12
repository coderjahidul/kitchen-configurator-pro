<?php
/**
 * Accessory price calculator.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing\Calculators;

use KitchenConfiguratorPro\Domain\ValueObjects\Money;
use KitchenConfiguratorPro\Repositories\AccessoryRepository;
use KitchenConfiguratorPro\Services\Pricing\AbstractCalculator;
use KitchenConfiguratorPro\Services\Pricing\CalculationContext;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Applies accessory prices (per-cabinet and per-kitchen).
 */
final class AccessoryCalculator extends AbstractCalculator {

	/**
	 * @param AccessoryRepository $accessories Accessory repository.
	 */
	public function __construct(
		private readonly AccessoryRepository $accessories
	) {
	}

	/**
	 * {@inheritDoc}
	 */
	public function priority(): int {
		return 50;
	}

	/**
	 * {@inheritDoc}
	 */
	public function calculate( CalculationContext $context ): void {
		$kitchen_accessories = array();

		foreach ( $context->input->cabinets as $item ) {
			$accessory_ids = Arr::get( $item, 'accessories', array() );

			if ( ! is_array( $accessory_ids ) ) {
				continue;
			}

			foreach ( $accessory_ids as $accessory_id ) {
				$id = (int) $accessory_id;

				if ( $id <= 0 ) {
					continue;
				}

				$accessory = $context->accessories[ $id ] ?? $this->accessories->find( $id );

				if ( null === $accessory ) {
					continue;
				}

				$context->accessories[ $id ] = $accessory;
				$price                         = Money::from( $accessory->price, $context->currency );

				if ( $accessory->is_per_cabinet ) {
					$this->add_line(
						$context,
						'accessory',
						$accessory->id,
						$accessory->name,
						$price,
						1,
						$this->breakdown_entry( array(), 'accessory_price', $price )
					);
				} elseif ( ! isset( $kitchen_accessories[ $id ] ) ) {
					$kitchen_accessories[ $id ] = $accessory;
				}
			}
		}

		$global_accessories = Arr::get( $context->input->global_options, 'accessories', array() );

		if ( is_array( $global_accessories ) ) {
			foreach ( $global_accessories as $accessory_id ) {
				$id = (int) $accessory_id;

				if ( $id <= 0 ) {
					continue;
				}

				$accessory = $context->accessories[ $id ] ?? $this->accessories->find( $id );

				if ( null !== $accessory ) {
					$context->accessories[ $id ] = $accessory;
					$kitchen_accessories[ $id ]  = $accessory;
				}
			}
		}

		foreach ( $kitchen_accessories as $accessory ) {
			$price = Money::from( $accessory->price, $context->currency );

			$this->add_line(
				$context,
				'accessory',
				$accessory->id,
				$accessory->name,
				$price,
				1,
				$this->breakdown_entry( array(), 'accessory_price', $price )
			);
		}
	}
}
