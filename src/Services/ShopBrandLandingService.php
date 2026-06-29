<?php
/**
 * Brand category landing page data for WooCommerce shop archives.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Integration\WooCommerce\ShopPresenter;

/**
 * Builds navigation, hero, and supplemental blocks for brand shop category pages.
 */
final class ShopBrandLandingService {

	/**
	 * Whether the current request should use the brand landing layout.
	 */
	public static function is_active(): bool {
		if ( ! is_product_category() ) {
			return false;
		}

		$term = self::get_queried_term();

		if ( null === $term ) {
			return false;
		}

		$root = self::get_root_term( $term );

		if ( null === $root || 'uncategorized' === $root->slug ) {
			return false;
		}

		return 0 === (int) $root->parent;
	}

	/**
	 * @return \WP_Term|null
	 */
	public static function get_queried_term(): ?\WP_Term {
		$term = get_queried_object();

		return $term instanceof \WP_Term ? $term : null;
	}

	/**
	 * @return \WP_Term|null
	 */
	public static function get_root_term( ?\WP_Term $term = null ): ?\WP_Term {
		$term = $term ?? self::get_queried_term();

		if ( null === $term ) {
			return null;
		}

		$current = $term;

		while ( $current->parent > 0 ) {
			$parent = get_term( (int) $current->parent, 'product_cat' );

			if ( ! $parent instanceof \WP_Term || is_wp_error( $parent ) ) {
				break;
			}

			$current = $parent;
		}

		return $current;
	}

	/**
	 * Whether the visitor is on the root brand category itself (e.g. /vipp/).
	 */
	public static function is_root_brand_page(): bool {
		if ( ! self::is_active() ) {
			return false;
		}

		$term = self::get_queried_term();
		$root = self::get_root_term( $term );

		return null !== $term && null !== $root && (int) $term->term_id === (int) $root->term_id;
	}

	/**
	 * @return array<int, array{label: string, url: string}>
	 */
	public static function get_breadcrumbs(): array {
		$crumbs = array();
		$shop   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';

		if ( is_string( $shop ) && '' !== $shop ) {
			$crumbs[] = array(
				'label' => __( 'Webwinkel', 'kitchen-configurator-pro' ),
				'url'   => $shop,
			);
		}

		$term = self::get_queried_term();
		$root = self::get_root_term( $term );

		if ( null === $term || null === $root ) {
			return $crumbs;
		}

		if ( (int) $term->term_id === (int) $root->term_id ) {
			$crumbs[] = array(
				'label' => $root->name,
				'url'   => '',
			);

			return $crumbs;
		}

		$crumbs[] = array(
			'label' => $root->name,
			'url'   => (string) get_term_link( $root ),
		);
		$crumbs[] = array(
			'label' => $term->name,
			'url'   => '',
		);

		return $crumbs;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_view_model(): array {
		$term = self::get_queried_term();
		$root = self::get_root_term( $term );

		if ( null === $term || null === $root ) {
			return array();
		}

		$spotlight    = self::get_spotlight_products( $root );
		$popular      = self::get_popular_products( $root );
		$settings     = ShopBrandLandingSettingsService::get_for_term( $root );
		$hero_image   = ShopBrandLandingSettingsService::get_hero_image_url( $root, $settings, $spotlight );
		$hero_video   = ProductCategoryVideoService::get_video_url( (int) $root->term_id );

		return array(
			'root'              => $root,
			'current'           => $term,
			'is_root'           => (int) $term->term_id === (int) $root->term_id,
			'brand_label'       => $root->name,
			'heading'           => $term->name,
			'breadcrumbs'       => self::get_breadcrumbs(),
			'navigation'        => self::get_navigation_sections( $root, $term ),
			'hero'              => array(
				'title'     => (string) $settings['hero_title'],
				'cta_label' => (string) $settings['hero_cta_label'],
				'cta_url'   => (string) $settings['hero_cta_url'],
				'image_url' => $hero_image,
				'video_url' => $hero_video,
				'badge'     => (string) $settings['hero_badge'],
			),
			'spotlight_products'=> $spotlight,
			'usps'              => is_array( $settings['usps'] ?? null ) ? $settings['usps'] : array(),
			'video_tiles'       => self::get_video_tiles( $root ),
			'popular_heading'   => (string) $settings['popular_heading'],
			'popular_products'  => $popular,
			'story_html'        => self::get_story_html( $root ),
			'story_title'       => strtolower( $root->name ),
			'back_url'          => self::get_back_url(),
			'back_label'        => (string) $settings['back_label'],
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_navigation_sections( \WP_Term $root, \WP_Term $current ): array {
		$sections = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => (int) $root->term_id,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $sections ) || empty( $sections ) ) {
			return array();
		}

		$output = array();

		foreach ( $sections as $section ) {
			if ( ! $section instanceof \WP_Term ) {
				continue;
			}

			$children = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'parent'     => (int) $section->term_id,
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);

			$links = array();

			if ( ! is_wp_error( $children ) ) {
				foreach ( $children as $child ) {
					if ( ! $child instanceof \WP_Term ) {
						continue;
					}

					$links[] = array(
						'id'       => (int) $child->term_id,
						'name'     => $child->name,
						'url'      => get_term_link( $child ),
						'is_active'=> (int) $child->term_id === (int) $current->term_id,
					);
				}
			}

			$section_active = (int) $section->term_id === (int) $current->term_id;

			if ( ! $section_active && ! empty( $links ) ) {
				foreach ( $links as $link ) {
					if ( ! empty( $link['is_active'] ) ) {
						$section_active = true;
						break;
					}
				}
			}

			$output[] = array(
				'id'         => (int) $section->term_id,
				'name'       => $section->name,
				'url'        => get_term_link( $section ),
				'is_active'  => $section_active,
				'children'   => $links,
			);
		}

		return $output;
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private static function get_video_tiles( \WP_Term $root ): array {
		$sections = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => (int) $root->term_id,
				'hide_empty' => false,
				'number'     => 3,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $sections ) || empty( $sections ) ) {
			return array();
		}

		$tiles = array();

		foreach ( $sections as $section ) {
			if ( ! $section instanceof \WP_Term ) {
				continue;
			}

			$thumbnail_id = (int) get_term_meta( $section->term_id, 'thumbnail_id', true );
			$image_url    = $thumbnail_id > 0 ? (string) wp_get_attachment_image_url( $thumbnail_id, 'medium_large' ) : '';
			$video_url    = ProductCategoryVideoService::get_video_url( (int) $section->term_id );

			$tiles[] = array(
				'name'      => $section->name,
				'url'       => (string) get_term_link( $section ),
				'image_url' => $image_url,
				'video_url' => $video_url,
			);
		}

		return $tiles;
	}

	/**
	 * @return array<int, \WC_Product>
	 */
	private static function query_brand_products( \WP_Term $root, array $args ): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$term_ids = array( (int) $root->term_id );
		$children = get_term_children( (int) $root->term_id, 'product_cat' );

		if ( is_array( $children ) ) {
			foreach ( $children as $child_id ) {
				$term_ids[] = (int) $child_id;
			}
		}

		$query = array_merge(
			array(
				'status'    => 'publish',
				'orderby'   => 'date',
				'order'     => 'DESC',
				'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $term_ids,
					),
				),
			),
			$args
		);

		$products = wc_get_products( $query );

		return array_values(
			array_filter(
				$products,
				static fn( $product ): bool => $product instanceof \WC_Product && $product->is_visible()
			)
		);
	}

	/**
	 * Featured product strip shown directly under the hero banner.
	 *
	 * @return array<int, \WC_Product>
	 */
	private static function get_spotlight_products( \WP_Term $root ): array {
		$featured = self::query_brand_products( $root, array( 'limit' => 1, 'featured' => true ) );

		if ( ! empty( $featured ) ) {
			return $featured;
		}

		return self::query_brand_products( $root, array( 'limit' => 1 ) );
	}

	/**
	 * @return array<int, \WC_Product>
	 */
	private static function get_popular_products( \WP_Term $root ): array {
		$featured = self::query_brand_products( $root, array( 'limit' => 4, 'featured' => true ) );

		if ( ! empty( $featured ) ) {
			return $featured;
		}

		return self::query_brand_products( $root, array( 'limit' => 4 ) );
	}

	private static function get_story_html( \WP_Term $root ): string {
		$description = term_description( (int) $root->term_id, 'product_cat' );

		if ( is_string( $description ) && '' !== trim( wp_strip_all_tags( $description ) ) ) {
			return $description;
		}

		return '<p><strong>' . esc_html( $root->name ) . '</strong> '
			. esc_html__(
				'is een Deens designmerk met een heel bijzondere ontstaans geschiedenis. Het begint allemaal in de jaren 30 van de vorige eeuw, wanneer de metaalbewerker Holger Nielsen een auto wint bij zijn kaartje voor een voetbalwedstrijd.',
				'kitchen-configurator-pro'
			)
			. '</p>';
	}

	private static function get_back_url(): string {
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$shop_url = wc_get_page_permalink( 'shop' );

			if ( is_string( $shop_url ) && '' !== $shop_url ) {
				return $shop_url;
			}
		}

		return home_url( '/' );
	}

	/**
	 * Resolve gallery image IDs for a product card.
	 *
	 * @return array<int, int>
	 */
	public static function get_product_gallery_ids( \WC_Product $product ): array {
		$ids   = array();
		$main  = (int) $product->get_image_id();

		if ( $main > 0 ) {
			$ids[] = $main;
		}

		foreach ( $product->get_gallery_image_ids() as $gallery_id ) {
			$gallery_id = (int) $gallery_id;

			if ( $gallery_id > 0 && ! in_array( $gallery_id, $ids, true ) ) {
				$ids[] = $gallery_id;
			}
		}

		return array_slice( $ids, 0, 2 );
	}

	/**
	 * Render stock label for archive cards.
	 */
	public static function get_stock_label( \WC_Product $product ): string {
		if ( $product->is_in_stock() ) {
			return __( 'op voorraad', 'kitchen-configurator-pro' );
		}

		return __( 'niet op voorraad', 'kitchen-configurator-pro' );
	}

	/**
	 * Render spotlight/featured product card markup.
	 */
	public static function render_featured_product_card( \WC_Product $product, string $brand_label = '' ): void {
		self::render_brand_product_card( $product, 'kcp-featured-product', $brand_label );
	}

	/**
	 * Render popular products grid card markup.
	 */
	public static function render_popular_product_card( \WC_Product $product, string $brand_label = '' ): void {
		self::render_brand_product_card( $product, 'kcp-popular-product', $brand_label );
	}

	/**
	 * Shared brand landing product card renderer.
	 */
	private static function render_brand_product_card( \WC_Product $product, string $base_class, string $brand_label ): void {
		$permalink = get_permalink( $product->get_id() );
		$images    = self::get_product_gallery_ids( $product );
		$price     = ShopPresenter::format_archive_price_html( $product );
		$stock     = self::get_stock_label( $product );
		$in_stock  = $product->is_in_stock();
		?>
		<a class="<?php echo esc_attr( $base_class ); ?>" href="<?php echo esc_url( $permalink ); ?>">
			<div class="<?php echo esc_attr( $base_class ); ?>__image<?php echo count( $images ) > 1 ? ' has-hover' : ''; ?>">
				<?php foreach ( $images as $index => $image_id ) : ?>
					<?php
					echo wp_get_attachment_image(
						$image_id,
						'woocommerce_single',
						false,
						array(
							'class'    => $base_class . '__img' . ( 0 === $index ? ' is-main' : ' is-hover' ),
							'loading'  => 'lazy',
							'decoding' => 'async',
						)
					);
					?>
				<?php endforeach; ?>
				<?php if ( empty( $images ) ) : ?>
					<span class="<?php echo esc_attr( $base_class ); ?>__placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $brand_label ) : ?>
				<span class="<?php echo esc_attr( $base_class ); ?>__brand"><?php echo esc_html( strtolower( $brand_label ) ); ?></span>
			<?php endif; ?>
			<span class="<?php echo esc_attr( $base_class ); ?>__name"><?php echo esc_html( $product->get_name() ); ?></span>
			<span class="<?php echo esc_attr( $base_class ); ?>__meta">
				<span class="<?php echo esc_attr( $base_class ); ?>__price"><?php echo esc_html( $price ); ?></span>
				<span class="<?php echo esc_attr( $base_class ); ?>__stock<?php echo $in_stock ? ' is-in-stock' : ''; ?>"><?php echo esc_html( $stock ); ?></span>
			</span>
		</a>
		<?php
	}

	/**
	 * Render a product card for brand landing sections.
	 */
	public static function render_product_card( \WC_Product $product, string $card_class = '' ): void {
		$permalink = get_permalink( $product->get_id() );
		$images    = self::get_product_gallery_ids( $product );
		$price     = ShopPresenter::format_archive_price_html( $product );
		$classes   = trim( 'kcp-shop-card ' . $card_class );
		?>
		<li <?php wc_product_class( $classes, $product ); ?>>
			<a class="kcp-shop-card__link" href="<?php echo esc_url( $permalink ); ?>">
				<div class="kcp-shop-card__media<?php echo count( $images ) > 1 ? ' kcp-shop-card__media--dual' : ''; ?>">
					<?php foreach ( $images as $index => $image_id ) : ?>
						<?php
						echo wp_get_attachment_image(
							$image_id,
							'woocommerce_thumbnail',
							false,
							array(
								'class'   => 'kcp-shop-card__image' . ( 0 === $index ? ' is-primary' : ' is-secondary' ),
								'loading' => 'lazy',
								'decoding'=> 'async',
							)
						);
						?>
					<?php endforeach; ?>
					<?php if ( empty( $images ) ) : ?>
						<span class="kcp-shop-card__placeholder" aria-hidden="true"></span>
					<?php endif; ?>
				</div>
				<div class="kcp-shop-card__body">
					<h2 class="kcp-shop-card__title"><?php echo esc_html( $product->get_name() ); ?></h2>
					<div class="kcp-shop-card__meta">
						<span class="kcp-shop-card__price"><?php echo esc_html( $price ); ?></span>
						<span class="kcp-shop-card__stock"><?php echo esc_html( self::get_stock_label( $product ) ); ?></span>
					</div>
				</div>
			</a>
		</li>
		<?php
	}
}
