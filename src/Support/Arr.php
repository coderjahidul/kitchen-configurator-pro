<?php
/**
 * Array helper utilities.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Support;

/**
 * Array manipulation helpers.
 */
final class Arr {

	/**
	 * Get a value from an array with default.
	 *
	 * @param array<string, mixed> $array   Source array.
	 * @param string               $key     Array key.
	 * @param mixed                $default Default value.
	 * @return mixed
	 */
	public static function get( array $array, string $key, mixed $default = null ): mixed {
		return array_key_exists( $key, $array ) ? $array[ $key ] : $default;
	}

	/**
	 * Convert entity object or array to array.
	 *
	 * @param mixed $item Entity or array.
	 * @return array<string, mixed>
	 */
	public static function to_array( mixed $item ): array {
		if ( is_array( $item ) ) {
			return $item;
		}

		if ( is_object( $item ) && method_exists( $item, 'to_array' ) ) {
			return $item->to_array();
		}

		return array();
	}
}
