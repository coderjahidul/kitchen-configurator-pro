<?php
/**
 * Builds KKF-style storefront options from WooCommerce variable products.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

/**
 * Maps native WooCommerce variation attributes to pill selector data.
 */
final class WooVariationOptionsBuilder {

	/**
	 * Whether a variable product can render pill selectors.
	 *
	 * @param \WC_Product $product Product.
	 * @return bool
	 */
	public function can_render( \WC_Product $product ): bool {
		if ( ! $product->is_type( 'variable' ) ) {
			return false;
		}

		$built = $this->build( $product );

		return ! empty( $built['colors'] ) || ! empty( $built['heights'] );
	}

	/**
	 * Build storefront option data from WooCommerce variations.
	 *
	 * @param \WC_Product $product Product.
	 * @return array<string, mixed>
	 */
	public function build( \WC_Product $product ): array {
		if ( ! $product->is_type( 'variable' ) ) {
			return array();
		}

		/** @var \WC_Product_Variable $product */
		$variations = $product->get_available_variations();
		$attributes = $product->get_variation_attributes();
		$defaults   = $product->get_default_attributes();

		if ( empty( $variations ) || empty( $attributes ) ) {
			return array();
		}

		$color_attr  = $this->find_attribute( $attributes, 'color' );
		$height_attr = $this->find_attribute( $attributes, 'height' );

		$default_color  = (string) ( $defaults[ $color_attr ] ?? '' );
		$default_height = (string) ( $defaults[ $height_attr ] ?? '' );

		$base_price = $this->resolve_base_price( $variations, $color_attr, $height_attr, $default_color, $default_height );

		return array(
			'color_attribute'  => $color_attr,
			'height_attribute' => $height_attr,
			'specs'            => array(
				'dimensions' => array(),
				'includes'   => array(),
			),
			'colors'           => $this->build_color_options( $variations, $color_attr, $default_color ),
			'heights'          => $this->build_height_options( $variations, $color_attr, $height_attr, $default_color, $default_height, $base_price ),
			'default_color'    => $default_color,
			'default_height'   => $default_height,
			'base_price'       => $base_price,
		);
	}

	/**
	 * @param array<string, array<int, string>> $attributes Variation attributes.
	 * @param string                            $type       color|height.
	 * @return string
	 */
	private function find_attribute( array $attributes, string $type ): string {
		foreach ( array_keys( $attributes ) as $attribute_name ) {
			$group = $this->classify_attribute( (string) $attribute_name );

			if ( $group === $type ) {
				return (string) $attribute_name;
			}
		}

		return '';
	}

	/**
	 * @param string $attribute_name Attribute name.
	 * @return string|null
	 */
	private function classify_attribute( string $attribute_name ): ?string {
		$slug = sanitize_title( $attribute_name );

		if (
			str_contains( $slug, 'frontkleur' )
			|| str_contains( $slug, 'front-kleur' )
			|| str_contains( $slug, 'color' )
			|| str_contains( $slug, 'kleur' )
		) {
			return 'color';
		}

		if ( str_contains( $slug, 'hoogte' ) || str_contains( $slug, 'height' ) ) {
			return 'height';
		}

		return null;
	}

	/**
	 * @param array<int, array<string, mixed>> $variations     Variations.
	 * @param string                           $color_attr     Color attribute.
	 * @param string                           $height_attr    Height attribute.
	 * @param string                           $default_color  Default color slug.
	 * @param string                           $default_height Default height slug.
	 * @return float
	 */
	private function resolve_base_price(
		array $variations,
		string $color_attr,
		string $height_attr,
		string $default_color,
		string $default_height
	): float {
		foreach ( $variations as $variation ) {
			$attrs = is_array( $variation['attributes'] ?? null ) ? $variation['attributes'] : array();

			if (
				( '' === $default_color || ( $attrs[ 'attribute_' . $color_attr ] ?? '' ) === $default_color )
				&& ( '' === $default_height || ( $attrs[ 'attribute_' . $height_attr ] ?? '' ) === $default_height )
			) {
				return (float) ( $variation['display_price'] ?? 0 );
			}
		}

		$prices = array_map(
			static fn ( array $variation ): float => (float) ( $variation['display_price'] ?? 0 ),
			$variations
		);

		return ! empty( $prices ) ? (float) min( $prices ) : 0.0;
	}

	/**
	 * @param array<int, array<string, mixed>> $variations    Variations.
	 * @param string                           $color_attr    Color attribute.
	 * @param string                           $default_color Default color slug.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_color_options( array $variations, string $color_attr, string $default_color ): array {
		if ( '' === $color_attr ) {
			return array();
		}

		$taxonomy = $this->attribute_taxonomy( $color_attr );
		$options  = array();
		$seen     = array();

		foreach ( $variations as $variation ) {
			$attrs = is_array( $variation['attributes'] ?? null ) ? $variation['attributes'] : array();
			$slug  = (string) ( $attrs[ 'attribute_' . $color_attr ] ?? '' );

			if ( '' === $slug || isset( $seen[ $slug ] ) ) {
				continue;
			}

			$seen[ $slug ] = true;
			$term          = '' !== $taxonomy ? get_term_by( 'slug', $slug, $taxonomy ) : false;
			$label         = $term instanceof \WP_Term ? $term->name : ucwords( str_replace( '-', ' ', $slug ) );
			$note          = $term instanceof \WP_Term ? (string) $term->description : '';
			$image         = $this->variation_image_url( $variation );

			if ( '' === $image && $term instanceof \WP_Term ) {
				$image = $this->term_image_url( (int) $term->term_id );
			}

			$options[] = array(
				'id'             => $slug,
				'label'          => $label,
				'image_url'      => $image,
				'hex_code'       => '',
				'price_modifier' => 0.0,
				'note'           => sanitize_text_field( $note ),
			);
		}

		if ( '' === $default_color && ! empty( $options ) ) {
			$default_color = (string) ( $options[0]['id'] ?? '' );
		}

		return $options;
	}

	/**
	 * @param array<int, array<string, mixed>> $variations      Variations.
	 * @param string                           $color_attr      Color attribute.
	 * @param string                           $height_attr     Height attribute.
	 * @param string                           $default_color   Default color slug.
	 * @param string                           $default_height  Default height slug.
	 * @param float                            $base_price      Base price.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_height_options(
		array $variations,
		string $color_attr,
		string $height_attr,
		string $default_color,
		string $default_height,
		float $base_price
	): array {
		if ( '' === $height_attr ) {
			return array();
		}

		$taxonomy = $this->attribute_taxonomy( $height_attr );
		$options  = array();
		$seen     = array();

		foreach ( $variations as $variation ) {
			$attrs = is_array( $variation['attributes'] ?? null ) ? $variation['attributes'] : array();
			$slug  = (string) ( $attrs[ 'attribute_' . $height_attr ] ?? '' );

			if ( '' === $slug || isset( $seen[ $slug ] ) ) {
				continue;
			}

			$seen[ $slug ] = true;
			$term          = '' !== $taxonomy ? get_term_by( 'slug', $slug, $taxonomy ) : false;
			$label         = $term instanceof \WP_Term ? $term->name : ucwords( str_replace( '-', ' ', $slug ) );
			$price         = $this->lowest_price_for_height( $variations, $color_attr, $height_attr, $default_color, $slug );

			$options[] = array(
				'id'             => $slug,
				'label'          => $label,
				'price_modifier' => max( 0, round( $price - $base_price, 0 ) ),
			);
		}

		return $options;
	}

	/**
	 * @param array<int, array<string, mixed>> $variations     Variations.
	 * @param string                           $color_attr     Color attribute.
	 * @param string                           $height_attr    Height attribute.
	 * @param string                           $default_color  Default color slug.
	 * @param string                           $height_slug    Height slug.
	 * @return float
	 */
	private function lowest_price_for_height(
		array $variations,
		string $color_attr,
		string $height_attr,
		string $default_color,
		string $height_slug
	): float {
		$prices = array();

		foreach ( $variations as $variation ) {
			$attrs = is_array( $variation['attributes'] ?? null ) ? $variation['attributes'] : array();

			if ( ( $attrs[ 'attribute_' . $height_attr ] ?? '' ) !== $height_slug ) {
				continue;
			}

			if ( '' !== $default_color && ( $attrs[ 'attribute_' . $color_attr ] ?? '' ) !== $default_color ) {
				continue;
			}

			$prices[] = (float) ( $variation['display_price'] ?? 0 );
		}

		if ( empty( $prices ) ) {
			foreach ( $variations as $variation ) {
				$attrs = is_array( $variation['attributes'] ?? null ) ? $variation['attributes'] : array();

				if ( ( $attrs[ 'attribute_' . $height_attr ] ?? '' ) === $height_slug ) {
					$prices[] = (float) ( $variation['display_price'] ?? 0 );
				}
			}
		}

		return ! empty( $prices ) ? (float) min( $prices ) : 0.0;
	}

	/**
	 * @param array<string, mixed> $variation Variation row.
	 * @return string
	 */
	private function variation_image_url( array $variation ): string {
		$image = is_array( $variation['image'] ?? null ) ? $variation['image'] : array();
		$url   = (string) ( $image['gallery_thumbnail_src'] ?? $image['thumb_src'] ?? $image['src'] ?? '' );

		return '' !== $url ? esc_url_raw( $url ) : '';
	}

	/**
	 * @param int $term_id Term ID.
	 * @return string
	 */
	private function term_image_url( int $term_id ): string {
		$thumbnail_id = (int) get_term_meta( $term_id, 'thumbnail_id', true );

		if ( $thumbnail_id <= 0 ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' );

		return is_string( $url ) ? esc_url_raw( $url ) : '';
	}

	/**
	 * @param string $attribute_name Attribute name.
	 * @return string
	 */
	private function attribute_taxonomy( string $attribute_name ): string {
		if ( str_starts_with( $attribute_name, 'pa_' ) ) {
			return $attribute_name;
		}

		if ( taxonomy_exists( 'pa_' . $attribute_name ) ) {
			return 'pa_' . $attribute_name;
		}

		return '';
	}
}
