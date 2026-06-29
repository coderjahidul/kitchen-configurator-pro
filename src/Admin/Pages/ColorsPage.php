<?php
/**
 * Colors admin page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Admin\AbstractCrudPage;
use KitchenConfiguratorPro\Domain\Enums\MaterialType;
use KitchenConfiguratorPro\Repositories\ColorRepository;
use KitchenConfiguratorPro\Repositories\MaterialRepository;
use KitchenConfiguratorPro\Support\Arr;

/**
 * CRUD admin page for colors.
 */
final class ColorsPage extends AbstractCrudPage {

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'kcp-colors';
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label(): string {
		return __( 'Color', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label_plural(): string {
		return __( 'Colors', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function repository_class(): string {
		return ColorRepository::class;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function list_columns(): array {
		return array(
			'name'        => __( 'Name', 'kitchen-configurator-pro' ),
			'material_id' => __( 'Material', 'kitchen-configurator-pro' ),
			'zone'        => __( 'Design Zone', 'kitchen-configurator-pro' ),
			'hex_code'    => __( 'Hex', 'kitchen-configurator-pro' ),
			'is_active'   => __( 'Active', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * Cached material rows keyed by ID.
	 *
	 * @var array<int, array{id: int, name: string, material_type: string}>|null
	 */
	private ?array $material_rows = null;

	/**
	 * {@inheritDoc}
	 */
	public function format_column( string $column, array $row ): string {
		if ( 'material_id' === $column ) {
			$material_id = (int) ( $row['material_id'] ?? 0 );
			$materials   = $this->material_rows();

			return esc_html( $materials[ $material_id ]['name'] ?? __( 'Unknown', 'kitchen-configurator-pro' ) );
		}

		if ( 'zone' === $column ) {
			$material_id = (int) ( $row['material_id'] ?? 0 );
			$materials   = $this->material_rows();
			$type        = (string) ( $materials[ $material_id ]['material_type'] ?? '' );

			return esc_html( $this->zone_label_for_material_type( $type ) );
		}

		return parent::format_column( $column, $row );
	}

	/**
	 * Load material rows for list display.
	 *
	 * @return array<int, array{id: int, name: string, material_type: string}>
	 */
	private function material_rows(): array {
		if ( null !== $this->material_rows ) {
			return $this->material_rows;
		}

		$this->material_rows = array();

		/** @var MaterialRepository $materials */
		$materials = $this->container->get( MaterialRepository::class );

		foreach ( $materials->find_all() as $material ) {
			$row = Arr::to_array( $material );
			$this->material_rows[ (int) $row['id'] ] = array(
				'id'            => (int) $row['id'],
				'name'          => (string) $row['name'],
				'material_type' => (string) $row['material_type'],
			);
		}

		return $this->material_rows;
	}

	/**
	 * Map material type to design zone label.
	 *
	 * @param string $material_type Material type value.
	 * @return string
	 */
	private function zone_label_for_material_type( string $material_type ): string {
		return match ( $material_type ) {
			MaterialType::FRONT->value   => __( 'frontkleur', 'kitchen-configurator-pro' ),
			MaterialType::CARCASS->value => __( 'kastkleur', 'kitchen-configurator-pro' ),
			MaterialType::PLINTH->value  => __( 'plintkleur', 'kitchen-configurator-pro' ),
			MaterialType::WORKTOP->value => __( 'worktop', 'kitchen-configurator-pro' ),
			default                      => __( '—', 'kitchen-configurator-pro' ),
		};
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_fields(): array {
		return array(
			'material_id'    => array( 'type' => 'select', 'label' => __( 'Material', 'kitchen-configurator-pro' ), 'required' => true, 'options' => array() ),
			'name'           => array( 'type' => 'text', 'label' => __( 'Name', 'kitchen-configurator-pro' ), 'required' => true ),
			'slug'           => array( 'type' => 'text', 'label' => __( 'Slug', 'kitchen-configurator-pro' ) ),
			'hex_code'       => array( 'type' => 'text', 'label' => __( 'Hex Code', 'kitchen-configurator-pro' ), 'description' => __( 'Format: #FFFFFF', 'kitchen-configurator-pro' ) ),
			'price_modifier' => array( 'type' => 'number', 'label' => __( 'Price Modifier', 'kitchen-configurator-pro' ), 'step' => '0.01', 'default' => 0 ),
			'thumbnail_url'  => array( 'type' => 'image', 'label' => __( 'Thumbnail', 'kitchen-configurator-pro' ) ),
			'sort_order'     => array( 'type' => 'number', 'label' => __( 'Sort Order', 'kitchen-configurator-pro' ), 'default' => 0, 'min' => 0 ),
			'is_active'      => array( 'type' => 'checkbox', 'label' => __( 'Active', 'kitchen-configurator-pro' ), 'default' => 1 ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_context( array $values ): array {
		/** @var MaterialRepository $materials */
		$materials = $this->container->get( MaterialRepository::class );
		$options   = array( '' => __( '— Select —', 'kitchen-configurator-pro' ) );

		foreach ( $materials->find_all() as $material ) {
			$row = Arr::to_array( $material );
			$label = sprintf(
				'%s (%s)',
				(string) $row['name'],
				$this->zone_label_for_material_type( (string) $row['material_type'] )
			);
			$options[ (string) $row['id'] ] = $label;
		}

		$fields = $this->form_fields();
		$fields['material_id']['options'] = $options;

		return array( 'fields' => $fields );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function render_form( bool $is_edit ): void {
		$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$values = array();

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

		$fields  = $this->form_fields();
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
