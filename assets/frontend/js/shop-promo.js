( function () {
	'use strict';

	const activateTile = ( tile ) => {
		const poster = tile.querySelector( '.kcp-shop-promo-tile__poster' );
		const video = tile.querySelector( '.kcp-shop-promo-tile__video' );

		if ( ! ( video instanceof HTMLVideoElement ) ) {
			return;
		}

		if ( poster instanceof HTMLImageElement ) {
			poster.hidden = true;
		}

		video.hidden = false;

		const playPromise = video.play();

		if ( playPromise && typeof playPromise.catch === 'function' ) {
			playPromise.catch( () => {} );
		}
	};

	const deactivateTile = ( tile ) => {
		const poster = tile.querySelector( '.kcp-shop-promo-tile__poster' );
		const video = tile.querySelector( '.kcp-shop-promo-tile__video' );

		if ( ! ( video instanceof HTMLVideoElement ) ) {
			return;
		}

		video.pause();
		video.currentTime = 0;
		video.hidden = true;

		if ( poster instanceof HTMLImageElement ) {
			poster.hidden = false;
		}
	};

	document.querySelectorAll( '.kcp-shop-promo-tile' ).forEach( ( tile ) => {
		tile.addEventListener( 'mouseenter', () => activateTile( tile ) );
		tile.addEventListener( 'mouseleave', () => deactivateTile( tile ) );
		tile.addEventListener( 'focusin', () => activateTile( tile ) );
		tile.addEventListener( 'focusout', () => deactivateTile( tile ) );
	} );
}() );
