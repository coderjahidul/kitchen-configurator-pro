<?php
/**
 * Pricing rule type enumeration.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Enums;

/**
 * Supported pricing rule types.
 */
enum PricingRuleType: string {

	case SURCHARGE   = 'surcharge';
	case DISCOUNT    = 'discount';
	case MULTIPLIER  = 'multiplier';
	case FIXED       = 'fixed';

	/**
	 * Human-readable labels for admin UI.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			self::SURCHARGE->value  => __( 'Surcharge', 'kitchen-configurator-pro' ),
			self::DISCOUNT->value   => __( 'Discount', 'kitchen-configurator-pro' ),
			self::MULTIPLIER->value => __( 'Multiplier', 'kitchen-configurator-pro' ),
			self::FIXED->value      => __( 'Fixed', 'kitchen-configurator-pro' ),
		);
	}
}
