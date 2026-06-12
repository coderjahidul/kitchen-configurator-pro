<?php
/**
 * Admin notice partial.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<int, array{type: string, message: string}> $notices
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $notices ) ) {
	return;
}

foreach ( $notices as $notice ) {
	$type    = in_array( $notice['type'], array( 'success', 'error', 'warning', 'info' ), true ) ? $notice['type'] : 'info';
	$classes = 'notice notice-' . $type . ' is-dismissible';

	printf(
		'<div class="%1$s"><p>%2$s</p></div>',
		esc_attr( $classes ),
		esc_html( $notice['message'] )
	);
}
