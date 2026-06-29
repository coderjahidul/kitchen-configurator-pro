( function ( window, $ ) {
	'use strict';

	if ( 'undefined' === typeof wp || ! wp.media ) {
		return;
	}

	const strings = window.kcpProductCategoryVideoFields || {};
	const selectTitle = strings.selectTitle || 'Select category video';
	const selectButton = strings.selectButton || 'Use video';
	const emptyLabel = strings.emptyLabel || 'No video selected';

	const setCategoryVideo = ( picker, attachment ) => {
		const input = picker.querySelector( '[data-kcp-category-video-input]' );
		const preview = picker.querySelector( '[data-kcp-category-video-preview]' );
		const removeButton = picker.querySelector( '[data-kcp-category-video-remove]' );

		if ( ! input || ! preview ) {
			return;
		}

		if ( ! attachment || ! attachment.id ) {
			input.value = '0';
			preview.classList.add( 'is-empty' );
			preview.innerHTML = `<span class="kcp-category-video-picker__placeholder">${ emptyLabel }</span>`;

			if ( removeButton ) {
				removeButton.hidden = true;
			}

			return;
		}

		const url = attachment.url || '';

		input.value = String( attachment.id );
		preview.classList.remove( 'is-empty' );
		preview.innerHTML = `<video src="${ String( url ).replace( /"/g, '&quot;' ) }" muted playsinline preload="metadata"></video>`;

		if ( removeButton ) {
			removeButton.hidden = false;
		}
	};

	document.querySelectorAll( '[data-kcp-category-video-picker]' ).forEach( ( picker ) => {
		const selectBtn = picker.querySelector( '[data-kcp-category-video-select]' );
		const removeButton = picker.querySelector( '[data-kcp-category-video-remove]' );

		if ( selectBtn ) {
			selectBtn.addEventListener( 'click', ( event ) => {
				event.preventDefault();

				const frame = wp.media( {
					title: selectTitle,
					button: { text: selectButton },
					library: { type: 'video' },
					multiple: false,
				} );

				frame.on( 'select', () => {
					const attachment = frame.state().get( 'selection' ).first().toJSON();
					setCategoryVideo( picker, attachment );
				} );

				frame.open();
			} );
		}

		if ( removeButton ) {
			removeButton.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				setCategoryVideo( picker, null );
			} );
		}
	} );
}( window, window.jQuery ) );
