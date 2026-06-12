<?php
/**
 * Handles admin page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Admin\AbstractCrudPage;
use KitchenConfiguratorPro\Repositories\HandleRepository;

/**
 * CRUD admin page for handles.
 */
final class HandlesPage extends AbstractCrudPage {

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'kcp-handles';
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label(): string {
		return __( 'Handle', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label_plural(): string {
		return __( 'Handles', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function repository_class(): string {
		return HandleRepository::class;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function list_columns(): array {
		return array(
			'name'      => __( 'Name', 'kitchen-configurator-pro' ),
			'price'     => __( 'Price', 'kitchen-configurator-pro' ),
			'is_active' => __( 'Active', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_fields(): array {
		return array(
			'name'          => array( 'type' => 'text', 'label' => __( 'Name', 'kitchen-configurator-pro' ), 'required' => true ),
			'slug'          => array( 'type' => 'text', 'label' => __( 'Slug', 'kitchen-configurator-pro' ) ),
			'description'   => array( 'type' => 'textarea', 'label' => __( 'Description', 'kitchen-configurator-pro' ), 'rows' => 3 ),
			'price'         => array( 'type' => 'number', 'label' => __( 'Price', 'kitchen-configurator-pro' ), 'step' => '0.01', 'default' => 0 ),
			'thumbnail_url' => array( 'type' => 'url', 'label' => __( 'Thumbnail URL', 'kitchen-configurator-pro' ) ),
			'sort_order'    => array( 'type' => 'number', 'label' => __( 'Sort Order', 'kitchen-configurator-pro' ), 'default' => 0, 'min' => 0 ),
			'is_active'     => array( 'type' => 'checkbox', 'label' => __( 'Active', 'kitchen-configurator-pro' ), 'default' => 1 ),
		);
	}
}
