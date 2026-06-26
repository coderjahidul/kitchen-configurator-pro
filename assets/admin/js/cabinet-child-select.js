/**
 * Filter helper for child cabinet checkbox list.
 */

( function () {
	function initField( root ) {
		const filterInput = root.querySelector( '.kcp-child-cabinets-field__filter' );
		const options = root.querySelectorAll( '.kcp-child-cabinets-field__option' );

		if ( ! filterInput || ! options.length ) {
			return;
		}

		filterInput.addEventListener( 'input', () => {
			const query = filterInput.value.trim().toLowerCase();

			options.forEach( ( option ) => {
				const label = String( option.dataset.label || '' );
				option.hidden = '' !== query && ! label.includes( query );
			} );
		} );
	}

	function boot() {
		document.querySelectorAll( '.kcp-child-cabinets-field' ).forEach( initField );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
