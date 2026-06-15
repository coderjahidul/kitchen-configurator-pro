<?php
/**
 * Product presets admin page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Admin\AbstractCrudPage;
use KitchenConfiguratorPro\Integration\WooCommerce\ProductManager;
use KitchenConfiguratorPro\Repositories\LayoutRepository;
use KitchenConfiguratorPro\Repositories\ProductPresetRepository;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Links WooCommerce products to configurator presets.
 */
final class ProductPresetsPage extends AbstractCrudPage {

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'kcp-products';
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label(): string {
		return __( 'Product', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label_plural(): string {
		return __( 'Products', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function repository_class(): string {
		return ProductPresetRepository::class;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function list_columns(): array {
		return array(
			'name'          => __( 'Name', 'kitchen-configurator-pro' ),
			'wc_product_id' => __( 'WooCommerce Product', 'kitchen-configurator-pro' ),
			'layout_id'     => __( 'Layout', 'kitchen-configurator-pro' ),
			'is_active'     => __( 'Active', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * Cached layout names keyed by ID.
	 *
	 * @var array<int, string>|null
	 */
	private ?array $layout_names = null;

	/**
	 * {@inheritDoc}
	 */
	public function format_column( string $column, array $row ): string {
		if ( 'wc_product_id' === $column ) {
			$product_id = (int) ( $row['wc_product_id'] ?? 0 );
			$title      = $product_id > 0 ? get_the_title( $product_id ) : '';

			return esc_html( '' !== $title ? $title : (string) $product_id );
		}

		if ( 'layout_id' === $column ) {
			$layout_id = (int) ( $row['layout_id'] ?? 0 );
			$names     = $this->layout_names();

			return esc_html( $names[ $layout_id ] ?? __( 'Unknown', 'kitchen-configurator-pro' ) );
		}

		return parent::format_column( $column, $row );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_fields(): array {
		return array(
			'wc_product_id'      => array(
				'type'     => 'select',
				'label'    => __( 'WooCommerce Product', 'kitchen-configurator-pro' ),
				'required' => true,
				'options'  => array(),
			),
			'layout_id'          => array(
				'type'     => 'select',
				'label'    => __( 'Layout', 'kitchen-configurator-pro' ),
				'required' => true,
				'options'  => array(),
			),
			'name'               => array(
				'type'        => 'text',
				'label'       => __( 'Preset Name', 'kitchen-configurator-pro' ),
				'description' => __( 'Optional admin label. Defaults to the WooCommerce product name.', 'kitchen-configurator-pro' ),
			),
			'configuration_json' => array(
				'type'        => 'json',
				'label'       => __( 'Configuration (JSON)', 'kitchen-configurator-pro' ),
				'required'    => true,
				'default'     => '{"schema_version":"1.0","layout_id":0,"title":"","cabinets":[],"global_options":{},"product_options":{"specs":{"dimensions":["311.4 cm breed","60 cm diep","209.3 cm hoog"],"includes":["1x oven 60 cm hoog","1x koelkast 178 cm hoog"]},"colors":[{"id":"light-oak","label":"licht gerookt eiken decor","image_url":"","price_modifier":0},{"id":"single-oak","label":"enkel gerookt eiken decor","image_url":"","price_modifier":0},{"id":"double-oak","label":"dubbel gerookt eiken decor","note":"in winkelwagen te personaliseren","image_url":"","price_modifier":0}],"heights":[{"id":"209","label":"209.3 cm hoog","price_modifier":0},{"id":"222","label":"222.2 cm hoog","price_modifier":195},{"id":"235","label":"235.2 cm hoog","price_modifier":314}],"default_color":"light-oak","default_height":"209"}}',
				'rows'        => 16,
				'description' => __( 'Default configurator data plus optional product_options for storefront color/height selectors.', 'kitchen-configurator-pro' ),
			),
			'catalog_scope_json' => array(
				'type'        => 'json',
				'label'       => __( 'Catalog Scope (JSON)', 'kitchen-configurator-pro' ),
				'default'     => '{}',
				'rows'        => 8,
				'description' => __( 'Optional limits on which catalog items are available for this product.', 'kitchen-configurator-pro' ),
			),
			'is_active'          => array(
				'type'    => 'checkbox',
				'label'   => __( 'Active', 'kitchen-configurator-pro' ),
				'default' => 1,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function validate( array $data ): ?string {
		$error = parent::validate( $data );

		if ( null !== $error ) {
			return $error;
		}

		$wc_product_id = (int) ( $data['wc_product_id'] ?? 0 );

		if ( $wc_product_id <= 0 ) {
			return __( 'Please select a WooCommerce product.', 'kitchen-configurator-pro' );
		}

		$product = wc_get_product( $wc_product_id );

		if ( ! $product ) {
			return __( 'Selected WooCommerce product was not found.', 'kitchen-configurator-pro' );
		}

		$container_id = ( new ProductManager() )->get_product_id();

		if ( $container_id > 0 && $wc_product_id === $container_id ) {
			return __( 'The hidden configurator container product cannot be linked here.', 'kitchen-configurator-pro' );
		}

		$layout_id = (int) ( $data['layout_id'] ?? 0 );

		if ( $layout_id <= 0 ) {
			return __( 'Please select a layout.', 'kitchen-configurator-pro' );
		}

		/** @var ProductPresetRepository $repository */
		$repository = $this->repository();
		$existing   = $repository->find_by_wc_product_id( $wc_product_id );
		$current_id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

		if ( null !== $existing && $existing->id !== $current_id ) {
			return __( 'This WooCommerce product is already linked to another preset.', 'kitchen-configurator-pro' );
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_context( array $values ): array {
		$fields = $this->form_fields();

		$fields['wc_product_id']['options'] = array( '' => __( '— Select WooCommerce product —', 'kitchen-configurator-pro' ) ) + $this->woocommerce_product_options();
		$fields['layout_id']['options']     = array( '' => __( '— Select layout —', 'kitchen-configurator-pro' ) ) + $this->layout_options();

		return array(
			'fields' => $fields,
		);
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

	/**
	 * Build WooCommerce product select options.
	 *
	 * @return array<string, string>
	 */
	private function woocommerce_product_options(): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$container_id = ( new ProductManager() )->get_product_id();
		$options      = array();

		$products = wc_get_products(
			array(
				'status'  => array( 'publish', 'draft', 'private' ),
				'limit'   => 200,
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);

		foreach ( $products as $product ) {
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$product_id = $product->get_id();

			if ( $container_id > 0 && $product_id === $container_id ) {
				continue;
			}

			$options[ (string) $product_id ] = sprintf(
				'%1$s (#%2$d)',
				$product->get_name(),
				$product_id
			);
		}

		return $options;
	}

	/**
	 * Build layout select options.
	 *
	 * @return array<string, string>
	 */
	private function layout_options(): array {
		/** @var LayoutRepository $layouts */
		$layouts = $this->container->get( LayoutRepository::class );
		$options = array();

		foreach ( $layouts->find_all( array( 'is_active' => '1' ) ) as $layout ) {
			$row = Arr::to_array( $layout );
			$options[ (string) $row['id'] ] = (string) $row['name'];
		}

		return $options;
	}

	/**
	 * Load layout names for list display.
	 *
	 * @return array<int, string>
	 */
	private function layout_names(): array {
		if ( null !== $this->layout_names ) {
			return $this->layout_names;
		}

		$this->layout_names = array();

		foreach ( $this->layout_options() as $id => $name ) {
			$this->layout_names[ (int) $id ] = $name;
		}

		return $this->layout_names;
	}
}
