<?php
/**
 * Dimensions value object.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\ValueObjects;

/**
 * Cabinet dimensions in millimeters.
 */
final class Dimensions {

	/**
	 * @param int $width  Width in mm.
	 * @param int $height Height in mm.
	 * @param int $depth  Depth in mm.
	 */
	public function __construct(
		public readonly int $width,
		public readonly int $height,
		public readonly int $depth
	) {
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, mixed> $data Dimension data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			max( 0, (int) ( $data['width'] ?? 0 ) ),
			max( 0, (int) ( $data['height'] ?? 0 ) ),
			max( 0, (int) ( $data['depth'] ?? 0 ) )
		);
	}

	/**
	 * Check if dimensions are within inclusive range.
	 *
	 * @param self $min Minimum dimensions.
	 * @param self $max Maximum dimensions.
	 * @return bool
	 */
	public function is_within_range( self $min, self $max ): bool {
		return $this->width >= $min->width
			&& $this->width <= $max->width
			&& $this->height >= $min->height
			&& $this->height <= $max->height
			&& $this->depth >= $min->depth
			&& $this->depth <= $max->depth;
	}

	/**
	 * Front face area in square millimeters.
	 *
	 * @return int
	 */
	public function front_area_mm2(): int {
		return $this->width * $this->height;
	}

	/**
	 * Front face area in square meters.
	 *
	 * @return string
	 */
	public function front_area_sqm(): string {
		$sqm = $this->front_area_mm2() / 1_000_000;

		return number_format( $sqm, 4, '.', '' );
	}

	/**
	 * Convert to array.
	 *
	 * @return array{width: int, height: int, depth: int}
	 */
	public function to_array(): array {
		return array(
			'width'  => $this->width,
			'height' => $this->height,
			'depth'  => $this->depth,
		);
	}
}
