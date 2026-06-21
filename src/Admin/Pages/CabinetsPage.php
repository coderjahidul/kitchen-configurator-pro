<?php
/**
 * Cabinets admin page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Admin\AbstractCrudPage;
use KitchenConfiguratorPro\Repositories\CabinetCategoryRepository;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Support\Arr;

/**
 * CRUD admin page for cabinets.
 */
final class CabinetsPage extends AbstractCrudPage {

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'kcp-cabinets';
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label(): string {
		return __( 'Cabinet', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label_plural(): string {
		return __( 'Cabinets', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function repository_class(): string {
		return CabinetRepository::class;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function list_columns(): array {
		return array(
			'id'          => __( 'ID', 'kitchen-configurator-pro' ),
			'name'        => __( 'Name', 'kitchen-configurator-pro' ),
			'category_id' => __( 'Category', 'kitchen-configurator-pro' ),
			'base_price'  => __( 'Base Price', 'kitchen-configurator-pro' ),
			'is_active'   => __( 'Active', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * Cached cabinet category names keyed by ID.
	 *
	 * @var array<int, string>|null
	 */
	private ?array $category_names = null;

	/**
	 * {@inheritDoc}
	 */
	public function format_column( string $column, array $row ): string {
		if ( 'category_id' === $column ) {
			$category_id = (int) ( $row['category_id'] ?? 0 );
			$names       = $this->category_names();

			return esc_html( $names[ $category_id ] ?? __( 'Unknown', 'kitchen-configurator-pro' ) );
		}

		return parent::format_column( $column, $row );
	}

	/**
	 * Load cabinet category names for list display.
	 *
	 * @return array<int, string>
	 */
	private function category_names(): array {
		if ( null !== $this->category_names ) {
			return $this->category_names;
		}

		$this->category_names = array();

		/** @var CabinetCategoryRepository $categories */
		$categories = $this->container->get( CabinetCategoryRepository::class );

		foreach ( $categories->find_all() as $category ) {
			$row = Arr::to_array( $category );
			$this->category_names[ (int) $row['id'] ] = (string) $row['name'];
		}

		return $this->category_names;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_fields(): array {
		return array(
			'category_id'          => array(
				'type'     => 'select',
				'label'    => __( 'Category', 'kitchen-configurator-pro' ),
				'required' => true,
				'options'  => array(),
			),
			'name'                 => array(
				'type'     => 'text',
				'label'    => __( 'Name', 'kitchen-configurator-pro' ),
				'required' => true,
			),
			'slug'                 => array(
				'type'        => 'text',
				'label'       => __( 'Slug', 'kitchen-configurator-pro' ),
				'description' => __( 'Leave empty to auto-generate from name.', 'kitchen-configurator-pro' ),
			),
			'description'          => array(
				'type'  => 'textarea',
				'label' => __( 'Description', 'kitchen-configurator-pro' ),
				'rows'  => 3,
			),
			'sku_prefix'           => array(
				'type'  => 'text',
				'label' => __( 'SKU Prefix', 'kitchen-configurator-pro' ),
			),
			'default_width'        => array( 'type' => 'number', 'label' => __( 'Default Width (mm)', 'kitchen-configurator-pro' ), 'required' => true, 'min' => 0 ),
			'default_height'       => array( 'type' => 'number', 'label' => __( 'Default Height (mm)', 'kitchen-configurator-pro' ), 'required' => true, 'min' => 0 ),
			'default_depth'        => array( 'type' => 'number', 'label' => __( 'Default Depth (mm)', 'kitchen-configurator-pro' ), 'required' => true, 'min' => 0 ),
			'min_width'            => array( 'type' => 'number', 'label' => __( 'Min Width (mm)', 'kitchen-configurator-pro' ), 'required' => true, 'min' => 0 ),
			'max_width'            => array( 'type' => 'number', 'label' => __( 'Max Width (mm)', 'kitchen-configurator-pro' ), 'required' => true, 'min' => 0 ),
			'min_height'           => array( 'type' => 'number', 'label' => __( 'Min Height (mm)', 'kitchen-configurator-pro' ), 'required' => true, 'min' => 0 ),
			'max_height'           => array( 'type' => 'number', 'label' => __( 'Max Height (mm)', 'kitchen-configurator-pro' ), 'required' => true, 'min' => 0 ),
			'min_depth'            => array( 'type' => 'number', 'label' => __( 'Min Depth (mm)', 'kitchen-configurator-pro' ), 'required' => true, 'min' => 0 ),
			'max_depth'            => array( 'type' => 'number', 'label' => __( 'Max Depth (mm)', 'kitchen-configurator-pro' ), 'required' => true, 'min' => 0 ),
			'width_step'           => array( 'type' => 'number', 'label' => __( 'Width Step (mm)', 'kitchen-configurator-pro' ), 'default' => 10, 'min' => 1 ),
			'height_step'          => array( 'type' => 'number', 'label' => __( 'Height Step (mm)', 'kitchen-configurator-pro' ), 'default' => 10, 'min' => 1 ),
			'depth_step'           => array( 'type' => 'number', 'label' => __( 'Depth Step (mm)', 'kitchen-configurator-pro' ), 'default' => 10, 'min' => 1 ),
			'base_price'           => array( 'type' => 'number', 'label' => __( 'Base Price', 'kitchen-configurator-pro' ), 'required' => true, 'step' => '0.01', 'min' => 0 ),
			'dimension_price_json' => array(
				'type'        => 'dimension_pricing',
				'label'       => __( 'Dimension Pricing', 'kitchen-configurator-pro' ),
				'description' => __( 'Optional per-mm surcharge above the base dimension. Leave base empty to use the cabinet default.', 'kitchen-configurator-pro' ),
			),
			'image_url'            => array( 'type' => 'image', 'label' => __( 'Image', 'kitchen-configurator-pro' ) ),
			'sort_order'           => array( 'type' => 'number', 'label' => __( 'Sort Order', 'kitchen-configurator-pro' ), 'default' => 0, 'min' => 0 ),
			'is_active'            => array( 'type' => 'checkbox', 'label' => __( 'Active', 'kitchen-configurator-pro' ), 'default' => 1 ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function collect_post_data(): array {
		$data = parent::collect_post_data();

		$pricing = isset( $_POST['dimension_pricing'] ) && is_array( $_POST['dimension_pricing'] )
			? wp_unslash( $_POST['dimension_pricing'] )
			: array();

		$rules = array();

		foreach ( array( 'width', 'height', 'depth' ) as $axis ) {
			if ( ! isset( $pricing[ $axis ] ) || ! is_array( $pricing[ $axis ] ) ) {
				continue;
			}

			$rate = is_numeric( $pricing[ $axis ]['rate_per_mm'] ?? null )
				? (float) $pricing[ $axis ]['rate_per_mm']
				: 0.0;
			$base = is_numeric( $pricing[ $axis ]['base'] ?? null )
				? (int) $pricing[ $axis ]['base']
				: 0;

			if ( $rate <= 0 && $base <= 0 ) {
				continue;
			}

			$rule = array();

			if ( $rate > 0 ) {
				$rule['rate_per_mm'] = $rate;
			}

			if ( $base > 0 ) {
				$rule['base'] = $base;
			}

			if ( ! empty( $rule ) ) {
				$rules[ $axis ] = $rule;
			}
		}

		$data['dimension_price_json'] = wp_json_encode( $rules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '{}';

		return $data;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_context( array $values ): array {
		/** @var CabinetCategoryRepository $categories */
		$categories = $this->container->get( CabinetCategoryRepository::class );
		$options    = array( '' => __( '— Select —', 'kitchen-configurator-pro' ) );

		foreach ( $categories->find_all() as $category ) {
			$row = Arr::to_array( $category );
			$options[ (string) $row['id'] ] = (string) $row['name'];
		}

		$fields = $this->form_fields();
		$fields['category_id']['options'] = $options;

		return array( 'fields' => $fields );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function render_form( bool $is_edit ): void {
		// Use dynamic fields with category options.
		$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$values = array();
		$item   = null;

		if ( $is_edit ) {
			if ( $id <= 0 ) {
				wp_die( esc_html__( 'Invalid record ID.', 'kitchen-configurator-pro' ) );
			}

			$item = $this->repository()->find( $id );

			if ( null === $item ) {
				wp_die( esc_html__( 'Record not found.', 'kitchen-configurator-pro' ) );
			}

			$values = Arr::to_array( $item );
		}

		$fields = $this->form_fields();
		$context = $this->form_context( $values );
		$fields  = $context['fields'] ?? $fields;

		foreach ( $fields as $key => $field ) {
			if ( ! isset( $values[ $key ] ) && isset( $field['default'] ) ) {
				$values[ $key ] = $field['default'];
			}
		}

		$this->load_template(
			'crud-form',
			array(
				'page'         => $this,
				'is_edit'      => $is_edit,
				'id'           => $id,
				'values'       => $values,
				'fields'       => $fields,
				'notices'      => $this->resolve_notices(),
				'list_url'     => $this->list_url(),
				'entity_label' => $this->entity_label(),
				'nonce_action' => $this->nonce_action(),
			)
		);
	}
}
