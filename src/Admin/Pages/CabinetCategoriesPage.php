<?php
/**
 * Cabinet categories admin page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Admin\AbstractCrudPage;
use KitchenConfiguratorPro\Repositories\CabinetCategoryRepository;

/**
 * CRUD admin page for cabinet categories.
 */
final class CabinetCategoriesPage extends AbstractCrudPage {

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'kcp-cabinet-categories';
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label(): string {
		return __( 'Cabinet Category', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label_plural(): string {
		return __( 'Cabinet Categories', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function repository_class(): string {
		return CabinetCategoryRepository::class;
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
			'name'        => array(
				'type'     => 'text',
				'label'    => __( 'Name', 'kitchen-configurator-pro' ),
				'required' => true,
			),
			'slug'        => array(
				'type'        => 'text',
				'label'       => __( 'Slug', 'kitchen-configurator-pro' ),
				'description' => __( 'Leave empty to auto-generate from name.', 'kitchen-configurator-pro' ),
			),
			'description' => array(
				'type'  => 'textarea',
				'label' => __( 'Description', 'kitchen-configurator-pro' ),
				'rows'  => 4,
			),
			'image_url_greep' => array(
				'type'        => 'image',
				'label'       => __( 'Preview image (greep)', 'kitchen-configurator-pro' ),
				'description' => __( 'Shown on the cabinet select page for kitchens with handles.', 'kitchen-configurator-pro' ),
			),
			'image_url_greeploos' => array(
				'type'        => 'image',
				'label'       => __( 'Preview image (greeploos)', 'kitchen-configurator-pro' ),
				'description' => __( 'Shown on the cabinet select page for handle-less kitchens.', 'kitchen-configurator-pro' ),
			),
			'sort_order'  => array(
				'type'    => 'number',
				'label'   => __( 'Sort Order', 'kitchen-configurator-pro' ),
				'default' => 0,
				'min'     => 0,
			),
			'is_active'   => array(
				'type'    => 'checkbox',
				'label'   => __( 'Active', 'kitchen-configurator-pro' ),
				'default' => 1,
			),
		);
	}
}
