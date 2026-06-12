<?php
/**
 * Material type enumeration.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Enums;

/**
 * Supported material types.
 */
enum MaterialType: string {

	case FRONT   = 'front';
	case CARCASS = 'carcass';
	case WORKTOP = 'worktop';
	case PLINTH  = 'plinth';

	/**
	 * Human-readable labels for admin UI.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			self::FRONT->value   => __( 'Front', 'kitchen-configurator-pro' ),
			self::CARCASS->value => __( 'Carcass', 'kitchen-configurator-pro' ),
			self::WORKTOP->value => __( 'Worktop', 'kitchen-configurator-pro' ),
			self::PLINTH->value  => __( 'Plinth', 'kitchen-configurator-pro' ),
		);
	}
}
