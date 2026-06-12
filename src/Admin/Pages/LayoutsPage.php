<?php
/**
 * Layouts admin page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Admin\AbstractCrudPage;
use KitchenConfiguratorPro\Repositories\LayoutRepository;

/**
 * CRUD admin page for kitchen layouts.
 */
final class LayoutsPage extends AbstractCrudPage {

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'kcp-layouts';
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label(): string {
		return __( 'Layout', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label_plural(): string {
		return __( 'Layouts', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function repository_class(): string {
		return LayoutRepository::class;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function list_columns(): array {
		return array(
			'name'       => __( 'Name', 'kitchen-configurator-pro' ),
			'slug'       => __( 'Slug', 'kitchen-configurator-pro' ),
			'sort_order' => __( 'Sort', 'kitchen-configurator-pro' ),
			'is_active'  => __( 'Active', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_fields(): array {
		return array(
			'name'          => array(
				'type'     => 'text',
				'label'    => __( 'Name', 'kitchen-configurator-pro' ),
				'required' => true,
			),
			'slug'          => array(
				'type'        => 'text',
				'label'       => __( 'Slug', 'kitchen-configurator-pro' ),
				'description' => __( 'Leave empty to auto-generate from name.', 'kitchen-configurator-pro' ),
			),
			'description'   => array(
				'type'  => 'textarea',
				'label' => __( 'Description', 'kitchen-configurator-pro' ),
				'rows'  => 4,
			),
			'thumbnail_url' => array(
				'type'  => 'url',
				'label' => __( 'Thumbnail URL', 'kitchen-configurator-pro' ),
			),
			'config_json'   => array(
				'type'        => 'json',
				'label'       => __( 'Layout Config (JSON)', 'kitchen-configurator-pro' ),
				'description' => __( 'Optional layout constraints and zones.', 'kitchen-configurator-pro' ),
				'rows'        => 8,
			),
			'sort_order'    => array(
				'type'    => 'number',
				'label'   => __( 'Sort Order', 'kitchen-configurator-pro' ),
				'default' => 0,
				'min'     => 0,
			),
			'is_active'     => array(
				'type'    => 'checkbox',
				'label'   => __( 'Active', 'kitchen-configurator-pro' ),
				'default' => 1,
			),
		);
	}
}
