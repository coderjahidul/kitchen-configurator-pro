<?php
/**
 * Cabinet group step page settings (onderkasten / bovenkasten / hoge-kasten).
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Repositories\CabinetCategoryRepository;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Reads and normalizes a single cabinet group page configuration.
 */
final class CabinetGroupStepService {

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'back_label' => __( 'terug naar kasten', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * @param string $slug Cabinet category slug.
	 * @return array<string, mixed>
	 */
	public static function get_public_config( string $slug ): array {
		$slug     = sanitize_title( $slug );
		$category = self::find_category( $slug );
		$select   = CabinetSelectStepService::get_settings();
		$design   = DesignStepService::get_settings();
		$defaults = self::defaults();

		$select_url = self::resolve_cabinet_select_page_url();
		$design_url = self::resolve_design_page_url();
		$shop_url   = self::resolve_shop_url();
		$shared     = CabinetSelectStepService::get_public_config();

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

		$heading = is_array( $category ) ? (string) ( $category['name'] ?? $slug ) : $slug;
		$items   = self::resolve_items( $slug, is_array( $category ) ? (int) ( $category['id'] ?? 0 ) : 0, $shop_url );

		return array(
			'slug'                    => $slug,
			'category_id'             => is_array( $category ) ? (int) ( $category['id'] ?? 0 ) : 0,
			'heading'                 => $heading,
			'breadcrumb_parent'         => $breadcrumb_parent,
			'breadcrumb_parent_url'   => $breadcrumb_parent_url,
			'breadcrumb_middle'       => $breadcrumb_middle,
			'breadcrumb_middle_url'   => $breadcrumb_middle_url,
			'breadcrumb_current'      => $heading,
			'back_url'                => $select_url,
			'back_label'              => (string) ( $defaults['back_label'] ?? '' ),
			'shop_url'                => $shop_url,
			'items'                   => $items,
			'design_zones'            => $shared['design_zones'] ?? array(),
			'catalog_options'         => $shared['catalog_options'] ?? array(),
			'kitchen_types'           => KitchenTypeService::labels(),
			'default_kitchen_type'    => KitchenTypeService::TYPE_GREP,
		);
	}

	/**
	 * Resolve permalink for a cabinet group page by slug.
	 */
	public static function resolve_group_page_url( string $slug ): string {
		$slug = sanitize_title( $slug );

		if ( '' === $slug ) {
			return '';
		}

		$transient_key = 'kcp_cabinet_group_url_' . $slug;
		$cached        = get_transient( $transient_key );

		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$shortcode = sprintf( '[kcp_cabinet_group slug="%s"]', $slug );
		$posts     = get_posts(
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
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			if ( ! has_shortcode( $post->post_content, 'kcp_cabinet_group' ) ) {
				continue;
			}

			if ( $post->post_name !== $slug && ! str_contains( $post->post_content, 'slug="' . $slug . '"' ) ) {
				continue;
			}

			$url = get_permalink( $post );
			if ( is_string( $url ) && '' !== $url ) {
				set_transient( $transient_key, $url, DAY_IN_SECONDS );
				return $url;
			}
		}

		$select_url = self::resolve_cabinet_select_page_url();
		if ( '' === $select_url ) {
			return '';
		}

		return trailingslashit( $select_url ) . $slug . '/';
	}

	/**
	 * @return array<string, string> Group page URLs keyed by category slug.
	 */
	public static function group_page_urls(): array {
		$urls = array();

		if ( ! function_exists( 'kcp_plugin' ) ) {
			return $urls;
		}

		/** @var CabinetCategoryRepository $repo */
		$repo = kcp_plugin()->container()->get( CabinetCategoryRepository::class );

		foreach ( $repo->find_all( array( 'is_active' => '1' ) ) as $category ) {
			$row  = Arr::to_array( $category );
			$slug = (string) ( $row['slug'] ?? '' );

			if ( '' === $slug ) {
				continue;
			}

			$url = self::resolve_group_page_url( $slug );
			if ( '' !== $url ) {
				$urls[ $slug ] = $url;
			}
		}

		return $urls;
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
	 * @return array<int, array<string, mixed>>
	 */
	private static function resolve_items( string $slug, int $category_id, string $shop_url ): array {
		$db_items = self::items_from_database( $category_id, $shop_url, $slug );

		if ( ! empty( $db_items ) ) {
			return $db_items;
		}

		$defaults = self::default_items();
		$items    = $defaults[ $slug ] ?? array();

		return array_map(
			static function ( array $item ) use ( $shop_url, $slug ): array {
				return array(
					'id'        => 0,
					'slug'      => (string) ( $item['slug'] ?? '' ),
					'name'      => (string) ( $item['name'] ?? '' ),
					'image_url' => (string) ( $item['image_url'] ?? '' ),
					'url'       => self::build_item_url( $shop_url, $slug, (string) ( $item['slug'] ?? '' ) ),
				);
			},
			$items
		);
	}

	/**
	 * Load active cabinets for a category from the admin catalog.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function items_from_database( int $category_id, string $shop_url, string $category_slug ): array {
		if ( $category_id <= 0 || ! function_exists( 'kcp_plugin' ) ) {
			return array();
		}

		/** @var CabinetRepository $repo */
		$repo  = kcp_plugin()->container()->get( CabinetRepository::class );
		$items = array();

		foreach (
			$repo->find_all(
				array(
					'category_id' => (string) $category_id,
					'is_active'   => '1',
				)
			) as $cabinet
		) {
			$row = Arr::to_array( $cabinet );

			$items[] = array(
				'id'        => (int) ( $row['id'] ?? 0 ),
				'slug'      => (string) ( $row['slug'] ?? '' ),
				'name'      => (string) ( $row['name'] ?? '' ),
				'image_url' => (string) ( $row['image_url'] ?? '' ),
				'url'       => self::build_item_url(
					$shop_url,
					$category_slug,
					(string) ( $row['slug'] ?? '' ),
					(int) ( $row['id'] ?? 0 )
				),
			);
		}

		return $items;
	}

	/**
	 * Fallback subtypes when no cabinets exist in admin for a category.
	 *
	 * @return array<string, array<int, array<string, string>>>
	 */
	private static function default_items(): array {
		return array(
			'onderkasten' => array(
				array( 'slug' => 'onderkasten-met-deuren', 'name' => 'onderkasten met deur(en)' ),
				array( 'slug' => 'onderkasten-met-laden', 'name' => 'onderkasten met lade(n)' ),
				array( 'slug' => 'onderkast-kookplaat-afzuiging', 'name' => 'onderkast voor kookplaat met werkbladafzuiging' ),
				array( 'slug' => 'onderkasten-magnetron-oven', 'name' => 'onderkasten voor magnetron of oven' ),
				array( 'slug' => 'spoelkasten', 'name' => 'spoelkasten met deur(en) of laden' ),
				array( 'slug' => 'fronten-vaatwasser', 'name' => 'fronten voor vaatwasser' ),
				array( 'slug' => 'onderkast-accessoires', 'name' => 'onderkast accessoires' ),
				array( 'slug' => 'afwerkpanelen', 'name' => 'afwerkpanelen' ),
				array( 'slug' => 'hoekkasten', 'name' => 'hoekkasten' ),
				array( 'slug' => 'carrousselkasten', 'name' => 'carrousselkasten' ),
				array( 'slug' => 'onderkasten-laden-deuren', 'name' => 'onderkasten met lade(n) en deur(en)' ),
				array( 'slug' => 'open-kast-regaal', 'name' => 'open kast regaal' ),
			),
			'bovenkasten' => array(
				array( 'slug' => 'bovenkasten-met-deuren', 'name' => 'Bovenkasten met deur(en)' ),
				array( 'slug' => 'bovenkast-magnetron', 'name' => 'Bovenkast voor magnetron' ),
				array( 'slug' => 'bovenkasten-afzuigsystemen', 'name' => 'Bovenkasten voor afzuigsystemen' ),
				array( 'slug' => 'bovenkast-accessoires', 'name' => 'Bovenkast accessoires' ),
				array( 'slug' => 'bovenkasten-met-klep', 'name' => 'Bovenkasten met klep' ),
				array( 'slug' => 'bovenkast-afwerkpanelen', 'name' => 'Bovenkast afwerkpanelen' ),
			),
			'hoge-kasten' => array(
				array( 'slug' => 'hoge-kast-143cm', 'name' => 'Hoge kast(en) 143cm hoog' ),
				array( 'slug' => 'hoge-kast-194cm', 'name' => 'Hoge kast(en) 194.8cm hoog' ),
				array( 'slug' => 'hoge-kast-207cm', 'name' => 'Hoge kast(en) 207.8cm hoog' ),
				array( 'slug' => 'hoge-kast-220cm', 'name' => 'Hoge kast(en) 220.8cm hoog' ),
				array( 'slug' => 'hoge-kast-accessoires', 'name' => 'Hoge kast accessoires' ),
				array( 'slug' => 'hoge-kast-afwerkpanelen', 'name' => 'Hoge kast afwerkpanelen' ),
			),
		);
	}

	private static function build_item_url( string $shop_url, string $category_slug, string $item_slug, int $cabinet_id = 0 ): string {
		if ( '' === $shop_url ) {
			return '';
		}

		$args = array(
			'kcp_category_slug' => $category_slug,
		);

		if ( '' !== $item_slug ) {
			$args['kcp_subtype'] = $item_slug;
		}

		if ( $cabinet_id > 0 ) {
			$args['kcp_cabinet'] = (string) $cabinet_id;
		}

		return add_query_arg( $args, $shop_url );
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
