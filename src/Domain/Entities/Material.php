<?php
/**
 * Material entity.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Entities;

/**
 * Material catalog entity.
 */
final class Material {

	/**
	 * @param int         $id               Primary key.
	 * @param string      $slug             URL slug.
	 * @param string      $name             Display name.
	 * @param string      $material_type    Material type.
	 * @param string      $description      Description.
	 * @param string      $price_modifier   Flat price modifier.
	 * @param string|null $price_per_sqm    Price per square meter.
	 * @param string      $price_multiplier Price multiplier.
	 * @param string      $thumbnail_url    Thumbnail URL.
	 * @param int         $sort_order       Sort order.
	 * @param bool        $is_active        Active flag.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $slug,
		public readonly string $name,
		public readonly string $material_type,
		public readonly string $description,
		public readonly string $price_modifier,
		public readonly ?string $price_per_sqm,
		public readonly string $price_multiplier,
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
			(string) $row['material_type'],
			(string) ( $row['description'] ?? '' ),
			(string) $row['price_modifier'],
			isset( $row['price_per_sqm'] ) ? (string) $row['price_per_sqm'] : null,
			(string) $row['price_multiplier'],
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
			'id'               => $this->id,
			'slug'             => $this->slug,
			'name'             => $this->name,
			'material_type'    => $this->material_type,
			'description'      => $this->description,
			'price_modifier'   => $this->price_modifier,
			'price_per_sqm'    => $this->price_per_sqm,
			'price_multiplier' => $this->price_multiplier,
			'thumbnail_url'    => $this->thumbnail_url,
			'sort_order'       => $this->sort_order,
			'is_active'        => $this->is_active,
		);
	}
}
