<?php
/**
 * Per-category video attachment for WooCommerce product_cat terms.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

/**
 * Reads and stores category video attachment IDs.
 */
final class ProductCategoryVideoService {

	public const META_KEY = 'kcp_category_video_id';

	public const PROMO_TILE_LABEL_META = 'kcp_promo_tile_label';

	public static function get_video_id( int $term_id ): int {
		return max( 0, (int) get_term_meta( $term_id, self::META_KEY, true ) );
	}

	public static function get_video_url( int $term_id ): string {
		$video_id = self::get_video_id( $term_id );

		if ( $video_id <= 0 ) {
			return '';
		}

		$url = wp_get_attachment_url( $video_id );

		return is_string( $url ) ? $url : '';
	}

	public static function get_promo_tile_label( int $term_id ): string {
		return sanitize_text_field( (string) get_term_meta( $term_id, self::PROMO_TILE_LABEL_META, true ) );
	}

	public static function save_video_id( int $term_id, int $video_id ): void {
		$video_id = max( 0, $video_id );

		if ( $video_id <= 0 ) {
			delete_term_meta( $term_id, self::META_KEY );

			return;
		}

		update_term_meta( $term_id, self::META_KEY, $video_id );
	}

	public static function save_promo_tile_label( int $term_id, string $label ): void {
		$label = sanitize_text_field( $label );

		if ( '' === $label ) {
			delete_term_meta( $term_id, self::PROMO_TILE_LABEL_META );

			return;
		}

		update_term_meta( $term_id, self::PROMO_TILE_LABEL_META, $label );
	}
}
