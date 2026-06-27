<?php
/**
 * Brand landing fields on WooCommerce product category screens.
 *
 * @package KitchenConfiguratorPro
 *
 * @var bool               $is_edit
 * @var array<string,mixed> $settings
 * @var string             $hero_image_url
 * @var int                $hero_image_id
 * @var array<int,string>  $usps
 * @var \WP_Term|null      $term
 */

defined( 'ABSPATH' ) || exit;

$wrapper_tag = $is_edit ? 'tr' : 'div';
$label_tag   = $is_edit ? 'th' : 'label';
$field_tag   = $is_edit ? 'td' : 'div';
$row_class   = $is_edit ? 'form-field kcp-brand-category-fields__row' : 'form-field kcp-brand-category-fields__row';

$render_row = static function ( string $label, string $content ) use ( $wrapper_tag, $label_tag, $field_tag, $row_class ): void {
	echo '<' . esc_attr( $wrapper_tag ) . ' class="' . esc_attr( $row_class ) . '">';
	echo '<' . esc_attr( $label_tag ) . ' scope="row">';
	echo '<label>' . esc_html( $label ) . '</label>';
	echo '</' . esc_attr( $label_tag ) . '>';
	echo '<' . esc_attr( $field_tag ) . '>';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted admin HTML fragments.
	echo $content;
	echo '</' . esc_attr( $field_tag ) . '>';
	echo '</' . esc_attr( $wrapper_tag ) . '>';
};

if ( $is_edit ) {
	echo '<tr class="form-field"><th colspan="2"><h2>' . esc_html__( 'Brand landing page', 'kitchen-configurator-pro' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'Content shown on this brand category landing page (e.g. Vipp). The category description below is used as the brand story.', 'kitchen-configurator-pro' ) . '</p></th></tr>';
} else {
	echo '<div class="form-field"><h2>' . esc_html__( 'Brand landing page', 'kitchen-configurator-pro' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'Only applies to top-level brand categories without a parent.', 'kitchen-configurator-pro' ) . '</p></div>';
}

wp_nonce_field( 'kcp_brand_landing_save', 'kcp_brand_landing_nonce' );

$render_row(
	__( 'Hero title', 'kitchen-configurator-pro' ),
	'<input type="text" class="regular-text" name="kcp_brand_hero_title" value="' . esc_attr( (string) ( $settings['hero_title'] ?? '' ) ) . '" />'
);

$render_row(
	__( 'Hero button label', 'kitchen-configurator-pro' ),
	'<input type="text" class="regular-text" name="kcp_brand_hero_cta_label" value="' . esc_attr( (string) ( $settings['hero_cta_label'] ?? '' ) ) . '" />'
);

$render_row(
	__( 'Hero button URL', 'kitchen-configurator-pro' ),
	'<input type="url" class="regular-text" name="kcp_brand_hero_cta_url" value="' . esc_attr( (string) ( $settings['hero_cta_url'] ?? '' ) ) . '" placeholder="#kcp-brand-products" />'
);

$hero_picker = '<div class="kcp-brand-hero-picker" data-kcp-brand-hero-picker>'
	. '<input type="hidden" name="kcp_brand_hero_image_id" value="' . esc_attr( (string) $hero_image_id ) . '" data-kcp-brand-hero-input />'
	. '<div class="kcp-brand-hero-picker__preview' . ( '' === $hero_image_url ? ' is-empty' : '' ) . '" data-kcp-brand-hero-preview>';

if ( '' !== $hero_image_url ) {
	$hero_picker .= '<img src="' . esc_url( $hero_image_url ) . '" alt="" />';
} else {
	$hero_picker .= '<span class="kcp-brand-hero-picker__placeholder">' . esc_html__( 'Uses category thumbnail if empty', 'kitchen-configurator-pro' ) . '</span>';
}

$hero_picker .= '</div><p><button type="button" class="button" data-kcp-brand-hero-select>' . esc_html__( 'Select hero image', 'kitchen-configurator-pro' ) . '</button> '
	. '<button type="button" class="button-link" data-kcp-brand-hero-remove' . ( $hero_image_id > 0 ? '' : ' hidden' ) . '>' . esc_html__( 'Remove', 'kitchen-configurator-pro' ) . '</button></p>'
	. '<p class="description">' . esc_html__( 'Optional. Falls back to the category thumbnail or a featured product image.', 'kitchen-configurator-pro' ) . '</p></div>';

$render_row( __( 'Hero image', 'kitchen-configurator-pro' ), $hero_picker );

$render_row(
	__( 'Hero badge', 'kitchen-configurator-pro' ),
	'<input type="text" class="regular-text" name="kcp_brand_hero_badge" value="' . esc_attr( (string) ( $settings['hero_badge'] ?? '' ) ) . '" placeholder="' . esc_attr( $term instanceof \WP_Term ? strtolower( $term->name ) : 'vipp' ) . '" />'
);

for ( $i = 1; $i <= 3; $i++ ) {
	$render_row(
		sprintf(
			/* translators: %d: USP bullet number */
			__( 'USP %d', 'kitchen-configurator-pro' ),
			$i
		),
		'<input type="text" class="large-text" name="kcp_brand_usp_' . esc_attr( (string) $i ) . '" value="' . esc_attr( (string) ( $usps[ $i - 1 ] ?? '' ) ) . '" />'
	);
}

$render_row(
	__( 'Popular products heading', 'kitchen-configurator-pro' ),
	'<input type="text" class="regular-text" name="kcp_brand_popular_heading" value="' . esc_attr( (string) ( $settings['popular_heading'] ?? '' ) ) . '" />'
);

$render_row(
	__( 'Back link label', 'kitchen-configurator-pro' ),
	'<input type="text" class="regular-text" name="kcp_brand_back_label" value="' . esc_attr( (string) ( $settings['back_label'] ?? '' ) ) . '" />'
);
