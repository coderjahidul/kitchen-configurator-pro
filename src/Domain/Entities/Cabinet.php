<?php
/**
 * Cabinet entity.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Entities;

/**
 * Cabinet catalog entity.
 */
final class Cabinet {

	/**
	 * @param int    $id                   Primary key.
	 * @param int    $category_id          Category ID.
	 * @param int    $type_id              Cabinet type ID.
	 * @param string $slug                 URL slug.
	 * @param string $name                 Display name.
	 * @param string $description          Description.
	 * @param string $sku_prefix           SKU prefix.
	 * @param int    $default_width        Default width mm.
	 * @param int    $default_height       Default height mm.
	 * @param int    $default_depth        Default depth mm.
	 * @param int    $min_width            Min width mm.
	 * @param int    $max_width            Max width mm.
	 * @param int    $min_height           Min height mm.
	 * @param int    $max_height           Max height mm.
	 * @param int    $min_depth            Min depth mm.
	 * @param int    $max_depth            Max depth mm.
	 * @param int    $width_step           Width step mm.
	 * @param int    $height_step          Height step mm.
	 * @param int    $depth_step           Depth step mm.
	 * @param string $base_price           Base price.
	 * @param string $dimension_price_json Dimension pricing JSON.
	 * @param string $image_url            Image URL.
	 * @param int    $sort_order           Sort order.
	 * @param bool   $is_active            Active flag.
	 */
	public function __construct(
		public readonly int $id,
		public readonly int $category_id,
		public readonly int $type_id,
		public readonly string $slug,
		public readonly string $name,
		public readonly string $description,
		public readonly string $sku_prefix,
		public readonly int $default_width,
		public readonly int $default_height,
		public readonly int $default_depth,
		public readonly int $min_width,
		public readonly int $max_width,
		public readonly int $min_height,
		public readonly int $max_height,
		public readonly int $min_depth,
		public readonly int $max_depth,
		public readonly int $width_step,
		public readonly int $height_step,
		public readonly int $depth_step,
		public readonly string $base_price,
		public readonly string $dimension_price_json,
		public readonly string $image_url,
		public readonly int $sort_order,
		public readonly bool $is_active
	) {
	}

	/**
	 * Create from database row.
	 *
	 * @param array<string, mixed> $row Database row.
	 * @return self
	 */
	public static function from_row( array $row ): self {
		return new self(
			(int) $row['id'],
			(int) $row['category_id'],
			(int) ( $row['type_id'] ?? 0 ),
			(string) $row['slug'],
			(string) $row['name'],
			(string) ( $row['description'] ?? '' ),
			(string) ( $row['sku_prefix'] ?? '' ),
			(int) $row['default_width'],
			(int) $row['default_height'],
			(int) $row['default_depth'],
			(int) $row['min_width'],
			(int) $row['max_width'],
			(int) $row['min_height'],
			(int) $row['max_height'],
			(int) $row['min_depth'],
			(int) $row['max_depth'],
			(int) ( $row['width_step'] ?? 10 ),
			(int) ( $row['height_step'] ?? 10 ),
			(int) ( $row['depth_step'] ?? 10 ),
			(string) $row['base_price'],
			(string) ( $row['dimension_price_json'] ?? '' ),
			(string) ( $row['image_url'] ?? '' ),
			(int) $row['sort_order'],
			(bool) (int) $row['is_active']
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                   => $this->id,
			'category_id'          => $this->category_id,
			'type_id'              => $this->type_id,
			'slug'                 => $this->slug,
			'name'                 => $this->name,
			'description'          => $this->description,
			'sku_prefix'           => $this->sku_prefix,
			'default_width'        => $this->default_width,
			'default_height'       => $this->default_height,
			'default_depth'        => $this->default_depth,
			'min_width'            => $this->min_width,
			'max_width'            => $this->max_width,
			'min_height'           => $this->min_height,
			'max_height'           => $this->max_height,
			'min_depth'            => $this->min_depth,
			'max_depth'            => $this->max_depth,
			'width_step'           => $this->width_step,
			'height_step'          => $this->height_step,
			'depth_step'           => $this->depth_step,
			'base_price'           => $this->base_price,
			'dimension_price_json' => $this->dimension_price_json,
			'image_url'            => $this->image_url,
			'sort_order'           => $this->sort_order,
			'is_active'            => $this->is_active,
		);
	}
}
