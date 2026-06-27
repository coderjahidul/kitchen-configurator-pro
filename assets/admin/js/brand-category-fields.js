( function ( window, $ ) {
	'use strict';

	if ( 'undefined' === typeof wp || ! wp.media ) {
		return;
	}

	const strings = window.kcpBrandCategoryFields || {};
	const selectTitle = strings.selectTitle || 'Select hero image';
	const selectButton = strings.selectButton || 'Use image';
	const emptyLabel = strings.emptyLabel || 'Uses category thumbnail if empty';

	const setHeroImage = ( picker, attachment ) => {
		const input = picker.querySelector( '[data-kcp-brand-hero-input]' );
		const preview = picker.querySelector( '[data-kcp-brand-hero-preview]' );
		const removeButton = picker.querySelector( '[data-kcp-brand-hero-remove]' );

		if ( ! input || ! preview ) {
			return;
		}

		if ( ! attachment || ! attachment.id ) {
			input.value = '0';
			preview.classList.add( 'is-empty' );
			preview.innerHTML = `<span class="kcp-brand-hero-picker__placeholder">${ emptyLabel }</span>`;

			if ( removeButton ) {
				removeButton.hidden = true;
			}

			return;
		}

		const url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

		input.value = String( attachment.id );
		preview.classList.remove( 'is-empty' );
		preview.innerHTML = `<img src="${ String( url ).replace( /"/g, '&quot;' ) }" alt="" />`;

		if ( removeButton ) {
			removeButton.hidden = false;
		}
	};

	document.querySelectorAll( '[data-kcp-brand-hero-picker]' ).forEach( ( picker ) => {
		const selectButton = picker.querySelector( '[data-kcp-brand-hero-select]' );
		const removeButton = picker.querySelector( '[data-kcp-brand-hero-remove]' );

		if ( selectButton ) {
			selectButton.addEventListener( 'click', ( event ) => {
				event.preventDefault();

				const frame = wp.media( {
					title: selectTitle,
					button: { text: selectButton },
					library: { type: 'image' },
					multiple: false,
				} );

				frame.on( 'select', () => {
					const attachment = frame.state().get( 'selection' ).first().toJSON();
					setHeroImage( picker, attachment );
				} );

				frame.open();
			} );
		}

		if ( removeButton ) {
			removeButton.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				setHeroImage( picker, null );
			} );
		}
	} );
}( window, window.jQuery ) );
