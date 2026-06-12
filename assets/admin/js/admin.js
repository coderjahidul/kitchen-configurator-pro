( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.kcp-delete-link' ).forEach( function ( link ) {
			link.addEventListener( 'click', function ( event ) {
				if ( ! window.confirm( 'Are you sure you want to delete this item?' ) ) {
					event.preventDefault();
				}
			} );
		} );
	} );
}() );
