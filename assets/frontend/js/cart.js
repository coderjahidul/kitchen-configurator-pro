/**
 * Cart page interactions.
 */

function formatDutchCartPrice( amount ) {
	return `${ Math.round( Number( amount ) || 0 ).toLocaleString( 'nl-NL' ) },-`;
}

function parseDisplayedCartTotal( totalNode ) {
	const digits = ( totalNode?.textContent || '' ).replace( /[^\d]/g, '' );
	return Number( digits ) || 0;
}

function readDesignCheckTotals() {
	const totalNode = document.querySelector( '[data-kcp-cart-total]' );
	const block = document.querySelector( '[data-kcp-design-check]' );

	if ( ! totalNode ) {
		return null;
	}

	const designCheckPrice = Number(
		totalNode.dataset.designCheckPrice
		|| block?.dataset.designCheckPrice
		|| window.kcpCart?.designCheckPrice
		|| 0
	);
	const selected = totalNode.dataset.designCheckSelected
		|| window.kcpCart?.selected
		|| 'no';
	let baseTotal = Number( totalNode.dataset.baseTotal || window.kcpCart?.baseTotal || 0 );

	if ( baseTotal <= 0 ) {
		const displayed = parseDisplayedCartTotal( totalNode );
		baseTotal = 'yes' === selected ? Math.max( 0, displayed - designCheckPrice ) : displayed;
	}

	return {
		totalNode,
		baseTotal,
		designCheckPrice,
		selected,
	};
}

function updateCartTotalDisplay( selected ) {
	const totals = readDesignCheckTotals();

	if ( ! totals ) {
		return;
	}

	const total = 'yes' === selected
		? totals.baseTotal + totals.designCheckPrice
		: totals.baseTotal;

	totals.totalNode.textContent = `€ ${ formatDutchCartPrice( total ) }`;
	totals.totalNode.dataset.designCheckSelected = selected;

	if ( window.kcpCart ) {
		window.kcpCart.selected = selected;
		window.kcpCart.baseTotal = totals.baseTotal;
	}
}

function syncDesignCheckChoice( selected ) {
	updateCartTotalDisplay( selected );

	if ( ! window.kcpCart?.ajaxUrl || ! window.kcpCart?.nonce ) {
		return;
	}

	const formData = new FormData();
	formData.append( 'action', 'kcp_design_check' );
	formData.append( 'nonce', kcpCart.nonce );
	formData.append( 'value', selected );

	fetch( kcpCart.ajaxUrl, {
		method: 'POST',
		body: formData,
		credentials: 'same-origin',
	} )
		.then( ( response ) => response.json() )
		.then( ( payload ) => {
			if ( ! payload?.success || ! payload.data?.total ) {
				return;
			}

			const totalNode = document.querySelector( '[data-kcp-cart-total]' );

			if ( totalNode ) {
				totalNode.textContent = `€ ${ payload.data.total }`;

				if ( typeof payload.data.base_total !== 'undefined' ) {
					totalNode.dataset.baseTotal = String( payload.data.base_total );
				}
			}

			if ( window.kcpCart ) {
				if ( typeof payload.data.base_total !== 'undefined' ) {
					window.kcpCart.baseTotal = Number( payload.data.base_total );
				}

				window.kcpCart.selected = payload.data.selected || selected;
			}
		} )
		.catch( () => {
			// Keep the optimistic total when the request fails.
		} );
}

function initDesignCheck() {
	const block = document.querySelector( '[data-kcp-design-check]' );

	if ( ! block ) {
		return;
	}

	const totals = readDesignCheckTotals();

	if ( totals ) {
		updateCartTotalDisplay( totals.selected );
	}

	if ( block.dataset.kcpDesignCheckReady === '1' ) {
		return;
	}

	block.dataset.kcpDesignCheckReady = '1';

	block.addEventListener( 'change', ( event ) => {
		const target = event.target;

		if ( ! ( target instanceof HTMLInputElement ) || target.name !== 'kcp_design_check' ) {
			return;
		}

		syncDesignCheckChoice( target.value );
	} );
}

window.kcpInitDesignCheck = initDesignCheck;

/* ── Afspraak modal ─────────────────────────────────────────── */
function initAfspraakModal() {
	const modal = document.getElementById( 'kcp-afspraak-modal' );

	if ( ! modal ) {
		return;
	}

	const openModal = () => {
		modal.hidden = false;
		document.body.style.overflow = 'hidden';
		modal.querySelector( '[name="kcp_naam"]' )?.focus();
	};

	const closeModal = () => {
		modal.hidden = true;
		document.body.style.overflow = '';
	};

	document.querySelectorAll( '[data-kcp-open-afspraak]' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', openModal );
	} );

	modal.querySelectorAll( '[data-kcp-close-afspraak]' ).forEach( ( el ) => {
		el.addEventListener( 'click', closeModal );
	} );

	document.addEventListener( 'keydown', ( e ) => {
		if ( e.key === 'Escape' && ! modal.hidden ) {
			closeModal();
		}
	} );

	const form = modal.querySelector( '#kcp-afspraak-form' );
	const successMsg = modal.querySelector( '.kcp-afspraak-modal__success' );
	const errorMsg   = modal.querySelector( '.kcp-afspraak-modal__error' );
	const submitBtn  = modal.querySelector( '.kcp-afspraak-modal__submit' );

	if ( ! form ) {
		return;
	}

	form.addEventListener( 'submit', async ( e ) => {
		e.preventDefault();

		if ( successMsg ) {
			successMsg.hidden = true;
		}

		if ( errorMsg ) {
			errorMsg.hidden = true;
		}

		if ( submitBtn ) {
			submitBtn.disabled = true;
		}

		const data = new FormData( form );
		data.append( 'action', 'kcp_afspraak' );

		try {
			const response = await fetch(
				( window.kcpCart?.ajaxUrl ) || '/wp-admin/admin-ajax.php',
				{ method: 'POST', body: data, credentials: 'same-origin' }
			);
			const payload = await response.json();

			if ( payload?.success ) {
				form.querySelectorAll( 'input, select' ).forEach( ( f ) => { f.value = ''; } );

				if ( successMsg ) {
					successMsg.hidden = false;
				}
			} else {
				throw new Error( 'fail' );
			}
		} catch {
			if ( errorMsg ) {
				errorMsg.hidden = false;
			}
		} finally {
			if ( submitBtn ) {
				submitBtn.disabled = false;
			}
		}
	} );
}

/* ── Save cart modal ────────────────────────────────────────── */
function initSaveCartModal() {
	const modal = document.getElementById( 'kcp-savecart-modal' );

	if ( ! modal ) {
		return;
	}

	const openModal = () => {
		modal.hidden = false;
		document.body.style.overflow = 'hidden';
		modal.querySelector( '[name="kcp_savecart_email"]' )?.focus();
	};

	const closeModal = () => {
		modal.hidden = true;
		document.body.style.overflow = '';
	};

	document.querySelectorAll( '[data-kcp-open-savecart]' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', openModal );
	} );

	modal.querySelectorAll( '[data-kcp-close-savecart]' ).forEach( ( el ) => {
		el.addEventListener( 'click', closeModal );
	} );

	document.addEventListener( 'keydown', ( e ) => {
		if ( e.key === 'Escape' && ! modal.hidden ) {
			closeModal();
		}
	} );

	const form = modal.querySelector( '#kcp-savecart-form' );
	const successMsg = modal.querySelector( '.kcp-savecart-modal__success' );

	if ( ! form ) {
		return;
	}

	form.addEventListener( 'submit', ( e ) => {
		e.preventDefault();

		const emailInput = form.querySelector( '[name="kcp_savecart_email"]' );

		if ( ! emailInput || ! emailInput.value ) {
			return;
		}

		if ( successMsg ) {
			successMsg.hidden = false;
		}
	} );
}

function initConfirmModal() {
	const modal   = document.getElementById( 'kcp-confirm-modal' );
	const msgEl   = modal ? modal.querySelector( '.kcp-confirm-modal__message' ) : null;
	const proceed = modal ? modal.querySelector( '#kcp-confirm-proceed' ) : null;

	if ( ! modal || ! msgEl || ! proceed ) {
		return;
	}

	const openConfirm = ( href, message, confirmLabel ) => {
		msgEl.textContent    = message;
		proceed.href         = href;
		proceed.textContent  = confirmLabel || 'Ja, bevestigen';
		modal.hidden         = false;
		document.body.style.overflow = 'hidden';
	};

	const closeConfirm = () => {
		modal.hidden = true;
		document.body.style.overflow = '';
	};

	modal.querySelectorAll( '[data-kcp-close-confirm]' ).forEach( ( el ) => {
		el.addEventListener( 'click', closeConfirm );
	} );

	document.addEventListener( 'keydown', ( e ) => {
		if ( e.key === 'Escape' && ! modal.hidden ) {
			closeConfirm();
		}
	} );

	const attachConfirm = ( selector, message, confirmLabel ) => {
		document.querySelectorAll( selector ).forEach( ( link ) => {
			link.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				openConfirm( link.href, message, confirmLabel );
			} );
		} );
	};

	attachConfirm(
		'[data-kcp-confirm-empty]',
		'Weet je zeker dat je deze groep wilt bewaren, maar alle artikelen uit de winkelwagen wilt verwijderen?',
		'Ja, leegmaken'
	);

	attachConfirm(
		'[data-kcp-confirm-remove-group]',
		'Weet je zeker dat je deze groep wilt verwijderen?',
		'Ja, verwijderen'
	);

	attachConfirm(
		'.kcp-cart-part__remove',
		'Weet je zeker dat je dit artikel wilt verwijderen?',
		'Ja, verwijderen'
	);

	attachConfirm(
		'.kcp-cart__title-trash',
		'Weet je zeker dat je de hele winkelwagen wilt legen?',
		'Ja, legen'
	);
}

function bootCartPage() {

	document.querySelectorAll( '.kcp-cart-product__refresh' ).forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			window.location.reload();
		} );
	} );

	initConfirmModal();
	initDesignCheck();
	initAfspraakModal();
	initSaveCartModal();
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', bootCartPage );
} else {
	bootCartPage();
}
