<?php
/**
 * Storefront color and height selectors on single product pages.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Domain\Entities\ProductPreset;
use KitchenConfiguratorPro\Repositories\ProductPresetRepository;
use KitchenConfiguratorPro\Services\ProductBreakdownBuilder;
use KitchenConfiguratorPro\Services\ProductStorefrontOptionsBuilder;

/**
 * Renders KKF-style product option bars from preset configuration.
 */
final class ProductOptionsPresenter {

	/**
	 * Custom WooCommerce add-to-cart handler for preset-backed variable products.
	 */
	public const ADD_TO_CART_HANDLER = 'kcp_preset';

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Meta key storing the auto-created placeholder variation for preset cart adds.
	 */
	private const PRESET_VARIATION_META = '_kcp_preset_variation_id';

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_options' ), 21 );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_hidden_fields' ), 5 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_handler', array( $this, 'add_to_cart_handler' ), 10, 2 );
		add_action( 'woocommerce_add_to_cart_handler_' . self::ADD_TO_CART_HANDLER, array( $this, 'handle_preset_add_to_cart' ) );
	}

	/**
	 * Render product specs, color, and height selectors.
	 *
	 * @return void
	 */
	public function render_options(): void {
		if ( ! is_product() ) {
			return;
		}

		$preset = $this->current_preset();

		if ( null === $preset || ! $this->options_builder()->can_render( $preset ) ) {
			return;
		}

		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );

		$options        = $this->options_builder()->build( $preset );
		$base_price     = (float) wc_get_price_to_display( $product );
		$option_groups  = is_array( $options['option_groups'] ?? null ) ? $options['option_groups'] : array();
		$colors         = is_array( $options['colors'] ?? null ) ? $options['colors'] : array();
		$heights        = is_array( $options['heights'] ?? null ) ? $options['heights'] : array();
		$default_color  = (string) ( $options['default_color'] ?? ( $colors[0]['id'] ?? '' ) );
		$default_height = (string) ( $options['default_height'] ?? ( $heights[0]['id'] ?? '' ) );

		include KCP_PLUGIN_DIR . 'templates/woocommerce/partials/product-options.php';
	}

	/**
	 * Output hidden cart fields for selected options.
	 *
	 * @return void
	 */
	public function render_hidden_fields(): void {
		$preset = $this->current_preset();

		if ( null === $preset || ! $this->options_builder()->supports_cart( $preset ) ) {
			return;
		}

		$options       = $this->options_builder()->build( $preset );
		$option_groups = is_array( $options['option_groups'] ?? null ) ? $options['option_groups'] : array();
		$colors        = is_array( $options['colors'] ?? null ) ? $options['colors'] : array();
		$heights       = is_array( $options['heights'] ?? null ) ? $options['heights'] : array();
		$default_color = (string) ( $options['default_color'] ?? ( $colors[0]['id'] ?? '' ) );
		$default_height = (string) ( $options['default_height'] ?? ( $heights[0]['id'] ?? '' ) );

		foreach ( $option_groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$group_id = sanitize_key( (string) ( $group['id'] ?? '' ) );

			if ( '' === $group_id ) {
				continue;
			}

			$items        = is_array( $group['items'] ?? null ) ? $group['items'] : array();
			$default_item = sanitize_key( (string) ( $group['default_item'] ?? '' ) );

			if ( '' === $default_item && ! empty( $items ) ) {
				$default_item = sanitize_key( (string) ( $items[0]['id'] ?? '' ) );
			}

			printf(
				'<input type="hidden" name="kcp_options[%1$s]" id="kcp-selected-%1$s" value="%2$s" />',
				esc_attr( $group_id ),
				esc_attr( $default_item )
			);
		}

		printf(
			'<input type="hidden" name="kcp_color" value="%s" />',
			esc_attr( $default_color )
		);
		printf(
			'<input type="hidden" name="kcp_height" value="%s" />',
			esc_attr( $default_height )
		);
	}

	/**
	 * Route preset-backed variable products through the KCP cart handler.
	 *
	 * @param string      $handler  Add-to-cart handler.
	 * @param \WC_Product $product  Product being added.
	 * @return string
	 */
	public function add_to_cart_handler( string $handler, \WC_Product $product ): string {
		if ( 'variable' !== $handler || ! $product->is_type( 'variable' ) ) {
			return $handler;
		}

		$preset = $this->get_preset_for_product( (int) $product->get_id() );

		if ( null === $preset || ! $this->options_builder()->supports_cart( $preset ) ) {
			return $handler;
		}

		return self::ADD_TO_CART_HANDLER;
	}

	/**
	 * Add a preset-backed variable product without requiring a WooCommerce variation ID.
	 *
	 * @param string $url Redirect URL passed by WooCommerce.
	 * @return void
	 */
	public function handle_preset_add_to_cart( $url = '' ): void {
		if ( ! WC()->cart ) {
			return;
		}

		$product_id = isset( $_REQUEST['add-to-cart'] ) ? absint( wp_unslash( (string) $_REQUEST['add-to-cart'] ) ) : 0;

		if ( $product_id <= 0 ) {
			return;
		}

		$preset = $this->get_preset_for_product( $product_id );

		if ( null === $preset || ! $this->options_builder()->supports_cart( $preset ) ) {
			return;
		}

		$product  = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product_Variable ) {
			return;
		}

		$quantity = empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( (string) $_REQUEST['quantity'] ) );
		$passed   = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, 0, array() );

		if ( ! $passed ) {
			return;
		}

		list( $variation_id, $variation ) = $this->resolve_preset_variation( $product );

		if ( $variation_id <= 0 ) {
			wc_add_notice(
				__( 'This product could not be added to the cart. Please try again.', 'kitchen-configurator-pro' ),
				'error'
			);
			return;
		}

		$passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation );

		if ( ! $passed ) {
			return;
		}

		$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

		if ( false === $added ) {
			return;
		}

		wc_add_to_cart_message( array( $product_id => $quantity ), true );

		/** @since 10.6.0 */
		do_action( 'internal_woocommerce_cart_item_added_from_user_request', $product_id, $quantity );

		$url = apply_filters( 'woocommerce_add_to_cart_redirect', $url, $product instanceof \WC_Product ? $product : null );

		if ( $url ) {
			wp_safe_redirect( $url );
			exit;
		}

		if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}
	}

	/**
	 * Resolve a WooCommerce variation ID for preset cart adds.
	 *
	 * @param \WC_Product_Variable $product Variable product.
	 * @return array{0: int, 1: array<string, string>}
	 */
	private function resolve_preset_variation( \WC_Product_Variable $product ): array {
		$posted_attributes = $this->collect_posted_variation_attributes( $product );

		if ( ! empty( $posted_attributes ) ) {
			/** @var \WC_Product_Data_Store_CPT $data_store */
			$data_store    = \WC_Data_Store::load( 'product' );
			$variation_id  = (int) $data_store->find_matching_product_variation( $product, $posted_attributes );

			if ( $variation_id > 0 ) {
				return array( $variation_id, $posted_attributes );
			}
		}

		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( (int) $child_id );

			if ( $variation instanceof \WC_Product_Variation && $variation->is_purchasable() ) {
				return array(
					(int) $child_id,
					wc_get_product_variation_attributes( (int) $child_id ),
				);
			}
		}

		$placeholder_id = (int) get_post_meta( $product->get_id(), self::PRESET_VARIATION_META, true );

		if ( $placeholder_id > 0 && 'product_variation' === get_post_type( $placeholder_id ) ) {
			return array(
				$placeholder_id,
				wc_get_product_variation_attributes( $placeholder_id ),
			);
		}

		$created_id = $this->create_placeholder_variation( $product );

		if ( $created_id <= 0 ) {
			return array( 0, array() );
		}

		update_post_meta( $product->get_id(), self::PRESET_VARIATION_META, $created_id );

		return array(
			$created_id,
			wc_get_product_variation_attributes( $created_id ),
		);
	}

	/**
	 * Collect posted WooCommerce variation attribute values from the add-to-cart request.
	 *
	 * @param \WC_Product_Variable $product Variable product.
	 * @return array<string, string>
	 */
	private function collect_posted_variation_attributes( \WC_Product_Variable $product ): array {
		$posted = array();

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_variation() ) {
				continue;
			}

			$attribute_key = 'attribute_' . sanitize_title( $attribute->get_name() );

			if ( ! isset( $_REQUEST[ $attribute_key ] ) ) {
				continue;
			}

			$value = wp_unslash( (string) $_REQUEST[ $attribute_key ] );

			if ( '' === $value && '0' !== $value ) {
				continue;
			}

			if ( $attribute->is_taxonomy() ) {
				$value = sanitize_title( $value );
			} else {
				$value = html_entity_decode( wc_clean( $value ), ENT_QUOTES, get_bloginfo( 'charset' ) );
			}

			$posted[ $attribute_key ] = $value;
		}

		return $posted;
	}

	/**
	 * Create a hidden placeholder variation so preset products can be added to cart.
	 *
	 * @param \WC_Product_Variable $product Variable product.
	 * @return int Variation ID or 0 on failure.
	 */
	private function create_placeholder_variation( \WC_Product_Variable $product ): int {
		$variation_attributes = array();

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_variation() ) {
				continue;
			}

			$attribute_key = 'attribute_' . sanitize_title( $attribute->get_name() );
			$value         = '';

			if ( $attribute->is_taxonomy() ) {
				$options = $attribute->get_options();

				if ( ! empty( $options ) ) {
					$term = get_term( (int) $options[0], $attribute->get_name() );
					$value = $term instanceof \WP_Term ? $term->slug : '';
				}
			} else {
				$options = $attribute->get_options();
				$value   = (string) ( $options[0] ?? '' );
			}

			if ( '' === $value && '0' !== $value ) {
				continue;
			}

			$variation_attributes[ $attribute_key ] = $value;
		}

		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_status( 'publish' );
		$variation->set_catalog_visibility( 'hidden' );
		$variation->set_regular_price( 0 );
		$variation->set_virtual( false );
		$variation->set_manage_stock( false );
		$variation->set_stock_status( 'instock' );
		$variation->set_attributes( $variation_attributes );

		return (int) $variation->save();
	}

	/**
	 * Persist selected options on the cart line item.
	 *
	 * @param array<string, mixed> $cart_item_data Cart item data.
	 * @param int                  $product_id     Product ID.
	 * @return array<string, mixed>
	 */
	public function add_cart_item_data( array $cart_item_data, int $product_id ): array {
		$preset = $this->get_preset_for_product( $product_id );

		if ( null === $preset || ! $this->options_builder()->supports_cart( $preset ) ) {
			return $cart_item_data;
		}

		$options = $this->options_builder()->build( $preset );
		$colors  = is_array( $options['colors'] ?? null ) ? $options['colors'] : array();
		$heights = is_array( $options['heights'] ?? null ) ? $options['heights'] : array();
		$groups  = is_array( $options['option_groups'] ?? null ) ? $options['option_groups'] : array();
		$posted  = isset( $_POST['kcp_options'] ) && is_array( $_POST['kcp_options'] )
			? wp_unslash( $_POST['kcp_options'] )
			: array();

		$selected_options = array();

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$group_id = sanitize_key( (string) ( $group['id'] ?? '' ) );
			$items    = is_array( $group['items'] ?? null ) ? $group['items'] : array();

			if ( '' === $group_id || empty( $items ) ) {
				continue;
			}

			$posted_value = isset( $posted[ $group_id ] ) ? sanitize_key( (string) $posted[ $group_id ] ) : '';
			$default_id   = sanitize_key( (string) ( $group['default_item'] ?? '' ) );
			$selected_id  = $this->resolve_option_id( $posted_value, $items, $default_id );

			if ( '' === $selected_id ) {
				continue;
			}

			$selected_options[ $group_id ] = array(
				'id'    => $selected_id,
				'label' => $this->option_label( $items, $selected_id ),
			);
		}

		$color  = isset( $_POST['kcp_color'] ) ? sanitize_key( wp_unslash( (string) $_POST['kcp_color'] ) ) : '';
		$height = isset( $_POST['kcp_height'] ) ? sanitize_key( wp_unslash( (string) $_POST['kcp_height'] ) ) : '';

		if ( isset( $selected_options['color'] ) ) {
			$color = $selected_options['color']['id'];
		}

		if ( isset( $selected_options['height'] ) ) {
			$height = $selected_options['height']['id'];
		}

		$color  = $this->resolve_option_id( $color, $colors, (string) ( $options['default_color'] ?? '' ) );
		$height = $this->resolve_option_id( $height, $heights, (string) ( $options['default_height'] ?? '' ) );

		if ( '' !== $color ) {
			$cart_item_data['kcp_color'] = $color;
			$cart_item_data['kcp_color_label'] = $this->option_label( $colors, $color );
		}

		if ( '' !== $height ) {
			$cart_item_data['kcp_height'] = $height;
			$cart_item_data['kcp_height_label'] = $this->option_label( $heights, $height );
		}

		if ( ! empty( $selected_options ) ) {
			$cart_item_data['kcp_selected_options'] = $selected_options;
		}

		if ( $this->breakdown_builder()->has_parts( $preset ) ) {
			$resolved    = $this->breakdown_builder()->resolve( $options, $color, $height );
			$group_title = (string) ( $options['group_title'] ?? '' );

			if ( '' === $group_title ) {
				$product = wc_get_product( $product_id );
				$group_title = $product instanceof \WC_Product ? $product->get_name() : $preset->name;
			}

			$cart_item_data[ ProductBreakdownBuilder::META_PARTS ]       = $resolved['parts'];
			$cart_item_data[ ProductBreakdownBuilder::META_TOTAL ]       = $resolved['total'];
			$cart_item_data[ ProductBreakdownBuilder::META_GROUP_TITLE ] = $group_title;
			$cart_item_data['kcp_breakdown_surcharges']                  = $resolved['surcharges'];
			$cart_item_data['kcp_unique_key']                            = wp_generate_password( 12, false, false );
		}

		return $cart_item_data;
	}

	/**
	 * Get preset for the current single product page.
	 *
	 * @return ProductPreset|null
	 */
	private function current_preset(): ?ProductPreset {
		if ( ! is_product() ) {
			return null;
		}

		return $this->get_preset_for_product( get_the_ID() );
	}

	/**
	 * Get preset for a WooCommerce product ID.
	 *
	 * @param int $product_id Product ID.
	 * @return ProductPreset|null
	 */
	private function get_preset_for_product( int $product_id ): ?ProductPreset {
		if ( $product_id <= 0 ) {
			return null;
		}

		/** @var ProductPresetRepository $presets */
		$presets = $this->container->get( ProductPresetRepository::class );
		$preset  = $presets->find_by_wc_product_id( $product_id );

		if ( null === $preset || ! $preset->is_active ) {
			return null;
		}

		return $preset;
	}

	/**
	 * Resolve a selected option ID, falling back to preset defaults.
	 *
	 * @param string            $selected   Posted option ID.
	 * @param array<int, mixed> $options    Option rows.
	 * @param string            $default_id Default option ID.
	 * @return string
	 */
	private function resolve_option_id( string $selected, array $options, string $default_id ): string {
		if ( '' !== $selected ) {
			return $selected;
		}

		if ( '' !== $default_id ) {
			return sanitize_key( $default_id );
		}

		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $option['id'] ?? '' ) );

			if ( '' !== $id ) {
				return $id;
			}
		}

		return '';
	}

	/**
	 * Resolve an option label from storefront option rows.
	 *
	 * @param array<int, mixed> $options Option rows.
	 * @param string            $id      Selected option ID.
	 * @return string
	 */
	private function option_label( array $options, string $id ): string {
		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			if ( sanitize_key( (string) ( $option['id'] ?? '' ) ) === $id ) {
				return (string) ( $option['label'] ?? $id );
			}
		}

		return $id;
	}

	/**
	 * @return ProductBreakdownBuilder
	 */
	private function breakdown_builder(): ProductBreakdownBuilder {
		/** @var ProductBreakdownBuilder $builder */
		$builder = $this->container->get( ProductBreakdownBuilder::class );

		return $builder;
	}

	/**
	 * @return ProductStorefrontOptionsBuilder
	 */
	private function options_builder(): ProductStorefrontOptionsBuilder {
		/** @var ProductStorefrontOptionsBuilder $builder */
		$builder = $this->container->get( ProductStorefrontOptionsBuilder::class );

		return $builder;
	}
}
