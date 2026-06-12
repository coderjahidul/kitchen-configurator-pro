<?php
/**
 * Pricing calculation context.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services\Pricing;

use KitchenConfiguratorPro\Domain\DTO\ConfigurationInput;
use KitchenConfiguratorPro\Domain\DTO\LineItem;
use KitchenConfiguratorPro\Domain\Entities\Accessory;
use KitchenConfiguratorPro\Domain\Entities\Cabinet;
use KitchenConfiguratorPro\Domain\Entities\Color;
use KitchenConfiguratorPro\Domain\Entities\Handle;
use KitchenConfiguratorPro\Domain\Entities\Layout;
use KitchenConfiguratorPro\Domain\Entities\Material;
use KitchenConfiguratorPro\Domain\Entities\Plinth;
use KitchenConfiguratorPro\Domain\Entities\Worktop;
use KitchenConfiguratorPro\Domain\ValueObjects\Dimensions;
use KitchenConfiguratorPro\Domain\ValueObjects\Money;

/**
 * Mutable state passed through the calculator pipeline.
 */
final class CalculationContext {

	/**
	 * Running subtotal.
	 *
	 * @var Money
	 */
	public Money $subtotal;

	/**
	 * @param ConfigurationInput    $input       Configuration input.
	 * @param string                $currency    Currency code.
	 * @param Layout|null           $layout      Resolved layout.
	 * @param array<int, Cabinet>   $cabinets    Cabinets keyed by ID.
	 * @param array<int, Material>  $materials   Materials keyed by ID.
	 * @param array<int, Color>     $colors      Colors keyed by ID.
	 * @param array<int, Handle>    $handles     Handles keyed by ID.
	 * @param array<int, Accessory> $accessories Accessories keyed by ID.
	 * @param Worktop|null          $worktop     Resolved worktop.
	 * @param Plinth|null           $plinth      Resolved plinth.
	 * @param array<int, LineItem>  $line_items  Accumulated line items.
	 */
	public function __construct(
		public readonly ConfigurationInput $input,
		public readonly string $currency,
		public ?Layout $layout = null,
		public array $cabinets = array(),
		public array $materials = array(),
		public array $colors = array(),
		public array $handles = array(),
		public array $accessories = array(),
		public ?Worktop $worktop = null,
		public ?Plinth $plinth = null,
		public array $line_items = array()
	) {
		$this->subtotal = Money::zero( $currency );
	}

	/**
	 * Append a line item and update subtotal.
	 *
	 * @param LineItem $item Line item.
	 * @return void
	 */
	public function add_line_item( LineItem $item ): void {
		$this->line_items[] = $item;
		$this->subtotal     = $this->subtotal->add( $item->subtotal );
	}

	/**
	 * Get cabinet configuration item by index.
	 *
	 * @param int $index Cabinet index in input.
	 * @return array<string, mixed>
	 */
	public function cabinet_item( int $index ): array {
		return $this->input->cabinets[ $index ] ?? array();
	}

	/**
	 * Resolve dimensions for a cabinet item.
	 *
	 * @param int $index Cabinet index.
	 * @return Dimensions
	 */
	public function cabinet_dimensions( int $index ): Dimensions {
		$item = $this->cabinet_item( $index );

		return Dimensions::from_array( is_array( $item['dimensions'] ?? null ) ? $item['dimensions'] : array() );
	}

	/**
	 * Get global option value.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function global_option( string $key, mixed $default = null ): mixed {
		return $this->input->global_options[ $key ] ?? $default;
	}

	/**
	 * Cabinet count in configuration.
	 *
	 * @return int
	 */
	public function cabinet_count(): int {
		return count( $this->input->cabinets );
	}
}
