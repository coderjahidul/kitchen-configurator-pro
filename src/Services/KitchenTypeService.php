<?php
/**
 * Kitchen type helpers (greep vs greeploos).
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

/**
 * Normalizes kitchen type values used across configurator steps.
 */
final class KitchenTypeService {

	public const TYPE_GREP      = 'greep';
	public const TYPE_GREEPLOOS = 'greeploos';
	public const QUERY_PARAM    = 'kcp_kitchen_type';

	/**
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			self::TYPE_GREP      => __( 'keuken met greep', 'kitchen-configurator-pro' ),
			self::TYPE_GREEPLOOS => __( 'keuken greeploos', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * @param string $value Raw kitchen type.
	 */
	public static function normalize( string $value ): string {
		$normalized = sanitize_key( $value );

		if ( self::TYPE_GREEPLOOS === $normalized ) {
			return self::TYPE_GREEPLOOS;
		}

		return self::TYPE_GREP;
	}

	/**
	 * @param string $kitchen_type Normalized kitchen type.
	 */
	public static function shows_handles( string $kitchen_type ): bool {
		return self::TYPE_GREP === self::normalize( $kitchen_type );
	}

	/**
	 * @param string $url          Target URL.
	 * @param string $kitchen_type Kitchen type slug.
	 */
	public static function append_query_param( string $url, string $kitchen_type ): string {
		$url = trim( $url );

		if ( '' === $url ) {
			return '';
		}

		return add_query_arg( self::QUERY_PARAM, self::normalize( $kitchen_type ), $url );
	}
}
