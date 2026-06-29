<?php
/**
 * Cabinet select step page settings.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Repositories\CabinetCategoryRepository;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Reads and normalizes the "selecteer kasten" step configuration.
 */
final class CabinetSelectStepService {

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'breadcrumb_parent'     => __( 'ontwerp jouw keuken', 'kitchen-configurator-pro' ),
			'breadcrumb_parent_url' => '',
			'breadcrumb_current'    => __( 'selecteer kasten', 'kitchen-configurator-pro' ),
			'heading'               => __( 'selecteer kasten', 'kitchen-configurator-pro' ),
			'description'           => __( 'selecteer hier de kast groep waar je mee wil beginnen', 'kitchen-configurator-pro' ),
			'preview_image_url'     => '',
			'back_url'              => '',
			'back_label'            => __( 'terug naar het ontwerp', 'kitchen-configurator-pro' ),
			'design_edit_url'       => '',
			'design_edit_label'     => __( 'wijzigen', 'kitchen-configurator-pro' ),
			'summary_heading'       => __( 'Jouw ontwerp', 'kitchen-configurator-pro' ),
			'category_list_url'     => '',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$settings = get_option( 'kcp_settings', array() );
		$step     = is_array( $settings['cabinet_select_step'] ?? null ) ? $settings['cabinet_select_step'] : array();

		return self::normalize( $step );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_public_config(): array {
		$config     = self::get_settings();
		$design     = DesignStepService::get_settings();
		$categories = array();
		$design_url = self::resolve_design_page_url();

		if ( function_exists( 'kcp_plugin' ) ) {
			$repo = kcp_plugin()->container()->get( CabinetCategoryRepository::class );

			foreach ( $repo->find_all( array( 'is_active' => '1' ) ) as $category ) {
				$row          = Arr::to_array( $category );
				$slug         = (string) ( $row['slug'] ?? '' );
				$position     = self::category_visual_position( $slug );
				$image_urls   = self::category_image_urls( $slug, $row );
				$mask_sets    = self::category_masks_by_type( $position );
				$categories[] = array(
					'id'                     => (int) ( $row['id'] ?? 0 ),
					'slug'                   => $slug,
					'name'                   => (string) ( $row['name'] ?? '' ),
					'description'            => (string) ( $row['description'] ?? '' ),
					'group_url'              => CabinetGroupStepService::resolve_group_page_url( $slug ),
					'image_url'              => (string) ( $image_urls[ KitchenTypeService::TYPE_GREP ] ?? '' ),
					'image_urls'             => $image_urls,
					'visual_position'        => $position,
					'category_masks'         => (array) ( $mask_sets[ KitchenTypeService::TYPE_GREP ] ?? array() ),
					'category_masks_by_type' => $mask_sets,
				);
			}
		}

		$breadcrumb_parent     = (string) ( $config['breadcrumb_parent'] ?? '' );
		$breadcrumb_parent_url = (string) ( $config['breadcrumb_parent_url'] ?? '' );
		$back_url              = (string) ( $config['back_url'] ?? '' );
		$design_edit_url       = (string) ( $config['design_edit_url'] ?? '' );

		if ( '' === $breadcrumb_parent ) {
			$breadcrumb_parent = (string) ( $design['heading'] ?? $design['breadcrumb'] ?? self::defaults()['breadcrumb_parent'] );
		}

		if ( '' === $breadcrumb_parent_url ) {
			$breadcrumb_parent_url = $design_url;
		}

		if ( '' === $back_url ) {
			$back_url = $design_url;
		}

		if ( '' === $design_edit_url ) {
			$design_edit_url = $design_url;
		}

		$category_list_url = (string) ( $config['category_list_url'] ?? '' );
		if ( '' === $category_list_url ) {
			$category_list_url = self::resolve_category_list_url();
		}

		$defaults = self::defaults();

		return array(
			'breadcrumb_parent'     => $breadcrumb_parent,
			'breadcrumb_parent_url' => $breadcrumb_parent_url,
			'breadcrumb_current'    => self::string_or_default( $config, 'breadcrumb_current', $defaults['breadcrumb_current'] ),
			'heading'               => self::string_or_default( $config, 'heading', $defaults['heading'] ),
			'description'           => self::string_or_default( $config, 'description', $defaults['description'] ),
			'preview_image_url'     => (string) ( $config['preview_image_url'] ?? '' ),
			'back_url'              => $back_url,
			'back_label'            => self::string_or_default( $config, 'back_label', $defaults['back_label'] ),
			'design_edit_url'       => $design_edit_url,
			'design_edit_label'     => self::string_or_default( $config, 'design_edit_label', $defaults['design_edit_label'] ),
			'summary_heading'       => self::string_or_default( $config, 'summary_heading', $defaults['summary_heading'] ),
			'category_list_url'     => $category_list_url,
			'category_group_urls'   => CabinetGroupStepService::group_page_urls(),
			'categories'            => $categories,
			'design_zones'          => self::design_zone_labels( $design ),
			'catalog_options'       => self::catalog_options_by_zone( $design ),
			'kitchen_types'         => KitchenTypeService::labels(),
			'default_kitchen_type'  => KitchenTypeService::TYPE_GREP,
		);
	}

	/**
	 * @param array<string, mixed> $post Raw POST data.
	 * @return array<string, mixed>
	 */
	public static function sanitize_post( array $post ): array {
		return self::normalize(
			array(
				'breadcrumb_parent'     => sanitize_text_field( wp_unslash( (string) ( $post['cabinet_select_breadcrumb_parent'] ?? '' ) ) ),
				'breadcrumb_parent_url' => esc_url_raw( wp_unslash( (string) ( $post['cabinet_select_breadcrumb_parent_url'] ?? '' ) ) ),
				'breadcrumb_current'    => sanitize_text_field( wp_unslash( (string) ( $post['cabinet_select_breadcrumb_current'] ?? '' ) ) ),
				'heading'               => sanitize_text_field( wp_unslash( (string) ( $post['cabinet_select_heading'] ?? '' ) ) ),
				'description'           => sanitize_textarea_field( wp_unslash( (string) ( $post['cabinet_select_description'] ?? '' ) ) ),
				'preview_image_url'     => esc_url_raw( wp_unslash( (string) ( $post['cabinet_select_preview_image_url'] ?? '' ) ) ),
				'back_url'              => esc_url_raw( wp_unslash( (string) ( $post['cabinet_select_back_url'] ?? '' ) ) ),
				'back_label'            => sanitize_text_field( wp_unslash( (string) ( $post['cabinet_select_back_label'] ?? '' ) ) ),
				'design_edit_url'       => esc_url_raw( wp_unslash( (string) ( $post['cabinet_select_design_edit_url'] ?? '' ) ) ),
				'design_edit_label'     => sanitize_text_field( wp_unslash( (string) ( $post['cabinet_select_design_edit_label'] ?? '' ) ) ),
				'summary_heading'       => sanitize_text_field( wp_unslash( (string) ( $post['cabinet_select_summary_heading'] ?? '' ) ) ),
				'category_list_url'     => esc_url_raw( wp_unslash( (string) ( $post['cabinet_select_category_list_url'] ?? '' ) ) ),
			)
		);
	}

	/**
	 * @param array<string, mixed> $step Raw settings.
	 * @return array<string, mixed>
	 */
	private static function normalize( array $step ): array {
		$defaults = self::defaults();

		return array(
			'breadcrumb_parent'     => (string) ( $step['breadcrumb_parent'] ?? $defaults['breadcrumb_parent'] ),
			'breadcrumb_parent_url' => (string) ( $step['breadcrumb_parent_url'] ?? '' ),
			'breadcrumb_current'    => (string) ( $step['breadcrumb_current'] ?? $defaults['breadcrumb_current'] ),
			'heading'               => (string) ( $step['heading'] ?? $defaults['heading'] ),
			'description'           => (string) ( $step['description'] ?? $defaults['description'] ),
			'preview_image_url'     => esc_url_raw( (string) ( $step['preview_image_url'] ?? '' ) ),
			'back_url'              => (string) ( $step['back_url'] ?? '' ),
			'back_label'            => (string) ( $step['back_label'] ?? $defaults['back_label'] ),
			'design_edit_url'       => (string) ( $step['design_edit_url'] ?? '' ),
			'design_edit_label'     => (string) ( $step['design_edit_label'] ?? $defaults['design_edit_label'] ),
			'summary_heading'       => (string) ( $step['summary_heading'] ?? $defaults['summary_heading'] ),
			'category_list_url'     => (string) ( $step['category_list_url'] ?? '' ),
		);
	}

	/**
	 * Active catalog options keyed by design zone id (for resolving stored selections).
	 *
	 * @param array<string, mixed> $design Design step settings.
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private static function catalog_options_by_zone( array $design ): array {
		if ( ! function_exists( 'kcp_plugin' ) ) {
			return array();
		}

		$zones = is_array( $design['zones'] ?? null ) ? $design['zones'] : DesignStepService::default_zones();
		/** @var DesignZoneCatalogService $catalog */
		$catalog   = kcp_plugin()->container()->get( DesignZoneCatalogService::class );
		$hydrated  = $catalog->hydrate_zones( $zones );
		$by_zone   = array();

		foreach ( $hydrated as $zone ) {
			if ( ! is_array( $zone ) ) {
				continue;
			}

			$zone_id = sanitize_key( (string) ( $zone['id'] ?? '' ) );

			if ( '' === $zone_id ) {
				continue;
			}

			$options = $zone['colors'] ?? array();
			$by_zone[ $zone_id ] = is_array( $options ) ? $options : array();
		}

		return $by_zone;
	}

	/**
	 * @param array<string, mixed> $design Design step settings.
	 * @return array<int, array{id: string, label: string}>
	 */
	private static function design_zone_labels( array $design ): array {
		$zones  = is_array( $design['zones'] ?? null ) ? $design['zones'] : DesignStepService::default_zones();
		$labels = array();
		$handle = null;

		foreach ( $zones as $zone ) {
			if ( ! is_array( $zone ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $zone['id'] ?? '' ) );

			if ( '' === $id ) {
				continue;
			}

			if ( 'handle_strip' === $id ) {
				$handle = array(
					'id'    => $id,
					'label' => (string) ( $zone['label'] ?? __( 'greep of knop', 'kitchen-configurator-pro' ) ),
				);
				continue;
			}

			$labels[] = array(
				'id'    => $id,
				'label' => (string) ( $zone['label'] ?? $id ),
			);
		}

		if ( null !== $handle ) {
			array_splice( $labels, 1, 0, array( $handle ) );
		}

		return $labels;
	}

	/**
	 * Resolve the design step page permalink.
	 */
	private static function resolve_design_page_url(): string {
		$cached = get_transient( 'kcp_design_page_url' );
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
			if ( ! $post instanceof \WP_Post || ! has_shortcode( $post->post_content, 'kcp_design_step' ) ) {
				continue;
			}

			$url = get_permalink( $post );
			if ( is_string( $url ) && '' !== $url ) {
				set_transient( 'kcp_design_page_url', $url, DAY_IN_SECONDS );
				return $url;
			}
		}

		$settings = get_option( 'kcp_settings', array() );
		$page_id  = (int) ( $settings['design_page_id'] ?? 0 );

		if ( $page_id > 0 ) {
			$url = get_permalink( $page_id );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return home_url( '/' );
	}

	/**
	 * Base cabinet preview images per kitchen type.
	 *
	 * @param string               $slug Category slug.
	 * @param array<string, mixed> $row  Optional category row from the database.
	 * @return array<string, string>
	 */
	private static function category_image_urls( string $slug, array $row = array() ): array {
		$defaults = self::default_category_image_files( $slug );
		$urls     = array();

		$field_map = array(
			KitchenTypeService::TYPE_GREP      => 'image_url_greep',
			KitchenTypeService::TYPE_GREEPLOOS => 'image_url_greeploos',
		);

		foreach ( $field_map as $type => $field ) {
			$custom = esc_url_raw( (string) ( $row[ $field ] ?? '' ) );

			if ( '' !== $custom ) {
				$urls[ $type ] = $custom;
				continue;
			}

			$file = (string) ( $defaults[ $type ] ?? '' );
			$urls[ $type ] = '' !== $file
				? KCP_PLUGIN_URL . 'assets/frontend/images/cabinet-select/' . $file
				: '';
		}

		return $urls;
	}

	/**
	 * Bundled preview image filenames per kitchen type.
	 *
	 * @return array<string, string>
	 */
	private static function default_category_image_files( string $slug ): array {
		$map = array(
			'onderkasten' => array(
				KitchenTypeService::TYPE_GREP      => 'greep-onderkasten.png',
				KitchenTypeService::TYPE_GREEPLOOS => 'greeploos-onderkasten.png',
			),
			'bovenkasten' => array(
				KitchenTypeService::TYPE_GREP      => 'greep-bovenkasten.png',
				KitchenTypeService::TYPE_GREEPLOOS => 'greeploos-bovenkasten.png',
			),
			'hoge-kasten' => array(
				KitchenTypeService::TYPE_GREP      => 'greep-hogekast2deuren.png',
				KitchenTypeService::TYPE_GREEPLOOS => 'greeploos-hogekast2deuren.png',
			),
		);

		return $map[ $slug ] ?? array();
	}

	/**
	 * Door mask images grouped by kitchen type.
	 *
	 * @return array<string, array<string, string>>
	 */
	private static function category_masks_by_type( int $position ): array {
		return array(
			KitchenTypeService::TYPE_GREP      => self::category_masks( $position, KitchenTypeService::TYPE_GREP ),
			KitchenTypeService::TYPE_GREEPLOOS => self::category_masks( $position, KitchenTypeService::TYPE_GREEPLOOS ),
		);
	}

	/**
	 * Visual stack position used by the reference layout.
	 */
	private static function category_visual_position( string $slug ): int {
		$map = array(
			'onderkasten' => 4,
			'bovenkasten' => 3,
			'hoge-kasten' => 2,
		);

		return (int) ( $map[ $slug ] ?? 1 );
	}

	/**
	 * Door mask images for category cabinet colour overlays.
	 *
	 * @return array<string, string>
	 */
	private static function category_masks( int $position, string $kitchen_type = KitchenTypeService::TYPE_GREP ): array {
		$folder = KitchenTypeService::TYPE_GREEPLOOS === KitchenTypeService::normalize( $kitchen_type )
			? 'greeploos'
			: 'greep';
		$base   = KCP_PLUGIN_URL . 'assets/frontend/images/cabinet-select/masks/' . $folder . '/';

		if ( 2 === $position ) {
			return array(
				'front_top'    => $base . '2-door-top.png',
				'front_bottom' => $base . '2-door-bottom.png',
			);
		}

		if ( 3 === $position ) {
			return array(
				'front' => $base . '3-door.png',
			);
		}

		if ( 4 === $position ) {
			return array(
				'front' => $base . '4-door.png',
			);
		}

		return array();
	}

	/**
	 * @param array<string, mixed> $config
	 * @param string               $key
	 * @param string               $default
	 */
	private static function string_or_default( array $config, string $key, string $default ): string {
		$value = trim( (string) ( $config[ $key ] ?? '' ) );

		return '' !== $value ? $value : $default;
	}

	/**
	 * Resolve the shop/category list URL used when a cabinet group is selected.
	 */
	private static function resolve_category_list_url(): string {
		$shop_page_id = (int) get_option( 'woocommerce_shop_page_id', 0 );

		if ( $shop_page_id > 0 ) {
			$url = get_permalink( $shop_page_id );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return home_url( '/shop/' );
	}
}
