<?php
/**
 * Product preset entity.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Entities;

/**
 * Maps a WooCommerce product to a configurator preset.
 */
final class ProductPreset {

	/**
	 * @param int    $id                   Primary key.
	 * @param int    $wc_product_id        WooCommerce product ID.
	 * @param int    $layout_id            Layout ID.
	 * @param string $name                 Admin label.
	 * @param string $configuration_json   Default configuration JSON.
	 * @param string $catalog_scope_json   Optional catalog scope JSON.
	 * @param bool   $is_active            Active flag.
	 */
	public function __construct(
		public readonly int $id,
		public readonly int $wc_product_id,
		public readonly int $layout_id,
		public readonly string $name,
		public readonly string $configuration_json,
		public readonly string $catalog_scope_json,
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
			(int) $row['wc_product_id'],
			(int) $row['layout_id'],
			(string) $row['name'],
			(string) $row['configuration_json'],
			(string) ( $row['catalog_scope_json'] ?? '' ),
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
			'id'                 => $this->id,
			'wc_product_id'      => $this->wc_product_id,
			'layout_id'          => $this->layout_id,
			'name'               => $this->name,
			'configuration_json' => $this->configuration_json,
			'catalog_scope_json' => $this->catalog_scope_json,
			'is_active'          => $this->is_active,
		);
	}

	/**
	 * Decode configuration JSON.
	 *
	 * @return array<string, mixed>
	 */
	public function configuration(): array {
		$config = json_decode( $this->configuration_json, true );

		return is_array( $config ) ? $config : array();
	}

	/**
	 * Get storefront option groups for the single product page.
	 *
	 * @return array<string, mixed>
	 */
	public function product_options(): array {
		$config  = $this->configuration();
		$options = $config['product_options'] ?? array();

		return is_array( $options ) ? $options : array();
	}

	/**
	 * Whether this preset should render storefront selectors on single product pages.
	 *
	 * @return bool
	 */
	public function has_storefront_options(): bool {
		return $this->is_active && $this->layout_id > 0;
	}
}
