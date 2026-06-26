<?php
/**
 * Cabinet child-list and detail URL routing.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

use KitchenConfiguratorPro\Repositories\CabinetRelationRepository;
use KitchenConfiguratorPro\Services\CabinetDetailStepService;
use KitchenConfiguratorPro\Services\CabinetListStepService;
use KitchenConfiguratorPro\Support\Helpers;

/**
 * Registers rewrite rules and handles child cabinet list/detail routes.
 */
final class CabinetRouter {

	private const REWRITE_VERSION = '3';

	private static bool $is_child_list_route = false;

	private static bool $is_detail_route = false;

	public function register(): void {
		add_action( 'init', array( $this, 'register_rewrite_rules' ), 20 );
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 99 );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_filter( 'request', array( $this, 'match_cabinet_request' ) );
		add_action( 'template_redirect', array( $this, 'handle_detail_route' ), 4 );
		add_action( 'template_redirect', array( $this, 'handle_child_list_route' ), 5 );
	}

	/**
	 * Register rewrite rules for child cabinet list and detail URLs.
	 *
	 * @return void
	 */
	public function register_rewrite_rules(): void {
		$select_path = $this->resolve_select_page_path();

		if ( '' === $select_path ) {
			return;
		}

		$quoted = preg_quote( $select_path, '/' );

		add_rewrite_rule(
			'^' . $quoted . '/([^/]+)/([^/]+)/([^/]+)/?$',
			'index.php?kcp_cabinet_detail=1&kcp_category_slug=$matches[1]&kcp_parent_cabinet_slug=$matches[2]&kcp_cabinet_slug=$matches[3]',
			'top'
		);

		add_rewrite_rule(
			'^' . $quoted . '/([^/]+)/([^/]+)/?$',
			'index.php?kcp_cabinet_child_list=1&kcp_category_slug=$matches[1]&kcp_parent_cabinet_slug=$matches[2]',
			'top'
		);
	}

	/**
	 * @param array<string, mixed> $query_vars Query vars.
	 * @return array<string, mixed>
	 */
	public function match_cabinet_request( array $query_vars ): array {
		if ( ! empty( $query_vars['kcp_cabinet_child_list'] ) || ! empty( $query_vars['kcp_cabinet_detail'] ) ) {
			return $query_vars;
		}

		$detail_match = $this->match_detail_request_path();

		if ( null !== $detail_match ) {
			unset( $query_vars['pagename'], $query_vars['name'], $query_vars['page'], $query_vars['error'] );

			return array_merge(
				$query_vars,
				array(
					'kcp_cabinet_detail'        => '1',
					'kcp_category_slug'         => $detail_match['category_slug'],
					'kcp_parent_cabinet_slug'   => $detail_match['parent_slug'],
					'kcp_cabinet_slug'          => $detail_match['cabinet_slug'],
				)
			);
		}

		$list_match = $this->match_list_request_path();

		if ( null === $list_match ) {
			return $query_vars;
		}

		unset( $query_vars['pagename'], $query_vars['name'], $query_vars['page'], $query_vars['error'] );

		return array_merge(
			$query_vars,
			array(
				'kcp_cabinet_child_list'    => '1',
				'kcp_category_slug'         => $list_match['category_slug'],
				'kcp_parent_cabinet_slug'   => $list_match['parent_slug'],
			)
		);
	}

	/**
	 * @param array<int, string> $vars Query vars.
	 * @return array<int, string>
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = 'kcp_cabinet_child_list';
		$vars[] = 'kcp_cabinet_detail';
		$vars[] = 'kcp_category_slug';
		$vars[] = 'kcp_parent_cabinet_slug';
		$vars[] = 'kcp_cabinet_slug';

		return $vars;
	}

	/**
	 * Render leaf cabinet detail for virtual routes.
	 *
	 * @return void
	 */
	public function handle_detail_route(): void {
		if ( ! get_query_var( 'kcp_cabinet_detail' ) ) {
			return;
		}

		$category_slug = sanitize_title( (string) get_query_var( 'kcp_category_slug', '' ) );
		$parent_slug   = sanitize_title( (string) get_query_var( 'kcp_parent_cabinet_slug', '' ) );
		$cabinet_slug  = sanitize_title( (string) get_query_var( 'kcp_cabinet_slug', '' ) );

		if ( '' === $category_slug || '' === $parent_slug || '' === $cabinet_slug ) {
			$this->render_not_found();
			return;
		}

		if ( ! $this->is_valid_detail_route( $category_slug, $parent_slug, $cabinet_slug ) ) {
			$this->render_not_found();
			return;
		}

		self::$is_detail_route = true;
		status_header( 200 );

		global $wp_query;
		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404 = false;
		}

		$config  = CabinetDetailStepService::get_public_config( $category_slug, $parent_slug, $cabinet_slug );
		$heading = (string) ( $config['heading'] ?? $cabinet_slug );

		add_filter(
			'pre_get_document_title',
			static function () use ( $heading ): string {
				$site = get_bloginfo( 'name', 'display' );
				return '' !== $site ? $heading . ' | ' . $site : $heading;
			},
			99
		);

		get_header();
		echo do_shortcode(
			sprintf(
				'[kcp_cabinet_detail category="%s" parent="%s" cabinet="%s"]',
				esc_attr( $category_slug ),
				esc_attr( $parent_slug ),
				esc_attr( $cabinet_slug )
			)
		);
		get_footer();
		exit;
	}

	/**
	 * Render child cabinet list for virtual routes.
	 *
	 * @return void
	 */
	public function handle_child_list_route(): void {
		if ( ! get_query_var( 'kcp_cabinet_child_list' ) ) {
			return;
		}

		$category_slug = sanitize_title( (string) get_query_var( 'kcp_category_slug', '' ) );
		$parent_slug   = sanitize_title( (string) get_query_var( 'kcp_parent_cabinet_slug', '' ) );

		if ( '' === $category_slug || '' === $parent_slug ) {
			$this->render_not_found();
			return;
		}

		if ( ! $this->is_valid_child_list_route( $category_slug, $parent_slug ) ) {
			$this->render_not_found();
			return;
		}

		self::$is_child_list_route = true;
		status_header( 200 );

		global $wp_query;
		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404 = false;
		}

		$config  = CabinetListStepService::get_public_config( $category_slug, $parent_slug );
		$heading = (string) ( $config['heading'] ?? $parent_slug );

		add_filter(
			'pre_get_document_title',
			static function () use ( $heading ): string {
				$site = get_bloginfo( 'name', 'display' );
				return '' !== $site ? $heading . ' | ' . $site : $heading;
			},
			99
		);

		get_header();
		echo do_shortcode(
			sprintf(
				'[kcp_cabinet_list category="%s" parent="%s"]',
				esc_attr( $category_slug ),
				esc_attr( $parent_slug )
			)
		);
		get_footer();
		exit;
	}

	public static function is_child_list_route(): bool {
		return self::$is_child_list_route || (bool) get_query_var( 'kcp_cabinet_child_list' );
	}

	public static function is_detail_route(): bool {
		return self::$is_detail_route || (bool) get_query_var( 'kcp_cabinet_detail' );
	}

	/**
	 * Validate that the parent cabinet exists and has children.
	 */
	private function is_valid_child_list_route( string $category_slug, string $parent_slug ): bool {
		if ( ! function_exists( 'kcp_plugin' ) ) {
			return false;
		}

		$config    = CabinetListStepService::get_public_config( $category_slug, $parent_slug );
		$parent_id = (int) ( $config['parent_cabinet_id'] ?? 0 );

		if ( $parent_id <= 0 ) {
			return false;
		}

		/** @var CabinetRelationRepository $relations */
		$relations = kcp_plugin()->container()->get( CabinetRelationRepository::class );

		return $relations->has_children( $parent_id );
	}

	private function is_valid_detail_route( string $category_slug, string $parent_slug, string $cabinet_slug ): bool {
		if ( ! function_exists( 'kcp_plugin' ) ) {
			return false;
		}

		$config = CabinetDetailStepService::get_public_config( $category_slug, $parent_slug, $cabinet_slug );
		$cabinet_id = (int) ( $config['cabinet_id'] ?? 0 );
		$parent_id  = (int) ( $config['parent_cabinet_id'] ?? 0 );

		if ( $cabinet_id <= 0 || $parent_id <= 0 ) {
			return false;
		}

		/** @var CabinetRelationRepository $relations */
		$relations = kcp_plugin()->container()->get( CabinetRelationRepository::class );

		if ( $relations->has_children( $cabinet_id ) ) {
			return false;
		}

		$child_ids = $relations->get_child_ids( $parent_id );

		return in_array( $cabinet_id, $child_ids, true );
	}

	/**
	 * @return array{category_slug: string, parent_slug: string, cabinet_slug: string}|null
	 */
	private function match_detail_request_path(): ?array {
		$select_path = $this->resolve_select_page_path();
		$uri         = $this->current_relative_request_path();

		if ( '' === $select_path || '' === $uri ) {
			return null;
		}

		$pattern = '#^' . preg_quote( $select_path, '#' ) . '/([^/]+)/([^/]+)/([^/]+)/?$#';

		if ( ! preg_match( $pattern, $uri, $matches ) ) {
			return null;
		}

		return array(
			'category_slug' => sanitize_title( (string) ( $matches[1] ?? '' ) ),
			'parent_slug'   => sanitize_title( (string) ( $matches[2] ?? '' ) ),
			'cabinet_slug'  => sanitize_title( (string) ( $matches[3] ?? '' ) ),
		);
	}

	/**
	 * @return array{category_slug: string, parent_slug: string}|null
	 */
	private function match_list_request_path(): ?array {
		$select_path = $this->resolve_select_page_path();
		$uri         = $this->current_relative_request_path();

		if ( '' === $select_path || '' === $uri ) {
			return null;
		}

		$pattern = '#^' . preg_quote( $select_path, '#' ) . '/([^/]+)/([^/]+)/?$#';

		if ( ! preg_match( $pattern, $uri, $matches ) ) {
			return null;
		}

		return array(
			'category_slug' => sanitize_title( (string) ( $matches[1] ?? '' ) ),
			'parent_slug'   => sanitize_title( (string) ( $matches[2] ?? '' ) ),
		);
	}

	private function current_relative_request_path(): string {
		$uri = isset( $_SERVER['REQUEST_URI'] )
			? (string) wp_unslash( $_SERVER['REQUEST_URI'] )
			: '';

		if ( '' === $uri ) {
			return '';
		}

		$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
		$home_path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );

		if ( '' !== $home_path ) {
			if ( $path === $home_path ) {
				return '';
			}

			if ( str_starts_with( $path, $home_path . '/' ) ) {
				return substr( $path, strlen( $home_path ) + 1 );
			}
		}

		return $path;
	}

	private function render_not_found(): void {
		global $wp_query;

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->set_404();
		}

		status_header( 404 );
		nocache_headers();
		get_template_part( 404 );
		exit;
	}

	private function resolve_select_page_path(): string {
		$cached = get_transient( 'kcp_cabinet_select_page_path' );

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
			if ( ! is_string( $url ) || '' === $url ) {
				continue;
			}

			$path = Helpers::relative_site_path_from_url( $url );
			if ( '' !== $path ) {
				set_transient( 'kcp_cabinet_select_page_path', $path, DAY_IN_SECONDS );
				return $path;
			}
		}

		return '';
	}

	public function maybe_flush_rewrite_rules(): void {
		$stored_version = (string) get_option( 'kcp_router_rewrite_version', '' );
		$select_path    = $this->resolve_select_page_path();
		$path_hash      = md5( $select_path );
		$stored_hash    = (string) get_option( 'kcp_router_path_hash', '' );

		if ( self::REWRITE_VERSION === $stored_version && $path_hash === $stored_hash ) {
			return;
		}

		update_option( 'kcp_router_rewrite_version', self::REWRITE_VERSION, false );
		update_option( 'kcp_router_path_hash', $path_hash, false );
		Helpers::flush_rewrite_rules();
	}
}
