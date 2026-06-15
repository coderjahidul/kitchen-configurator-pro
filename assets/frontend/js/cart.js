/**
 * Cart page interactions.
 */

document.addEventListener( 'DOMContentLoaded', () => {
	const confirmLink = ( selector, message ) => {
		document.querySelectorAll( selector ).forEach( ( link ) => {
			link.addEventListener( 'click', ( event ) => {
				if ( ! window.confirm( message ) ) {
					event.preventDefault();
				}
			} );
		} );
	};

	confirmLink(
		'.kcp-cart-part__remove',
		'Weet je zeker dat je dit artikel wilt verwijderen?'
	);

	confirmLink(
		'[data-kcp-confirm-remove-group]',
		'Weet je zeker dat je deze groep wilt verwijderen?'
	);

	confirmLink(
		'[data-kcp-confirm-empty]',
		'Weet je zeker dat je deze groep wilt leegmaken en herstellen naar standaard?'
	);

	confirmLink(
		'.kcp-cart__title-trash',
		'Weet je zeker dat je de hele winkelwagen wilt legen?'
	);

	document.querySelectorAll( '.kcp-cart-product__refresh' ).forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			window.location.reload();
		} );
	} );
} );
