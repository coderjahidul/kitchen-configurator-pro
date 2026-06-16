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
				'key'           => $id,
				'id'            => $id,
				'label'         => (string) ( $definition['label'] ?? '' ),
				'description'   => (string) ( $definition['description'] ?? '' ),
				'image_url'     => esc_url_raw( (string) ( $definition['image_url'] ?? '' ) ),
				'price'         => $price,
				'editable'      => ! empty( $definition['editable'] ),
				'selected_item' => sanitize_key( (string) ( $definition['selected_item'] ?? '' ) ),
				'items'         => is_array( $definition['items'] ?? null ) ? $definition['items'] : array(),
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
	 * Apply a selected variant item to a cart breakdown part.
	 *
	 * @param array<string, mixed>             $part  Existing cart part row.
	 * @param string                           $item_id Selected item ID.
	 * @param array<int, array<string, mixed>> $items Available item rows.
	 * @return array<string, mixed>|null
	 */
	public function apply_part_item( array $part, string $item_id, array $items ): ?array {
		if ( '' === $item_id ) {
			return null;
		}

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( sanitize_key( (string) ( $item['id'] ?? '' ) ) !== $item_id ) {
				continue;
			}

			$part['selected_item'] = $item_id;
			$part['price']         = (float) ( $item['price'] ?? $part['price'] ?? 0 );
			$part['description']   = sanitize_text_field( (string) ( $item['description'] ?? $item['value'] ?? $part['description'] ?? '' ) );

			$image_url = esc_url_raw( (string) ( $item['image_url'] ?? '' ) );

			if ( '' !== $image_url ) {
				$part['image_url'] = $image_url;
			}

			return $part;
		}

		return null;
	}

	/**
	 * Duplicate a part inside a cart breakdown.
	 *
	 * @param array<int, array<string, mixed>> $parts      Existing parts.
	 * @param string                           $part_key    Part key to duplicate.
	 * @param int                              $part_index  Exact row index from UI.
	 * @return array<int, array<string, mixed>>
	 */
	public function duplicate_part( array $parts, string $part_key, int $part_index = -1 ): array {
		$target_part = null;
		$part_key    = sanitize_key( $part_key );

		if ( $part_index >= 0 && isset( $parts[ $part_index ] ) && is_array( $parts[ $part_index ] ) ) {
			$candidate = $parts[ $part_index ];

			if ( sanitize_key( (string) ( $candidate['key'] ?? '' ) ) === $part_key ) {
				$target_part = $candidate;
			}
		}

		if ( ! is_array( $target_part ) ) {
			foreach ( $parts as $part ) {
				if ( sanitize_key( (string) ( $part['key'] ?? '' ) ) !== $part_key ) {
					continue;
				}

				$target_part = $part;
				break;
			}
		}

		if ( is_array( $target_part ) ) {
			$copy            = $target_part;
			$copy['key']     = sanitize_key(
				sanitize_key( (string) ( $target_part['id'] ?? 'part' ) ) . '-' . wp_generate_password( 6, false, false )
			);
			$copy['copy_of'] = $part_key;
			$parts[]         = $copy;
		}

		return $parts;
	}

	/**
	 * Remove a part from a cart breakdown.
	 *
	 * @param array<int, array<string, mixed>> $parts      Existing parts.
	 * @param string                           $part_key    Part key to remove.
	 * @param int                              $part_index  Exact row index from UI.
	 * @return array<int, array<string, mixed>>
	 */
	public function remove_part( array $parts, string $part_key, int $part_index = -1 ): array {
		$part_key = sanitize_key( $part_key );

		if ( $part_index >= 0 && isset( $parts[ $part_index ] ) && is_array( $parts[ $part_index ] ) ) {
			$candidate = $parts[ $part_index ];

			if ( sanitize_key( (string) ( $candidate['key'] ?? '' ) ) === $part_key ) {
				unset( $parts[ $part_index ] );

				return array_values( $parts );
			}
		}

		return array_values(
			array_filter(
				$parts,
				static fn ( array $part ): bool => sanitize_key( (string) ( $part['key'] ?? '' ) ) !== $part_key
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
