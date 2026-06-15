/**
 * Single product color and height option selectors.
 */

function formatDutchPrice( amount ) {
	return `${ Math.round( amount ).toLocaleString( 'nl-NL' ) },-`;
}

function initQuantityStepper() {
	const cart = document.querySelector( '.kcp-single-product__cart .cart' );

	if ( ! cart ) {
		return;
	}

	const quantity = cart.querySelector( '.quantity' );

	if ( ! quantity || quantity.classList.contains( 'kcp-qty--ready' ) ) {
		return;
	}

	const input = quantity.querySelector( '.qty' );

	if ( ! input ) {
		return;
	}

	quantity.classList.add( 'kcp-qty', 'kcp-qty--ready' );

	const minus = document.createElement( 'button' );
	minus.type = 'button';
	minus.className = 'kcp-qty__btn';
	minus.setAttribute( 'aria-label', 'Decrease quantity' );
	minus.textContent = '−';

	const plus = document.createElement( 'button' );
	plus.type = 'button';
	plus.className = 'kcp-qty__btn';
	plus.setAttribute( 'aria-label', 'Increase quantity' );
	plus.textContent = '+';

	quantity.insertBefore( minus, input );
	quantity.appendChild( plus );

	const step = () => Number( input.step || 1 );
	const min = () => Number( input.min || 1 );
	const max = () => ( input.max ? Number( input.max ) : null );

	const setValue = ( next ) => {
		const ceiling = max();
		let value = Math.max( min(), next );

		if ( null !== ceiling ) {
			value = Math.min( ceiling, value );
		}

		input.value = String( value );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	};

	minus.addEventListener( 'click', () => {
		setValue( Number( input.value || min() ) - step() );
	} );

	plus.addEventListener( 'click', () => {
		setValue( Number( input.value || min() ) + step() );
	} );
}

function updateHeightPriceLabels( root, getHeightPrice ) {
	const activeHeight = root.querySelector( '[data-kcp-option-group="height"].kcp-option-bar--active' );

	if ( ! activeHeight || typeof getHeightPrice !== 'function' ) {
		return;
	}

	const selectedPrice = getHeightPrice( activeHeight );

	if ( null === selectedPrice || Number.isNaN( selectedPrice ) ) {
		return;
	}

	root.querySelectorAll( '[data-kcp-option-group="height"]' ).forEach( ( button ) => {
		let priceEl = button.querySelector( '.kcp-option-bar__price' );
		const isActive = button.classList.contains( 'kcp-option-bar--active' );
		const optionPrice = getHeightPrice( button );
		const diff = null !== optionPrice ? optionPrice - selectedPrice : 0;

		if ( isActive || 0 === diff ) {
			if ( priceEl ) {
				priceEl.hidden = true;
			}
			return;
		}

		if ( ! priceEl ) {
			priceEl = document.createElement( 'span' );
			priceEl.className = 'kcp-option-bar__price';
			button.appendChild( priceEl );
		}

		priceEl.hidden = false;
		priceEl.textContent = diff > 0
			? `+${ formatDutchPrice( diff ) }`
			: `-${ formatDutchPrice( Math.abs( diff ) ) }`;
	} );
}

function initOptionButtons( root, onChange ) {
	root.querySelectorAll( '.kcp-option-bar' ).forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			const group = button.dataset.kcpOptionGroup;

			root.querySelectorAll( `[data-kcp-option-group="${ group }"]` ).forEach( ( item ) => {
				item.classList.remove( 'kcp-option-bar--active' );
				item.setAttribute( 'aria-pressed', 'false' );
			} );

			button.classList.add( 'kcp-option-bar--active' );
			button.setAttribute( 'aria-pressed', 'true' );

			if ( typeof onChange === 'function' ) {
				onChange( button, group );
			}
		} );
	} );
}

function initPresetOptions( root ) {
	const basePrice = Number( root.dataset.basePrice || 0 );
	const colorInput = document.getElementById( 'kcp-selected-color' );
	const heightInput = document.getElementById( 'kcp-selected-height' );
	const priceNodes = document.querySelectorAll( '.kcp-live-price, .kcp-single-product__summary .price .kcp-price, .kcp-product-sticky__price .kcp-price' );
	const groupIds = Array.from( root.querySelectorAll( '[data-kcp-group-id]' ) ).map( ( group ) => group.dataset.kcpGroupId || '' ).filter( Boolean );

	const getActiveModifier = ( group ) => {
		const active = root.querySelector( `[data-kcp-option-group="${ group }"].kcp-option-bar--active` );
		return active ? Number( active.dataset.kcpOptionModifier || 0 ) : 0;
	};

	const updatePrices = () => {
		let total = basePrice;

		groupIds.forEach( ( groupId ) => {
			total += getActiveModifier( groupId );
		} );

		const formatted = formatDutchPrice( total );

		priceNodes.forEach( ( node ) => {
			node.textContent = formatted;
		} );
	};

	const getHeightPrice = ( button ) => basePrice + Number( button.dataset.kcpOptionModifier || 0 );

	initOptionButtons( root, ( button, group ) => {
		const optionInput = document.getElementById( `kcp-selected-${ group }` );

		if ( optionInput ) {
			optionInput.value = button.dataset.kcpOptionId || '';
		}

		if ( group === 'color' && colorInput ) {
			colorInput.value = button.dataset.kcpOptionId || '';
		}

		if ( group === 'height' && heightInput ) {
			heightInput.value = button.dataset.kcpOptionId || '';
		}

		updatePrices();

		if ( group === 'height' ) {
			updateHeightPriceLabels( root, getHeightPrice );
		}
	} );

	document.querySelectorAll( '.kcp-single-product__summary .price .kcp-price, .kcp-product-sticky__price .kcp-price' ).forEach( ( node ) => {
		node.classList.add( 'kcp-live-price' );
	} );

	updatePrices();
	updateHeightPriceLabels( root, getHeightPrice );
}

function initVariationOptions( root ) {
	const form = root.closest( 'form.variations_form' );

	if ( ! form || typeof jQuery === 'undefined' ) {
		return;
	}

	const $form = jQuery( form );
	const priceNodes = document.querySelectorAll( '.kcp-live-price, .kcp-single-product__summary .price .kcp-price' );

	const updatePrices = ( amount ) => {
		const formatted = formatDutchPrice( Number( amount || root.dataset.basePrice || 0 ) );

		priceNodes.forEach( ( node ) => {
			node.textContent = formatted;
		} );
	};

	const syncSelect = ( button ) => {
		const attribute = button.dataset.kcpWcAttribute;
		const value = button.dataset.kcpWcValue;

		if ( ! attribute || ! value ) {
			return;
		}

		const select = form.querySelector( `select[name="attribute_${ attribute }"]` );

		if ( ! select ) {
			return;
		}

		select.value = value;
		jQuery( select ).trigger( 'change' );
	};

	const colorAttribute = root.dataset.colorAttribute || '';
	const heightAttribute = root.dataset.heightAttribute || '';
	let variations = [];

	try {
		variations = JSON.parse( form.dataset.productVariations || '[]' );
	} catch ( error ) {
		variations = [];
	}

	const getSelectedAttributeValue = ( attribute ) => {
		if ( ! attribute ) {
			return '';
		}

		const select = form.querySelector( `select[name="attribute_${ attribute }"]` );
		return select ? select.value : '';
	};

	const getVariationPrice = ( colorValue, heightValue ) => {
		const match = variations.find( ( variation ) => {
			const attrs = variation.attributes || {};

			if ( colorAttribute && colorValue && attrs[ `attribute_${ colorAttribute }` ] !== colorValue ) {
				return false;
			}

			if ( heightAttribute && heightValue && attrs[ `attribute_${ heightAttribute }` ] !== heightValue ) {
				return false;
			}

			return true;
		} );

		return match ? Number( match.display_price ) : null;
	};

	const getHeightPrice = ( button ) => {
		const fromVariation = getVariationPrice(
			getSelectedAttributeValue( colorAttribute ),
			button.dataset.kcpWcValue || ''
		);

		if ( null !== fromVariation && ! Number.isNaN( fromVariation ) ) {
			return fromVariation;
		}

		return Number( root.dataset.basePrice || 0 ) + Number( button.dataset.kcpOptionModifier || 0 );
	};

	const syncActiveFromSelects = () => {
		root.querySelectorAll( '.kcp-option-bar' ).forEach( ( button ) => {
			const attribute = button.dataset.kcpWcAttribute;
			const value = button.dataset.kcpWcValue;
			const select = attribute ? form.querySelector( `select[name="attribute_${ attribute }"]` ) : null;
			const isActive = select && select.value === value;

			button.classList.toggle( 'kcp-option-bar--active', isActive );
			button.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
		} );

		updateHeightPriceLabels( root, getHeightPrice );
	};

	initOptionButtons( root, ( button ) => {
		syncSelect( button );
	} );

	$form.on( 'found_variation', ( event, variation ) => {
		updatePrices( variation.display_price );
		syncActiveFromSelects();
	} );

	$form.on( 'reset_data', () => {
		updatePrices( root.dataset.basePrice );
		syncActiveFromSelects();
	} );

	$form.on( 'woocommerce_update_variation_values', syncActiveFromSelects );

	document.querySelectorAll( '.kcp-single-product__summary .price .kcp-price' ).forEach( ( node ) => {
		node.classList.add( 'kcp-live-price' );
	} );

	syncActiveFromSelects();
	updatePrices( root.dataset.basePrice );
	$form.trigger( 'check_variations' );
}

document.addEventListener( 'DOMContentLoaded', () => {
	initQuantityStepper();

	const root = document.querySelector( '.kcp-product-options' );

	if ( ! root ) {
		return;
	}

	if ( '1' === root.dataset.wcVariations ) {
		initVariationOptions( root );
		return;
	}

	initPresetOptions( root );
} );
