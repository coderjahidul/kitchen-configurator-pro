<?php
/**
 * Design step page settings.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

/**
 * Reads and normalizes the global design step configuration.
 */
final class DesignStepService {

	/**
	 * Default zone definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function default_zones(): array {
		return array(
			array(
				'id'    => 'front',
				'label' => __( 'frontkleur', 'kitchen-configurator-pro' ),
				'top'   => 42,
				'left'  => 34,
			),
			array(
				'id'    => 'handle_strip',
				'label' => __( 'greep of knop', 'kitchen-configurator-pro' ),
				'top'   => 18,
				'left'  => 34,
			),
			array(
				'id'    => 'cabinet',
				'label' => __( 'kastkleur', 'kitchen-configurator-pro' ),
				'top'   => 38,
				'left'  => 68,
			),
			array(
				'id'    => 'plinth',
				'label' => __( 'plintkleur', 'kitchen-configurator-pro' ),
				'top'   => 93,
				'left'  => 48,
			),
		);
	}

	/**
	 * Default design step settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'breadcrumb'     => __( 'ontwerp jouw keuken', 'kitchen-configurator-pro' ),
			'heading'        => __( 'ontwerp jouw keuken', 'kitchen-configurator-pro' ),
			'description'    => __( 'Selecteer hier de kleuren van jouw keuken. Klik op het bewerk symbool om het onderdeel van de keuken een kleur te geven.', 'kitchen-configurator-pro' ),
			'base_image_url' => '',
			'back_url'       => '',
			'back_label'     => __( 'terug naar kast type', 'kitchen-configurator-pro' ),
			'skip_url'             => '',
			'skip_label'           => __( 'deze stap overslaan', 'kitchen-configurator-pro' ),
			'cabinet_select_url'   => '',
			'cabinet_select_label' => __( 'selecteer kasten', 'kitchen-configurator-pro' ),
			'monsterbox_url'   => '',
			'monsterbox_promo' => __( 'Wil jij eerst de kleur thuis goed bekijken?', 'kitchen-configurator-pro' ),
			'monsterbox_label' => __( 'bestel onze monsterbox', 'kitchen-configurator-pro' ),
			'zones'          => self::default_zones(),
		);
	}

	/**
	 * Resolve design step settings from plugin options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$settings = get_option( 'kcp_settings', array() );
		$design   = is_array( $settings['design_step'] ?? null ) ? $settings['design_step'] : array();

		return self::normalize( $design );
	}

	/**
	 * Public payload for the frontend.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_public_config(): array {
		$config = self::get_settings();
		$zones  = is_array( $config['zones'] ?? null ) ? $config['zones'] : array();

		if ( function_exists( 'kcp_plugin' ) ) {
			$catalog = kcp_plugin()->container()->get( DesignZoneCatalogService::class );
			$zones   = $catalog->hydrate_zones( $zones );
		}

		$base_image = esc_url_raw( (string) ( $config['base_image_url'] ?? '' ) );
		if ( '' === $base_image ) {
			$base_image = KCP_PLUGIN_URL . 'assets/frontend/images/design/kitchen-cabinet-handle.png';
		}

		$cabinet_select_url = (string) ( $config['cabinet_select_url'] ?? '' );
		if ( '' === $cabinet_select_url ) {
			$cabinet_select_url = self::resolve_cabinet_select_page_url();
		}
		$cabinet_select_url = self::normalize_step_url( $cabinet_select_url );

		$skip_url = (string) ( $config['skip_url'] ?? '' );
		if ( '' === $skip_url ) {
			$skip_url = $cabinet_select_url;
		}
		$skip_url = self::normalize_step_url( $skip_url );

		$back_url = (string) ( $config['back_url'] ?? '' );
		if ( '' === $back_url ) {
			$back_url = self::resolve_back_page_url();
		}

		return array(
			'breadcrumb'     => (string) ( $config['breadcrumb'] ?? '' ),
			'heading'        => (string) ( $config['heading'] ?? '' ),
			'description'    => (string) ( $config['description'] ?? '' ),
			'base_image_url' => $base_image,
			'back_url'       => $back_url,
			'back_label'     => (string) ( $config['back_label'] ?? '' ),
			'skip_url'             => $skip_url,
			'skip_label'           => (string) ( $config['skip_label'] ?? self::defaults()['skip_label'] ),
			'cabinet_select_url'   => $cabinet_select_url,
			'cabinet_select_label' => (string) ( $config['cabinet_select_label'] ?? self::defaults()['cabinet_select_label'] ),
			'monsterbox_url'       => (string) ( $config['monsterbox_url'] ?? '' ),
			'monsterbox_promo' => (string) ( $config['monsterbox_promo'] ?? '' ),
			'monsterbox_label' => (string) ( $config['monsterbox_label'] ?? '' ),
			'zones'            => $zones,
			'preview_masks'    => self::preview_masks(),
		);
	}

	/**
	 * Mask image URLs for the handle cabinet preview overlays.
	 *
	 * @return array<string, string>
	 */
	private static function preview_masks(): array {
		$base = KCP_PLUGIN_URL . 'assets/frontend/images/design/masks/handle/';

		return array(
			'front'   => $base . 'kitchen-cabinet-handle-front.png',
			'cabinet' => $base . 'kitchen-cabinet-handle-cabinet.png',
			'plinth'  => $base . 'kitchen-cabinet-handle-skirt.png',
		);
	}

	/**
	 * Sanitize design step settings from admin POST data.
	 *
	 * @param array<string, mixed> $post Raw POST data.
	 * @return array<string, mixed>
	 */
	public static function sanitize_post( array $post ): array {
		$raw_zones = $post['design_step_zones'] ?? array();
		$zones     = array();

		if ( is_array( $raw_zones ) ) {
			foreach ( $raw_zones as $zone ) {
				if ( ! is_array( $zone ) ) {
					continue;
				}

				$zone_id = sanitize_key( (string) ( $zone['id'] ?? '' ) );

				if ( '' === $zone_id ) {
					continue;
				}

				$zones[] = array(
					'id'    => $zone_id,
					'label' => sanitize_text_field( wp_unslash( (string) ( $zone['label'] ?? '' ) ) ),
					'top'   => self::clamp_percent( $zone['top'] ?? 50 ),
					'left'  => self::clamp_percent( $zone['left'] ?? 50 ),
				);
			}
		}

		return self::normalize(
			array(
				'breadcrumb'     => sanitize_text_field( wp_unslash( (string) ( $post['design_step_breadcrumb'] ?? '' ) ) ),
				'heading'        => sanitize_text_field( wp_unslash( (string) ( $post['design_step_heading'] ?? '' ) ) ),
				'description'    => sanitize_textarea_field( wp_unslash( (string) ( $post['design_step_description'] ?? '' ) ) ),
				'base_image_url' => esc_url_raw( wp_unslash( (string) ( $post['design_step_base_image_url'] ?? '' ) ) ),
				'back_url'       => esc_url_raw( wp_unslash( (string) ( $post['design_step_back_url'] ?? '' ) ) ),
				'back_label'     => sanitize_text_field( wp_unslash( (string) ( $post['design_step_back_label'] ?? '' ) ) ),
				'skip_url'               => esc_url_raw( wp_unslash( (string) ( $post['design_step_skip_url'] ?? '' ) ) ),
				'skip_label'             => sanitize_text_field( wp_unslash( (string) ( $post['design_step_skip_label'] ?? '' ) ) ),
				'cabinet_select_url'     => esc_url_raw( wp_unslash( (string) ( $post['design_step_cabinet_select_url'] ?? '' ) ) ),
				'cabinet_select_label'   => sanitize_text_field( wp_unslash( (string) ( $post['design_step_cabinet_select_label'] ?? '' ) ) ),
				'monsterbox_url'   => esc_url_raw( wp_unslash( (string) ( $post['design_step_monsterbox_url'] ?? '' ) ) ),
				'monsterbox_promo' => sanitize_text_field( wp_unslash( (string) ( $post['design_step_monsterbox_promo'] ?? '' ) ) ),
				'monsterbox_label' => sanitize_text_field( wp_unslash( (string) ( $post['design_step_monsterbox_label'] ?? '' ) ) ),
				'zones'            => $zones,
			)
		);
	}

	/**
	 * Merge saved values with defaults.
	 *
	 * @param array<string, mixed> $design Raw design settings.
	 * @return array<string, mixed>
	 */
	private static function normalize( array $design ): array {
		$defaults = self::defaults();
		$zones    = self::normalize_zones( is_array( $design['zones'] ?? null ) ? $design['zones'] : array() );

		$base_image = esc_url_raw( (string) ( $design['base_image_url'] ?? '' ) );
		if ( '' === $base_image ) {
			$base_image = KCP_PLUGIN_URL . 'assets/frontend/images/design/kitchen-cabinet-handle.png';
		}

		$back_url = (string) ( $design['back_url'] ?? '' );
		if ( '' === $back_url ) {
			$back_url = ConfiguratorLandingService::get_page_url();
		}

		return array(
			'breadcrumb'     => (string) ( $design['breadcrumb'] ?? $defaults['breadcrumb'] ),
			'heading'        => (string) ( $design['heading'] ?? $defaults['heading'] ),
			'description'    => (string) ( $design['description'] ?? $defaults['description'] ),
			'base_image_url' => $base_image,
			'back_url'       => $back_url,
			'back_label'     => (string) ( $design['back_label'] ?? $defaults['back_label'] ),
			'skip_url'             => (string) ( $design['skip_url'] ?? '' ),
			'skip_label'           => (string) ( $design['skip_label'] ?? $defaults['skip_label'] ),
			'cabinet_select_url'   => (string) ( $design['cabinet_select_url'] ?? '' ),
			'cabinet_select_label' => (string) ( $design['cabinet_select_label'] ?? $defaults['cabinet_select_label'] ),
			'monsterbox_url'   => esc_url_raw( (string) ( $design['monsterbox_url'] ?? '' ) ),
			'monsterbox_promo' => (string) ( $design['monsterbox_promo'] ?? $defaults['monsterbox_promo'] ),
			'monsterbox_label' => (string) ( $design['monsterbox_label'] ?? $defaults['monsterbox_label'] ),
			'zones'            => $zones,
		);
	}

	/**
	 * Normalize zone list, falling back to defaults when empty.
	 *
	 * @param array<int, array<string, mixed>> $zones Raw zones.
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalize_zones( array $zones ): array {
		$defaults_by_id = array();

		foreach ( self::default_zones() as $default_zone ) {
			$defaults_by_id[ (string) $default_zone['id'] ] = $default_zone;
		}

		$saved_by_id = array();

		foreach ( $zones as $zone ) {
			if ( ! is_array( $zone ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $zone['id'] ?? '' ) );

			if ( '' !== $id ) {
				$saved_by_id[ $id ] = $zone;
			}
		}

		$normalized = array();

		foreach ( self::default_zones() as $default_zone ) {
			$id     = (string) $default_zone['id'];
			$saved  = $saved_by_id[ $id ] ?? array();

			$position = self::resolve_zone_position( $id, $saved, $default_zone );

			$normalized[] = array(
				'id'    => $id,
				'label' => (string) $default_zone['label'],
				'top'   => $position['top'],
				'left'  => $position['left'],
			);
		}

		return $normalized;
	}

	/**
	 * Previous default hotspot coordinates (migrated to reference layout).
	 *
	 * @return array<string, array<int, array{top: float, left: float}>>
	 */
	private static function legacy_zone_positions(): array {
		return array(
			'front'        => array(
				array( 'top' => 45.0, 'left' => 38.0 ),
				array( 'top' => 25.0, 'left' => 10.0 ),
			),
			'handle_strip' => array(
				array( 'top' => 8.0, 'left' => 47.0 ),
				array( 'top' => 10.0, 'left' => 10.0 ),
			),
			'cabinet'      => array(
				array( 'top' => 45.0, 'left' => 62.0 ),
				array( 'top' => 25.0, 'left' => 90.0 ),
			),
			'plinth'       => array(
				array( 'top' => 88.0, 'left' => 47.0 ),
				array( 'top' => 98.0, 'left' => 47.0 ),
			),
		);
	}

	/**
	 * Resolve hotspot position, migrating untouched legacy defaults.
	 *
	 * @param string               $id      Zone ID.
	 * @param array<string, mixed> $saved   Saved zone values.
	 * @param array<string, mixed> $default Default zone values.
	 * @return array{top: float, left: float}
	 */
	private static function resolve_zone_position( string $id, array $saved, array $default ): array {
		$top  = self::clamp_percent( $saved['top'] ?? $default['top'] );
		$left = self::clamp_percent( $saved['left'] ?? $default['left'] );
		$legacy_sets = self::legacy_zone_positions()[ $id ] ?? array();

		foreach ( $legacy_sets as $legacy ) {
			if (
				abs( $top - $legacy['top'] ) < 0.01
				&& abs( $left - $legacy['left'] ) < 0.01
			) {
				return array(
					'top'  => self::clamp_percent( $default['top'] ),
					'left' => self::clamp_percent( $default['left'] ),
				);
			}
		}

		return array(
			'top'  => $top,
			'left' => $left,
		);
	}

	/**
	 * Clamp a coordinate to 0-100.
	 *
	 * @param mixed $value Raw value.
	 * @return float
	 */
	private static function clamp_percent( mixed $value ): float {
		return max( 0.0, min( 100.0, (float) $value ) );
	}

	/**
	 * Published design step page permalink.
	 */
	public static function get_page_url(): string {
		$page_id = self::get_page_id();

		if ( $page_id <= 0 ) {
			return '';
		}

		$url = get_permalink( $page_id );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Published page ID that renders the design step shortcode.
	 */
	public static function get_page_id(): int {
		return self::resolve_design_page_id();
	}

	/**
	 * Avoid linking cabinet-select steps back to the design page itself.
	 *
	 * @param string $url Candidate URL.
	 */
	private static function normalize_step_url( string $url ): string {
		$url = esc_url_raw( $url );
		if ( '' === $url ) {
			return '';
		}

		$design_page_id = self::resolve_design_page_id();
		if ( $design_page_id <= 0 ) {
			return $url;
		}

		$design_url = get_permalink( $design_page_id );
		if ( ! is_string( $design_url ) || '' === $design_url ) {
			return $url;
		}

		if ( untrailingslashit( $url ) === untrailingslashit( $design_url ) ) {
			$resolved = self::resolve_cabinet_select_page_url();
			return '' !== $resolved ? $resolved : $url;
		}

		return $url;
	}

	/**
	 * Published page ID that renders the design step shortcode.
	 */
	private static function resolve_design_page_id(): int {
		$cached = get_transient( 'kcp_design_page_id' );
		if ( is_numeric( $cached ) && (int) $cached > 0 ) {
			return (int) $cached;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		foreach ( $posts as $post_id ) {
			$post = get_post( (int) $post_id );
			if ( ! $post instanceof \WP_Post || ! has_shortcode( $post->post_content, 'kcp_design_step' ) ) {
				continue;
			}

			set_transient( 'kcp_design_page_id', (int) $post_id, DAY_IN_SECONDS );
			return (int) $post_id;
		}

		return 0;
	}

	/**
	 * Resolve the cabinet select step page permalink.
	 */
	private static function resolve_cabinet_select_page_url(): string {
		$cached = get_transient( 'kcp_cabinet_select_page_url' );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		foreach ( $posts as $post_id ) {
			$post = get_post( (int) $post_id );
			if ( ! $post instanceof \WP_Post || ! has_shortcode( $post->post_content, 'kcp_cabinet_select' ) ) {
				continue;
			}

			$url = get_permalink( $post );
			if ( is_string( $url ) && '' !== $url ) {
				set_transient( 'kcp_cabinet_select_page_url', $url, DAY_IN_SECONDS );
				return $url;
			}
		}

		$design = self::get_settings();
		$skip   = (string) ( $design['skip_url'] ?? '' );

		return $skip;
	}

	/**
	 * Resolve the landing page users return to for cabinet type selection.
	 */
	private static function resolve_back_page_url(): string {
		$cached = get_transient( 'kcp_design_back_page_url' );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$url = home_url( '/' );
		set_transient( 'kcp_design_back_page_url', $url, DAY_IN_SECONDS );

		return $url;
	}
}
