( function () {
	'use strict';

	const NESTED_REPEATERS = {
		option_groups: {
			itemRepeater: 'option_items',
			groupSelector: '.kcp-option-group',
		},
		part_groups: {
			itemRepeater: 'part_items',
			groupSelector: '.kcp-part-group',
		},
	};

	const getRepeaterRows = ( rowsWrap ) => rowsWrap.querySelectorAll(
		':scope > .kcp-repeater__row, :scope > fieldset.kcp-repeater__row, :scope > fieldset.kcp-option-group, :scope > fieldset.kcp-part-group'
	);

	const getNestedConfig = ( repeaterType ) => NESTED_REPEATERS[ repeaterType ] || null;

	const getGroupIndex = ( group, repeaterType ) => {
		const config = getNestedConfig( repeaterType );

		if ( ! config || ! group ) {
			return 0;
		}

		const groupsRepeater = group.closest( `[data-kcp-repeater="${ repeaterType }"]` );
		const groupsWrap = groupsRepeater?.querySelector( '.kcp-repeater__rows' );

		if ( ! groupsWrap ) {
			return 0;
		}

		return Array.from( getRepeaterRows( groupsWrap ) ).indexOf( group );
	};

	const getRepeaterFromRowsWrap = ( rowsWrap ) => {
		const repeater = rowsWrap?.parentElement;

		return repeater instanceof HTMLElement && repeater.hasAttribute( 'data-kcp-repeater' )
			? repeater
			: null;
	};

	const reindexRowNames = ( row, index, type ) => {
		row.querySelectorAll( '[name]' ).forEach( ( input ) => {
			const name = input.getAttribute( 'name' );

			if ( ! name ) {
				return;
			}

			input.setAttribute(
				'name',
				name.replace(
					new RegExp( `kcp_preset\\[${ type }\\]\\[\\d+\\]` ),
					`kcp_preset[${ type }][${ index }]`
				)
			);
		} );
	};

	const reindexNestedItem = ( row, repeaterType, groupIndex, itemIndex ) => {
		row.querySelectorAll( '[name]' ).forEach( ( input ) => {
			const name = input.getAttribute( 'name' );

			if ( ! name ) {
				return;
			}

			input.setAttribute(
				'name',
				name.replace(
					new RegExp( `kcp_preset\\[${ repeaterType }\\]\\[${ groupIndex }\\]\\[items\\]\\[\\d+\\]` ),
					`kcp_preset[${ repeaterType }][${ groupIndex }][items][${ itemIndex }]`
				)
			);
		} );
	};

	const reindexNestedGroup = ( group, repeaterType, groupIndex ) => {
		group.querySelectorAll( '[name]' ).forEach( ( input ) => {
			const name = input.getAttribute( 'name' );

			if ( ! name ) {
				return;
			}

			input.setAttribute(
				'name',
				name.replace(
					new RegExp( `kcp_preset\\[${ repeaterType }\\]\\[\\d+\\]` ),
					`kcp_preset[${ repeaterType }][${ groupIndex }]`
				)
			);
		} );

		const config = getNestedConfig( repeaterType );
		const itemsRepeater = config ? group.querySelector( `[data-kcp-repeater="${ config.itemRepeater }"]` ) : null;

		if ( itemsRepeater ) {
			reindexRepeater( itemsRepeater );
		}
	};

	const reindexRepeater = ( repeater ) => {
		const type = repeater.getAttribute( 'data-kcp-repeater' );
		const rowsWrap = repeater.querySelector( '.kcp-repeater__rows' );

		if ( ! rowsWrap ) {
			return;
		}

		const rows = getRepeaterRows( rowsWrap );
		const nestedConfig = getNestedConfig( type );

		if ( nestedConfig ) {
			rows.forEach( ( row, index ) => {
				reindexNestedGroup( row, type, index );
			} );
			return;
		}

		Object.values( NESTED_REPEATERS ).forEach( ( config ) => {
			if ( config.itemRepeater !== type ) {
				return;
			}

			const parentType = Object.keys( NESTED_REPEATERS ).find(
				( key ) => NESTED_REPEATERS[ key ].itemRepeater === type
			);

			if ( ! parentType ) {
				return;
			}

			const group = repeater.closest( config.groupSelector );
			const groupIndex = getGroupIndex( group, parentType );

			rows.forEach( ( row, itemIndex ) => {
				reindexNestedItem( row, parentType, groupIndex, itemIndex );
			} );
		} );

		if ( Object.values( NESTED_REPEATERS ).some( ( config ) => config.itemRepeater === type ) ) {
			return;
		}

		rows.forEach( ( row, index ) => {
			reindexRowNames( row, index, type );
		} );
	};

	const clearRowValues = ( row ) => {
		row.querySelectorAll( 'input, textarea, select' ).forEach( ( input ) => {
			if ( input.classList.contains( 'kcp-image-picker__input' ) ) {
				return;
			}

			if ( 'checkbox' === input.type ) {
				input.checked = false;
				return;
			}

			if ( 'SELECT' === input.tagName ) {
				input.selectedIndex = 0;
				return;
			}

			input.value = 'number' === input.type ? '0' : '';
		} );

		row.querySelectorAll( '[data-kcp-image-picker]' ).forEach( ( picker ) => {
			if ( 'function' === typeof window.kcpClearImagePicker ) {
				window.kcpClearImagePicker( picker );
			}
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
		const button = event.target.closest( '[data-kcp-add]' );

		if ( ! button ) {
			return;
		}

		const repeater = button.closest( '[data-kcp-repeater]' );

		if ( ! repeater || button.closest( '[data-kcp-repeater]' ) !== repeater ) {
			return;
		}

		const rowsWrap = repeater.querySelector( '.kcp-repeater__rows' );

		if ( ! rowsWrap ) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		const rows = getRepeaterRows( rowsWrap );
		const template = rows[ rows.length - 1 ];

		if ( ! template ) {
			return;
		}

		const clone = template.cloneNode( true );
		clearRowValues( clone );
		unbindImagePickers( clone );
		rowsWrap.appendChild( clone );
		reindexRepeater( repeater );
		initImagePickers( clone );
	};

	const handleRemoveClick = ( event ) => {
		const target = event.target;

		if ( ! ( target instanceof HTMLElement ) || ! target.classList.contains( 'kcp-repeater__remove' ) ) {
			return;
		}

		const row = target.closest( '.kcp-repeater__row, fieldset.kcp-option-group, fieldset.kcp-part-group' );
		const rowsWrap = row?.parentElement;

		if ( ! row || ! rowsWrap?.classList.contains( 'kcp-repeater__rows' ) ) {
			return;
		}

		const repeater = getRepeaterFromRowsWrap( rowsWrap );

		if ( ! repeater ) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		const rows = getRepeaterRows( rowsWrap );

		if ( rows.length <= 1 ) {
			clearRowValues( row );
			return;
		}

		row.remove();
		reindexRepeater( repeater );
	};

	document.addEventListener( 'DOMContentLoaded', () => {
		const form = document.querySelector( '.kcp-product-preset-form__form' );

		if ( form ) {
			form.addEventListener( 'click', handleAddClick );
			form.addEventListener( 'click', handleRemoveClick );
		}

		document.addEventListener( 'change', ( event ) => {
			const target = event.target;

			if ( ! ( target instanceof HTMLSelectElement ) || ! target.classList.contains( 'kcp-option-group__type' ) ) {
				return;
			}

			const group = target.closest( '.kcp-option-group, .kcp-part-group' );

			if ( ! group ) {
				return;
			}

			const idInput = group.querySelector( 'input[name$="[id]"]' );

			if ( ! idInput || 'custom' === target.value ) {
				return;
			}

			idInput.value = target.value;
		} );
	} );
}() );
