<?php
/**
 * Plinths admin page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Admin\AbstractCrudPage;
use KitchenConfiguratorPro\Repositories\PlinthRepository;

/**
 * CRUD admin page for plinths.
 */
final class PlinthsPage extends AbstractCrudPage {

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'kcp-plinths';
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label(): string {
		return __( 'Plinth', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label_plural(): string {
		return __( 'Plinths', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function repository_class(): string {
		return PlinthRepository::class;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function list_columns(): array {
		return array(
			'name'       => __( 'Name', 'kitchen-configurator-pro' ),
			'base_price' => __( 'Base Price', 'kitchen-configurator-pro' ),
			'is_active'  => __( 'Active', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_fields(): array {
		return array(
			'name'                   => array( 'type' => 'text', 'label' => __( 'Name', 'kitchen-configurator-pro' ), 'required' => true ),
			'slug'                   => array( 'type' => 'text', 'label' => __( 'Slug', 'kitchen-configurator-pro' ) ),
			'description'            => array( 'type' => 'textarea', 'label' => __( 'Description', 'kitchen-configurator-pro' ), 'rows' => 3 ),
			'default_height'         => array( 'type' => 'number', 'label' => __( 'Default Height (mm)', 'kitchen-configurator-pro' ), 'default' => 150, 'min' => 0 ),
			'min_height'             => array( 'type' => 'number', 'label' => __( 'Min Height (mm)', 'kitchen-configurator-pro' ), 'default' => 100, 'min' => 0 ),
			'max_height'             => array( 'type' => 'number', 'label' => __( 'Max Height (mm)', 'kitchen-configurator-pro' ), 'default' => 200, 'min' => 0 ),
			'height_step'            => array( 'type' => 'number', 'label' => __( 'Height Step (mm)', 'kitchen-configurator-pro' ), 'default' => 10, 'min' => 1 ),
			'default_length'         => array( 'type' => 'number', 'label' => __( 'Default Length (mm)', 'kitchen-configurator-pro' ), 'default' => 3000, 'min' => 0 ),
			'min_length'             => array( 'type' => 'number', 'label' => __( 'Min Length (mm)', 'kitchen-configurator-pro' ), 'default' => 600, 'min' => 0 ),
			'max_length'             => array( 'type' => 'number', 'label' => __( 'Max Length (mm)', 'kitchen-configurator-pro' ), 'default' => 10000, 'min' => 0 ),
			'length_step'            => array( 'type' => 'number', 'label' => __( 'Length Step (mm)', 'kitchen-configurator-pro' ), 'default' => 10, 'min' => 1 ),
			'base_price'             => array( 'type' => 'number', 'label' => __( 'Base Price', 'kitchen-configurator-pro' ), 'step' => '0.01', 'default' => 0 ),
			'price_per_linear_meter' => array( 'type' => 'number', 'label' => __( 'Price per Linear Meter', 'kitchen-configurator-pro' ), 'step' => '0.0001', 'default' => 0 ),
			'thumbnail_url'          => array( 'type' => 'url', 'label' => __( 'Thumbnail URL', 'kitchen-configurator-pro' ) ),
			'sort_order'             => array( 'type' => 'number', 'label' => __( 'Sort Order', 'kitchen-configurator-pro' ), 'default' => 0, 'min' => 0 ),
			'is_active'              => array( 'type' => 'checkbox', 'label' => __( 'Active', 'kitchen-configurator-pro' ), 'default' => 1 ),
		);
	}
}
