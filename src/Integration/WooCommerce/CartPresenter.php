<?php
/**
 * WooCommerce cart page presentation.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Repositories\ProductPresetRepository;
use KitchenConfiguratorPro\Services\ProductBreakdownBuilder;
use KitchenConfiguratorPro\Services\ProductStorefrontOptionsBuilder;
use KitchenConfiguratorPro\Services\WooVariationOptionsBuilder;

/**
 * Renders the KKF-style cart page and redirects after add to cart.
 */
final class CartPresenter {

	/**
	 * WooCommerce session key for the optional design check.
	 */
	private const SESSION_DESIGN_CHECK = 'kcp_design_check';

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register WordPress and WooCommerce hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 10, 3 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_fields' ) );
		add_filter( 'woocommerce_checkout_get_value', array( $this, 'checkout_default_country' ), 10, 2 );
		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'checkout_posted_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'redirect_after_add_to_cart' ) );
		add_action( 'template_redirect', array( $this, 'handle_copy_request' ) );
		add_action( 'template_redirect', array( $this, 'handle_part_request' ) );
		add_action( 'template_redirect', array( $this, 'handle_empty_group_request' ) );
		add_action( 'template_redirect', array( $this, 'handle_empty_cart_request' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_design_check_fee' ) );
		add_action( 'wp_ajax_kcp_design_check', array( $this, 'ajax_design_check' ) );
		add_action( 'wp_ajax_nopriv_kcp_design_check', array( $this, 'ajax_design_check' ) );
		add_action( 'wp_ajax_kcp_afspraak', array( $this, 'ajax_afspraak' ) );
		add_action( 'wp_ajax_nopriv_kcp_afspraak', array( $this, 'ajax_afspraak' ) );
		add_action( 'wp_footer', array( $this, 'print_design_check_config' ), 21 );

		remove_action( 'woocommerce_before_cart', 'woocommerce_output_all_notices', 10 );
		add_action( 'woocommerce_before_cart', 'woocommerce_output_all_notices', 5 );
	}

	/**
	 * Load plugin cart template overrides.
	 *
	 * @param string $template      Default template path.
	 * @param string $template_name Template name.
	 * @param string $template_path Template path.
	 * @return string
	 */
	public function locate_template( string $template, string $template_name, string $template_path ): string {
		unset( $template_path );

		$allowed = array();

		if ( is_cart() ) {
			$allowed = array(
				'cart/cart.php',
				'cart/cart-empty.php',
			);
		}

		if ( is_checkout() && ! is_order_received_page() ) {
			$allowed = array(
				'checkout/form-checkout.php',
				'checkout/payment.php',
				'checkout/review-order.php',
			);
		}

		if ( is_order_received_page() ) {
			$allowed = array(
				'checkout/thankyou.php',
				'checkout/order-received.php',
			);
		}

		if ( ! in_array( $template_name, $allowed, true ) ) {
			return $template;
		}

		$override = KCP_PLUGIN_DIR . 'templates/woocommerce/' . $template_name;

		return file_exists( $override ) ? $override : $template;
	}

	/**
	 * Add cart page body classes.
	 *
	 * @param array<int, string> $classes Body classes.
	 * @return array<int, string>
	 */
	public function body_class( array $classes ): array {
		if ( is_cart() ) {
			$classes[] = 'kcp-cart-active';
			$classes[] = 'kcp-shop-active';
		}

		if ( is_checkout() && ! is_order_received_page() ) {
			$classes[] = 'kcp-checkout-active';
			$classes[] = 'kcp-shop-active';
		}

		if ( is_order_received_page() ) {
			$classes[] = 'kcp-thankyou-active';
			$classes[] = 'kcp-shop-active';
		}

		return $classes;
	}

	/**
	 * Remove checkout fields that are not part of the reference design.
	 *
	 * @param array<string, array<string, array<string, mixed>>> $fields Checkout fields.
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public function checkout_fields( array $fields ): array {
		$remove = array(
			'billing_company',
			'billing_state',
			'shipping_company',
			'shipping_state',
		);

		foreach ( $remove as $key ) {
			if ( str_starts_with( $key, 'billing_' ) ) {
				unset( $fields['billing'][ $key ] );
				continue;
			}

			unset( $fields['shipping'][ $key ] );
		}

		$default_country = $this->get_checkout_default_country();

		foreach ( array( 'billing_country', 'shipping_country' ) as $country_key ) {
			$section = str_starts_with( $country_key, 'billing_' ) ? 'billing' : 'shipping';

			if ( ! isset( $fields[ $section ][ $country_key ] ) ) {
				continue;
			}

			$fields[ $section ][ $country_key ]['type']        = 'hidden';
			$fields[ $section ][ $country_key ]['label']       = false;
			$fields[ $section ][ $country_key ]['required']    = true;
			$fields[ $section ][ $country_key ]['default']       = $default_country;
			$fields[ $section ][ $country_key ]['class']         = array( 'form-row-wide', 'kcp-checkout-hidden' );
			$fields[ $section ][ $country_key ]['input_class']   = array( 'kcp-checkout-hidden__input' );
		}

		return $fields;
	}

	/**
	 * Default hidden country values on checkout.
	 *
	 * @param mixed  $value Field value.
	 * @param string $input Field key.
	 * @return mixed
	 */
	public function checkout_default_country( $value, string $input ) {
		if ( null !== $value ) {
			return $value;
		}

		if ( in_array( $input, array( 'billing_country', 'shipping_country' ), true ) ) {
			return $this->get_checkout_default_country();
		}

		return $value;
	}

	/**
	 * Ensure posted checkout data always includes a valid country.
	 *
	 * @param array<string, mixed> $data Posted checkout data.
	 * @return array<string, mixed>
	 */
	public function checkout_posted_data( array $data ): array {
		$default_country = $this->get_checkout_default_country();

		if ( empty( $data['billing_country'] ) ) {
			$data['billing_country'] = $default_country;
		}

		if ( empty( $data['shipping_country'] ) ) {
			$data['shipping_country'] = $data['billing_country'];
		}

		return $data;
	}

	/**
	 * Resolve the default checkout country for hidden address fields.
	 *
	 * @return string
	 */
	private function get_checkout_default_country(): string {
		$country = WC()->countries->get_base_country();

		if ( array_key_exists( $country, WC()->countries->get_allowed_countries() ) ) {
			return $country;
		}

		$allowed = WC()->countries->get_allowed_countries();

		if ( ! empty( $allowed ) ) {
			return (string) array_key_first( $allowed );
		}

		return 'NL';
	}

	/**
	 * Enqueue cart page assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		if ( is_cart() ) {
			$this->enqueue_font_awesome();
		}

		wp_enqueue_style(
			'kcp-shop',
			KCP_PLUGIN_URL . 'assets/frontend/css/shop.css',
			array(),
			KCP_VERSION
		);

		if ( is_order_received_page() ) {
			wp_enqueue_style(
				'kcp-thankyou',
				KCP_PLUGIN_URL . 'assets/frontend/css/thankyou.css',
				array( 'kcp-shop' ),
				KCP_VERSION
			);

			return;
		}

		if ( is_cart() ) {
			wp_enqueue_style(
				'kcp-cart',
				KCP_PLUGIN_URL . 'assets/frontend/css/cart.css',
				array( 'kcp-shop' ),
				KCP_VERSION
			);

			wp_enqueue_script(
				'kcp-cart',
				KCP_PLUGIN_URL . 'assets/frontend/js/cart.js',
				array(),
				KCP_VERSION,
				true
			);

			wp_localize_script(
				'kcp-cart',
				'kcpCart',
				array(
					'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
					'nonce'            => wp_create_nonce( 'kcp_cart_design_check' ),
					'baseTotal'        => $this->get_cart_base_total(),
					'designCheckPrice' => $this->get_design_check_price(),
					'selected'         => $this->is_design_check_selected() ? 'yes' : 'no',
				)
			);
		}

		if ( is_checkout() && ! is_order_received_page() ) {
			wp_enqueue_style(
				'kcp-checkout',
				KCP_PLUGIN_URL . 'assets/frontend/css/checkout.css',
				array( 'kcp-shop' ),
				KCP_VERSION
			);

			wp_enqueue_script(
				'kcp-checkout',
				KCP_PLUGIN_URL . 'assets/frontend/js/checkout.js',
				array(),
				KCP_VERSION,
				true
			);
		}
	}

	/**
	 * Ensure Font Awesome 4 is available for cart icons.
	 *
	 * @return void
	 */
	private function enqueue_font_awesome(): void {
		$handles = array(
			'font-awesome',
			'fontawesome',
			'fontawesome-free',
			'fa',
		);

		foreach ( $handles as $handle ) {
			if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'registered' ) ) {
				if ( ! wp_style_is( $handle, 'enqueued' ) ) {
					wp_enqueue_style( $handle );
				}

				return;
			}
		}

		wp_enqueue_style(
			'kcp-font-awesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
			array(),
			'4.7.0'
		);
	}

	/**
	 * Redirect KCP storefront products to the cart after add to cart.
	 *
	 * @param string $url Default redirect URL.
	 * @return string
	 */
	public function redirect_after_add_to_cart( string $url ): string {
		$product_id = absint( wp_unslash( (string) ( $_REQUEST['add-to-cart'] ?? $_REQUEST['product_id'] ?? 0 ) ) );

		if ( $product_id > 0 && $this->is_kcp_product( $product_id ) ) {
			return wc_get_cart_url();
		}

		return $url;
	}

	/**
	 * Duplicate a cart line when the copy action is requested.
	 *
	 * @return void
	 */
	public function handle_copy_request(): void {
		if ( ! is_cart() || ! isset( $_GET['kcp_copy'], $_GET['key'] ) ) {
			return;
		}

		$cart_key = wc_clean( wp_unslash( (string) $_GET['key'] ) );

		if ( '' === $cart_key || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$cart_item = WC()->cart->get_cart_item( $cart_key );

		if ( ! is_array( $cart_item ) ) {
			wc_add_notice( __( 'Dit artikel kon niet worden gekopieerd.', 'kitchen-configurator-pro' ), 'error' );
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}

		$product_id   = (int) ( $cart_item['product_id'] ?? 0 );
		$quantity     = (int) ( $cart_item['quantity'] ?? 1 );
		$variation_id = (int) ( $cart_item['variation_id'] ?? 0 );
		$variation    = is_array( $cart_item['variation'] ?? null ) ? $cart_item['variation'] : array();
		$cart_data    = array_diff_key(
			$cart_item,
			array_flip(
				array(
					'key',
					'product_id',
					'variation_id',
					'variation',
					'quantity',
					'data',
					'data_hash',
					'line_tax_data',
					'line_subtotal',
					'line_subtotal_tax',
					'line_total',
					'line_tax',
				)
			)
		);

		if ( CartHandler::is_kcp_cart_item( $cart_item ) ) {
			$cart_data[ CartHandler::META_UNIQUE ] = (string) ( $cart_item[ CartHandler::META_UUID ] ?? '' )
				. ':' . wp_generate_password( 8, false, false );
		}

		$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_data );

		if ( $added ) {
			wc_add_notice( __( 'Artikel gekopieerd naar je winkelwagen.', 'kitchen-configurator-pro' ) );
		} else {
			wc_add_notice( __( 'Dit artikel kon niet worden gekopieerd.', 'kitchen-configurator-pro' ), 'error' );
		}

		wp_safe_redirect( remove_query_arg( array( 'kcp_copy', 'key' ), wc_get_cart_url() ) );
		exit;
	}

	/**
	 * Duplicate or remove an individual breakdown part.
	 *
	 * @return void
	 */
	public function handle_part_request(): void {
		if ( ! is_cart() || ! isset( $_GET['kcp_part_action'], $_GET['key'], $_GET['part_key'] ) ) {
			return;
		}

		$action   = sanitize_key( wp_unslash( (string) $_GET['kcp_part_action'] ) );
		$cart_key = wc_clean( wp_unslash( (string) $_GET['key'] ) );
		$part_key = sanitize_key( wp_unslash( (string) $_GET['part_key'] ) );
		$part_pos = isset( $_GET['part_pos'] ) ? max( -1, (int) wp_unslash( (string) $_GET['part_pos'] ) ) : -1;

		if ( '' === $cart_key || '' === $part_key || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$cart_item = WC()->cart->get_cart_item( $cart_key );

		if ( ! is_array( $cart_item ) || ! CartHandler::is_product_breakdown_item( $cart_item ) ) {
			return;
		}

		$parts      = is_array( $cart_item[ ProductBreakdownBuilder::META_PARTS ] ?? null )
			? $cart_item[ ProductBreakdownBuilder::META_PARTS ]
			: array();
		$surcharges = is_array( $cart_item['kcp_breakdown_surcharges'] ?? null )
			? $cart_item['kcp_breakdown_surcharges']
			: array();
		$builder    = $this->breakdown_builder();

		if ( 'duplicate' === $action ) {
			$parts = $builder->duplicate_part( $parts, $part_key, $part_pos );
			wc_add_notice( __( 'Artikel gedupliceerd.', 'kitchen-configurator-pro' ) );
		} elseif ( 'remove' === $action ) {
			if ( count( $parts ) <= 1 ) {
				WC()->cart->remove_cart_item( $cart_key );
				wc_add_notice( __( 'Groep verwijderd uit je winkelwagen.', 'kitchen-configurator-pro' ) );
				wp_safe_redirect( remove_query_arg( array( 'kcp_part_action', 'key', 'part_key' ), wc_get_cart_url() ) );
				exit;
			}

			$parts = $builder->remove_part( $parts, $part_key, $part_pos );
			wc_add_notice( __( 'Artikel verwijderd.', 'kitchen-configurator-pro' ) );
		} else {
			return;
		}

		WC()->cart->cart_contents[ $cart_key ][ ProductBreakdownBuilder::META_PARTS ] = $parts;
		WC()->cart->cart_contents[ $cart_key ][ ProductBreakdownBuilder::META_TOTAL ] = $builder->calculate_total( $parts, $surcharges );
		WC()->cart->set_session();

		wp_safe_redirect( remove_query_arg( array( 'kcp_part_action', 'key', 'part_key' ), wc_get_cart_url() ) );
		exit;
	}

	/**
	 * Build grouped cart display data for the custom cart template.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_display_groups(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array();
		}

		$groups = array();

		foreach ( WC()->cart->get_cart() as $cart_key => $cart_item ) {
			if ( CartHandler::is_product_breakdown_item( $cart_item ) ) {
				$groups[] = $this->enrich_group( $this->group_from_product_breakdown( $cart_key, $cart_item ), $cart_item );
				continue;
			}

			if ( CartHandler::is_kcp_cart_item( $cart_item ) ) {
				$groups[] = $this->enrich_group( $this->group_from_configuration( $cart_key, $cart_item ), $cart_item );
				continue;
			}

			$groups[] = $this->enrich_group( $this->group_from_simple_product( $cart_key, $cart_item ), $cart_item );
		}

		return $groups;
	}

	/**
	 * Build display rows for the custom cart template.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_display_rows(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array();
		}

		$rows = array();

		foreach ( WC()->cart->get_cart() as $cart_key => $cart_item ) {
			if ( CartHandler::is_kcp_cart_item( $cart_item ) ) {
				$config_rows = $this->rows_from_configuration( $cart_key, $cart_item );
				foreach ( $config_rows as $index => $row ) {
					$row['show_actions'] = 0 === $index;
					$rows[]              = $row;
				}
				continue;
			}

			$row                 = $this->row_from_product( $cart_key, $cart_item );
			$row['show_actions'] = true;
			$rows[]              = $row;
		}

		return $rows;
	}

	/**
	 * Build a grouped cart view from a storefront product breakdown.
	 *
	 * @param string               $cart_key  Cart item key.
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return array<string, mixed>
	 */
	private function group_from_product_breakdown( string $cart_key, array $cart_item ): array {
		$product     = $cart_item['data'] ?? null;
		$parts       = is_array( $cart_item[ ProductBreakdownBuilder::META_PARTS ] ?? null )
			? $cart_item[ ProductBreakdownBuilder::META_PARTS ]
			: array();
		$surcharges  = is_array( $cart_item['kcp_breakdown_surcharges'] ?? null )
			? $cart_item['kcp_breakdown_surcharges']
			: array();
		$group_title = (string) ( $cart_item[ ProductBreakdownBuilder::META_GROUP_TITLE ] ?? '' );

		if ( '' === $group_title && $product instanceof \WC_Product ) {
			$group_title = $product->get_name();
		}

		$edit_url = (string) ( $cart_item['kcp_edit_url'] ?? '' );
		if ( '' === $edit_url && $product instanceof \WC_Product ) {
			if ( $product->is_type( 'variation' ) ) {
				$parent = wc_get_product( $product->get_parent_id() );
				$edit_url = $parent instanceof \WC_Product ? (string) $parent->get_permalink() : '';
			} else {
				$edit_url = (string) $product->get_permalink();
			}
		}

		return array(
			'type'        => 'breakdown',
			'cart_key'    => $cart_key,
			'group_title' => $group_title,
			'edit_url'    => $edit_url,
			'remove_url'  => wc_get_cart_remove_url( $cart_key ),
			'parts'       => $this->hydrate_part_urls( $cart_key, $parts, $edit_url ),
			'surcharges'  => $surcharges,
			'group_total' => (float) ( $cart_item[ ProductBreakdownBuilder::META_TOTAL ] ?? 0 ),
		);
	}

	/**
	 * Build a grouped cart view from a saved configuration.
	 *
	 * @param string               $cart_key  Cart item key.
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return array<string, mixed>
	 */
	private function group_from_configuration( string $cart_key, array $cart_item ): array {
		$group_title = (string) ( $cart_item[ CartHandler::META_TITLE ] ?? '' );
		$pricing     = json_decode( (string) ( $cart_item[ CartHandler::META_PRICING ] ?? '{}' ), true );
		$lines       = is_array( $pricing['line_items'] ?? null ) ? $pricing['line_items'] : array();
		$parts       = array();

		foreach ( $lines as $index => $line ) {
			if ( ! is_array( $line ) ) {
				continue;
			}

			$label = (string) ( $line['label'] ?? '' );

			if ( '' === $label ) {
				continue;
			}

			$parts[] = array(
				'key'         => 'line-' . $index,
				'label'       => $label,
				'description' => '',
				'image_url'   => '',
				'price'       => (float) ( $line['subtotal'] ?? 0 ),
				'editable'    => false,
			);
		}

		if ( empty( $parts ) ) {
			$row   = $this->row_from_product( $cart_key, $cart_item );
			$parts = array(
				array(
					'key'         => 'configuration',
					'label'       => (string) ( $row['label'] ?? '' ),
					'description' => (string) ( $row['description'] ?? '' ),
					'image_url'   => (string) ( $row['image_url'] ?? '' ),
					'price'       => (float) ( $row['price'] ?? 0 ),
					'editable'    => false,
				),
			);
		}

		if ( '' === $group_title ) {
			$group_title = __( 'keukenconfiguratie', 'kitchen-configurator-pro' );
		}

		return array(
			'type'        => 'configuration',
			'cart_key'    => $cart_key,
			'group_title' => $group_title,
			'edit_url'    => '',
			'remove_url'  => wc_get_cart_remove_url( $cart_key ),
			'parts'       => $parts,
			'surcharges'  => array(),
			'group_total' => (float) ( $cart_item[ CartHandler::META_TOTAL ] ?? 0 ),
		);
	}

	/**
	 * Build a simple single-line cart group.
	 *
	 * @param string               $cart_key  Cart item key.
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return array<string, mixed>
	 */
	private function group_from_simple_product( string $cart_key, array $cart_item ): array {
		$row      = $this->row_from_product( $cart_key, $cart_item );
		$product  = $cart_item['data'] ?? null;
		$edit_url = $product instanceof \WC_Product ? $product->get_permalink() : '';

		return array(
			'type'        => 'simple',
			'cart_key'    => $cart_key,
			'group_title' => (string) ( $row['label'] ?? '' ),
			'edit_url'    => $edit_url,
			'remove_url'  => wc_get_cart_remove_url( $cart_key ),
			'parts'       => array(
				array(
					'key'           => $cart_key,
					'label'         => (string) ( $row['label'] ?? '' ),
					'description'   => (string) ( $row['description'] ?? '' ),
					'image_url'     => (string) ( $row['image_url'] ?? '' ),
					'price'         => (float) ( $row['price'] ?? 0 ),
					'editable'      => false,
					'duplicate_url' => add_query_arg(
						array(
							'kcp_copy' => '1',
							'key'      => $cart_key,
						),
						wc_get_cart_url()
					),
					'remove_url'    => wc_get_cart_remove_url( $cart_key ),
				),
			),
			'surcharges'  => array(),
			'group_total' => (float) ( $row['price'] ?? 0 ),
		);
	}

	/**
	 * Add action URLs to breakdown parts.
	 *
	 * @param string                           $cart_key Cart item key.
	 * @param array<int, array<string, mixed>> $parts    Part rows.
	 * @param string                           $edit_url Product edit URL.
	 * @return array<int, array<string, mixed>>
	 */
	private function hydrate_part_urls( string $cart_key, array $parts, string $edit_url ): array {
		$cart_url = wc_get_cart_url();

		return array_map(
			static function ( array $part, int $index ) use ( $cart_key, $edit_url, $cart_url ): array {
				$part_key = (string) ( $part['key'] ?? '' );

				$part['duplicate_url'] = add_query_arg(
					array(
						'kcp_part_action' => 'duplicate',
						'key'             => $cart_key,
						'part_key'        => $part_key,
						'part_pos'        => $index,
					),
					$cart_url
				);
				$part['remove_url']    = add_query_arg(
					array(
						'kcp_part_action' => 'remove',
						'key'             => $cart_key,
						'part_key'        => $part_key,
						'part_pos'        => $index,
					),
					$cart_url
				);
				$part['edit_url']      = ! empty( $part['editable'] ) && '' !== $edit_url
					? add_query_arg(
						array(
							'kcp_edit' => '1',
							'key'      => $cart_key,
							'part_key' => $part_key,
							'part_pos' => $index,
						),
						$edit_url
					)
					: '';

				return $part;
			},
			$parts,
			array_keys( $parts )
		);
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
	 * Get the primary preview image URL for the cart drawings section.
	 *
	 * @return string
	 */
	public function get_preview_image_url(): string {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return '';
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'] ?? null;

			if ( $product instanceof \WC_Product ) {
				$image_id = $product->get_image_id();

				if ( $image_id > 0 ) {
					$url = wp_get_attachment_image_url( $image_id, 'large' );

					if ( is_string( $url ) && '' !== $url ) {
						return $url;
					}
				}
			}
		}

		return '';
	}

	/**
	 * Get total cart item count for the header badge.
	 *
	 * @return int
	 */
	public function get_item_count(): int {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return 0;
		}

		return (int) WC()->cart->get_cart_contents_count();
	}

	/**
	 * Get formatted cart total in Dutch storefront style.
	 *
	 * @return string
	 */
	public function get_formatted_total(): string {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return ShopPresenter::format_dutch_price( 0 );
		}

		WC()->cart->calculate_totals();

		return ShopPresenter::format_dutch_price( (float) WC()->cart->get_total( 'edit' ) );
	}

	/**
	 * Cart total before the optional design check fee.
	 *
	 * @return float
	 */
	public function get_cart_base_total(): float {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return 0.0;
		}

		$total = 0.0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( CartHandler::is_product_breakdown_item( $cart_item ) ) {
				$total += (float) ( $cart_item[ ProductBreakdownBuilder::META_TOTAL ] ?? 0 );
				continue;
			}

			if ( CartHandler::is_kcp_cart_item( $cart_item ) ) {
				$total += (float) ( $cart_item[ CartHandler::META_TOTAL ] ?? 0 );
				continue;
			}

			$product = $cart_item['data'] ?? null;

			if ( $product instanceof \WC_Product ) {
				$total += (float) $product->get_price() * (float) ( $cart_item['quantity'] ?? 1 );
			}
		}

		return max( 0.0, $total );
	}

	/**
	 * Output cart totals for the design check script after footer scripts are registered.
	 *
	 * @return void
	 */
	public function print_design_check_config(): void {
		if ( ! is_cart() || ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$config = array(
			'baseTotal'        => $this->get_cart_base_total(),
			'designCheckPrice' => $this->get_design_check_price(),
			'selected'         => $this->is_design_check_selected() ? 'yes' : 'no',
		);

		printf(
			'<script>window.kcpCart=Object.assign(window.kcpCart||{},%s);if(window.kcpInitDesignCheck){window.kcpInitDesignCheck();}</script>' . "\n",
			wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '{}'
		);
	}

	/**
	 * View data for the optional design check block on the cart page.
	 *
	 * @return array{price: float, price_label: string, selected: string}
	 */
	public function get_design_check_view(): array {
		$price = $this->get_design_check_price();

		return array(
			'price'       => $price,
			'price_label' => '+ ' . ShopPresenter::format_dutch_price( $price ),
			'selected'    => $this->is_design_check_selected() ? 'yes' : 'no',
		);
	}

	/**
	 * Add the design check fee when the customer opted in.
	 *
	 * @param \WC_Cart $cart WooCommerce cart.
	 * @return void
	 */
	public function add_design_check_fee( \WC_Cart $cart ): void {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( ! $this->is_design_check_selected() ) {
			return;
		}

		$price = $this->get_design_check_price();

		if ( $price <= 0 ) {
			return;
		}

		$cart->add_fee(
			__( 'Ontwerp controleren', 'kitchen-configurator-pro' ),
			$price,
			false
		);
	}

	/**
	 * Persist the design check choice and return the updated cart total.
	 *
	 * @return void
	 */
	public function ajax_design_check(): void {
		check_ajax_referer( 'kcp_cart_design_check', 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session ) {
			wp_send_json_error(
				array(
					'message' => __( 'Winkelwagen niet beschikbaar.', 'kitchen-configurator-pro' ),
				),
				400
			);
		}

		$value = sanitize_key( wp_unslash( (string) ( $_POST['value'] ?? 'no' ) ) );

		if ( ! in_array( $value, array( 'yes', 'no' ), true ) ) {
			$value = 'no';
		}

		WC()->session->set( self::SESSION_DESIGN_CHECK, $value );
		WC()->cart->calculate_totals();

		wp_send_json_success(
			array(
				'total'      => $this->get_formatted_total(),
				'base_total' => $this->get_cart_base_total(),
				'selected'   => $value,
			)
		);
	}

	/**
	 * @return bool
	 */
	private function is_design_check_selected(): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return false;
		}

		return 'yes' === WC()->session->get( self::SESSION_DESIGN_CHECK, 'no' );
	}

	/**
	 * @return float
	 */
	private function get_design_check_price(): float {
		$settings = get_option(
			'kcp_settings',
			array(
				'design_check_price' => 75,
			)
		);

		return max( 0, (float) ( $settings['design_check_price'] ?? 75 ) );
	}

	/**
	 * Get URL to empty the cart.
	 *
	 * @return string
	 */
	public function get_empty_cart_url(): string {
		return wp_nonce_url(
			add_query_arg( 'kcp_empty_cart', '1', wc_get_cart_url() ),
			'kcp_empty_cart'
		);
	}

	/**
	 * Build configuration summary lines for the cart overview card.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	public function get_configuration_summary(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array();
		}

		$lines = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item['kcp_color_label'] ) ) {
				$lines[] = array(
					'label' => __( 'Frontmateriaal', 'kitchen-configurator-pro' ),
					'value' => (string) $cart_item['kcp_color_label'],
				);
			}

			if ( ! empty( $cart_item['kcp_height_label'] ) ) {
				$lines[] = array(
					'label' => __( 'Hoogte', 'kitchen-configurator-pro' ),
					'value' => (string) $cart_item['kcp_height_label'],
				);
			}

			$product    = $cart_item['data'] ?? null;
			$product_id = $product instanceof \WC_Product ? $product->get_id() : 0;

			if ( $product_id > 0 ) {
				/** @var ProductPresetRepository $presets */
				$presets = $this->container->get( ProductPresetRepository::class );
				$preset  = $presets->find_by_wc_product_id( $product_id );

				if ( null !== $preset ) {
					$manual  = $preset->product_options();
					$summary = is_array( $manual['summary'] ?? null ) ? $manual['summary'] : array();

					foreach ( $summary as $row ) {
						if ( ! is_array( $row ) ) {
							continue;
						}

						$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );
						$value = sanitize_text_field( (string) ( $row['value'] ?? '' ) );

						if ( '' !== $label && '' !== $value ) {
							$lines[] = array(
								'label' => $label,
								'value' => $value,
							);
						}
					}
				}
			}
		}

		return $lines;
	}

	/**
	 * Collect plinth surcharge lines for the extras section.
	 *
	 * @return array<int, array{label: string, unit_label: string, subtotal: float}>
	 */
	public function get_plinth_lines(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array();
		}

		$lines = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product    = $cart_item['data'] ?? null;
			$product_id = $product instanceof \WC_Product ? $product->get_id() : 0;

			if ( $product_id <= 0 ) {
				continue;
			}

			/** @var ProductPresetRepository $presets */
			$presets = $this->container->get( ProductPresetRepository::class );
			$preset  = $presets->find_by_wc_product_id( $product_id );

			if ( null === $preset ) {
				continue;
			}

			$manual = $preset->product_options();
			$plinth = is_array( $manual['plinth_extra'] ?? null ) ? $manual['plinth_extra'] : array();

			if ( ! empty( $plinth['label'] ) ) {
				$lines[] = array(
					'label'      => sanitize_text_field( (string) $plinth['label'] ),
					'unit_label' => sanitize_text_field( (string) ( $plinth['unit_label'] ?? '' ) ),
					'subtotal'   => (float) ( $plinth['subtotal'] ?? 0 ),
				);
			}
		}

		if ( ! empty( $lines ) ) {
			return $lines;
		}

		foreach ( $this->get_display_surcharges() as $surcharge ) {
			$price = (float) ( $surcharge['price'] ?? 0 );

			if ( $price <= 0 ) {
				continue;
			}

			$lines[] = array(
				'label'      => (string) ( $surcharge['label'] ?? '' ),
				'unit_label' => '',
				'subtotal'   => $price,
			);
		}

		return $lines;
	}

	/**
	 * Delivery week options for the cart dropdown.
	 *
	 * @return array<int, array{id: string, label: string}>
	 */
	public function get_delivery_weeks(): array {
		$weeks = array();

		for ( $offset = 4; $offset <= 12; $offset++ ) {
			$timestamp = strtotime( '+' . $offset . ' weeks' );
			$week      = (int) gmdate( 'W', $timestamp );
			$year      = (int) gmdate( 'Y', $timestamp );

			$weeks[] = array(
				'id'    => $week . '-' . $year,
				'label' => sprintf(
					/* translators: 1: week number, 2: year */
					__( 'week %1$d - %2$d', 'kitchen-configurator-pro' ),
					$week,
					$year
				),
			);
		}

		return $weeks;
	}

	/**
	 * URL to reset a cart group back to its preset breakdown.
	 *
	 * @param string $cart_key Cart item key.
	 * @return string
	 */
	public function get_empty_group_url( string $cart_key ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'kcp_empty_group' => '1',
					'key'             => $cart_key,
				),
				wc_get_cart_url()
			),
			'kcp_empty_group_' . $cart_key
		);
	}

	/**
	 * Reset a breakdown group to its original preset parts.
	 *
	 * @return void
	 */
	public function handle_empty_group_request(): void {
		if ( ! is_cart() || ! isset( $_GET['kcp_empty_group'], $_GET['key'] ) ) {
			return;
		}

		$cart_key = wc_clean( wp_unslash( (string) $_GET['key'] ) );
		$nonce    = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';

		if ( '' === $cart_key || ! wp_verify_nonce( $nonce, 'kcp_empty_group_' . $cart_key ) ) {
			return;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$cart_item = WC()->cart->get_cart_item( $cart_key );

		if ( ! is_array( $cart_item ) || ! CartHandler::is_product_breakdown_item( $cart_item ) ) {
			return;
		}

		$product    = $cart_item['data'] ?? null;
		$product_id = $product instanceof \WC_Product ? $product->get_id() : 0;

		if ( $product_id <= 0 ) {
			return;
		}

		/** @var ProductPresetRepository $presets */
		$presets = $this->container->get( ProductPresetRepository::class );
		$preset  = $presets->find_by_wc_product_id( $product_id );

		if ( null === $preset ) {
			return;
		}

		/** @var ProductStorefrontOptionsBuilder $options_builder */
		$options_builder = $this->container->get( ProductStorefrontOptionsBuilder::class );
		$options         = $options_builder->build( $preset );
		$color           = (string) ( $cart_item['kcp_color'] ?? $options['default_color'] ?? '' );
		$height          = (string) ( $cart_item['kcp_height'] ?? $options['default_height'] ?? '' );
		$resolved        = $this->breakdown_builder()->resolve( $options, $color, $height );

		WC()->cart->cart_contents[ $cart_key ][ ProductBreakdownBuilder::META_PARTS ] = $resolved['parts'];
		WC()->cart->cart_contents[ $cart_key ][ ProductBreakdownBuilder::META_TOTAL ] = $resolved['total'];
		WC()->cart->cart_contents[ $cart_key ]['kcp_breakdown_surcharges']           = $resolved['surcharges'];
		WC()->cart->set_session();

		wc_add_notice( __( 'Groep is hersteld naar de standaard onderdelen.', 'kitchen-configurator-pro' ) );
		wp_safe_redirect( remove_query_arg( array( 'kcp_empty_group', 'key', '_wpnonce' ), wc_get_cart_url() ) );
		exit;
	}

	/**
	 * Collect surcharge lines from all cart groups for the extras section.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_display_surcharges(): array {
		$lines = array();

		foreach ( $this->get_display_groups() as $group ) {
			foreach ( is_array( $group['surcharges'] ?? null ) ? $group['surcharges'] : array() as $surcharge ) {
				if ( ! is_array( $surcharge ) ) {
					continue;
				}

				$price = (float) ( $surcharge['price'] ?? 0 );

				if ( $price <= 0 ) {
					continue;
				}

				$lines[] = $surcharge;
			}
		}

		return $lines;
	}

	/**
	 * Empty the cart when requested from the cart page header.
	 *
	 * @return void
	 */
	public function handle_empty_cart_request(): void {
		if ( ! is_cart() || ! isset( $_GET['kcp_empty_cart'] ) ) {
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'kcp_empty_cart' ) ) {
			return;
		}

		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->empty_cart();
			wc_add_notice( __( 'Je winkelwagen is geleegd.', 'kitchen-configurator-pro' ) );
		}

		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	/**
	 * Add hero preview metadata to a cart group.
	 *
	 * @param array<string, mixed> $group     Group data.
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return array<string, mixed>
	 */
	private function enrich_group( array $group, array $cart_item ): array {
		$product    = $cart_item['data'] ?? null;
		$product_id = $product instanceof \WC_Product ? $product->get_id() : 0;

		$group['subtitle']       = __( 'basiselement', 'kitchen-configurator-pro' );
		$group['preview_image']  = '';

		if ( $product_id > 0 ) {
			/** @var ProductPresetRepository $presets */
			$presets = $this->container->get( ProductPresetRepository::class );
			$preset  = $presets->find_by_wc_product_id( $product_id );

			if ( null !== $preset ) {
				$manual = $preset->product_options();

				if ( ! empty( $manual['subtitle'] ) ) {
					$group['subtitle'] = sanitize_text_field( (string) $manual['subtitle'] );
				}

				if ( ! empty( $manual['preview_image'] ) ) {
					$group['preview_image'] = esc_url_raw( (string) $manual['preview_image'] );
				}
			}

			if ( '' === $group['preview_image'] && $product instanceof \WC_Product ) {
				$image_id = $product->get_image_id();

				if ( $image_id > 0 ) {
					$url = wp_get_attachment_image_url( $image_id, 'large' );
					$group['preview_image'] = is_string( $url ) ? $url : '';
				}
			}
		}

		return $group;
	}

	/**
	 * Whether a WooCommerce product uses KCP storefront options.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	private function is_kcp_product( int $product_id ): bool {
		if ( $product_id <= 0 ) {
			return false;
		}

		/** @var ProductPresetRepository $presets */
		$presets = $this->container->get( ProductPresetRepository::class );
		$preset  = $presets->find_by_wc_product_id( $product_id );

		if ( null !== $preset ) {
			/** @var ProductStorefrontOptionsBuilder $builder */
			$builder = $this->container->get( ProductStorefrontOptionsBuilder::class );

			if ( $builder->supports_cart( $preset ) ) {
				return true;
			}
		}

		$product = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		/** @var WooVariationOptionsBuilder $variation_builder */
		$variation_builder = $this->container->get( WooVariationOptionsBuilder::class );

		return $variation_builder->can_render( $product );
	}

	/**
	 * Build a cart row from a standard WooCommerce product line.
	 *
	 * @param string               $cart_key  Cart item key.
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return array<string, mixed>
	 */
	private function row_from_product( string $cart_key, array $cart_item ): array {
		$product = $cart_item['data'] ?? null;
		$name    = $product instanceof \WC_Product ? $product->get_name() : '';
		$price   = isset( $cart_item['line_total'] )
			? (float) $cart_item['line_total']
			: ( $product instanceof \WC_Product ? (float) wc_get_price_to_display( $product ) * (int) ( $cart_item['quantity'] ?? 1 ) : 0 );

		$description = $this->build_product_description( $cart_item );

		$image_url = '';
		if ( $product instanceof \WC_Product && $product->get_image_id() > 0 ) {
			$image_url = (string) wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );
		}

		return array(
			'cart_key'    => $cart_key,
			'label'       => $name,
			'description' => $description,
			'price'       => $price,
			'image_url'   => $image_url,
			'copyable'    => true,
		);
	}

	/**
	 * Build cart rows from a KCP configuration pricing snapshot.
	 *
	 * @param string               $cart_key  Cart item key.
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return array<int, array<string, mixed>>
	 */
	private function rows_from_configuration( string $cart_key, array $cart_item ): array {
		$pricing = json_decode( (string) ( $cart_item[ CartHandler::META_PRICING ] ?? '{}' ), true );
		$lines   = is_array( $pricing['line_items'] ?? null ) ? $pricing['line_items'] : array();
		$rows    = array();

		if ( empty( $lines ) ) {
			$rows[] = $this->row_from_product( $cart_key, $cart_item );
			return $rows;
		}

		foreach ( $lines as $line ) {
			if ( ! is_array( $line ) ) {
				continue;
			}

			$label = (string) ( $line['label'] ?? '' );
			$price = (float) ( $line['subtotal'] ?? 0 );

			if ( '' === $label ) {
				continue;
			}

			$rows[] = array(
				'cart_key'    => $cart_key,
				'label'       => $label,
				'description' => '',
				'price'       => $price,
				'image_url'   => '',
				'copyable'    => false,
			);
		}

		return $rows;
	}

	/**
	 * Build a description string for storefront product options.
	 *
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return string
	 */
	private function build_product_description( array $cart_item ): string {
		$parts = array();

		if ( ! empty( $cart_item['kcp_color_label'] ) ) {
			$parts[] = (string) $cart_item['kcp_color_label'];
		}

		if ( ! empty( $cart_item['kcp_height_label'] ) ) {
			$parts[] = (string) $cart_item['kcp_height_label'];
		}

		if ( ! empty( $parts ) ) {
			return implode( ' · ', $parts );
		}

		$product = $cart_item['data'] ?? null;

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$attributes = $product->is_type( 'variation' ) ? $product->get_variation_attributes() : array();

		foreach ( $attributes as $name => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}

			$taxonomy = str_replace( 'attribute_', '', (string) $name );
			$label    = wc_attribute_label( $taxonomy );
			$parts[]  = sprintf( '%s: %s', $label, wc_attribute_label( $value ) );
		}

		return implode( ' · ', $parts );
	}

	/**
	 * Handle afspraak form submission via AJAX.
	 *
	 * @return void
	 */
	public function ajax_afspraak(): void {
		check_ajax_referer( 'kcp_afspraak', 'kcp_afspraak_nonce' );

		$naam      = sanitize_text_field( wp_unslash( (string) ( $_POST['kcp_naam'] ?? '' ) ) );
		$telefoon  = sanitize_text_field( wp_unslash( (string) ( $_POST['kcp_telefoon'] ?? '' ) ) );
		$email     = sanitize_email( wp_unslash( (string) ( $_POST['kcp_email'] ?? '' ) ) );
		$woonplaats = sanitize_text_field( wp_unslash( (string) ( $_POST['kcp_woonplaats'] ?? '' ) ) );
		$opmerking = sanitize_textarea_field( wp_unslash( (string) ( $_POST['kcp_opmerking'] ?? '' ) ) );
		$week      = sanitize_text_field( wp_unslash( (string) ( $_POST['kcp_week'] ?? '' ) ) );

		if ( '' === $naam || '' === $telefoon || '' === $email ) {
			wp_send_json_error( array( 'message' => 'Vul alle verplichte velden in.' ), 422 );
		}

		$admin_email = get_option( 'admin_email', '' );
		$site_name   = get_bloginfo( 'name' );

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Nieuwe afspraak aanvraag via %s', 'kitchen-configurator-pro' ),
			$site_name
		);

		$body  = "Naam: {$naam}\n";
		$body .= "Telefoon: {$telefoon}\n";
		$body .= "E-mail: {$email}\n";

		if ( '' !== $woonplaats ) {
			$body .= "Woonplaats: {$woonplaats}\n";
		}

		if ( '' !== $week ) {
			$body .= "Gewenste week: {$week}\n";
		}

		if ( '' !== $opmerking ) {
			$body .= "Opmerking: {$opmerking}\n";
		}

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			"Reply-To: {$naam} <{$email}>",
		);

		$sent = wp_mail( $admin_email, $subject, $body, $headers );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => 'Bedankt! We nemen snel contact met je op.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Er is iets misgegaan.' ), 500 );
		}
	}
}
