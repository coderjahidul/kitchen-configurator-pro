<?php
/**
 * Variable product add to cart with KKF pill selectors.
 *
 * @package KitchenConfiguratorPro
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

	global $product;

	use KitchenConfiguratorPro\Integration\WooCommerce\ProductOptionsPresenter;
	use KitchenConfiguratorPro\Services\WooVariationOptionsBuilder;

	/** @var ProductOptionsPresenter $options_presenter */
	$options_presenter = kcp_plugin()->container()->get( ProductOptionsPresenter::class );
	$part_edit_mode    = $options_presenter->is_part_edit_mode();

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

/** @var WooVariationOptionsBuilder $builder */
$builder = kcp_plugin()->container()->get( WooVariationOptionsBuilder::class );
$options = $builder->build( $product );

do_action( 'woocommerce_before_add_to_cart_form' );
?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php
	$base_price    = (float) ( $options['base_price'] ?? wc_get_price_to_display( $product ) );
	$option_groups = is_array( $options['option_groups'] ?? null ) ? $options['option_groups'] : array();
	$colors        = is_array( $options['colors'] ?? null ) ? $options['colors'] : array();
	$heights       = is_array( $options['heights'] ?? null ) ? $options['heights'] : array();
	$default_color  = (string) ( $options['default_color'] ?? ( $colors[0]['id'] ?? '' ) );
	$default_height = (string) ( $options['default_height'] ?? ( $heights[0]['id'] ?? '' ) );
	$color_attr     = (string) ( $options['color_attribute'] ?? '' );
	$height_attr    = (string) ( $options['height_attribute'] ?? '' );
	$has_kcp_options = ! empty( $option_groups ) || ! empty( $colors ) || ! empty( $heights );
	$preset_mode     = ! empty( $options['from_preset'] );
	?>

	<?php if ( empty( $available_variations ) && false !== $available_variations && ! $has_kcp_options ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<?php
		if ( $has_kcp_options && ! $part_edit_mode ) :
			$wc_variations_mode = ! $preset_mode && ! empty( $options['use_wc_variations'] );
			include KCP_PLUGIN_DIR . 'templates/woocommerce/partials/product-options.php';
		endif;
		?>

		<table class="variations kcp-variations-hidden" cellspacing="0" role="presentation">
			<tbody>
				<?php foreach ( $attributes as $attribute_name => $attribute_options ) : ?>
					<tr>
						<th class="label"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo wc_attribute_label( $attribute_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label></th>
						<td class="value">
							<?php
							wc_dropdown_variation_attribute_options(
								array(
									'options'   => $attribute_options,
									'attribute' => $attribute_name,
									'product'   => $product,
								)
							);
							echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#" aria-label="' . esc_attr__( 'Clear options', 'woocommerce' ) . '">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : '';
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
			do_action( 'woocommerce_before_single_variation' );

			if ( $preset_mode ) {
				do_action( 'woocommerce_before_add_to_cart_quantity' );

				woocommerce_quantity_input(
					array(
						'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
						'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
						'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security.NonceVerification.Missing
					)
				);

				do_action( 'woocommerce_before_add_to_cart_button' );
				?>
				<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
				<?php
				do_action( 'woocommerce_after_add_to_cart_button' );
			} else {
				do_action( 'woocommerce_single_variation' );
			}

			do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );
