( function () {
	'use strict';

	const repeater = document.querySelector( '[data-kcp-shop-hero-images]' );

	if ( ! repeater ) {
		return;
	}

	const rowsWrap = repeater.querySelector( '.kcp-repeater__rows' );
	const addButton = repeater.querySelector( '[data-kcp-add-shop-hero-image]' );
	const template = repeater.querySelector( 'template[data-kcp-shop-hero-image-template]' );

	const bindRow = ( row ) => {
		if ( window.kcpInitImagePickers ) {
			window.kcpInitImagePickers( row );
		}
	};

	const addRow = () => {
		if ( ! template || ! rowsWrap ) {
			return;
		}

		const fragment = template.content.cloneNode( true );
		const row = fragment.querySelector( '.kcp-repeater__row' );

		if ( ! row ) {
			return;
		}

		rowsWrap.appendChild( fragment );
		bindRow( row );
	};

	if ( addButton ) {
		addButton.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			addRow();
		} );
	}

	repeater.addEventListener( 'click', ( event ) => {
		const removeButton = event.target instanceof Element
			? event.target.closest( '.kcp-repeater__remove' )
			: null;

		if ( ! removeButton || ! rowsWrap ) {
			return;
		}

		event.preventDefault();

		const row = removeButton.closest( '.kcp-repeater__row' );

		if ( ! row ) {
			return;
		}

		if ( rowsWrap.children.length <= 1 ) {
			const input = row.querySelector( '.kcp-image-picker__input' );

			if ( input instanceof HTMLInputElement && window.kcpClearImagePicker ) {
				window.kcpClearImagePicker( row.querySelector( '[data-kcp-image-picker]' ) );
			}

			return;
		}

		row.remove();
	} );

	rowsWrap?.querySelectorAll( '.kcp-repeater__row' ).forEach( bindRow );
}() );
