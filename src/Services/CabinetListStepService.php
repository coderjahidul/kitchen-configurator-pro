<?php
/**
 * Cabinet child-list step configuration service.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Repositories\CabinetCategoryRepository;
use KitchenConfiguratorPro\Repositories\CabinetRelationRepository;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Builds public config for parent cabinet child-list pages.
 */
final class CabinetListStepService {

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'back_label' => __( 'terug naar kasten', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * @param string $category_slug      Cabinet category slug.
	 * @param string $parent_cabinet_slug Parent cabinet slug.
	 * @return array<string, mixed>
	 */
	public static function get_public_config( string $category_slug, string $parent_cabinet_slug ): array {
		$category_slug       = sanitize_title( $category_slug );
		$parent_cabinet_slug = sanitize_title( $parent_cabinet_slug );
		$category            = self::find_category( $category_slug );
		$parent              = self::find_parent_cabinet( $parent_cabinet_slug, is_array( $category ) ? (int) ( $category['id'] ?? 0 ) : 0 );
		$select              = CabinetSelectStepService::get_settings();
		$design              = DesignStepService::get_settings();
		$defaults            = self::defaults();

		$select_url  = self::resolve_cabinet_select_page_url();
		$design_url  = self::resolve_design_page_url();
		$shop_url    = self::resolve_shop_url();
		$group_url   = CabinetGroupStepService::resolve_group_page_url( $category_slug );
		$shared      = CabinetSelectStepService::get_public_config();

		$breadcrumb_parent     = (string) ( $select['breadcrumb_parent'] ?? '' );
		$breadcrumb_parent_url = (string) ( $select['breadcrumb_parent_url'] ?? '' );

		if ( '' === $breadcrumb_parent ) {
			$breadcrumb_parent = (string) ( $design['heading'] ?? $design['breadcrumb'] ?? __( 'ontwerp jouw keuken', 'kitchen-configurator-pro' ) );
		}

		if ( '' === $breadcrumb_parent_url ) {
			$breadcrumb_parent_url = $design_url;
		}

		$breadcrumb_middle     = (string) ( $shared['breadcrumb_current'] ?? __( 'selecteer kasten', 'kitchen-configurator-pro' ) );
		$breadcrumb_middle_url = $select_url;
		$breadcrumb_group      = is_array( $category ) ? (string) ( $category['name'] ?? $category_slug ) : $category_slug;
		$breadcrumb_group_url  = $group_url;
		$heading               = is_array( $parent ) ? (string) ( $parent['name'] ?? $parent_cabinet_slug ) : $parent_cabinet_slug;
		$parent_id             = is_array( $parent ) ? (int) ( $parent['id'] ?? 0 ) : 0;
		$items                 = self::resolve_child_items( $parent_id, $category_slug, $parent_cabinet_slug );
		$breadcrumbs           = self::resolve_breadcrumb_chain( $category_slug, $parent_id, $breadcrumb_parent, $breadcrumb_parent_url, $breadcrumb_middle, $breadcrumb_middle_url, $breadcrumb_group, $breadcrumb_group_url );

		return array(
			'category_slug'           => $category_slug,
			'parent_cabinet_slug'       => $parent_cabinet_slug,
			'parent_cabinet_id'         => $parent_id,
			'heading'                   => $heading,
			'breadcrumb_parent'         => $breadcrumb_parent,
			'breadcrumb_parent_url'     => $breadcrumb_parent_url,
			'breadcrumb_middle'         => $breadcrumb_middle,
			'breadcrumb_middle_url'     => $breadcrumb_middle_url,
			'breadcrumb_group'          => $breadcrumb_group,
			'breadcrumb_group_url'      => $breadcrumb_group_url,
			'breadcrumbs'               => $breadcrumbs,
			'back_url'                  => self::resolve_back_url( $parent_id, $category_slug, $group_url ),
			'back_label'                => (string) ( $defaults['back_label'] ?? '' ),
			'shop_url'                  => $shop_url,
			'items'                     => $items,
			'design_zones'              => $shared['design_zones'] ?? array(),
			'catalog_options'           => $shared['catalog_options'] ?? array(),
			'kitchen_types'             => KitchenTypeService::labels(),
			'default_kitchen_type'      => KitchenTypeService::TYPE_GREP,
		);
	}

	/**
	 * Resolve permalink for a parent cabinet child-list page.
	 */
	public static function resolve_child_list_url( string $category_slug, string $parent_cabinet_slug ): string {
		$category_slug       = sanitize_title( $category_slug );
		$parent_cabinet_slug = sanitize_title( $parent_cabinet_slug );

		if ( '' === $category_slug || '' === $parent_cabinet_slug ) {
			return '';
		}

		$transient_key = 'kcp_cabinet_child_url_' . $category_slug . '_' . $parent_cabinet_slug;
		$cached        = get_transient( $transient_key );

		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$group_url = CabinetGroupStepService::resolve_group_page_url( $category_slug );

		if ( '' === $group_url ) {
			return '';
		}

		$url = trailingslashit( $group_url ) . $parent_cabinet_slug . '/';
		set_transient( $transient_key, $url, DAY_IN_SECONDS );

		return $url;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function find_category( string $slug ): ?array {
		if ( '' === $slug || ! function_exists( 'kcp_plugin' ) ) {
			return null;
		}

		/** @var CabinetCategoryRepository $repo */
		$repo = kcp_plugin()->container()->get( CabinetCategoryRepository::class );

		foreach ( $repo->find_all( array( 'is_active' => '1' ) ) as $category ) {
			$row = Arr::to_array( $category );
			if ( (string) ( $row['slug'] ?? '' ) === $slug ) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function find_parent_cabinet( string $slug, int $category_id ): ?array {
		if ( '' === $slug || ! function_exists( 'kcp_plugin' ) ) {
			return null;
		}

		/** @var CabinetRepository $repo */
		$repo    = kcp_plugin()->container()->get( CabinetRepository::class );
		$cabinet = $repo->find_by_slug( $slug );

		if ( null === $cabinet ) {
			return null;
		}

		$row = Arr::to_array( $cabinet );

		if ( $category_id > 0 && (int) ( $row['category_id'] ?? 0 ) !== $category_id ) {
			return null;
		}

		if ( empty( $row['is_active'] ) ) {
			return null;
		}

		return $row;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function resolve_child_items( int $parent_id, string $category_slug, string $parent_cabinet_slug ): array {
		if ( $parent_id <= 0 || ! function_exists( 'kcp_plugin' ) ) {
			return array();
		}

		/** @var CabinetRelationRepository $relations */
		$relations = kcp_plugin()->container()->get( CabinetRelationRepository::class );
		$items     = array();

		foreach ( $relations->get_children( $parent_id ) as $cabinet ) {
			$row          = Arr::to_array( $cabinet );
			$cabinet_id   = (int) ( $row['id'] ?? 0 );
			$cabinet_slug = (string) ( $row['slug'] ?? '' );
			$has_children = $relations->has_children( $cabinet_id );

			$items[] = array(
				'id'           => $cabinet_id,
				'slug'         => $cabinet_slug,
				'name'         => (string) ( $row['name'] ?? '' ),
				'image_url'    => (string) ( $row['image_url'] ?? '' ),
				'has_children' => $has_children,
				'url'          => $has_children
					? self::resolve_child_list_url( $category_slug, $cabinet_slug )
					: self::resolve_detail_url( $category_slug, $parent_cabinet_slug, $cabinet_slug ),
			);
		}

		return $items;
	}

	/**
	 * Resolve permalink for a leaf cabinet detail page.
	 */
	public static function resolve_detail_url( string $category_slug, string $parent_cabinet_slug, string $cabinet_slug ): string {
		$category_slug       = sanitize_title( $category_slug );
		$parent_cabinet_slug = sanitize_title( $parent_cabinet_slug );
		$cabinet_slug        = sanitize_title( $cabinet_slug );

		if ( '' === $category_slug || '' === $parent_cabinet_slug || '' === $cabinet_slug ) {
			return '';
		}

		$transient_key = 'kcp_cabinet_detail_url_' . $category_slug . '_' . $parent_cabinet_slug . '_' . $cabinet_slug;
		$cached        = get_transient( $transient_key );

		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$list_url = self::resolve_child_list_url( $category_slug, $parent_cabinet_slug );

		if ( '' === $list_url ) {
			return '';
		}

		$url = trailingslashit( $list_url ) . $cabinet_slug . '/';
		set_transient( $transient_key, $url, DAY_IN_SECONDS );

		return $url;
	}

	/**
	 * @return array<int, array{label: string, url: string}>
	 */
	private static function resolve_breadcrumb_chain(
		string $category_slug,
		int $parent_id,
		string $design_label,
		string $design_url,
		string $select_label,
		string $select_url,
		string $group_label,
		string $group_url
	): array {
		$crumbs = array();

		if ( '' !== trim( $design_label ) ) {
			$crumbs[] = array(
				'label' => $design_label,
				'url'   => $design_url,
			);
		}

		if ( '' !== trim( $select_label ) ) {
			$crumbs[] = array(
				'label' => $select_label,
				'url'   => $select_url,
			);
		}

		if ( '' !== trim( $group_label ) ) {
			$crumbs[] = array(
				'label' => $group_label,
				'url'   => $group_url,
			);
		}

		if ( $parent_id <= 0 || ! function_exists( 'kcp_plugin' ) ) {
			return $crumbs;
		}

		/** @var CabinetRelationRepository $relations */
		$relations = kcp_plugin()->container()->get( CabinetRelationRepository::class );
		/** @var CabinetRepository $repo */
		$repo      = kcp_plugin()->container()->get( CabinetRepository::class );

		$ancestors = array();
		$current   = $parent_id;
		$visited   = array();

		while ( $current > 0 && ! in_array( $current, $visited, true ) ) {
			$visited[] = $current;
			$cabinet   = $repo->find( $current );

			if ( null === $cabinet ) {
				break;
			}

			$row = Arr::to_array( $cabinet );
			$ancestors[] = array(
				'id'   => $current,
				'slug' => (string) ( $row['slug'] ?? '' ),
				'name' => (string) ( $row['name'] ?? '' ),
			);

			$current = $relations->get_parent_id( $current );
		}

		$ancestors = array_reverse( $ancestors );
		$chain     = array();

		foreach ( $ancestors as $index => $ancestor ) {
			$slug = (string) ( $ancestor['slug'] ?? '' );

			if ( '' === $slug ) {
				continue;
			}

			$is_last = ( $index === count( $ancestors ) - 1 );
			$url     = '';

			if ( ! $is_last ) {
				$url = self::resolve_child_list_url( $category_slug, $slug );
			}

			$chain[] = array(
				'label' => (string) ( $ancestor['name'] ?? $slug ),
				'url'   => $url,
			);
		}

		return array_merge( $crumbs, $chain );
	}

	private static function resolve_back_url( int $parent_id, string $category_slug, string $group_url ): string {
		if ( $parent_id <= 0 || ! function_exists( 'kcp_plugin' ) ) {
			return $group_url;
		}

		/** @var CabinetRelationRepository $relations */
		$relations = kcp_plugin()->container()->get( CabinetRelationRepository::class );
		$grandparent_id = $relations->get_parent_id( $parent_id );

		if ( $grandparent_id <= 0 ) {
			return $group_url;
		}

		/** @var CabinetRepository $repo */
		$repo = kcp_plugin()->container()->get( CabinetRepository::class );
		$cabinet = $repo->find( $grandparent_id );

		if ( null === $cabinet ) {
			return $group_url;
		}

		$row  = Arr::to_array( $cabinet );
		$slug = (string) ( $row['slug'] ?? '' );

		if ( '' === $slug ) {
			return $group_url;
		}

		$url = self::resolve_child_list_url( $category_slug, $slug );

		return '' !== $url ? $url : $group_url;
	}

	/**
	 * Resolve the cabinet select page URL.
	 */
	public static function resolve_select_page_url(): string {
		return self::resolve_cabinet_select_page_url();
	}

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

		return home_url( '/' );
	}

	private static function resolve_design_page_url(): string {
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

	private static function resolve_shop_url(): string {
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
