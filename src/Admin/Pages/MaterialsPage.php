<?php
/**
 * Materials admin page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Admin\AbstractCrudPage;
use KitchenConfiguratorPro\Domain\Enums\MaterialType;
use KitchenConfiguratorPro\Repositories\MaterialRepository;

/**
 * CRUD admin page for materials.
 */
final class MaterialsPage extends AbstractCrudPage {

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'kcp-materials';
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label(): string {
		return __( 'Material', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label_plural(): string {
		return __( 'Materials', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function repository_class(): string {
		return MaterialRepository::class;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function list_columns(): array {
		return array(
			'name'          => __( 'Name', 'kitchen-configurator-pro' ),
			'material_type' => __( 'Type', 'kitchen-configurator-pro' ),
			'price_modifier'=> __( 'Modifier', 'kitchen-configurator-pro' ),
			'is_active'     => __( 'Active', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_fields(): array {
		return array(
			'name'             => array( 'type' => 'text', 'label' => __( 'Name', 'kitchen-configurator-pro' ), 'required' => true ),
			'slug'             => array( 'type' => 'text', 'label' => __( 'Slug', 'kitchen-configurator-pro' ) ),
			'material_type'    => array(
				'type'     => 'select',
				'label'    => __( 'Material Type', 'kitchen-configurator-pro' ),
				'required' => true,
				'options'  => MaterialType::labels(),
			),
			'description'      => array( 'type' => 'textarea', 'label' => __( 'Description', 'kitchen-configurator-pro' ), 'rows' => 3 ),
			'price_modifier'   => array( 'type' => 'number', 'label' => __( 'Price Modifier', 'kitchen-configurator-pro' ), 'step' => '0.01', 'default' => 0 ),
			'price_per_sqm'    => array( 'type' => 'number', 'label' => __( 'Price per m²', 'kitchen-configurator-pro' ), 'step' => '0.0001' ),
			'price_multiplier' => array( 'type' => 'number', 'label' => __( 'Price Multiplier', 'kitchen-configurator-pro' ), 'step' => '0.0001', 'default' => 1 ),
			'thumbnail_url'    => array( 'type' => 'url', 'label' => __( 'Thumbnail URL', 'kitchen-configurator-pro' ) ),
			'sort_order'       => array( 'type' => 'number', 'label' => __( 'Sort Order', 'kitchen-configurator-pro' ), 'default' => 0, 'min' => 0 ),
			'is_active'        => array( 'type' => 'checkbox', 'label' => __( 'Active', 'kitchen-configurator-pro' ), 'default' => 1 ),
		);
	}
}
