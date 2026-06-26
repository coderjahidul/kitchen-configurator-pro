/**
 * Repeater for cabinet items under a parent cabinet.
 */

( function () {
	'use strict';

	const getRows = ( rowsWrap ) => rowsWrap.querySelectorAll( ':scope > .kcp-repeater__row--cabinet-item' );

	const reindexRow = ( row, index ) => {
		row.querySelectorAll( '[name]' ).forEach( ( input ) => {
			const name = input.getAttribute( 'name' );

			if ( ! name ) {
				return;
			}

			input.setAttribute(
				'name',
				name.replace( /cabinet_items\[\d+\]/, `cabinet_items[${ index }]` )
			);
		} );

		const imageInput = row.querySelector( '.kcp-image-picker__input' );

		if ( imageInput ) {
			imageInput.id = `kcp-cabinet-item-image-${ index }`;
		}
	};

	const reindexRepeater = ( repeater ) => {
		const rowsWrap = repeater.querySelector( '.kcp-repeater__rows' );

		if ( ! rowsWrap ) {
			return;
		}

		getRows( rowsWrap ).forEach( ( row, index ) => {
			reindexRow( row, index );
		} );
	};

	const clearRowValues = ( row ) => {
		row.querySelectorAll( 'input, textarea, select' ).forEach( ( input ) => {
			if ( input.classList.contains( 'kcp-image-picker__input' ) ) {
				return;
			}

			if ( 'hidden' === input.type && input.name.includes( '[id]' ) ) {
				input.value = '';
				return;
			}

			if ( 'checkbox' === input.type ) {
				input.checked = true;
				return;
			}

			if ( 'number' === input.type ) {
				input.value = '0';
				return;
			}

			input.value = '';
		} );

		row.querySelectorAll( '[data-kcp-image-picker]' ).forEach( ( picker ) => {
			if ( 'function' === typeof window.kcpClearImagePicker ) {
				window.kcpClearImagePicker( picker );
			}
		} );

		row.querySelectorAll( '.description a' ).forEach( ( link ) => {
			link.closest( 'p' )?.remove();
		} );
	};

	const initImagePickers = ( root ) => {
		if ( 'function' === typeof window.kcpInitImagePickers ) {
			window.kcpInitImagePickers( root );
		}
	};

	const unbindImagePickers = ( root ) => {
		root.querySelectorAll( '[data-kcp-image-picker]' ).forEach( ( picker ) => {
			delete picker.dataset.kcpImagePickerBound;
		} );
	};

	const handleAddClick = ( event ) => {
		const button = event.target.closest( '[data-kcp-add="cabinet_items"]' );

		if ( ! button ) {
			return;
		}

		const repeater = button.closest( '[data-kcp-repeater="cabinet_items"]' );
		const rowsWrap = repeater?.querySelector( '.kcp-repeater__rows' );
		const rows = rowsWrap ? getRows( rowsWrap ) : [];
		const template = rows[ rows.length - 1 ];

		if ( ! repeater || ! rowsWrap || ! template ) {
			return;
		}

		event.preventDefault();

		const clone = template.cloneNode( true );
		clearRowValues( clone );
		unbindImagePickers( clone );
		rowsWrap.appendChild( clone );
		reindexRepeater( repeater );
		initImagePickers( clone );
	};

	const handleRemoveClick = ( event ) => {
		const button = event.target.closest( '.kcp-repeater__remove' );

		if ( ! button || ! button.closest( '.kcp-repeater__row--cabinet-item' ) ) {
			return;
		}

		const row = button.closest( '.kcp-repeater__row--cabinet-item' );
		const rowsWrap = row?.parentElement;
		const repeater = button.closest( '[data-kcp-repeater="cabinet_items"]' );

		if ( ! row || ! rowsWrap || ! repeater ) {
			return;
		}

		event.preventDefault();

		const rows = getRows( rowsWrap );

		if ( rows.length <= 1 ) {
			clearRowValues( row );
			return;
		}

		row.remove();
		reindexRepeater( repeater );
	};

	const boot = () => {
		const field = document.querySelector( '.kcp-cabinet-items-field' );

		if ( ! field ) {
			return;
		}

		initImagePickers( field );
		field.addEventListener( 'click', handleAddClick );
		field.addEventListener( 'click', handleRemoveClick );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
