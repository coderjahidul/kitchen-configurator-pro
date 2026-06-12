<?php
/**
 * Color entity.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Entities;

/**
 * Color catalog entity.
 */
final class Color {

	/**
	 * @param int    $id             Primary key.
	 * @param int    $material_id    Parent material ID.
	 * @param string $slug           URL slug.
	 * @param string $name           Display name.
	 * @param string $hex_code       Hex color code.
	 * @param string $price_modifier Price modifier.
	 * @param string $thumbnail_url  Thumbnail URL.
	 * @param int    $sort_order     Sort order.
	 * @param bool   $is_active      Active flag.
	 */
	public function __construct(
		public readonly int $id,
		public readonly int $material_id,
		public readonly string $slug,
		public readonly string $name,
		public readonly string $hex_code,
		public readonly string $price_modifier,
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
			(int) $row['material_id'],
			(string) $row['slug'],
			(string) $row['name'],
			(string) ( $row['hex_code'] ?? '' ),
			(string) $row['price_modifier'],
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
			'id'             => $this->id,
			'material_id'    => $this->material_id,
			'slug'           => $this->slug,
			'name'           => $this->name,
			'hex_code'       => $this->hex_code,
			'price_modifier' => $this->price_modifier,
			'thumbnail_url'  => $this->thumbnail_url,
			'sort_order'     => $this->sort_order,
			'is_active'      => $this->is_active,
		);
	}
}
