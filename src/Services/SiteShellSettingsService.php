<?php
/**
 * Site shell settings (announcement bar, contact, badges, etc.).
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

/**
 * Reads and sanitizes header/footer shell settings from kcp_settings.
 */
final class SiteShellSettingsService {

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'announcement_enabled' => true,
			'announcement_text'    => __( 'Onze showroom in Dordrecht is vandaag gesloten', 'kitchen-configurator-pro' ),
			'announcement_cta'     => __( 'Persoonlijk advies? Maak een afspraak', 'kitchen-configurator-pro' ),
			'announcement_url'     => 'https://www.keukenkastenfabriek.nl/afspraak-maken',
			'corporate_url'        => 'https://www.keukenkastenfabriek.nl/',
			'logo_url'             => '',
			'show_theme_toggle'    => true,
			'payment_icons'        => array( 'ideal', 'applepay', 'mastercard', 'bancontact' ),
			'contact_links'        => array(
				array( 'label' => '31 (0)788424787', 'url' => 'https://wa.me/310788424787', 'icon' => 'whatsapp' ),
				array( 'label' => '31 (0)788424787', 'url' => 'tel:0031788424787', 'icon' => 'phone' ),
				array( 'label' => __( 'mail ons', 'kitchen-configurator-pro' ), 'url' => 'mailto:contact@keukenkastenfabriek.nl', 'icon' => 'mail' ),
				array( 'label' => __( 'Voorstraat 123 Dordrecht', 'kitchen-configurator-pro' ), 'url' => 'https://maps.app.goo.gl/rGTUugW8A7VTy8AFA', 'icon' => 'location' ),
			),
			'trust_badges'         => array(
				array( 'label' => __( '5 jaar garantie op keukenkasten', 'kitchen-configurator-pro' ), 'icon' => 'warranty' ),
				array( 'label' => __( '4,8/5 beoordelingen keukenkastenfabriek', 'kitchen-configurator-pro' ), 'icon' => 'reviews' ),
				array( 'label' => __( 'de hoogste kwaliteit', 'kitchen-configurator-pro' ), 'icon' => 'quality' ),
				array( 'label' => __( 'eigen brandstore', 'kitchen-configurator-pro' ), 'icon' => 'brandstore' ),
			),
			'legal_links'          => array(
				array( 'label' => 'keukenkastenfabriek', 'url' => 'https://www.keukenkastenfabriek.nl/' ),
				array( 'label' => __( 'algemene voorwaarden', 'kitchen-configurator-pro' ), 'url' => 'https://www.keukenkastenfabriek.nl/algemene-voorwaarden' ),
				array( 'label' => __( 'garantie voorwaarden', 'kitchen-configurator-pro' ), 'url' => 'https://www.keukenkastenfabriek.nl/garantievoorwaarden' ),
				array( 'label' => __( 'privacy & cookies', 'kitchen-configurator-pro' ), 'url' => 'https://www.keukenkastenfabriek.nl/privacyverklaring' ),
			),
			'footer_titles'        => array(
				'col_1' => __( 'webshop', 'kitchen-configurator-pro' ),
				'col_2' => __( 'opstellingen', 'kitchen-configurator-pro' ),
				'col_3' => __( 'configurator', 'kitchen-configurator-pro' ),
				'col_4' => __( 'contact', 'kitchen-configurator-pro' ),
			),
			'webshop_category_slug'      => '',
			'opstellingen_category_slug' => '',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$settings = get_option( 'kcp_settings', array() );
		$shell    = is_array( $settings['site_shell'] ?? null ) ? $settings['site_shell'] : array();

		return self::normalize( $shell );
	}

	/**
	 * @param array<string, mixed> $raw Raw settings.
	 * @return array<string, mixed>
	 */
	public static function normalize( array $raw ): array {
		$defaults = self::defaults();
		$output   = $defaults;

		foreach ( array( 'announcement_enabled', 'show_theme_toggle' ) as $bool_key ) {
			if ( array_key_exists( $bool_key, $raw ) ) {
				$output[ $bool_key ] = ! empty( $raw[ $bool_key ] );
			}
		}

		foreach ( array(
			'announcement_text',
			'announcement_cta',
			'announcement_url',
			'corporate_url',
			'logo_url',
			'webshop_category_slug',
			'opstellingen_category_slug',
		) as $string_key ) {
			if ( array_key_exists( $string_key, $raw ) ) {
				$output[ $string_key ] = sanitize_text_field( (string) $raw[ $string_key ] );
			}
		}

		if ( isset( $raw['payment_icons'] ) && is_array( $raw['payment_icons'] ) ) {
			$output['payment_icons'] = array_values(
				array_filter(
					array_map(
						static fn( $icon ): string => sanitize_key( (string) $icon ),
						$raw['payment_icons']
					)
				)
			);
		}

		foreach ( array( 'contact_links', 'trust_badges' ) as $list_key ) {
			if ( ! isset( $raw[ $list_key ] ) || ! is_array( $raw[ $list_key ] ) ) {
				continue;
			}

			$output[ $list_key ] = self::normalize_link_rows(
				$raw[ $list_key ],
				'trust_badges' === $list_key ? 'icon' : ( 'contact_links' === $list_key ? 'contact_icon' : null )
			);
		}

		if ( isset( $raw['footer_titles'] ) && is_array( $raw['footer_titles'] ) ) {
			foreach ( array( 'col_1', 'col_2', 'col_3', 'col_4' ) as $col_key ) {
				if ( isset( $raw['footer_titles'][ $col_key ] ) ) {
					$output['footer_titles'][ $col_key ] = sanitize_text_field( (string) $raw['footer_titles'][ $col_key ] );
				}
			}
		}

		return $output;
	}

	/**
	 * @param array<string, mixed> $post Raw POST data.
	 * @return array<string, mixed>
	 */
	public static function sanitize_post( array $post ): array {
		$raw = array(
			'announcement_enabled'       => ! empty( $post['site_shell_announcement_enabled'] ),
			'announcement_text'          => (string) ( $post['site_shell_announcement_text'] ?? '' ),
			'announcement_cta'           => (string) ( $post['site_shell_announcement_cta'] ?? '' ),
			'announcement_url'           => (string) ( $post['site_shell_announcement_url'] ?? '' ),
			'corporate_url'              => (string) ( $post['site_shell_corporate_url'] ?? '' ),
			'logo_url'                   => (string) ( $post['site_shell_logo_url'] ?? '' ),
			'show_theme_toggle'          => ! empty( $post['site_shell_show_theme_toggle'] ),
			'webshop_category_slug'      => (string) ( $post['site_shell_webshop_category_slug'] ?? '' ),
			'opstellingen_category_slug' => (string) ( $post['site_shell_opstellingen_category_slug'] ?? '' ),
			'payment_icons'              => isset( $post['site_shell_payment_icons'] ) && is_array( $post['site_shell_payment_icons'] )
				? array_map( 'strval', $post['site_shell_payment_icons'] )
				: array(),
			'footer_titles'              => array(
				'col_1' => (string) ( $post['site_shell_footer_title_1'] ?? '' ),
				'col_2' => (string) ( $post['site_shell_footer_title_2'] ?? '' ),
				'col_3' => (string) ( $post['site_shell_footer_title_3'] ?? '' ),
				'col_4' => (string) ( $post['site_shell_footer_title_4'] ?? '' ),
			),
		);

		foreach ( array( 'contact', 'trust' ) as $prefix ) {
			$labels = isset( $post[ "site_shell_{$prefix}_label" ] ) && is_array( $post[ "site_shell_{$prefix}_label" ] )
				? $post[ "site_shell_{$prefix}_label" ]
				: array();
			$urls = isset( $post[ "site_shell_{$prefix}_url" ] ) && is_array( $post[ "site_shell_{$prefix}_url" ] )
				? $post[ "site_shell_{$prefix}_url" ]
				: array();
			$icons = isset( $post['site_shell_trust_icon'] ) && is_array( $post['site_shell_trust_icon'] )
				? $post['site_shell_trust_icon']
				: array();
			$contact_icons = isset( $post['site_shell_contact_icon'] ) && is_array( $post['site_shell_contact_icon'] )
				? $post['site_shell_contact_icon']
				: array();

			$rows = array();
			$count = max(
				count( $labels ),
				count( $urls ),
				'trust' === $prefix ? count( $icons ) : 0,
				'contact' === $prefix ? count( $contact_icons ) : 0
			);

			for ( $i = 0; $i < $count; $i++ ) {
				$label = sanitize_text_field( (string) ( $labels[ $i ] ?? '' ) );
				$url   = esc_url_raw( (string) ( $urls[ $i ] ?? '' ) );

				if ( '' === $label ) {
					continue;
				}

				$row = array(
					'label' => $label,
					'url'   => $url,
				);

				if ( 'trust' === $prefix ) {
					$row['icon'] = sanitize_key( (string) ( $icons[ $i ] ?? 'default' ) );
				}

				if ( 'contact' === $prefix ) {
					$row['icon'] = sanitize_key( (string) ( $contact_icons[ $i ] ?? '' ) );
				}

				$rows[] = $row;
			}

			$key = match ( $prefix ) {
				'contact' => 'contact_links',
				'trust'   => 'trust_badges',
				default   => 'legal_links',
			};

			$raw[ $key ] = $rows;
		}

		return self::normalize( $raw );
	}

	/**
	 * @param array<int, mixed>    $rows       Raw rows.
	 * @param string|null          $extra_key  Optional extra field key to preserve (`icon`, `contact_icon`).
	 * @return array<int, array<string, string>>
	 */
	private static function normalize_link_rows( array $rows, ?string $extra_key = null ): array {
		$output = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );

			if ( '' === $label ) {
				continue;
			}

			$item = array(
				'label' => $label,
				'url'   => esc_url_raw( (string) ( $row['url'] ?? '' ) ),
			);

			if ( 'icon' === $extra_key ) {
				$item['icon'] = sanitize_key( (string) ( $row['icon'] ?? 'default' ) );
			}

			if ( 'contact_icon' === $extra_key ) {
				$item['icon'] = sanitize_key( (string) ( $row['icon'] ?? '' ) );
			}

			$output[] = $item;
		}

		return $output;
	}
}
