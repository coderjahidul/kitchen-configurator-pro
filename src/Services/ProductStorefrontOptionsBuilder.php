<?php
/**
 * Builds single product storefront options from configurator catalog data.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Domain\Entities\Cabinet;
use KitchenConfiguratorPro\Domain\Entities\Color;
use KitchenConfiguratorPro\Domain\Entities\Layout;
use KitchenConfiguratorPro\Domain\Entities\Material;
use KitchenConfiguratorPro\Domain\Entities\ProductPreset;
use KitchenConfiguratorPro\Domain\Enums\MaterialType;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Repositories\ColorRepository;
use KitchenConfiguratorPro\Repositories\LayoutRepository;
use KitchenConfiguratorPro\Repositories\MaterialRepository;

/**
 * Resolves KKF-style product options from linked preset + catalog records.
 */
final class ProductStorefrontOptionsBuilder {

	/**
	 * @param LayoutRepository   $layouts   Layout repository.
	 * @param MaterialRepository $materials Material repository.
	 * @param ColorRepository    $colors    Color repository.
	 * @param CabinetRepository  $cabinets  Cabinet repository.
	 */
	public function __construct(
		private readonly LayoutRepository $layouts,
		private readonly MaterialRepository $materials,
		private readonly ColorRepository $colors,
		private readonly CabinetRepository $cabinets
	) {
	}

	/**
	 * Build storefront options for a linked product preset.
	 *
	 * Manual `product_options` values override auto-generated catalog data.
	 *
	 * @param ProductPreset $preset Product preset.
	 * @return array<string, mixed>
	 */
	public function build( ProductPreset $preset ): array {
		$manual  = $preset->product_options();
		$layout  = $this->layouts->find( $preset->layout_id );
		$config  = $this->parse_layout_config( $layout );
		$scope   = $this->parse_scope( $preset->catalog_scope_json );
		$cabinet = $this->resolve_primary_cabinet( $preset, $config );

		$built = array(
			'specs'          => $this->build_specs( $config, $manual, $cabinet ),
			'colors'         => $this->build_colors( $config, $manual, $scope ),
			'heights'        => $this->build_heights( $config, $manual, $cabinet ),
			'default_color'  => (string) ( $manual['default_color'] ?? $config['default_color'] ?? '' ),
			'default_height' => (string) ( $manual['default_height'] ?? '' ),
		);

		if ( '' === $built['default_color'] && ! empty( $built['colors'] ) ) {
			$built['default_color'] = (string) ( $built['colors'][0]['id'] ?? '' );
		}

		if ( '' === $built['default_height'] && ! empty( $built['heights'] ) ) {
			$built['default_height'] = (string) ( $built['heights'][0]['id'] ?? '' );
		}

		return $built;
	}

	/**
	 * Whether a preset can render storefront selectors.
	 *
	 * @param ProductPreset $preset Product preset.
	 * @return bool
	 */
	public function can_render( ProductPreset $preset ): bool {
		if ( ! $preset->is_active || $preset->layout_id <= 0 ) {
			return false;
		}

		$options = $this->build( $preset );

		return ! empty( $options['colors'] )
			|| ! empty( $options['heights'] )
			|| ! empty( $options['specs']['dimensions'] )
			|| ! empty( $options['specs']['includes'] );
	}

	/**
	 * Parse layout config JSON.
	 *
	 * @param Layout|null $layout Layout entity.
	 * @return array<string, mixed>
	 */
	private function parse_layout_config( ?Layout $layout ): array {
		if ( null === $layout || '' === trim( $layout->config_json ) ) {
			return array();
		}

		$config = json_decode( $layout->config_json, true );

		return is_array( $config ) ? $config : array();
	}

	/**
	 * Parse optional catalog scope JSON.
	 *
	 * @param string $json Raw JSON.
	 * @return array<string, mixed>
	 */
	private function parse_scope( string $json ): array {
		$scope = json_decode( trim( $json ), true );

		return is_array( $scope ) ? $scope : array();
	}

	/**
	 * Build specification blocks.
	 *
	 * @param array<string, mixed> $config Layout config.
	 * @param array<string, mixed> $manual Manual overrides.
	 * @param Cabinet|null         $cabinet Primary cabinet.
	 * @return array<string, mixed>
	 */
	private function build_specs( array $config, array $manual, ?Cabinet $cabinet ): array {
		$manual_specs = is_array( $manual['specs'] ?? null ) ? $manual['specs'] : array();
		$dimensions   = is_array( $manual_specs['dimensions'] ?? null ) ? $manual_specs['dimensions'] : array();
		$includes     = is_array( $manual_specs['includes'] ?? null ) ? $manual_specs['includes'] : array();

		if ( empty( $dimensions ) ) {
			$dimensions = $this->dimensions_from_config( $config, $cabinet );
		}

		if ( empty( $includes ) ) {
			$includes = $this->includes_from_config( $config );
		}

		return array(
			'dimensions' => $dimensions,
			'includes'   => $includes,
		);
	}

	/**
	 * Build color options from front material colors.
	 *
	 * @param array<string, mixed> $config Layout config.
	 * @param array<string, mixed> $manual Manual overrides.
	 * @param array<string, mixed> $scope  Catalog scope.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_colors( array $config, array $manual, array $scope ): array {
		if ( ! empty( $manual['colors'] ) && is_array( $manual['colors'] ) ) {
			return $this->normalize_colors( $manual['colors'] );
		}

		$front_materials = $this->front_materials( $scope );
		$material_map    = array();
		$color_notes     = is_array( $config['color_notes'] ?? null ) ? $config['color_notes'] : array();

		foreach ( $front_materials as $material ) {
			$material_map[ $material->id ] = $material;
		}

		if ( empty( $material_map ) ) {
			return array();
		}

		$allowed_color_ids = array_map( 'intval', (array) ( $scope['color_ids'] ?? array() ) );
		$options           = array();

		foreach ( $this->colors->find_all( array( 'is_active' => '1' ), 'sort_order', 'ASC' ) as $color ) {
			if ( ! $color instanceof Color || ! isset( $material_map[ $color->material_id ] ) ) {
				continue;
			}

			if ( ! empty( $allowed_color_ids ) && ! in_array( $color->id, $allowed_color_ids, true ) ) {
				continue;
			}

			$material = $material_map[ $color->material_id ];
			$label    = $this->color_label( $color, $material );
			$note     = sanitize_text_field( (string) ( $color_notes[ $color->slug ] ?? '' ) );

			$options[] = array(
				'id'             => $color->slug,
				'label'          => $label,
				'image_url'      => $this->resolve_image_url( $color->thumbnail_url, $material->thumbnail_url ),
				'hex_code'       => sanitize_hex_color( (string) $color->hex_code ) ?: '',
				'price_modifier' => (float) $color->price_modifier,
				'note'           => $note,
				'sort_order'     => $color->sort_order,
			);
		}

		usort(
			$options,
			static fn ( array $a, array $b ): int => ( $a['sort_order'] ?? 0 ) <=> ( $b['sort_order'] ?? 0 )
		);

		return array_map(
			static function ( array $option ): array {
				unset( $option['sort_order'] );

				return $option;
			},
			$options
		);
	}

	/**
	 * Build height options from layout config or cabinet dimensions.
	 *
	 * @param array<string, mixed> $config Layout config.
	 * @param array<string, mixed> $manual Manual overrides.
	 * @param Cabinet|null         $cabinet Primary cabinet.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_heights( array $config, array $manual, ?Cabinet $cabinet ): array {
		if ( ! empty( $manual['heights'] ) && is_array( $manual['heights'] ) ) {
			return $this->normalize_heights( $manual['heights'] );
		}

		if ( ! empty( $config['heights'] ) && is_array( $config['heights'] ) ) {
			return $this->normalize_heights( $config['heights'] );
		}

		$default_cm = $this->layout_height_cm( $config, $cabinet );

		if ( $default_cm <= 0 ) {
			return array();
		}

		if ( null !== $cabinet ) {
			return $this->heights_from_cabinet( $default_cm, $cabinet );
		}

		return array(
			array(
				'id'             => $this->height_id( $default_cm ),
				'label'          => $this->height_label( $default_cm ),
				'price_modifier' => 0.0,
			),
		);
	}

	/**
	 * Resolve the primary cabinet for dimension/height pricing.
	 *
	 * @param ProductPreset        $preset Product preset.
	 * @param array<string, mixed> $config Layout config.
	 * @return Cabinet|null
	 */
	private function resolve_primary_cabinet( ProductPreset $preset, array $config ): ?Cabinet {
		$preset_config = $preset->configuration();
		$cabinet_items = is_array( $preset_config['cabinets'] ?? null ) ? $preset_config['cabinets'] : array();

		foreach ( $cabinet_items as $item ) {
			$cabinet_id = (int) ( is_array( $item ) ? ( $item['cabinet_id'] ?? 0 ) : 0 );

			if ( $cabinet_id <= 0 ) {
				continue;
			}

			$cabinet = $this->cabinets->find( $cabinet_id );

			if ( null !== $cabinet && $cabinet->is_active ) {
				return $cabinet;
			}
		}

		$scope_cabinet_id = (int) ( $config['primary_cabinet_id'] ?? 0 );

		if ( $scope_cabinet_id > 0 ) {
			$cabinet = $this->cabinets->find( $scope_cabinet_id );

			if ( null !== $cabinet && $cabinet->is_active ) {
				return $cabinet;
			}
		}

		$tall_cabinets = array();

		foreach ( $this->cabinets->find_all( array( 'is_active' => '1' ) ) as $cabinet ) {
			if ( ! $cabinet instanceof Cabinet ) {
				continue;
			}

			if ( $cabinet->max_height >= 2000 ) {
				$tall_cabinets[] = $cabinet;
			}
		}

		if ( empty( $tall_cabinets ) ) {
			return null;
		}

		usort(
			$tall_cabinets,
			static fn ( Cabinet $a, Cabinet $b ): int => $b->max_height <=> $a->max_height
		);

		return $tall_cabinets[0];
	}

	/**
	 * @param array<string, mixed> $config Layout config.
	 * @param Cabinet|null         $cabinet Primary cabinet.
	 * @return array<int, string>
	 */
	private function dimensions_from_config( array $config, ?Cabinet $cabinet ): array {
		$width_cm  = $this->read_dimension_cm( $config, 'width', $cabinet?->default_width );
		$depth_cm  = $this->read_dimension_cm( $config, 'depth', $cabinet?->default_depth );
		$height_cm = $this->layout_height_cm( $config, $cabinet );

		$lines = array();

		if ( $width_cm > 0 ) {
			$lines[] = $this->format_dimension_line( $width_cm, __( 'breed', 'kitchen-configurator-pro' ) );
		}

		if ( $depth_cm > 0 ) {
			$lines[] = $this->format_dimension_line( $depth_cm, __( 'diep', 'kitchen-configurator-pro' ) );
		}

		if ( $height_cm > 0 ) {
			$lines[] = $this->format_dimension_line( $height_cm, __( 'hoog', 'kitchen-configurator-pro' ) );
		}

		return $lines;
	}

	/**
	 * @param array<string, mixed> $config Layout config.
	 * @return array<int, string>
	 */
	private function includes_from_config( array $config ): array {
		$includes = $config['includes'] ?? $config['storefront_includes'] ?? array();

		if ( ! is_array( $includes ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( static fn ( mixed $line ): string => sanitize_text_field( (string) $line ), $includes )
			)
		);
	}

	/**
	 * @param array<string, mixed> $scope Catalog scope.
	 * @return array<int, Material>
	 */
	private function front_materials( array $scope ): array {
		$allowed_material_ids = array_map( 'intval', (array) ( $scope['material_ids'] ?? array() ) );
		$materials          = array();

		foreach ( $this->materials->find_all( array( 'is_active' => '1' ) ) as $material ) {
			if ( ! $material instanceof Material || MaterialType::FRONT->value !== $material->material_type ) {
				continue;
			}

			if ( ! empty( $allowed_material_ids ) && ! in_array( $material->id, $allowed_material_ids, true ) ) {
				continue;
			}

			$materials[] = $material;
		}

		return $materials;
	}

	/**
	 * @param float    $default_cm Default height in cm.
	 * @param Cabinet  $cabinet    Primary cabinet.
	 * @return array<int, array<string, mixed>>
	 */
	private function heights_from_cabinet( float $default_cm, Cabinet $cabinet ): array {
		$default_mm = (int) round( $default_cm * 10 );
		$options    = array(
			array(
				'id'             => $this->height_id( $default_cm ),
				'label'          => $this->height_label( $default_cm ),
				'price_modifier' => 0.0,
			),
		);

		$rules = json_decode( $cabinet->dimension_price_json, true );
		$rate  = 0.0;

		if ( is_array( $rules ) && isset( $rules['height'] ) && is_array( $rules['height'] ) ) {
			$rate = (float) ( $rules['height']['rate_per_mm'] ?? $rules['height']['per_mm'] ?? 0 );
		}

		for ( $height_mm = max( $default_mm + $cabinet->height_step, $cabinet->min_height ); $height_mm <= $cabinet->max_height; $height_mm += $cabinet->height_step ) {
			if ( count( $options ) >= 4 ) {
				break;
			}

			if ( $height_mm <= $default_mm ) {
				continue;
			}

			$height_cm = round( $height_mm / 10, 1 );
			$modifier  = max( 0, $height_mm - $default_mm ) * $rate;

			$options[] = array(
				'id'             => $this->height_id( $height_cm ),
				'label'          => $this->height_label( $height_cm ),
				'price_modifier' => round( $modifier, 0 ),
			);
		}

		return $options;
	}

	/**
	 * @param array<int, mixed> $colors Raw color rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_colors( array $colors ): array {
		$normalized = array();

		foreach ( $colors as $color ) {
			if ( ! is_array( $color ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $color['id'] ?? $color['slug'] ?? '' ) );

			if ( '' === $id ) {
				continue;
			}

			$hex = sanitize_hex_color( (string) ( $color['hex_code'] ?? '' ) ) ?: '';

			$normalized[] = array(
				'id'             => $id,
				'label'          => sanitize_text_field( (string) ( $color['label'] ?? $color['name'] ?? $id ) ),
				'image_url'      => esc_url_raw( (string) ( $color['image_url'] ?? $color['thumbnail_url'] ?? '' ) ),
				'hex_code'       => $hex,
				'price_modifier' => (float) ( $color['price_modifier'] ?? 0 ),
				'note'           => sanitize_text_field( (string) ( $color['note'] ?? '' ) ),
			);
		}

		return $normalized;
	}

	/**
	 * @param array<int, mixed> $heights Raw height rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_heights( array $heights ): array {
		$normalized = array();

		foreach ( $heights as $height ) {
			if ( is_numeric( $height ) ) {
				$value_cm = (float) $height;
				$normalized[] = array(
					'id'             => $this->height_id( $value_cm ),
					'label'          => $this->height_label( $value_cm ),
					'price_modifier' => 0.0,
				);
				continue;
			}

			if ( ! is_array( $height ) ) {
				continue;
			}

			$value_cm = (float) ( $height['value'] ?? $height['height'] ?? $height['height_cm'] ?? 0 );
			$id       = sanitize_key( (string) ( $height['id'] ?? ( $value_cm > 0 ? $this->height_id( $value_cm ) : '' ) ) );
			$label    = sanitize_text_field( (string) ( $height['label'] ?? ( $value_cm > 0 ? $this->height_label( $value_cm ) : '' ) ) );

			if ( '' === $id || '' === $label ) {
				continue;
			}

			$normalized[] = array(
				'id'             => $id,
				'label'          => $label,
				'price_modifier' => (float) ( $height['price_modifier'] ?? 0 ),
			);
		}

		return $normalized;
	}

	/**
	 * @param array<string, mixed> $config Layout config.
	 * @param Cabinet|null         $cabinet Primary cabinet.
	 * @return float
	 */
	private function layout_height_cm( array $config, ?Cabinet $cabinet ): float {
		$height = $this->read_dimension_cm( $config, 'height', $cabinet?->default_height );

		return $height > 0 ? $height : 0.0;
	}

	/**
	 * @param array<string, mixed> $config Layout config.
	 * @param string               $key    Dimension key.
	 * @param int|null             $fallback_mm Fallback in mm.
	 * @return float
	 */
	private function read_dimension_cm( array $config, string $key, ?int $fallback_mm ): float {
		if ( isset( $config[ $key ] ) && is_numeric( $config[ $key ] ) ) {
			return (float) $config[ $key ];
		}

		if ( null !== $fallback_mm && $fallback_mm > 0 ) {
			return round( $fallback_mm / 10, 1 );
		}

		return 0.0;
	}

	/**
	 * @param float  $value Dimension in cm.
	 * @param string $suffix Dutch suffix.
	 * @return string
	 */
	private function format_dimension_line( float $value, string $suffix ): string {
		return sprintf(
			'%s cm %s',
			rtrim( rtrim( number_format( $value, 1, '.', '' ), '0' ), '.' ),
			$suffix
		);
	}

	/**
	 * @param Color    $color    Color entity.
	 * @param Material $material Material entity.
	 * @return string
	 */
	private function color_label( Color $color, Material $material ): string {
		$name = trim( $color->name );

		if ( '' !== $name ) {
			return $name;
		}

		return $material->name;
	}

	/**
	 * @param string ...$urls Candidate image URLs.
	 * @return string
	 */
	private function resolve_image_url( string ...$urls ): string {
		foreach ( $urls as $url ) {
			if ( '' !== trim( $url ) ) {
				return esc_url_raw( $url );
			}
		}

		return '';
	}

	/**
	 * @param float $height_cm Height in cm.
	 * @return string
	 */
	private function height_id( float $height_cm ): string {
		return sanitize_key( str_replace( '.', '-', (string) round( $height_cm, 1 ) ) );
	}

	/**
	 * @param float $height_cm Height in cm.
	 * @return string
	 */
	private function height_label( float $height_cm ): string {
		return sprintf(
			'%s cm hoog',
			rtrim( rtrim( number_format( $height_cm, 1, '.', '' ), '0' ), '.' )
		);
	}
}
