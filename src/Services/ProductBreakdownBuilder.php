<?php
/**
 * Builds storefront product breakdown parts for the cart.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Domain\Entities\ProductPreset;

/**
 * Resolves per-product part lines from preset product_options.
 */
final class ProductBreakdownBuilder {

	/**
	 * Cart item meta key for resolved breakdown parts.
	 */
	public const META_PARTS = 'kcp_breakdown_parts';

	/**
	 * Cart item meta key for calculated breakdown total.
	 */
	public const META_TOTAL = 'kcp_breakdown_total';

	/**
	 * Cart item meta key for the cart group title.
	 */
	public const META_GROUP_TITLE = 'kcp_group_title';

	/**
	 * Get raw part definitions from a product preset.
	 *
	 * @param ProductPreset $preset Product preset.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_part_definitions( ProductPreset $preset ): array {
		$options = $preset->product_options();
		$parts   = $options['parts'] ?? array();

		return is_array( $parts ) ? $parts : array();
	}

	/**
	 * Whether a preset defines storefront breakdown parts.
	 *
	 * @param ProductPreset $preset Product preset.
	 * @return bool
	 */
	public function has_parts( ProductPreset $preset ): bool {
		return ! empty( $this->get_part_definitions( $preset ) );
	}

	/**
	 * Resolve breakdown parts and total for selected storefront options.
	 *
	 * @param array<string, mixed> $options   Built product options.
	 * @param string               $color_id  Selected color ID.
	 * @param string               $height_id Selected height ID.
	 * @return array{parts: array<int, array<string, mixed>>, surcharges: array<int, array<string, mixed>>, total: float}
	 */
	public function resolve( array $options, string $color_id, string $height_id ): array {
		$definitions = is_array( $options['parts'] ?? null ) ? $options['parts'] : array();
		$colors      = is_array( $options['colors'] ?? null ) ? $options['colors'] : array();
		$heights     = is_array( $options['heights'] ?? null ) ? $options['heights'] : array();
		$parts       = array();

		foreach ( $definitions as $definition ) {
			if ( ! is_array( $definition ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $definition['id'] ?? '' ) );

			if ( '' === $id ) {
				continue;
			}

			$price = (float) ( $definition['price'] ?? 0 );
			$height_prices = is_array( $definition['height_prices'] ?? null ) ? $definition['height_prices'] : array();

			if ( '' !== $height_id && isset( $height_prices[ $height_id ] ) ) {
				$price = (float) $height_prices[ $height_id ];
			}

			$parts[] = array(
				'key'         => $id,
				'id'          => $id,
				'label'       => (string) ( $definition['label'] ?? '' ),
				'description' => (string) ( $definition['description'] ?? '' ),
				'image_url'   => esc_url_raw( (string) ( $definition['image_url'] ?? '' ) ),
				'price'       => $price,
				'editable'    => ! empty( $definition['editable'] ),
			);
		}

		$color_modifier  = $this->option_modifier( $colors, $color_id );
		$height_modifier = $this->option_modifier( $heights, $height_id );
		$surcharges      = array();

		if ( $color_modifier > 0 ) {
			$surcharges[] = array(
				'label' => $this->option_label( $colors, $color_id ),
				'price' => $color_modifier,
			);
		}

		if ( $height_modifier > 0 ) {
			$surcharges[] = array(
				'label' => $this->option_label( $heights, $height_id ),
				'price' => $height_modifier,
			);
		}

		$parts_total = array_sum(
			array_map(
				static fn ( array $part ): float => (float) ( $part['price'] ?? 0 ),
				$parts
			)
		);

		$surcharge_total = array_sum(
			array_map(
				static fn ( array $line ): float => (float) ( $line['price'] ?? 0 ),
				$surcharges
			)
		);

		return array(
			'parts'      => $parts,
			'surcharges' => $surcharges,
			'total'      => $parts_total + $surcharge_total,
		);
	}

	/**
	 * Calculate total from stored cart parts and surcharges.
	 *
	 * @param array<int, array<string, mixed>> $parts      Resolved parts.
	 * @param array<int, array<string, mixed>> $surcharges Surcharge lines.
	 * @return float
	 */
	public function calculate_total( array $parts, array $surcharges = array() ): float {
		$parts_total = array_sum(
			array_map(
				static fn ( array $part ): float => (float) ( $part['price'] ?? 0 ),
				$parts
			)
		);

		$surcharge_total = array_sum(
			array_map(
				static fn ( array $line ): float => (float) ( $line['price'] ?? 0 ),
				$surcharges
			)
		);

		return $parts_total + $surcharge_total;
	}

	/**
	 * Duplicate a part inside a cart breakdown.
	 *
	 * @param array<int, array<string, mixed>> $parts   Existing parts.
	 * @param string                           $part_key Part key to duplicate.
	 * @return array<int, array<string, mixed>>
	 */
	public function duplicate_part( array $parts, string $part_key ): array {
		foreach ( $parts as $part ) {
			if ( (string) ( $part['key'] ?? '' ) !== $part_key ) {
				continue;
			}

			$copy            = $part;
			$copy['key']     = sanitize_key( (string) ( $part['id'] ?? 'part' ) ) . '-' . wp_generate_password( 6, false, false );
			$copy['copy_of'] = $part_key;
			$parts[]         = $copy;
			break;
		}

		return $parts;
	}

	/**
	 * Remove a part from a cart breakdown.
	 *
	 * @param array<int, array<string, mixed>> $parts    Existing parts.
	 * @param string                           $part_key Part key to remove.
	 * @return array<int, array<string, mixed>>
	 */
	public function remove_part( array $parts, string $part_key ): array {
		return array_values(
			array_filter(
				$parts,
				static fn ( array $part ): bool => (string) ( $part['key'] ?? '' ) !== $part_key
			)
		);
	}

	/**
	 * Read option price modifier.
	 *
	 * @param array<int, mixed> $options Option rows.
	 * @param string            $id      Selected option ID.
	 * @return float
	 */
	private function option_modifier( array $options, string $id ): float {
		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			if ( sanitize_key( (string) ( $option['id'] ?? '' ) ) === $id ) {
				return (float) ( $option['price_modifier'] ?? 0 );
			}
		}

		return 0.0;
	}

	/**
	 * Read option label.
	 *
	 * @param array<int, mixed> $options Option rows.
	 * @param string            $id      Selected option ID.
	 * @return string
	 */
	private function option_label( array $options, string $id ): string {
		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			if ( sanitize_key( (string) ( $option['id'] ?? '' ) ) === $id ) {
				return (string) ( $option['label'] ?? $id );
			}
		}

		return $id;
	}
}
