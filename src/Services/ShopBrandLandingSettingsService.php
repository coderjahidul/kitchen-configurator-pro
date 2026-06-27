<?php
/**
 * Per-category brand landing page settings (WooCommerce product_cat term meta).
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

/**
 * Reads and normalizes brand landing content for root product categories.
 */
final class ShopBrandLandingSettingsService {

	public const META_KEY = 'kcp_brand_landing';

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'hero_title'       => __( 'gemaakt voor het leven', 'kitchen-configurator-pro' ),
			'hero_cta_label'   => __( 'bekijk ons assortiment', 'kitchen-configurator-pro' ),
			'hero_cta_url'     => '#kcp-brand-products',
			'hero_image_id'    => 0,
			'hero_badge'       => '',
			'usps'             => array(
				__( 'groot assortiment in onze winkel en online', 'kitchen-configurator-pro' ),
				__( 'Deens design gemaakt voor het leven', 'kitchen-configurator-pro' ),
				__( 'geen verzendkosten', 'kitchen-configurator-pro' ),
			),
			'popular_heading'  => __( 'populaire producten', 'kitchen-configurator-pro' ),
			'back_label'       => __( 'stap terug', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_for_term( \WP_Term $term ): array {
		$raw = get_term_meta( (int) $term->term_id, self::META_KEY, true );

		return self::normalize( is_array( $raw ) ? $raw : array(), $term );
	}

	/**
	 * Resolve hero image URL for a brand root category.
	 */
	public static function get_hero_image_url( \WP_Term $term, array $settings, array $spotlight_products = array() ): string {
		$hero_image_id = (int) ( $settings['hero_image_id'] ?? 0 );

		if ( $hero_image_id > 0 ) {
			$url = wp_get_attachment_image_url( $hero_image_id, 'large' );

			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		$thumbnail_id = (int) get_term_meta( (int) $term->term_id, 'thumbnail_id', true );

		if ( $thumbnail_id > 0 ) {
			$url = wp_get_attachment_image_url( $thumbnail_id, 'large' );

			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		if ( ! empty( $spotlight_products ) ) {
			$first = $spotlight_products[0];

			if ( $first instanceof \WC_Product ) {
				$image_id = (int) $first->get_image_id();

				if ( $image_id > 0 ) {
					$url = wp_get_attachment_image_url( $image_id, 'large' );

					if ( is_string( $url ) && '' !== $url ) {
						return $url;
					}
				}
			}
		}

		return '';
	}

	/**
	 * @param array<string, mixed> $post Raw POST values from category form.
	 * @return array<string, mixed>
	 */
	public static function sanitize_post( array $post ): array {
		$usps = array();

		for ( $i = 1; $i <= 3; $i++ ) {
			$usps[] = sanitize_text_field( wp_unslash( (string) ( $post[ 'kcp_brand_usp_' . $i ] ?? '' ) ) );
		}

		$cta_url = esc_url_raw( wp_unslash( (string) ( $post['kcp_brand_hero_cta_url'] ?? '' ) ) );

		if ( '' === $cta_url ) {
			$cta_url = '#kcp-brand-products';
		}

		return self::normalize(
			array(
				'hero_title'      => sanitize_text_field( wp_unslash( (string) ( $post['kcp_brand_hero_title'] ?? '' ) ) ),
				'hero_cta_label'  => sanitize_text_field( wp_unslash( (string) ( $post['kcp_brand_hero_cta_label'] ?? '' ) ) ),
				'hero_cta_url'    => $cta_url,
				'hero_image_id'   => max( 0, (int) ( $post['kcp_brand_hero_image_id'] ?? 0 ) ),
				'hero_badge'      => sanitize_text_field( wp_unslash( (string) ( $post['kcp_brand_hero_badge'] ?? '' ) ) ),
				'usps'            => $usps,
				'popular_heading' => sanitize_text_field( wp_unslash( (string) ( $post['kcp_brand_popular_heading'] ?? '' ) ) ),
				'back_label'      => sanitize_text_field( wp_unslash( (string) ( $post['kcp_brand_back_label'] ?? '' ) ) ),
			)
		);
	}

	/**
	 * Persist settings for a product category term.
	 *
	 * @param array<string, mixed> $settings Sanitized settings.
	 */
	public static function save_for_term( int $term_id, array $settings ): void {
		update_term_meta( $term_id, self::META_KEY, self::normalize( $settings ) );
	}

	/**
	 * @param array<string, mixed> $raw Raw stored settings.
	 * @return array<string, mixed>
	 */
	private static function normalize( array $raw, ?\WP_Term $term = null ): array {
		$defaults = self::defaults();
		$usps     = is_array( $raw['usps'] ?? null ) ? $raw['usps'] : array();
		$merged   = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$value = trim( (string) ( $usps[ $i ] ?? '' ) );
			$merged[] = '' !== $value ? $value : (string) $defaults['usps'][ $i ];
		}

		$badge = trim( (string) ( $raw['hero_badge'] ?? '' ) );

		if ( '' === $badge && $term instanceof \WP_Term ) {
			$badge = strtolower( $term->name );
		}

		$hero_title = trim( (string) ( $raw['hero_title'] ?? '' ) );

		return array(
			'hero_title'      => '' !== $hero_title ? $hero_title : (string) $defaults['hero_title'],
			'hero_cta_label'  => self::text_or_default( $raw, 'hero_cta_label', $defaults ),
			'hero_cta_url'    => self::url_or_default( $raw, 'hero_cta_url', (string) $defaults['hero_cta_url'] ),
			'hero_image_id'   => max( 0, (int) ( $raw['hero_image_id'] ?? 0 ) ),
			'hero_badge'      => $badge,
			'usps'            => $merged,
			'popular_heading' => self::text_or_default( $raw, 'popular_heading', $defaults ),
			'back_label'      => self::text_or_default( $raw, 'back_label', $defaults ),
		);
	}

	/**
	 * @param array<string, mixed> $raw Raw values.
	 * @param array<string, mixed> $defaults Defaults.
	 */
	private static function text_or_default( array $raw, string $key, array $defaults ): string {
		$value = trim( (string) ( $raw[ $key ] ?? '' ) );

		return '' !== $value ? $value : (string) ( $defaults[ $key ] ?? '' );
	}

	/**
	 * @param array<string, mixed> $raw Raw values.
	 */
	private static function url_or_default( array $raw, string $key, string $fallback ): string {
		$value = esc_url_raw( (string) ( $raw[ $key ] ?? '' ) );

		return '' !== $value ? $value : $fallback;
	}
}
