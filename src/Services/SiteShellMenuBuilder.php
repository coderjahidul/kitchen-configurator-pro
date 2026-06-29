<?php
/**
 * Builds navigation trees from WordPress menus or dynamic fallbacks.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Frontend\SiteShellMenuItemFields;
use KitchenConfiguratorPro\Frontend\SiteShellMenuRegistry;
use KitchenConfiguratorPro\Repositories\CabinetCategoryRepository;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Converts WP nav menus and WooCommerce/cabinet data into shell view models.
 */
final class SiteShellMenuBuilder {

	/**
	 * @param string $configurator_url Configurator URL.
	 * @param string $shop_url         Shop URL.
	 * @param bool   $use_fallback     Whether to use hardcoded fallbacks when no menu is assigned.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_primary_nav( string $configurator_url, string $shop_url, bool $use_fallback = true ): array {
		$menu = self::get_menu_tree( SiteShellMenuRegistry::HEADER_PRIMARY, 1 );

		if ( ! empty( $menu ) ) {
			return $menu;
		}

		if ( ! $use_fallback ) {
			return array();
		}

		return array(
			self::link( __( 'configurator', 'kitchen-configurator-pro' ), $configurator_url, function_exists( 'is_shop' ) && is_shop() ),
			self::link( __( 'populaire opstellingen', 'kitchen-configurator-pro' ), $shop_url, function_exists( 'is_product_category' ) && is_product_category() ),
			self::link( __( 'webshop', 'kitchen-configurator-pro' ), $shop_url, function_exists( 'is_shop' ) && is_shop() ),
			self::link( __( 'bezoek showroom', 'kitchen-configurator-pro' ), SiteShellSettingsService::get_settings()['announcement_url'] ?? '', false ),
		);
	}

	/**
	 * @param string $shop_url     Shop URL.
	 * @param bool   $use_fallback Whether to use hardcoded fallbacks when no menu is assigned.
	 * @return array<int, array<string, string>>
	 */
	public static function get_secondary_nav( string $shop_url, bool $use_fallback = true ): array {
		$menu = self::get_menu_tree( SiteShellMenuRegistry::HEADER_SECONDARY, 1 );

		if ( ! empty( $menu ) ) {
			return self::flatten_links( $menu );
		}

		if ( ! $use_fallback ) {
			return array();
		}

		return self::product_category_links( $shop_url, SiteShellSettingsService::get_settings()['webshop_category_slug'] ?? '' );
	}

	/**
	 * @param bool $use_fallback Whether to use hardcoded fallbacks when no menu is assigned.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_desktop_nav( bool $use_fallback = true ): array {
		$menu = self::get_menu_tree( SiteShellMenuRegistry::HEADER_DESKTOP, 2 );

		if ( ! empty( $menu ) ) {
			return $menu;
		}

		if ( ! $use_fallback ) {
			return array();
		}

		$shop_url = function_exists( 'wc_get_page_permalink' )
			? (string) wc_get_page_permalink( 'shop' )
			: home_url( '/shop/' );

		return array(
			array(
				'label'    => __( 'Configurator', 'kitchen-configurator-pro' ),
				'url'      => $shop_url,
				'is_active'=> function_exists( 'is_shop' ) && is_shop(),
				'children' => array(),
			),
			array(
				'label'    => __( 'Populaire opstellingen', 'kitchen-configurator-pro' ),
				'url'      => $shop_url,
				'is_active'=> function_exists( 'is_product_category' ) && is_product_category(),
				'children' => self::product_category_links( $shop_url, SiteShellSettingsService::get_settings()['opstellingen_category_slug'] ?? '' ),
			),
			array(
				'label'    => __( 'Webshop', 'kitchen-configurator-pro' ),
				'url'      => $shop_url,
				'is_active'=> false,
				'children' => self::product_category_links( $shop_url, SiteShellSettingsService::get_settings()['webshop_category_slug'] ?? '' ),
			),
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_footer_columns(): array {
		$settings = SiteShellSettingsService::get_settings();
		$titles   = is_array( $settings['footer_titles'] ?? null ) ? $settings['footer_titles'] : array();
		$shop_url = function_exists( 'wc_get_page_permalink' )
			? (string) wc_get_page_permalink( 'shop' )
			: home_url( '/shop/' );

		$locations = array(
			SiteShellMenuRegistry::FOOTER_1 => array(
				'title'    => (string) ( $titles['col_1'] ?? __( 'webshop', 'kitchen-configurator-pro' ) ),
				'fallback' => self::product_category_links( $shop_url, (string) ( $settings['webshop_category_slug'] ?? '' ) ),
			),
			SiteShellMenuRegistry::FOOTER_2 => array(
				'title'    => (string) ( $titles['col_2'] ?? __( 'opstellingen', 'kitchen-configurator-pro' ) ),
				'fallback' => self::product_category_links( $shop_url, (string) ( $settings['opstellingen_category_slug'] ?? '' ) ),
			),
			SiteShellMenuRegistry::FOOTER_3 => array(
				'title'    => (string) ( $titles['col_3'] ?? __( 'configurator', 'kitchen-configurator-pro' ) ),
				'fallback' => self::cabinet_category_links(),
			),
			SiteShellMenuRegistry::FOOTER_4 => array(
				'title'    => (string) ( $titles['col_4'] ?? __( 'contact', 'kitchen-configurator-pro' ) ),
				'fallback' => is_array( $settings['contact_links'] ?? null ) ? $settings['contact_links'] : array(),
			),
		);

		$columns = array();

		foreach ( $locations as $location => $config ) {
			$menu_links = self::flatten_links( self::get_menu_tree( $location, 1 ) );
			$links      = ! empty( $menu_links ) ? $menu_links : $config['fallback'];

			$columns[] = array(
				'title' => $config['title'],
				'links' => $links,
			);
		}

		return $columns;
	}

	/**
	 * Footer legal links from WordPress menu, with settings fallback.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get_legal_links(): array {
		$menu_links = self::get_flat_menu_links( SiteShellMenuRegistry::FOOTER_LEGAL );

		if ( ! empty( $menu_links ) ) {
			return $menu_links;
		}

		$settings = SiteShellSettingsService::get_settings();
		$fallback = is_array( $settings['legal_links'] ?? null ) ? $settings['legal_links'] : array();

		return ! empty( $fallback ) ? $fallback : SiteShellSettingsService::defaults()['legal_links'];
	}

	/**
	 * @param string $location Menu location slug.
	 * @return array<int, array<string, string>>
	 */
	private static function get_flat_menu_links( string $location ): array {
		$locations = get_nav_menu_locations();
		$menu_id   = (int) ( $locations[ $location ] ?? 0 );

		if ( $menu_id <= 0 ) {
			return array();
		}

		$items = wp_get_nav_menu_items( $menu_id );

		if ( ! is_array( $items ) || empty( $items ) ) {
			return array();
		}

		$links = array();

		foreach ( $items as $item ) {
			if ( ! $item instanceof \WP_Post || (int) $item->menu_item_parent > 0 ) {
				continue;
			}

			$url = (string) ( $item->url ?? '' );

			$links[] = array(
				'label'  => html_entity_decode( (string) ( $item->title ?? '' ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'url'    => $url,
				'target' => (string) ( $item->target ?? '' ),
			);
		}

		return $links;
	}

	/**
	 * @param string $location Menu location slug.
	 * @param int    $depth    Max depth.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_menu_tree( string $location, int $depth = 2 ): array {
		$locations = get_nav_menu_locations();
		$menu_id   = (int) ( $locations[ $location ] ?? 0 );

		if ( $menu_id <= 0 ) {
			return array();
		}

		$items = wp_get_nav_menu_items( $menu_id );

		if ( ! is_array( $items ) || empty( $items ) ) {
			return array();
		}

		$by_parent = array();

		foreach ( $items as $item ) {
			if ( ! $item instanceof \WP_Post ) {
				continue;
			}

			$parent_id = (int) $item->menu_item_parent;
			$by_parent[ $parent_id ] ??= array();
			$by_parent[ $parent_id ][] = $item;
		}

		return self::build_branch( $by_parent, 0, $depth );
	}

	/**
	 * @param array<int, array<int, \WP_Post>> $by_parent Items grouped by parent ID.
	 * @param int                              $parent_id Parent menu item ID.
	 * @param int                              $depth     Remaining depth.
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_branch( array $by_parent, int $parent_id, int $depth ): array {
		$branch = array();

		foreach ( $by_parent[ $parent_id ] ?? array() as $item ) {
			$url    = (string) ( $item->url ?? '' );
			$images = SiteShellMenuItemFields::get_item_images( (int) $item->ID );
			$row    = array(
				'label'       => html_entity_decode( (string) ( $item->title ?? '' ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'url'         => $url,
				'is_active'   => self::is_url_active( $url, $item ),
				'image'       => $images['image'],
				'image_hover' => $images['image_hover'],
				'children'    => array(),
			);

			if ( $depth > 1 ) {
				$row['children'] = self::build_branch( $by_parent, (int) $item->ID, $depth - 1 );
			}

			$branch[] = $row;
		}

		return $branch;
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private static function product_category_links( string $fallback_url, string $parent_slug ): array {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		$parent_id = 0;

		if ( '' !== $parent_slug ) {
			$parent = get_term_by( 'slug', sanitize_title( $parent_slug ), 'product_cat' );
			if ( $parent instanceof \WP_Term ) {
				$parent_id = (int) $parent->term_id;
			}
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent_id,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$links = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term || 'uncategorized' === $term->slug ) {
				continue;
			}

			$url = get_term_link( $term );
			$links[] = array(
				'label'       => $term->name,
				'url'         => is_string( $url ) && ! is_wp_error( $url ) ? $url : $fallback_url,
				'image'       => self::get_term_image_url( $term ),
				'image_hover' => '',
			);
		}

		return $links;
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private static function cabinet_category_links(): array {
		if ( ! function_exists( 'kcp_plugin' ) ) {
			return array();
		}

		$select_url = CabinetListStepService::resolve_select_page_url();
		$links      = array(
			array(
				'label' => __( 'keukenkasten met handgreep', 'kitchen-configurator-pro' ),
				'url'   => KitchenTypeService::append_query_param( $select_url, KitchenTypeService::TYPE_GREP ),
			),
			array(
				'label' => __( 'keukenkasten greeploos', 'kitchen-configurator-pro' ),
				'url'   => KitchenTypeService::append_query_param( $select_url, KitchenTypeService::TYPE_GREEPLOOS ),
			),
		);

		$repo = kcp_plugin()->container()->get( CabinetCategoryRepository::class );

		foreach ( $repo->find_all( array( 'is_active' => '1' ) ) as $category ) {
			$row  = Arr::to_array( $category );
			$slug = (string) ( $row['slug'] ?? '' );

			if ( '' === $slug ) {
				continue;
			}

			$links[] = array(
				'label' => (string) ( $row['name'] ?? $slug ),
				'url'   => CabinetGroupStepService::resolve_group_page_url( $slug ),
			);
		}

		$links[] = array(
			'label' => __( 'hulp bij ontwerpen', 'kitchen-configurator-pro' ),
			'url'   => trailingslashit( (string) ( SiteShellSettingsService::get_settings()['corporate_url'] ?? '' ) ) . 'contact',
		);

		return $links;
	}

	/**
	 * @param array<int, array<string, mixed>> $tree Menu tree.
	 * @return array<int, array<string, string>>
	 */
	private static function flatten_links( array $tree ): array {
		$links = array();

		foreach ( $tree as $item ) {
			$links[] = array(
				'label' => (string) ( $item['label'] ?? '' ),
				'url'   => (string) ( $item['url'] ?? '' ),
			);
		}

		return $links;
	}

	/**
	 * @param string    $label Link label.
	 * @param string    $url   Link URL.
	 * @param bool      $active Whether the link is active.
	 * @return array<string, mixed>
	 */
	private static function link( string $label, string $url, bool $active ): array {
		return array(
			'label'       => $label,
			'url'         => $url,
			'is_active'   => $active,
			'image'       => '',
			'image_hover' => '',
			'children'    => array(),
		);
	}

	/**
	 * @param \WP_Term $term Product category term.
	 */
	private static function get_term_image_url( \WP_Term $term ): string {
		$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );

		if ( $thumbnail_id <= 0 ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $thumbnail_id, 'medium' );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * @param string         $url  Menu item URL.
	 * @param \WP_Post|null  $item Menu item.
	 */
	private static function is_url_active( string $url, ?\WP_Post $item = null ): bool {
		if ( '' === $url ) {
			return false;
		}

		if ( null !== $item && 'custom' !== $item->type ) {
			if ( 'post_type' === $item->type && 'page' === $item->object && is_page( (int) $item->object_id ) ) {
				return true;
			}

			if ( 'taxonomy' === $item->type && 'product_cat' === $item->object && function_exists( 'is_product_category' ) && is_product_category( (int) $item->object_id ) ) {
				return true;
			}
		}

		$current = home_url( add_query_arg( array(), (string) ( $_SERVER['REQUEST_URI'] ?? '' ) ) );
		$current = untrailingslashit( strtok( $current, '?' ) );
		$target  = untrailingslashit( strtok( $url, '?' ) );

		return $current === $target;
	}
}
