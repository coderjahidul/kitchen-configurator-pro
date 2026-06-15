( function ( window, $ ) {
	'use strict';

	if ( 'undefined' === typeof wp || ! wp.media ) {
		return;
	}

	const strings = window.kcpProductPresetMedia || {};
	const selectTitle = strings.selectTitle || 'Select image';
	const selectButton = strings.selectButton || 'Use image';
	const emptyLabel = strings.emptyLabel || 'No image selected';

	const setPickerValue = ( picker, url ) => {
		const input = picker.querySelector( '.kcp-image-picker__input' );
		const preview = picker.querySelector( '.kcp-image-picker__preview' );
		const removeButton = picker.querySelector( '.kcp-image-picker__remove' );

		if ( ! input || ! preview ) {
			return;
		}

		const value = url || '';
		input.value = value;

		if ( '' === value ) {
			preview.classList.add( 'is-empty' );
			preview.innerHTML = `<span class="kcp-image-picker__placeholder">${ emptyLabel }</span>`;

			if ( removeButton ) {
				removeButton.hidden = true;
			}

			return;
		}

		preview.classList.remove( 'is-empty' );
		preview.innerHTML = `<img src="${ value.replace( /"/g, '&quot;' ) }" alt="" />`;

		if ( removeButton ) {
			removeButton.hidden = false;
		}
	};

	const bindPicker = ( picker ) => {
		if ( picker.dataset.kcpImagePickerBound ) {
			return;
		}

		picker.dataset.kcpImagePickerBound = '1';

		const selectButton = picker.querySelector( '.kcp-image-picker__select' );
		const removeButton = picker.querySelector( '.kcp-image-picker__remove' );

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
					const url = attachment.url || '';

					setPickerValue( picker, url );
				} );

				frame.open();
			} );
		}

		if ( removeButton ) {
			removeButton.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				setPickerValue( picker, '' );
			} );
		}
	};

	window.kcpInitImagePickers = ( root ) => {
		const scope = root || document;

		scope.querySelectorAll( '[data-kcp-image-picker]' ).forEach( ( picker ) => {
			bindPicker( picker );
		} );
	};

	window.kcpClearImagePicker = ( picker ) => {
		if ( picker ) {
			setPickerValue( picker, '' );
		}
	};

	document.addEventListener( 'DOMContentLoaded', () => {
		window.kcpInitImagePickers( document );
	} );
}( window, window.jQuery ) );
