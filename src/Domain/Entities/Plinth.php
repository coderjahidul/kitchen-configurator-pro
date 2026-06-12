<?php
/**
 * Plinth entity.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Entities;

/**
 * Plinth (kickboard) catalog entity.
 */
final class Plinth {

	/**
	 * @param int    $id                     Primary key.
	 * @param string $slug                   URL slug.
	 * @param string $name                   Display name.
	 * @param string $description            Description.
	 * @param int    $default_height         Default height in mm.
	 * @param int    $min_height             Minimum height in mm.
	 * @param int    $max_height             Maximum height in mm.
	 * @param int    $height_step            Height step in mm.
	 * @param int    $default_length         Default length in mm.
	 * @param int    $min_length             Minimum length in mm.
	 * @param int    $max_length             Maximum length in mm.
	 * @param int    $length_step            Length step in mm.
	 * @param string $base_price             Base price.
	 * @param string $price_per_linear_meter Price per linear meter.
	 * @param string $thumbnail_url          Thumbnail URL.
	 * @param int    $sort_order             Sort order.
	 * @param bool   $is_active              Active flag.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $slug,
		public readonly string $name,
		public readonly string $description,
		public readonly int $default_height,
		public readonly int $min_height,
		public readonly int $max_height,
		public readonly int $height_step,
		public readonly int $default_length,
		public readonly int $min_length,
		public readonly int $max_length,
		public readonly int $length_step,
		public readonly string $base_price,
		public readonly string $price_per_linear_meter,
		public readonly string $thumbnail_url,
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
			(string) $row['slug'],
			(string) $row['name'],
			(string) ( $row['description'] ?? '' ),
			(int) $row['default_height'],
			(int) $row['min_height'],
			(int) $row['max_height'],
			(int) $row['height_step'],
			(int) $row['default_length'],
			(int) $row['min_length'],
			(int) $row['max_length'],
			(int) $row['length_step'],
			(string) $row['base_price'],
			(string) $row['price_per_linear_meter'],
			(string) ( $row['thumbnail_url'] ?? '' ),
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
			'id'                     => $this->id,
			'slug'                   => $this->slug,
			'name'                   => $this->name,
			'description'            => $this->description,
			'default_height'         => $this->default_height,
			'min_height'             => $this->min_height,
			'max_height'             => $this->max_height,
			'height_step'            => $this->height_step,
			'default_length'         => $this->default_length,
			'min_length'             => $this->min_length,
			'max_length'             => $this->max_length,
			'length_step'            => $this->length_step,
			'base_price'             => $this->base_price,
			'price_per_linear_meter' => $this->price_per_linear_meter,
			'thumbnail_url'          => $this->thumbnail_url,
			'sort_order'             => $this->sort_order,
			'is_active'              => $this->is_active,
		);
	}
}
