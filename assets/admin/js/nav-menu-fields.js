( function ( window, $ ) {
	'use strict';

	const initScope = ( scope ) => {
		if ( 'function' === typeof window.kcpInitImagePickers ) {
			window.kcpInitImagePickers( scope || document );
		}
	};

	$( document ).ready( function () {
		initScope( document );

		$( document ).on( 'menu-item-added', function ( event, $menuItem ) {
			if ( $menuItem && $menuItem.length ) {
				initScope( $menuItem.get( 0 ) );
			}
		} );
	} );
}( window, window.jQuery ) );
