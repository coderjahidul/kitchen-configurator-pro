<?php
/**
 * Worktops admin page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Admin\AbstractCrudPage;
use KitchenConfiguratorPro\Repositories\WorktopRepository;

/**
 * CRUD admin page for worktops.
 */
final class WorktopsPage extends AbstractCrudPage {

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'kcp-worktops';
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label(): string {
		return __( 'Worktop', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label_plural(): string {
		return __( 'Worktops', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function repository_class(): string {
		return WorktopRepository::class;
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
			'default_length'         => array( 'type' => 'number', 'label' => __( 'Default Length (mm)', 'kitchen-configurator-pro' ), 'default' => 3000, 'min' => 0 ),
			'default_depth'          => array( 'type' => 'number', 'label' => __( 'Default Depth (mm)', 'kitchen-configurator-pro' ), 'default' => 600, 'min' => 0 ),
			'min_length'             => array( 'type' => 'number', 'label' => __( 'Min Length (mm)', 'kitchen-configurator-pro' ), 'default' => 600, 'min' => 0 ),
			'max_length'             => array( 'type' => 'number', 'label' => __( 'Max Length (mm)', 'kitchen-configurator-pro' ), 'default' => 5000, 'min' => 0 ),
			'min_depth'              => array( 'type' => 'number', 'label' => __( 'Min Depth (mm)', 'kitchen-configurator-pro' ), 'default' => 400, 'min' => 0 ),
			'max_depth'              => array( 'type' => 'number', 'label' => __( 'Max Depth (mm)', 'kitchen-configurator-pro' ), 'default' => 1200, 'min' => 0 ),
			'length_step'            => array( 'type' => 'number', 'label' => __( 'Length Step (mm)', 'kitchen-configurator-pro' ), 'default' => 10, 'min' => 1 ),
			'depth_step'             => array( 'type' => 'number', 'label' => __( 'Depth Step (mm)', 'kitchen-configurator-pro' ), 'default' => 10, 'min' => 1 ),
			'base_price'             => array( 'type' => 'number', 'label' => __( 'Base Price', 'kitchen-configurator-pro' ), 'step' => '0.01', 'default' => 0 ),
			'price_per_sqm'          => array( 'type' => 'number', 'label' => __( 'Price per m²', 'kitchen-configurator-pro' ), 'step' => '0.0001' ),
			'price_per_linear_meter' => array( 'type' => 'number', 'label' => __( 'Price per Linear Meter', 'kitchen-configurator-pro' ), 'step' => '0.0001' ),
			'thumbnail_url'        => array( 'type' => 'url', 'label' => __( 'Thumbnail URL', 'kitchen-configurator-pro' ) ),
			'sort_order'             => array( 'type' => 'number', 'label' => __( 'Sort Order', 'kitchen-configurator-pro' ), 'default' => 0, 'min' => 0 ),
			'is_active'              => array( 'type' => 'checkbox', 'label' => __( 'Active', 'kitchen-configurator-pro' ), 'default' => 1 ),
		);
	}
}
