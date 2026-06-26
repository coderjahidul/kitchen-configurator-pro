<?php
/**
 * Cabinet detail step configuration service.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Repositories\AccessoryRepository;
use KitchenConfiguratorPro\Repositories\CabinetCategoryRepository;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Repositories\LayoutRepository;
use KitchenConfiguratorPro\Repositories\PlinthRepository;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Builds public config for leaf cabinet detail pages.
 */
final class CabinetDetailStepService {

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'back_label' => __( 'terug naar kasten', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * @param string $category_slug       Cabinet category slug.
	 * @param string $parent_cabinet_slug Parent cabinet slug.
	 * @param string $cabinet_slug        Leaf cabinet slug.
	 * @return array<string, mixed>
	 */
	public static function get_public_config( string $category_slug, string $parent_cabinet_slug, string $cabinet_slug ): array {
		$category_slug       = sanitize_title( $category_slug );
		$parent_cabinet_slug = sanitize_title( $parent_cabinet_slug );
		$cabinet_slug        = sanitize_title( $cabinet_slug );
		$category            = self::find_category( $category_slug );
		$category_id         = is_array( $category ) ? (int) ( $category['id'] ?? 0 ) : 0;
		$cabinet             = self::find_cabinet( $cabinet_slug, $category_id );
		$parent              = self::find_cabinet( $parent_cabinet_slug, $category_id );
		$select_settings     = CabinetSelectStepService::get_settings();
		$design              = DesignStepService::get_settings();
		$defaults            = self::defaults();
		$shared              = CabinetSelectStepService::get_public_config();

		$select_url = CabinetListStepService::resolve_select_page_url();
		$design_url = self::resolve_design_page_url();
		$group_url  = CabinetGroupStepService::resolve_group_page_url( $category_slug );
		$list_url   = CabinetListStepService::resolve_child_list_url( $category_slug, $parent_cabinet_slug );

		$breadcrumb_parent     = (string) ( $select_settings['breadcrumb_parent'] ?? '' );
		$breadcrumb_parent_url = (string) ( $select_settings['breadcrumb_parent_url'] ?? '' );

		if ( '' === $breadcrumb_parent ) {
			$breadcrumb_parent = (string) ( $design['heading'] ?? $design['breadcrumb'] ?? __( 'ontwerp jouw keuken', 'kitchen-configurator-pro' ) );
		}

		if ( '' === $breadcrumb_parent_url ) {
			$breadcrumb_parent_url = $design_url;
		}

		$list_config = CabinetListStepService::get_public_config( $category_slug, $parent_cabinet_slug );
		$breadcrumbs = is_array( $list_config['breadcrumbs'] ?? null ) ? $list_config['breadcrumbs'] : array();

		if ( ! empty( $breadcrumbs ) ) {
			$last_index                        = count( $breadcrumbs ) - 1;
			$breadcrumbs[ $last_index ]['url'] = $list_url;
		} elseif ( is_array( $parent ) ) {
			$breadcrumbs[] = array(
				'label' => (string) ( $parent['name'] ?? $parent_cabinet_slug ),
				'url'   => $list_url,
			);
		}

		$heading     = is_array( $cabinet ) ? (string) ( $cabinet['name'] ?? $cabinet_slug ) : $cabinet_slug;
		$dimensions  = self::resolve_dimensions( $cabinet );
		$plinth      = self::resolve_plinth_field( $defaults );
		$base_price  = is_array( $cabinet ) ? (float) ( $cabinet['base_price'] ?? 0 ) : 0.0;

		return array(
			'category_slug'       => $category_slug,
			'parent_cabinet_slug' => $parent_cabinet_slug,
			'parent_cabinet_id'   => is_array( $parent ) ? (int) ( $parent['id'] ?? 0 ) : 0,
			'cabinet_slug'        => $cabinet_slug,
			'cabinet_id'          => is_array( $cabinet ) ? (int) ( $cabinet['id'] ?? 0 ) : 0,
			'layout_id'           => self::resolve_layout_id(),
			'heading'             => $heading,
			'images'              => self::resolve_images( $cabinet ),
			'product_info'        => self::parse_product_info( is_array( $cabinet ) ? (string) ( $cabinet['description'] ?? '' ) : '' ),
			'base_price'          => $base_price,
			'display_price'       => $base_price,
			'dimensions'          => $dimensions,
			'plinth'              => $plinth,
			'plinth_id'           => (int) ( $plinth['id'] ?? 0 ),
			'upsells'             => self::resolve_upsells(),
			'breadcrumbs'         => $breadcrumbs,
			'back_url'            => $list_url,
			'back_label'          => (string) ( $defaults['back_label'] ?? '' ),
			'cart_url'            => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
			'cart_enabled'        => false,
			'labels'              => self::labels(),
			'api'                 => self::resolve_api_config(),
			'selections'          => array(
				'width'    => (int) ( $dimensions['width']['value'] ?? 0 ),
				'height'   => (int) ( $dimensions['height']['value'] ?? 0 ),
				'depth'    => (int) ( $dimensions['depth']['value'] ?? 0 ),
				'plinth'   => (int) ( $plinth['value'] ?? 0 ),
				'upsells'  => array(),
				'quantity' => 1,
			),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private static function labels(): array {
		return array(
			'select_format'    => __( 'Selecteer formaat', 'kitchen-configurator-pro' ),
			'width'            => __( 'Breedte', 'kitchen-configurator-pro' ),
			'height'           => __( 'Hoogte', 'kitchen-configurator-pro' ),
			'depth'            => __( 'Diepte', 'kitchen-configurator-pro' ),
			'plinth'           => __( 'Plinthoogte', 'kitchen-configurator-pro' ),
			'product_info'     => __( 'Productinformatie', 'kitchen-configurator-pro' ),
			'upsell_heading'   => __( 'Maak je aankoop compleet', 'kitchen-configurator-pro' ),
			'select_quantity'  => __( 'Selecteer aantal', 'kitchen-configurator-pro' ),
			'per_unit'         => __( 'per stuk', 'kitchen-configurator-pro' ),
			'add_to_cart'      => __( 'Voeg toe aan winkelwagen', 'kitchen-configurator-pro' ),
			'decrease_qty'     => __( 'Minder', 'kitchen-configurator-pro' ),
			'increase_qty'     => __( 'Meer', 'kitchen-configurator-pro' ),
			'quantity'         => __( 'Aantal', 'kitchen-configurator-pro' ),
			'cm_breed'         => __( 'breed', 'kitchen-configurator-pro' ),
			'cm_hoog'          => __( 'hoog', 'kitchen-configurator-pro' ),
			'cm_diep'          => __( 'diep', 'kitchen-configurator-pro' ),
			'plinth_suffix'    => __( 'hoge plint', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function resolve_api_config(): array {
		return array(
			'apiUrl'     => esc_url_raw( rest_url( 'kcp/v1' ) ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'isLoggedIn' => is_user_logged_in(),
		);
	}

	/**
	 * @param array<string, mixed>|null $cabinet Cabinet row.
	 * @return array<int, array<string, string>>
	 */
	private static function resolve_images( ?array $cabinet ): array {
		if ( ! is_array( $cabinet ) ) {
			return array();
		}

		$image_url = esc_url_raw( (string) ( $cabinet['image_url'] ?? '' ) );

		if ( '' === $image_url ) {
			return array();
		}

		$name = (string) ( $cabinet['name'] ?? '' );

		return array(
			array(
				'url'   => $image_url,
				'alt'   => $name,
				'thumb' => true,
			),
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function resolve_upsells(): array {
		if ( ! function_exists( 'kcp_plugin' ) ) {
			return array();
		}

		/** @var AccessoryRepository $repo */
		$repo    = kcp_plugin()->container()->get( AccessoryRepository::class );
		$upsells = array();

		foreach ( $repo->find_all( array( 'is_active' => '1' ) ) as $accessory ) {
			$row = Arr::to_array( $accessory );

			if ( empty( $row['is_per_cabinet'] ) ) {
				continue;
			}

			$price = (float) ( $row['price'] ?? 0 );
			$name  = (string) ( $row['name'] ?? '' );

			$upsells[] = array(
				'id'          => (int) ( $row['id'] ?? 0 ),
				'name'        => $name,
				'description' => (string) ( $row['description'] ?? '' ),
				'price'       => $price,
				'label'       => sprintf(
					'%s + %s,-',
					$name,
					number_format_i18n( $price, 0 )
				),
			);
		}

		return $upsells;
	}

	/**
	 * @param array<string, mixed> $defaults Default settings.
	 * @return array<string, mixed>
	 */
	private static function resolve_plinth_field( array $defaults ): array {
		unset( $defaults );

		$plinth = self::resolve_default_plinth();

		if ( null === $plinth ) {
			return array(
				'id'      => 0,
				'value'   => 0,
				'options' => array(),
			);
		}

		$default_value = (int) $plinth->default_height;
		$min           = (int) $plinth->min_height;
		$max           = (int) $plinth->max_height;
		$step          = max( 1, (int) $plinth->height_step );
		$options       = array();

		if ( $max > 0 && $min <= $max ) {
			for ( $mm = $min; $mm <= $max; $mm += $step ) {
				$options[] = array(
					'value'    => $mm,
					'label'    => self::format_plinth_label( $mm ),
					'selected' => $mm === $default_value,
				);
			}
		}

		if ( empty( $options ) && $default_value > 0 ) {
			$options[] = array(
				'value'    => $default_value,
				'label'    => self::format_plinth_label( $default_value ),
				'selected' => true,
			);
		}

		$options = apply_filters( 'kcp_cabinet_detail_plinth_options', $options, $plinth->to_array() );

		return array(
			'id'      => (int) $plinth->id,
			'value'   => $default_value,
			'options' => $options,
		);
	}

	private static function resolve_default_plinth(): ?\KitchenConfiguratorPro\Domain\Entities\Plinth {
		if ( ! function_exists( 'kcp_plugin' ) ) {
			return null;
		}

		/** @var PlinthRepository $repo */
		$repo = kcp_plugin()->container()->get( PlinthRepository::class );

		foreach ( $repo->find_all( array( 'is_active' => '1' ) ) as $plinth ) {
			return $plinth;
		}

		return null;
	}

	private static function format_plinth_label( int $mm ): string {
		$cm = $mm / 10;
		$formatted = fmod( $cm, 1.0 ) === 0.0
			? (string) (int) $cm
			: str_replace( '.', ',', (string) $cm );

		return sprintf(
			/* translators: %s: plinth height in cm */
			__( '%s cm hoge plint', 'kitchen-configurator-pro' ),
			$formatted
		);
	}

	/**
	 * @param string $description Cabinet description.
	 * @return array<int, string>
	 */
	private static function parse_product_info( string $description ): array {
		$raw = trim( $description );

		if ( '' === $raw ) {
			return array();
		}

		if ( str_contains( $raw, '<li' ) ) {
			$items = array();
			preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', $raw, $matches );

			foreach ( $matches[1] ?? array() as $item ) {
				$text = trim( wp_strip_all_tags( (string) $item ) );

				if ( '' !== $text ) {
					$items[] = $text;
				}
			}

			return $items;
		}

		$lines = preg_split( '/\r\n|\r|\n/', $raw ) ?: array();
		$items = array();

		foreach ( $lines as $line ) {
			$line = trim( (string) preg_replace( '/^[-*•]\s*/', '', trim( $line ) ) );

			if ( '' !== $line ) {
				$items[] = $line;
			}
		}

		return $items;
	}

	/**
	 * @param array<string, mixed>|null $cabinet Cabinet row.
	 * @return array<string, mixed>
	 */
	private static function resolve_dimensions( ?array $cabinet ): array {
		if ( ! is_array( $cabinet ) ) {
			return array();
		}

		$rules = json_decode( (string) ( $cabinet['dimension_price_json'] ?? '' ), true );
		$rules = is_array( $rules ) ? $rules : array();

		return array(
			'width'  => self::resolve_dimension_axis( $cabinet, $rules, 'width', __( 'breed', 'kitchen-configurator-pro' ) ),
			'height' => self::resolve_dimension_axis( $cabinet, $rules, 'height', __( 'hoog', 'kitchen-configurator-pro' ) ),
			'depth'  => self::resolve_dimension_axis( $cabinet, $rules, 'depth', __( 'diep', 'kitchen-configurator-pro' ) ),
		);
	}

	/**
	 * @param array<string, mixed> $cabinet Cabinet row.
	 * @param array<string, mixed> $rules   Dimension pricing rules.
	 * @param string               $axis    Axis key.
	 * @param string               $suffix  Label suffix.
	 * @return array<string, mixed>
	 */
	private static function resolve_dimension_axis( array $cabinet, array $rules, string $axis, string $suffix ): array {
		$field = self::normalize_dimension_field(
			(int) ( $cabinet[ 'default_' . $axis ] ?? 0 ),
			(int) ( $cabinet[ 'min_' . $axis ] ?? 0 ),
			(int) ( $cabinet[ 'max_' . $axis ] ?? 0 ),
			(int) ( $cabinet[ $axis . '_step' ] ?? 10 )
		);

		$discrete = array();

		if ( isset( $rules[ $axis ]['options'] ) && is_array( $rules[ $axis ]['options'] ) ) {
			$discrete = array_values(
				array_filter(
					array_map( 'intval', $rules[ $axis ]['options'] ),
					static fn ( int $value ): bool => $value > 0
				)
			);
		}

		$field['options'] = self::build_dimension_options( $field, $suffix, $discrete );

		return $field;
	}

	/**
	 * @param array{value:int,min:int,max:int,step:int} $field   Normalized field.
	 * @param string                                    $suffix  Label suffix.
	 * @param array<int>                                $discrete Optional discrete values in mm.
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_dimension_options( array $field, string $suffix, array $discrete = array() ): array {
		$selected = (int) ( $field['value'] ?? 0 );
		$values   = $discrete;

		if ( empty( $values ) ) {
			$min  = (int) ( $field['min'] ?? 0 );
			$max  = (int) ( $field['max'] ?? 0 );
			$step = max( 1, (int) ( $field['step'] ?? 10 ) );

			if ( $max > 0 && $min !== $max ) {
				for ( $mm = $min; $mm <= $max; $mm += $step ) {
					$values[] = $mm;
				}
			} elseif ( $selected > 0 ) {
				$values[] = $selected;
			}
		}

		$options = array();

		foreach ( array_values( array_unique( $values ) ) as $mm ) {
			$options[] = array(
				'value'    => $mm,
				'label'    => self::format_dimension_label( $mm, $suffix ),
				'selected' => $mm === $selected,
			);
		}

		if ( empty( $options ) && $selected > 0 ) {
			$options[] = array(
				'value'    => $selected,
				'label'    => self::format_dimension_label( $selected, $suffix ),
				'selected' => true,
			);
		}

		return $options;
	}

	private static function format_dimension_label( int $mm, string $suffix ): string {
		$cm        = $mm / 10;
		$formatted = fmod( $cm, 1.0 ) === 0.0
			? (string) (int) $cm
			: str_replace( '.', ',', (string) round( $cm, 1 ) );

		return sprintf( '%s cm %s', $formatted, $suffix );
	}

	/**
	 * @return array{value:int,min:int,max:int,step:int}
	 */
	private static function normalize_dimension_field( int $value, int $min, int $max, int $step ): array {
		$normalized_min = min( $min, $max );
		$normalized_max = max( $min, $max );

		if ( $normalized_max <= 0 && $value > 0 ) {
			$normalized_min = $value;
			$normalized_max = $value;
		}

		return array(
			'value' => $value,
			'min'   => $normalized_min,
			'max'   => $normalized_max,
			'step'  => max( 1, $step ),
		);
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
	private static function find_cabinet( string $slug, int $category_id ): ?array {
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

	private static function resolve_layout_id(): int {
		if ( ! function_exists( 'kcp_plugin' ) ) {
			return 0;
		}

		/** @var LayoutRepository $repo */
		$repo = kcp_plugin()->container()->get( LayoutRepository::class );

		foreach ( $repo->find_all( array( 'is_active' => '1' ) ) as $layout ) {
			$row = Arr::to_array( $layout );
			$id  = (int) ( $row['id'] ?? 0 );

			if ( $id > 0 ) {
				return $id;
			}
		}

		return 0;
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
}
