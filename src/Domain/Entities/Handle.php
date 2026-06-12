<?php
/**
 * Handle entity.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Entities;

/**
 * Handle catalog entity.
 */
final class Handle {

	/**
	 * @param int    $id            Primary key.
	 * @param string $slug          URL slug.
	 * @param string $name          Display name.
	 * @param string $description   Description.
	 * @param string $price         Price.
	 * @param string $thumbnail_url Thumbnail URL.
	 * @param int    $sort_order    Sort order.
	 * @param bool   $is_active     Active flag.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $slug,
		public readonly string $name,
		public readonly string $description,
		public readonly string $price,
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
			(string) $row['price'],
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
			'id'            => $this->id,
			'slug'          => $this->slug,
			'name'          => $this->name,
			'description'   => $this->description,
			'price'         => $this->price,
			'thumbnail_url' => $this->thumbnail_url,
			'sort_order'    => $this->sort_order,
			'is_active'     => $this->is_active,
		);
	}
}
