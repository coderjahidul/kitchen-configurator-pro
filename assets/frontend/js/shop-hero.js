( function () {
	'use strict';

	const initSlideshow = ( root ) => {
		const slides = Array.from( root.querySelectorAll( '.kcp-shop-hero__slide' ) );

		if ( slides.length <= 1 ) {
			slides.forEach( ( slide ) => slide.classList.add( 'is-active' ) );
			return;
		}

		const interval = Math.max( 2000, parseInt( root.dataset.interval || '4000', 10 ) || 4000 );
		let activeIndex = slides.findIndex( ( slide ) => slide.classList.contains( 'is-active' ) );

		if ( activeIndex < 0 ) {
			activeIndex = 0;
			slides[ 0 ].classList.add( 'is-active' );
		}

		const showSlide = ( nextIndex ) => {
			slides[ activeIndex ].classList.remove( 'is-active' );
			activeIndex = nextIndex;
			slides[ activeIndex ].classList.add( 'is-active' );
		};

		window.setInterval( () => {
			showSlide( ( activeIndex + 1 ) % slides.length );
		}, interval );
	};

	document.addEventListener( 'DOMContentLoaded', () => {
		if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			document.querySelectorAll( '.kcp-shop-hero__slideshow' ).forEach( ( root ) => {
				const slides = root.querySelectorAll( '.kcp-shop-hero__slide' );

				slides.forEach( ( slide, index ) => {
					slide.classList.toggle( 'is-active', 0 === index );
				} );
			} );

			return;
		}

		document.querySelectorAll( '.kcp-shop-hero__slideshow' ).forEach( initSlideshow );
	} );
}() );
