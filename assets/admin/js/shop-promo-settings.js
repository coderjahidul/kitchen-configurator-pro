( function () {
	'use strict';

	const repeater = document.querySelector( '[data-kcp-shop-promo-tiles]' );

	if ( ! repeater ) {
		return;
	}

	const rowsWrap = repeater.querySelector( '.kcp-repeater__rows' );

	const bindRow = ( row ) => {
		if ( window.kcpInitImagePickers ) {
			window.kcpInitImagePickers( row );
		}
	};

	rowsWrap?.querySelectorAll( '.kcp-repeater__row' ).forEach( bindRow );
}() );
