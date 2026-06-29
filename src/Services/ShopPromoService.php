<?php
/**
 * Shop page USP bar and promotional video tiles.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

/**
 * Reads and normalizes configurable shop promo content.
 */
final class ShopPromoService {

	public const TILES_SOURCE_CATEGORIES = 'categories';

	public const TILES_SOURCE_MANUAL = 'manual';

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'enabled'            => true,
			'tiles_source'       => self::TILES_SOURCE_CATEGORIES,
			'tiles_parent_slug'  => '',
			'tiles_limit'        => 0,
			'usps'               => array(
				array(
					'icon'  => 'cabinet',
					'label' => __( 'voorgemonteerde kasten', 'kitchen-configurator-pro' ),
				),
				array(
					'icon'  => 'palette',
					'label' => __( 'eindeloos veel kleuren/mogelijkheden', 'kitchen-configurator-pro' ),
				),
				array(
					'icon'  => 'advice',
					'label' => __( 'professioneel advies', 'kitchen-configurator-pro' ),
				),
				array(
					'icon'  => 'factory',
					'label' => __( 'direct uit de fabriek', 'kitchen-configurator-pro' ),
				),
			),
			'tiles'              => array(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$settings = get_option( 'kcp_settings', array() );
		$promo    = is_array( $settings['shop_promo'] ?? null ) ? $settings['shop_promo'] : array();
		$promo    = self::normalize( $promo );

		if ( self::TILES_SOURCE_CATEGORIES === (string) ( $promo['tiles_source'] ?? '' ) ) {
			$category_tiles = self::get_category_tiles( $promo );

			if ( ! empty( $category_tiles ) ) {
				$promo['tiles'] = $category_tiles;
			}
		}

		return $promo;
	}

	public static function is_enabled(): bool {
		$settings = get_option( 'kcp_settings', array() );
		$promo    = is_array( $settings['shop_promo'] ?? null ) ? $settings['shop_promo'] : array();

		return self::normalize( $promo )['enabled'] ?? true;
	}

	/**
	 * Render the shop promo partial when enabled and content exists.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		$promo = self::get_settings();
		$path  = KCP_PLUGIN_DIR . 'templates/woocommerce/partials/shop-promo.php';

		if ( ! is_readable( $path ) ) {
			return;
		}

		include $path;
	}

	public static function get_icon_url( string $icon ): string {
		$map = array(
			'cabinet' => 'sneller-rondje.svg',
			'palette' => 'palet-rondje.svg',
			'advice'  => 'hulp-rondje.svg',
			'factory' => 'fabriek-rondje.svg',
		);

		return self::asset_url( $map[ $icon ] ?? 'sneller-rondje.svg' );
	}

	/**
	 * Build promo tiles from WooCommerce product categories.
	 *
	 * @param array<string, mixed> $promo Normalized promo settings.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_category_tiles( array $promo = array() ): array {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		$parent_id = self::resolve_tiles_parent_id( (string) ( $promo['tiles_parent_slug'] ?? '' ) );
		$limit     = max( 0, (int) ( $promo['tiles_limit'] ?? 0 ) );

		$query = array(
			'taxonomy'   => 'product_cat',
			'parent'     => $parent_id,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		if ( $limit > 0 ) {
			$query['number'] = $limit;
		}

		$terms = get_terms( $query );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$tiles = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term || 'uncategorized' === $term->slug ) {
				continue;
			}

			if ( ! self::is_promo_category( $term ) ) {
				continue;
			}

			$tiles[] = self::build_tile_from_term( $term );

			if ( $limit > 0 && count( $tiles ) >= $limit ) {
				break;
			}
		}

		return $tiles;
	}

	/**
	 * @param array<string, mixed> $post Raw POST values.
	 * @return array<string, mixed>
	 */
	public static function sanitize_post( array $post ): array {
		$usps = array();

		for ( $i = 0; $i < 4; $i++ ) {
			$icon = sanitize_key( wp_unslash( (string) ( $post[ 'shop_promo_usp_icon_' . $i ] ?? '' ) ) );

			if ( ! in_array( $icon, self::allowed_icons(), true ) ) {
				$icon = self::defaults()['usps'][ $i ]['icon'] ?? 'cabinet';
			}

			$usps[] = array(
				'icon'  => $icon,
				'label' => sanitize_text_field( wp_unslash( (string) ( $post[ 'shop_promo_usp_label_' . $i ] ?? '' ) ) ),
			);
		}

		$raw_tiles = $post['shop_promo_tiles'] ?? array();
		$tiles     = array();

		if ( is_array( $raw_tiles ) ) {
			foreach ( $raw_tiles as $tile ) {
				if ( ! is_array( $tile ) ) {
					continue;
				}

				$label = sanitize_text_field( wp_unslash( (string) ( $tile['label'] ?? '' ) ) );
				$url   = esc_url_raw( wp_unslash( (string) ( $tile['url'] ?? '' ) ) );

				if ( '' === $label && '' === $url ) {
					continue;
				}

				$tiles[] = array(
					'label'           => $label,
					'url'             => $url,
					'image_url'       => esc_url_raw( wp_unslash( (string) ( $tile['image_url'] ?? '' ) ) ),
					'video_url'       => esc_url_raw( wp_unslash( (string) ( $tile['video_url'] ?? '' ) ) ),
					'badge_text'      => sanitize_text_field( wp_unslash( (string) ( $tile['badge_text'] ?? '' ) ) ),
					'badge_image_url' => esc_url_raw( wp_unslash( (string) ( $tile['badge_image_url'] ?? '' ) ) ),
				);
			}
		}

		$tiles_source = sanitize_key( wp_unslash( (string) ( $post['shop_promo_tiles_source'] ?? self::TILES_SOURCE_CATEGORIES ) ) );

		if ( ! in_array( $tiles_source, array( self::TILES_SOURCE_CATEGORIES, self::TILES_SOURCE_MANUAL ), true ) ) {
			$tiles_source = self::TILES_SOURCE_CATEGORIES;
		}

		return self::normalize(
			array(
				'enabled'           => isset( $post['shop_promo_enabled'] ) ? '1' : '0',
				'tiles_source'      => $tiles_source,
				'tiles_parent_slug' => sanitize_title( wp_unslash( (string) ( $post['shop_promo_tiles_parent_slug'] ?? '' ) ) ),
				'tiles_limit'       => max( 0, (int) ( $post['shop_promo_tiles_limit'] ?? 0 ) ),
				'usps'              => $usps,
				'tiles'             => $tiles,
			)
		);
	}

	/**
	 * @return array<int, string>
	 */
	public static function allowed_icons(): array {
		return array( 'cabinet', 'palette', 'advice', 'factory' );
	}

	/**
	 * @param array<string, mixed> $promo Raw promo settings.
	 * @return array<string, mixed>
	 */
	private static function normalize( array $promo ): array {
		$defaults = self::defaults();
		$enabled  = true;

		if ( array_key_exists( 'enabled', $promo ) ) {
			$enabled = ! empty( $promo['enabled'] ) && '0' !== (string) $promo['enabled'];
		}

		$tiles_source = sanitize_key( (string) ( $promo['tiles_source'] ?? $defaults['tiles_source'] ) );

		if ( ! in_array( $tiles_source, array( self::TILES_SOURCE_CATEGORIES, self::TILES_SOURCE_MANUAL ), true ) ) {
			$tiles_source = (string) $defaults['tiles_source'];
		}

		$raw_usps = is_array( $promo['usps'] ?? null ) ? $promo['usps'] : array();
		$usps     = array();

		for ( $i = 0; $i < 4; $i++ ) {
			$raw_usp = is_array( $raw_usps[ $i ] ?? null ) ? $raw_usps[ $i ] : array();
			$icon    = sanitize_key( (string) ( $raw_usp['icon'] ?? '' ) );
			$default = $defaults['usps'][ $i ];
			$label   = trim( (string) ( $raw_usp['label'] ?? '' ) );

			$usps[] = array(
				'icon'     => in_array( $icon, self::allowed_icons(), true ) ? $icon : (string) $default['icon'],
				'icon_url' => self::get_icon_url( in_array( $icon, self::allowed_icons(), true ) ? $icon : (string) $default['icon'] ),
				'label'    => '' !== $label ? $label : (string) $default['label'],
			);
		}

		$raw_tiles = is_array( $promo['tiles'] ?? null ) ? $promo['tiles'] : array();
		$tiles     = array();

		foreach ( $raw_tiles as $raw_tile ) {
			if ( ! is_array( $raw_tile ) ) {
				continue;
			}

			$label = trim( (string) ( $raw_tile['label'] ?? '' ) );

			$tiles[] = array(
				'label'           => $label,
				'url'             => esc_url_raw( (string) ( $raw_tile['url'] ?? '' ) ),
				'image_url'       => esc_url_raw( (string) ( $raw_tile['image_url'] ?? '' ) ),
				'video_url'       => esc_url_raw( (string) ( $raw_tile['video_url'] ?? '' ) ),
				'badge_text'      => sanitize_text_field( (string) ( $raw_tile['badge_text'] ?? '' ) ),
				'badge_image_url' => esc_url_raw( (string) ( $raw_tile['badge_image_url'] ?? '' ) ),
				'term_id'         => max( 0, (int) ( $raw_tile['term_id'] ?? 0 ) ),
			);
		}

		return array(
			'enabled'           => $enabled,
			'tiles_source'      => $tiles_source,
			'tiles_parent_slug' => sanitize_title( (string) ( $promo['tiles_parent_slug'] ?? '' ) ),
			'tiles_limit'       => max( 0, (int) ( $promo['tiles_limit'] ?? 0 ) ),
			'usps'              => $usps,
			'tiles'             => $tiles,
		);
	}

	private static function resolve_tiles_parent_id( string $parent_slug ): int {
		if ( '' === $parent_slug ) {
			$shell = SiteShellSettingsService::get_settings();
			$parent_slug = sanitize_title( (string) ( $shell['webshop_category_slug'] ?? '' ) );
		}

		if ( '' !== $parent_slug ) {
			$parent = get_term_by( 'slug', $parent_slug, 'product_cat' );

			if ( $parent instanceof \WP_Term ) {
				return (int) $parent->term_id;
			}
		}

		return 0;
	}

	private static function is_promo_category( \WP_Term $term ): bool {
		if ( ProductCategoryVideoService::get_video_id( (int) $term->term_id ) > 0 ) {
			return true;
		}

		if ( '' !== ProductCategoryVideoService::get_promo_tile_label( (int) $term->term_id ) ) {
			return true;
		}

		$thumbnail_id = (int) get_term_meta( (int) $term->term_id, 'thumbnail_id', true );

		if ( $thumbnail_id > 0 ) {
			return true;
		}

		if ( (int) $term->count > 0 ) {
			return true;
		}

		$children = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => (int) $term->term_id,
				'hide_empty' => false,
				'number'     => 1,
				'fields'     => 'ids',
			)
		);

		return ! is_wp_error( $children ) && ! empty( $children );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function build_tile_from_term( \WP_Term $term ): array {
		$term_id      = (int) $term->term_id;
		$thumbnail_id = (int) get_term_meta( $term_id, 'thumbnail_id', true );
		$image_url    = $thumbnail_id > 0 ? (string) wp_get_attachment_image_url( $thumbnail_id, 'large' ) : '';
		$video_url    = ProductCategoryVideoService::get_video_url( $term_id );
		$label        = ProductCategoryVideoService::get_promo_tile_label( $term_id );

		if ( '' === $label ) {
			$description = term_description( $term_id, 'product_cat' );

			if ( is_string( $description ) ) {
				$label = trim( wp_strip_all_tags( $description ) );
			}
		}

		if ( '' === $label ) {
			$label = strtolower( html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		}

		$link = get_term_link( $term );

		return array(
			'label'           => $label,
			'url'             => is_string( $link ) && ! is_wp_error( $link ) ? $link : '',
			'image_url'       => $image_url,
			'video_url'       => $video_url,
			'badge_text'      => strtolower( html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ),
			'badge_image_url' => '',
			'term_id'         => $term_id,
		);
	}

	private static function asset_url( string $filename ): string {
		return KCP_PLUGIN_URL . 'assets/frontend/images/shop-promo/' . ltrim( $filename, '/' );
	}
}
