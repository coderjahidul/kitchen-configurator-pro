<?php
/**
 * Builds the storefront view model for cart part editing.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Integration\WooCommerce\ShopPresenter;

/**
 * Prepares part item dropdown data for the cart part editor.
 */
final class PartEditViewBuilder {

	/**
	 * Build the part edit page view model.
	 *
	 * @param array<string, mixed> $context Part edit context from the presenter.
	 * @return array<string, mixed>
	 */
	public function build( array $context ): array {
		$items         = is_array( $context['items'] ?? null ) ? $context['items'] : array();
		$selected_item = sanitize_key( (string) ( $context['selected_item'] ?? '' ) );
		$parsed_items  = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $item['id'] ?? '' ) );

			if ( '' === $id ) {
				continue;
			}

			$label       = (string) ( $item['value'] ?? $item['label'] ?? '' );
			$description = (string) ( $item['description'] ?? '' );

			$parsed_items[] = array(
				'id'              => $id,
				'label'           => $label,
				'description'     => $description,
				'dropdown_label'  => $this->build_dropdown_label( $label, $description ),
				'image_url'       => esc_url_raw( (string) ( $item['image_url'] ?? '' ) ),
				'price'           => (float) ( $item['price'] ?? 0 ),
			);
		}

		if ( empty( $parsed_items ) ) {
			return array();
		}

		$selected = $this->find_item( $parsed_items, $selected_item ) ?? $parsed_items[0];

		$image_url = (string) ( $context['image_url'] ?? '' );

		if ( '' === $image_url ) {
			$image_url = (string) ( $selected['image_url'] ?? '' );
		}

		return array(
			'part_label'    => (string) ( $context['part_label'] ?? '' ),
			'cart_key'      => (string) ( $context['cart_key'] ?? '' ),
			'part_pos'      => (int) ( $context['part_pos'] ?? 0 ),
			'cart_url'      => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' ),
			'image_url'     => $image_url,
			'info_lines'    => $this->resolve_info_lines( $context ),
			'items'         => $parsed_items,
			'selected_item' => (string) ( $selected['id'] ?? '' ),
			'price_label'   => ShopPresenter::format_dutch_price( (float) ( $selected['price'] ?? 0 ) ),
			'items_json'    => wp_json_encode( $parsed_items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '[]',
		);
	}

	/**
	 * @param string $label       Item label.
	 * @param string $description Item description.
	 * @return string
	 */
	private function build_dropdown_label( string $label, string $description ): string {
		if ( '' !== $description ) {
			return $description;
		}

		return $label;
	}

	/**
	 * @param array<int, array<string, mixed>> $items Parsed items.
	 * @param string                           $item_id Selected item ID.
	 * @return array<string, mixed>|null
	 */
	private function find_item( array $items, string $item_id ): ?array {
		foreach ( $items as $item ) {
			if ( (string) ( $item['id'] ?? '' ) === $item_id ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $context Part edit context.
	 * @return array<int, string>
	 */
	private function resolve_info_lines( array $context ): array {
		$lines = is_array( $context['info_lines'] ?? null ) ? $context['info_lines'] : array();
		$lines = array_values(
			array_filter(
				array_map( static fn ( mixed $line ): string => sanitize_text_field( (string) $line ), $lines )
			)
		);

		if ( ! empty( $lines ) ) {
			return $lines;
		}

		return array(
			__( 'Wordt gebruikt om de zichtbare zijkanten van een kast af te werken.', 'kitchen-configurator-pro' ),
			__( 'Wordt geleverd in het door jou gekozen frontmateriaal.', 'kitchen-configurator-pro' ),
			__( 'Het materiaal is identiek aan het materiaal van de fronten.', 'kitchen-configurator-pro' ),
			__( 'Wordt geleverd zonder boringen.', 'kitchen-configurator-pro' ),
		);
	}
}
