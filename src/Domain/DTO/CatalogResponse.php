<?php
/**
 * Catalog response DTO.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\DTO;

/**
 * Full catalog payload for REST API.
 */
final class CatalogResponse {

	/**
	 * @param array<int, array<string, mixed>> $layouts            Layouts.
	 * @param array<int, array<string, mixed>> $cabinet_categories Cabinet categories.
	 * @param array<int, array<string, mixed>> $cabinets           Cabinets.
	 * @param array<int, array<string, mixed>> $materials          Materials.
	 * @param array<int, array<string, mixed>> $colors             Colors.
	 * @param array<int, array<string, mixed>> $handles            Handles.
	 * @param array<int, array<string, mixed>> $accessories        Accessories.
	 * @param array<int, array<string, mixed>> $worktops           Worktops.
	 * @param array<int, array<string, mixed>> $plinths            Plinths.
	 */
	public function __construct(
		public readonly array $layouts,
		public readonly array $cabinet_categories,
		public readonly array $cabinets,
		public readonly array $materials,
		public readonly array $colors,
		public readonly array $handles,
		public readonly array $accessories,
		public readonly array $worktops,
		public readonly array $plinths
	) {
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'layouts'            => $this->layouts,
			'cabinet_categories' => $this->cabinet_categories,
			'cabinets'           => $this->cabinets,
			'materials'          => $this->materials,
			'colors'             => $this->colors,
			'handles'            => $this->handles,
			'accessories'        => $this->accessories,
			'worktops'           => $this->worktops,
			'plinths'            => $this->plinths,
		);
	}
}
