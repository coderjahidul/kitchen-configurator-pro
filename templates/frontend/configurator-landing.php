<?php
/**
 * Configurator landing page template.
 *
 * @package KitchenConfiguratorPro
 */

use KitchenConfiguratorPro\Services\ConfiguratorLandingService;
use KitchenConfiguratorPro\Services\ShopHeroService;
use KitchenConfiguratorPro\Services\ShopPromoService;

defined( 'ABSPATH' ) || exit;

?>
<div class="kcp-configurator-landing">
	<?php
	if ( ShopHeroService::is_enabled() ) {
		$hero = ShopHeroService::get_settings();
		$path = KCP_PLUGIN_DIR . 'templates/woocommerce/partials/shop-hero.php';

		if ( is_readable( $path ) ) {
			include $path;
		}
	}
	?>

	<div class="kcp-configurator-landing__main">
		<div class="kcp-shop-header">
			<h2 class="kcp-shop-header__title"><?php esc_html_e( 'populaire opstellingen', 'kitchen-configurator-pro' ); ?></h2>
			<span class="kcp-shop-header__meta"><?php esc_html_e( 'bekijk alle populaire opstellingen', 'kitchen-configurator-pro' ); ?></span>
		</div>

		<?php if ( function_exists( 'wc_get_loop_prop' ) && class_exists( 'WC_Product' ) ) : ?>
			<?php
			$query = new WP_Query( ConfiguratorLandingService::get_products_query_args() );

			if ( $query->have_posts() ) :
				wc_set_loop_prop( 'columns', 4 );
				?>
				<div class="kcp-configurator-landing__products woocommerce">
					<?php woocommerce_product_loop_start(); ?>
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						wc_get_template_part( 'content', 'product' );
					endwhile;
					?>
					<?php woocommerce_product_loop_end(); ?>
				</div>
				<?php
				wp_reset_postdata();
			endif;
			?>
		<?php endif; ?>

		<?php ShopPromoService::render(); ?>
	</div>
</div>
