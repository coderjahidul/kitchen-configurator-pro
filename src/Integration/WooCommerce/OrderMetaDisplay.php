<?php
/**
 * WooCommerce order meta display.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

/**
 * Displays configuration details in admin orders and customer emails.
 */
final class OrderMetaDisplay {

	/**
	 * Register display hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_raw_meta' ) );
		add_action( 'woocommerce_after_order_itemmeta', array( $this, 'render_admin_item_meta' ), 10, 3 );
		add_action( 'woocommerce_order_item_meta_end', array( $this, 'render_customer_item_meta' ), 10, 4 );
	}

	/**
	 * Hide raw JSON meta from default order item meta list.
	 *
	 * @param array<int, string> $hidden Hidden meta keys.
	 * @return array<int, string>
	 */
	public function hide_raw_meta( array $hidden ): array {
		$hidden[] = OrderHandler::META_CONFIG;
		$hidden[] = OrderHandler::META_PRICING;
		$hidden[] = OrderHandler::META_HASH;

		return $hidden;
	}

	/**
	 * Render configuration summary in admin order screen.
	 *
	 * @param int|string           $item_id Order item ID.
	 * @param \WC_Order_Item       $item    Order item.
	 * @param \WC_Product|null     $product Product object.
	 * @return void
	 */
	public function render_admin_item_meta( $item_id, $item, $product ): void {
		unset( $item_id, $product );

		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return;
		}

		$this->output_summary( $item, true );
	}

	/**
	 * Render configuration summary for customer-facing order views.
	 *
	 * @param int|string           $item_id   Order item ID.
	 * @param \WC_Order_Item       $item      Order item.
	 * @param \WC_Order            $order     Order object.
	 * @param bool                 $plain_text Plain text mode.
	 * @return void
	 */
	public function render_customer_item_meta( $item_id, $item, $order, bool $plain_text ): void {
		unset( $item_id, $order );

		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return;
		}

		$this->output_summary( $item, ! $plain_text );
	}

	/**
	 * Output formatted configuration summary.
	 *
	 * @param \WC_Order_Item_Product $item    Order item.
	 * @param bool                   $as_html Render as HTML.
	 * @return void
	 */
	private function output_summary( \WC_Order_Item_Product $item, bool $as_html ): void {
		$uuid = (string) $item->get_meta( OrderHandler::META_UUID, true );

		if ( '' === $uuid ) {
			return;
		}

		$config  = json_decode( (string) $item->get_meta( OrderHandler::META_CONFIG, true ), true );
		$pricing = json_decode( (string) $item->get_meta( OrderHandler::META_PRICING, true ), true );
		$total   = (string) $item->get_meta( OrderHandler::META_TOTAL, true );

		$cabinet_count = is_array( $config['cabinets'] ?? null ) ? count( $config['cabinets'] ) : 0;
		$title         = is_array( $config ) ? (string) ( $config['title'] ?? '' ) : '';

		if ( $as_html ) {
			echo '<div class="kcp-order-meta">';
			echo '<p><strong>' . esc_html__( 'Kitchen Configuration', 'kitchen-configurator-pro' ) . '</strong></p>';
			echo '<ul class="kcp-order-meta__list">';
			if ( '' !== $title ) {
				echo '<li><strong>' . esc_html__( 'Project', 'kitchen-configurator-pro' ) . ':</strong> ' . esc_html( $title ) . '</li>';
			}
			echo '<li><strong>' . esc_html__( 'Reference', 'kitchen-configurator-pro' ) . ':</strong> <code>' . esc_html( $uuid ) . '</code></li>';
			echo '<li><strong>' . esc_html__( 'Cabinets', 'kitchen-configurator-pro' ) . ':</strong> ' . esc_html( (string) $cabinet_count ) . '</li>';
			if ( '' !== $total && function_exists( 'wc_price' ) ) {
				echo '<li><strong>' . esc_html__( 'Configured total', 'kitchen-configurator-pro' ) . ':</strong> ' . wp_kses_post( wc_price( (float) $total ) ) . '</li>';
			}
			if ( is_array( $pricing['line_items'] ?? null ) && ! empty( $pricing['line_items'] ) ) {
				echo '<li><strong>' . esc_html__( 'Line items', 'kitchen-configurator-pro' ) . ':</strong><ul>';
				foreach ( array_slice( $pricing['line_items'], 0, 8 ) as $line ) {
					$label = (string) ( $line['label'] ?? '' );
					$sub   = (float) ( $line['subtotal'] ?? 0 );
					echo '<li>' . esc_html( $label ) . ' — ' . wp_kses_post( wc_price( $sub ) ) . '</li>';
				}
				echo '</ul></li>';
			}
			echo '</ul></div>';
			return;
		}

		echo "\n" . esc_html__( 'Kitchen Configuration', 'kitchen-configurator-pro' ) . "\n";
		if ( '' !== $title ) {
			echo esc_html__( 'Project', 'kitchen-configurator-pro' ) . ': ' . esc_html( $title ) . "\n";
		}
		echo esc_html__( 'Reference', 'kitchen-configurator-pro' ) . ': ' . esc_html( $uuid ) . "\n";
		echo esc_html__( 'Cabinets', 'kitchen-configurator-pro' ) . ': ' . esc_html( (string) $cabinet_count ) . "\n";
	}
}
