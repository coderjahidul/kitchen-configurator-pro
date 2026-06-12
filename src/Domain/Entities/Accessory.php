<?php
/**
 * Accessory entity.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Entities;

/**
 * Accessory catalog entity.
 */
final class Accessory {

	/**
	 * @param int    $id             Primary key.
	 * @param string $slug           URL slug.
	 * @param string $name           Display name.
	 * @param string $category       Category.
	 * @param string $description    Description.
	 * @param string $price          Price.
	 * @param bool   $is_per_cabinet Per-cabinet flag.
	 * @param string $thumbnail_url  Thumbnail URL.
	 * @param int    $sort_order     Sort order.
	 * @param bool   $is_active      Active flag.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $slug,
		public readonly string $name,
		public readonly string $category,
		public readonly string $description,
		public readonly string $price,
		public readonly bool $is_per_cabinet,
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
			(string) $row['category'],
			(string) ( $row['description'] ?? '' ),
			(string) $row['price'],
			(bool) (int) $row['is_per_cabinet'],
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
			'slug'           => $this->slug,
			'name'           => $this->name,
			'category'       => $this->category,
			'description'    => $this->description,
			'price'          => $this->price,
			'is_per_cabinet' => $this->is_per_cabinet,
			'thumbnail_url'  => $this->thumbnail_url,
			'sort_order'     => $this->sort_order,
			'is_active'      => $this->is_active,
		);
	}
}
