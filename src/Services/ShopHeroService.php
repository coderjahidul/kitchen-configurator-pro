<?php
/**
 * Shop page hero section settings.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

/**
 * Reads and normalizes configurable shop hero content.
 */
final class ShopHeroService {

	/**
	 * Default hero settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'enabled'        => true,
			'heading'        => __( 'stel zelf je keuken samen', 'kitchen-configurator-pro' ),
			'description'    => __( 'maak hier alvast je keuze voor een greeploze kast of een kast met greep en start met samenstellen', 'kitchen-configurator-pro' ),
			'image_urls'     => array(),
			'image_interval' => 4,
			'button_1'       => array(
				'label' => __( 'kies met greep', 'kitchen-configurator-pro' ),
				'url'   => '',
			),
			'button_2'       => array(
				'label' => __( 'kies greeploos', 'kitchen-configurator-pro' ),
				'url'   => '',
			),
			'help_link'      => array(
				'label' => __( 'hoe stel ik mijn keuken samen?', 'kitchen-configurator-pro' ),
				'url'   => '',
			),
		);
	}

	/**
	 * Resolve hero settings from plugin options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$settings = get_option( 'kcp_settings', array() );
		$hero     = is_array( $settings['shop_hero'] ?? null ) ? $settings['shop_hero'] : array();

		return self::normalize( $hero );
	}

	/**
	 * Whether the hero should render on the shop page.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		$hero = self::get_settings();

		return ! empty( $hero['enabled'] );
	}

	/**
	 * Sanitize hero settings from admin POST data.
	 *
	 * @param array<string, mixed> $post Raw POST subset.
	 * @return array<string, mixed>
	 */
	public static function sanitize_post( array $post ): array {
		$raw_images = $post['shop_hero_images'] ?? array();
		$images     = array();

		if ( is_array( $raw_images ) ) {
			foreach ( $raw_images as $url ) {
				$sanitized = esc_url_raw( wp_unslash( (string) $url ) );

				if ( '' !== $sanitized ) {
					$images[] = $sanitized;
				}
			}
		}

		return self::normalize(
			array(
				'enabled'        => isset( $post['shop_hero_enabled'] ) ? '1' : '0',
				'heading'        => sanitize_text_field( wp_unslash( (string) ( $post['shop_hero_heading'] ?? '' ) ) ),
				'description'    => sanitize_textarea_field( wp_unslash( (string) ( $post['shop_hero_description'] ?? '' ) ) ),
				'image_urls'     => $images,
				'image_interval' => max( 2, (int) ( $post['shop_hero_image_interval'] ?? 4 ) ),
				'button_1'       => array(
					'label' => sanitize_text_field( wp_unslash( (string) ( $post['shop_hero_button_1_label'] ?? '' ) ) ),
					'url'   => esc_url_raw( wp_unslash( (string) ( $post['shop_hero_button_1_url'] ?? '' ) ) ),
				),
				'button_2'       => array(
					'label' => sanitize_text_field( wp_unslash( (string) ( $post['shop_hero_button_2_label'] ?? '' ) ) ),
					'url'   => esc_url_raw( wp_unslash( (string) ( $post['shop_hero_button_2_url'] ?? '' ) ) ),
				),
				'help_link'      => array(
					'label' => sanitize_text_field( wp_unslash( (string) ( $post['shop_hero_help_link_label'] ?? '' ) ) ),
					'url'   => esc_url_raw( wp_unslash( (string) ( $post['shop_hero_help_link_url'] ?? '' ) ) ),
				),
			)
		);
	}

	/**
	 * Merge saved hero values with defaults.
	 *
	 * @param array<string, mixed> $hero Raw hero settings.
	 * @return array<string, mixed>
	 */
	private static function normalize( array $hero ): array {
		$defaults = self::defaults();

		$button_1 = is_array( $hero['button_1'] ?? null ) ? $hero['button_1'] : array();
		$button_2 = is_array( $hero['button_2'] ?? null ) ? $hero['button_2'] : array();
		$help     = is_array( $hero['help_link'] ?? null ) ? $hero['help_link'] : array();

		$enabled = true;

		if ( array_key_exists( 'enabled', $hero ) ) {
			$enabled = ! empty( $hero['enabled'] ) && '0' !== (string) $hero['enabled'];
		}

		$image_urls = self::normalize_image_urls( $hero );

		return array(
			'enabled'        => $enabled,
			'heading'        => (string) ( $hero['heading'] ?? $defaults['heading'] ),
			'description'    => (string) ( $hero['description'] ?? $defaults['description'] ),
			'image_urls'     => $image_urls,
			'image_interval' => max( 2, (int) ( $hero['image_interval'] ?? $defaults['image_interval'] ) ),
			'button_1'       => array(
				'label' => (string) ( $button_1['label'] ?? $defaults['button_1']['label'] ),
				'url'   => (string) ( $button_1['url'] ?? '' ),
			),
			'button_2'       => array(
				'label' => (string) ( $button_2['label'] ?? $defaults['button_2']['label'] ),
				'url'   => (string) ( $button_2['url'] ?? '' ),
			),
			'help_link'      => array(
				'label' => (string) ( $help['label'] ?? $defaults['help_link']['label'] ),
				'url'   => (string) ( $help['url'] ?? '' ),
			),
		);
	}

	/**
	 * Normalize hero image URLs, including legacy single-image storage.
	 *
	 * @param array<string, mixed> $hero Raw hero settings.
	 * @return array<int, string>
	 */
	private static function normalize_image_urls( array $hero ): array {
		$urls = array();

		if ( is_array( $hero['image_urls'] ?? null ) ) {
			foreach ( $hero['image_urls'] as $url ) {
				$url = esc_url_raw( (string) $url );

				if ( '' !== $url ) {
					$urls[] = $url;
				}
			}
		}

		if ( ! empty( $urls ) ) {
			return $urls;
		}

		$legacy_url = esc_url_raw( (string) ( $hero['image_url'] ?? '' ) );

		if ( '' !== $legacy_url ) {
			return array( $legacy_url );
		}

		return array();
	}
}
