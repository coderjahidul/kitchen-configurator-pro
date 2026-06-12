<?php
/**
 * Configuration input DTO.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\DTO;

use KitchenConfiguratorPro\Support\Arr;

/**
 * Validated configuration input for pricing and persistence.
 */
final class ConfigurationInput {

	/**
	 * @param string               $schema_version Schema version.
	 * @param int                  $layout_id      Layout ID.
	 * @param string               $title          Configuration title.
	 * @param array<int, array<string, mixed>> $cabinets       Cabinet items.
	 * @param array<string, mixed> $global_options Global options.
	 */
	public function __construct(
		public readonly string $schema_version,
		public readonly int $layout_id,
		public readonly string $title,
		public readonly array $cabinets,
		public readonly array $global_options
	) {
	}

	/**
	 * Create from array payload.
	 *
	 * @param array<string, mixed> $data Input data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			(string) Arr::get( $data, 'schema_version', '1.0' ),
			(int) Arr::get( $data, 'layout_id', 0 ),
			(string) Arr::get( $data, 'title', '' ),
			is_array( $data['cabinets'] ?? null ) ? $data['cabinets'] : array(),
			is_array( $data['global_options'] ?? null ) ? $data['global_options'] : array()
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'schema_version' => $this->schema_version,
			'layout_id'      => $this->layout_id,
			'title'          => $this->title,
			'cabinets'       => $this->cabinets,
			'global_options' => $this->global_options,
		);
	}
}
