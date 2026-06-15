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
use KitchenConfiguratorPro\Services\ProductPresetFormSerializer;
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
			'is_active'     => __( 'Active', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function format_column( string $column, array $row ): string {
		if ( 'wc_product_id' === $column ) {
			$product_id = (int) ( $row['wc_product_id'] ?? 0 );
			$title      = $product_id > 0 ? get_the_title( $product_id ) : '';

			return esc_html( '' !== $title ? $title : (string) $product_id );
		}

		return parent::format_column( $column, $row );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_fields(): array {
		return array(
			'wc_product_id' => array(
				'type'     => 'select',
				'label'    => __( 'WooCommerce Product', 'kitchen-configurator-pro' ),
				'required' => true,
			),
			'name'          => array(
				'type'  => 'text',
				'label' => __( 'Preset Name', 'kitchen-configurator-pro' ),
			),
			'is_active'     => array(
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
			return __( 'No layout is available. At least one active layout must exist in the database.', 'kitchen-configurator-pro' );
		}

		/** @var ProductPresetRepository $repository */
		$repository = $this->repository();
		$existing   = $repository->find_by_wc_product_id( $wc_product_id );
		$current_id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

		if ( null !== $existing && $existing->id !== $current_id ) {
			return __( 'This WooCommerce product is already linked to another preset.', 'kitchen-configurator-pro' );
		}

		$config = json_decode( (string) ( $data['configuration_json'] ?? '{}' ), true );
		$parts  = is_array( $config['product_options']['parts'] ?? null ) ? $config['product_options']['parts'] : array();

		if ( empty( $parts ) ) {
			return __( 'Add at least one cart breakdown part with a label.', 'kitchen-configurator-pro' );
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function collect_post_data(): array {
		$data = array(
			'wc_product_id' => isset( $_POST['wc_product_id'] ) ? (int) $_POST['wc_product_id'] : 0,
			'name'          => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['name'] ) ) : '',
			'is_active'     => isset( $_POST['is_active'] ) ? '1' : '0',
			'layout_id'     => 0,
		);

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

		if ( $id > 0 ) {
			$item = $this->repository()->find( $id );

			if ( null !== $item ) {
				$data['layout_id'] = $item->layout_id;
			}
		}

		if ( (int) $data['layout_id'] <= 0 ) {
			$data['layout_id'] = $this->default_layout_id();
		}

		$preset_post     = isset( $_POST['kcp_preset'] ) && is_array( $_POST['kcp_preset'] )
			? wp_unslash( $_POST['kcp_preset'] )
			: array();
		$existing_config = array();
		$catalog_scope   = '';

		if ( $id > 0 ) {
			$item = $this->repository()->find( $id );

			if ( null !== $item ) {
				$decoded = json_decode( $item->configuration_json, true );
				$existing_config = is_array( $decoded ) ? $decoded : array();
				$catalog_scope   = (string) $item->catalog_scope_json;
			}
		}

		$data['configuration_json'] = $this->serializer()->to_configuration_json(
			$preset_post,
			(int) $data['layout_id'],
			$existing_config
		);
		$data['catalog_scope_json'] = $catalog_scope;

		return $data;
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

		if ( ! isset( $values['is_active'] ) ) {
			$values['is_active'] = 1;
		}

		$preset = $this->serializer()->from_configuration_json( (string) ( $values['configuration_json'] ?? '{}' ) );

		$this->load_template(
			'product-preset-form',
			array(
				'page'                 => $this,
				'is_edit'              => $is_edit,
				'id'                   => $id,
				'values'               => $values,
				'preset'               => $preset,
				'option_type_labels'   => $this->serializer()->option_type_labels(),
				'part_type_labels'     => $this->serializer()->part_type_labels(),
				'wc_product_options'   => array( '' => __( '— Select WooCommerce product —', 'kitchen-configurator-pro' ) ) + $this->woocommerce_product_options(),
				'notices'              => $this->resolve_notices(),
				'list_url'             => $this->list_url(),
				'entity_label'         => $this->entity_label(),
				'nonce_action'         => $this->nonce_action(),
			)
		);
	}

	/**
	 * @return ProductPresetFormSerializer
	 */
	private function serializer(): ProductPresetFormSerializer {
		return new ProductPresetFormSerializer();
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
	 * Resolve the default layout ID for new product presets.
	 *
	 * @return int
	 */
	private function default_layout_id(): int {
		/** @var LayoutRepository $layouts */
		$layouts = $this->container->get( LayoutRepository::class );
		$options = array();

		foreach ( $layouts->find_all( array( 'is_active' => '1' ) ) as $layout ) {
			$row = Arr::to_array( $layout );
			$options[ (string) $row['id'] ] = (string) $row['name'];
		}

		$ids = array_keys( $options );

		return $ids ? (int) $ids[0] : 0;
	}
}
