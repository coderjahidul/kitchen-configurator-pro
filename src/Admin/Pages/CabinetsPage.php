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
use KitchenConfiguratorPro\Repositories\CabinetRelationRepository;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Support\Arr;
use KitchenConfiguratorPro\Support\Helpers;

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
			'child_cabinets'       => array(
				'type'        => 'cabinet_items',
				'label'       => __( 'Cabinet Items', 'kitchen-configurator-pro' ),
				'description' => __( 'Add items that appear when customers select this cabinet. Use “Add New Item” for each sub-option.', 'kitchen-configurator-pro' ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function handle_post(): void {
		$cabinet_items = $this->collect_cabinet_items();

		$nonce_action = $this->nonce_action();
		$nonce        = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			$this->add_notice( 'error', __( 'Security check failed.', 'kitchen-configurator-pro' ) );
			return;
		}

		$action = sanitize_key( wp_unslash( (string) $_POST['kcp_action'] ) );
		$data   = $this->collect_post_data();

		$validation_error = $this->validate( $data );

		if ( null !== $validation_error ) {
			$this->add_notice( 'error', $validation_error );
			return;
		}

		$repository = $this->repository();

		if ( 'create' === $action ) {
			$result = $repository->create( $data );

			if ( null === $result ) {
				$this->add_notice( 'error', __( 'Failed to create record.', 'kitchen-configurator-pro' ) );
				return;
			}

			$new_id = (int) ( Arr::to_array( $result )['id'] ?? 0 );

			if ( $new_id > 0 ) {
				$this->sync_cabinet_items( $new_id, $data, $cabinet_items );
			}

			if ( $this->invalidates_catalog_cache() ) {
				Helpers::bump_catalog_cache_version();
			}

			$this->redirect_with_notice(
				'created',
				add_query_arg(
					array(
						'page'   => $this->slug(),
						'action' => 'edit',
						'id'     => $new_id,
					),
					admin_url( 'admin.php' )
				)
			);
		}

		if ( 'update' === $action ) {
			$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

			if ( $id <= 0 ) {
				$this->add_notice( 'error', __( 'Invalid record ID.', 'kitchen-configurator-pro' ) );
				return;
			}

			$result = $repository->update( $id, $data );

			if ( null === $result ) {
				$this->add_notice( 'error', __( 'Failed to update record.', 'kitchen-configurator-pro' ) );
				return;
			}

			$this->sync_cabinet_items( $id, $data, $cabinet_items );

			if ( $this->invalidates_catalog_cache() ) {
				Helpers::bump_catalog_cache_version();
			}

			$this->redirect_with_notice(
				'updated',
				add_query_arg(
					array(
						'page'   => $this->slug(),
						'action' => 'edit',
						'id'     => $id,
					),
					admin_url( 'admin.php' )
				)
			);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function handle_delete(): void {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

		if ( $id > 0 ) {
			/** @var CabinetRelationRepository $relations */
			$relations = $this->container->get( CabinetRelationRepository::class );

			foreach ( $relations->get_child_ids( $id ) as $child_id ) {
				$this->repository()->delete( $child_id );
			}

			$relations->delete_by_cabinet( $id );
		}

		parent::handle_delete();
	}

	/**
	 * Collect cabinet items from POST data.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function collect_cabinet_items(): array {
		if ( ! isset( $_POST['cabinet_items'] ) || ! is_array( $_POST['cabinet_items'] ) ) {
			return array();
		}

		$items = array();

		foreach ( wp_unslash( $_POST['cabinet_items'] ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$items[] = array(
				'id'         => isset( $item['id'] ) ? (int) $item['id'] : 0,
				'name'       => sanitize_text_field( (string) ( $item['name'] ?? '' ) ),
				'slug'       => sanitize_title( (string) ( $item['slug'] ?? '' ) ),
				'image_url'  => esc_url_raw( (string) ( $item['image_url'] ?? '' ) ),
				'base_price' => is_numeric( $item['base_price'] ?? null ) ? (string) $item['base_price'] : '0',
				'is_active'  => isset( $item['is_active'] ) ? '1' : '0',
			);
		}

		return $items;
	}

	/**
	 * Create, update, or remove child cabinet items for a parent.
	 *
	 * @param int                          $parent_id     Parent cabinet ID.
	 * @param array<string, mixed>         $parent_data   Parent cabinet data.
	 * @param array<int, array<string, mixed>> $items     Submitted item rows.
	 * @return void
	 */
	private function sync_cabinet_items( int $parent_id, array $parent_data, array $items ): void {
		if ( $parent_id <= 0 ) {
			return;
		}

		/** @var CabinetRelationRepository $relations */
		$relations = $this->container->get( CabinetRelationRepository::class );
		/** @var CabinetRepository $repository */
		$repository   = $this->repository();
		$existing_ids = $relations->get_child_ids( $parent_id );
		$kept_ids     = array();
		$sort_order   = 0;

		foreach ( $items as $item ) {
			$name = trim( (string) ( $item['name'] ?? '' ) );

			if ( '' === $name ) {
				continue;
			}

			$child_id   = (int) ( $item['id'] ?? 0 );
			$child_data = $this->build_child_item_data( $parent_data, $item, $sort_order );
			++$sort_order;

			if ( $child_id > 0 && in_array( $child_id, $existing_ids, true ) ) {
				$updated = $repository->update( $child_id, $child_data );

				if ( null !== $updated ) {
					$kept_ids[] = $child_id;
				}

				continue;
			}

			$created = $repository->create( $child_data );

			if ( null === $created ) {
				continue;
			}

			$kept_ids[] = (int) ( Arr::to_array( $created )['id'] ?? 0 );
		}

		$kept_ids = array_values( array_filter( array_unique( $kept_ids ) ) );
		$relations->sync_children( $parent_id, $kept_ids );

		foreach ( array_diff( $existing_ids, $kept_ids ) as $removed_id ) {
			$repository->delete( (int) $removed_id );
		}
	}

	/**
	 * Build cabinet row data for a child item from parent defaults.
	 *
	 * @param array<string, mixed> $parent_data Parent cabinet data.
	 * @param array<string, mixed> $item        Item row data.
	 * @param int                  $sort_order  Sort order.
	 * @return array<string, mixed>
	 */
	private function build_child_item_data( array $parent_data, array $item, int $sort_order ): array {
		$base_price = (string) ( $item['base_price'] ?? '' );

		if ( ! is_numeric( $base_price ) || (float) $base_price <= 0 ) {
			$base_price = (string) ( $parent_data['base_price'] ?? '0' );
		}

		return array(
			'category_id'          => (int) ( $parent_data['category_id'] ?? 0 ),
			'name'                 => (string) ( $item['name'] ?? '' ),
			'slug'                 => (string) ( $item['slug'] ?? '' ),
			'description'          => '',
			'sku_prefix'           => (string) ( $parent_data['sku_prefix'] ?? '' ),
			'default_width'        => (int) ( $parent_data['default_width'] ?? 0 ),
			'default_height'       => (int) ( $parent_data['default_height'] ?? 0 ),
			'default_depth'        => (int) ( $parent_data['default_depth'] ?? 0 ),
			'min_width'            => (int) ( $parent_data['min_width'] ?? 0 ),
			'max_width'            => (int) ( $parent_data['max_width'] ?? 0 ),
			'min_height'           => (int) ( $parent_data['min_height'] ?? 0 ),
			'max_height'           => (int) ( $parent_data['max_height'] ?? 0 ),
			'min_depth'            => (int) ( $parent_data['min_depth'] ?? 0 ),
			'max_depth'            => (int) ( $parent_data['max_depth'] ?? 0 ),
			'width_step'           => (int) ( $parent_data['width_step'] ?? 10 ),
			'height_step'          => (int) ( $parent_data['height_step'] ?? 10 ),
			'depth_step'           => (int) ( $parent_data['depth_step'] ?? 10 ),
			'base_price'           => $base_price,
			'dimension_price_json' => (string) ( $parent_data['dimension_price_json'] ?? '' ),
			'image_url'            => (string) ( $item['image_url'] ?? '' ),
			'sort_order'           => $sort_order,
			'is_active'            => (string) ( $item['is_active'] ?? '1' ),
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

		$cabinet_id = (int) ( $values['id'] ?? 0 );

		$fields = $this->form_fields();
		$fields['category_id']['options'] = $options;

		/** @var CabinetRelationRepository $relations */
		$relations = $this->container->get( CabinetRelationRepository::class );
		$fields['child_cabinets']['cabinet_items'] = $this->resolve_cabinet_items( $cabinet_id, $values );

		return array( 'fields' => $fields );
	}

	/**
	 * Load item rows assigned to a parent cabinet.
	 *
	 * @param int                 $parent_id Parent cabinet ID.
	 * @param array<string,mixed> $parent    Parent cabinet values.
	 * @return array<int, array<string, mixed>>
	 */
	private function resolve_cabinet_items( int $parent_id, array $parent = array() ): array {
		if ( $parent_id <= 0 ) {
			return array();
		}

		/** @var CabinetRelationRepository $relations */
		$relations = $this->container->get( CabinetRelationRepository::class );
		$items     = array();

		foreach ( $relations->get_children_for_admin( $parent_id ) as $cabinet ) {
			$row = Arr::to_array( $cabinet );

			$items[] = array(
				'id'         => (int) ( $row['id'] ?? 0 ),
				'name'       => (string) ( $row['name'] ?? '' ),
				'slug'       => (string) ( $row['slug'] ?? '' ),
				'image_url'  => (string) ( $row['image_url'] ?? '' ),
				'base_price' => (string) ( $row['base_price'] ?? ( $parent['base_price'] ?? '0' ) ),
				'is_active'  => ! empty( $row['is_active'] ),
			);
		}

		return $items;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function render_list(): void {
		/** @var CabinetRelationRepository $relations */
		$relations = $this->container->get( CabinetRelationRepository::class );
		$child_ids = $relations->get_all_child_cabinet_ids();

		$items = array_values(
			array_filter(
				$this->repository()->find_all(),
				static function ( $cabinet ) use ( $child_ids ): bool {
					$row = Arr::to_array( $cabinet );
					$id  = (int) ( $row['id'] ?? 0 );

					return $id > 0 && ! in_array( $id, $child_ids, true );
				}
			)
		);

		$this->load_template(
			'crud-list',
			array(
				'page'         => $this,
				'items'        => $items,
				'columns'      => $this->list_columns(),
				'notices'      => $this->resolve_notices(),
				'add_url'      => $this->form_url(),
				'entity_label' => $this->entity_label(),
			)
		);
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

		if ( ! $is_edit ) {
			unset( $fields['child_cabinets'] );
		}

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
