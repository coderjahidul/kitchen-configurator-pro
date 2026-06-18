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

function getProductGallery( form ) {
	return form.closest( '.product' )?.querySelector( '.woocommerce-product-gallery' ) || null;
}

function storeOriginalGalleryImage( gallery ) {
	const image = gallery?.querySelector( '.woocommerce-product-gallery__image img' );
	const link = image?.closest( 'a' );

	if ( ! image || image.dataset.kcpOriginalSrc ) {
		return;
	}

	image.dataset.kcpOriginalSrc = image.getAttribute( 'src' ) || '';
	image.dataset.kcpOriginalSrcset = image.getAttribute( 'srcset' ) || '';
	image.dataset.kcpOriginalSizes = image.getAttribute( 'sizes' ) || '';
	image.dataset.kcpOriginalAlt = image.getAttribute( 'alt' ) || '';
	image.dataset.kcpOriginalTitle = image.getAttribute( 'title' ) || '';

	if ( link ) {
		link.dataset.kcpOriginalHref = link.getAttribute( 'href' ) || '';
	}
}

function setImageAttribute( image, name, value ) {
	if ( value ) {
		image.setAttribute( name, value );
		return;
	}

	image.removeAttribute( name );
}

function updateProductGalleryImage( form, variation ) {
	const gallery = getProductGallery( form );
	const imageData = variation?.image || {};
	const src = imageData.src || imageData.full_src || '';

	if ( ! gallery || ! src ) {
		return;
	}

	storeOriginalGalleryImage( gallery );

	const image = gallery.querySelector( '.woocommerce-product-gallery__image img' );
	const link = image?.closest( 'a' );

	if ( ! image ) {
		return;
	}

	image.setAttribute( 'src', src );
	setImageAttribute( image, 'srcset', imageData.srcset || '' );
	setImageAttribute( image, 'sizes', imageData.sizes || '' );
	setImageAttribute( image, 'alt', imageData.alt || '' );
	setImageAttribute( image, 'title', imageData.title || '' );

	if ( link && imageData.full_src ) {
		link.setAttribute( 'href', imageData.full_src );
	}

	if ( typeof jQuery !== 'undefined' ) {
		jQuery( gallery ).trigger( 'woocommerce_gallery_reset_slide_position' );
	}
}

function updateProductGalleryImageUrl( form, src, alt = '' ) {
	const gallery = getProductGallery( form );

	if ( ! gallery || ! src ) {
		return;
	}

	storeOriginalGalleryImage( gallery );

	const image = gallery.querySelector( '.woocommerce-product-gallery__image img' );
	const link = image?.closest( 'a' );

	if ( ! image ) {
		return;
	}

	image.setAttribute( 'src', src );
	image.setAttribute( 'alt', alt );
	setImageAttribute( image, 'srcset', '' );
	setImageAttribute( image, 'sizes', '' );
	setImageAttribute( image, 'title', alt );

	if ( link ) {
		link.setAttribute( 'href', src );
	}
}

function resetProductGalleryImage( form ) {
	const gallery = getProductGallery( form );
	const image = gallery?.querySelector( '.woocommerce-product-gallery__image img' );
	const link = image?.closest( 'a' );

	if ( ! image || ! image.dataset.kcpOriginalSrc ) {
		return;
	}

	image.setAttribute( 'src', image.dataset.kcpOriginalSrc );
	setImageAttribute( image, 'srcset', image.dataset.kcpOriginalSrcset || '' );
	setImageAttribute( image, 'sizes', image.dataset.kcpOriginalSizes || '' );
	setImageAttribute( image, 'alt', image.dataset.kcpOriginalAlt || '' );
	setImageAttribute( image, 'title', image.dataset.kcpOriginalTitle || '' );

	if ( link && link.dataset.kcpOriginalHref ) {
		link.setAttribute( 'href', link.dataset.kcpOriginalHref );
	}
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
	const form = root.closest( 'form.cart' );
	const basePrice = Number( root.dataset.basePrice || 0 );
	const colorInput = document.getElementById( 'kcp-selected-color' );
	const heightInput = document.getElementById( 'kcp-selected-height' );
	const priceNodes = document.querySelectorAll( '.kcp-live-price, .kcp-single-product__summary .price .kcp-price, .kcp-product-sticky__price .kcp-price' );
	const groupIds = Array.from( root.querySelectorAll( '[data-kcp-group-id]' ) ).map( ( group ) => group.dataset.kcpGroupId || '' ).filter( Boolean );
	let variations = [];

	try {
		const parsedVariations = form ? JSON.parse( form.dataset.productVariations || '[]' ) : [];
		variations = Array.isArray( parsedVariations ) ? parsedVariations : [];
	} catch ( error ) {
		variations = [];
	}

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

	const getSelectedPresetValues = () => {
		return Array.from( root.querySelectorAll( '.kcp-option-bar--active' ) )
			.map( ( button ) => button.dataset.kcpOptionId || '' )
			.filter( Boolean );
	};

	const variationHasValue = ( variation, value ) => {
		return Object.values( variation.attributes || {} ).includes( value );
	};

	const findPresetVariation = () => {
		const values = getSelectedPresetValues().filter( ( value ) => {
			return variations.some( ( variation ) => variationHasValue( variation, value ) );
		} );

		if ( ! values.length ) {
			return null;
		}

		return variations.find( ( variation ) => {
			return values.every( ( value ) => variationHasValue( variation, value ) );
		} ) || null;
	};

	const updatePresetGalleryImage = ( button ) => {
		if ( ! form || 'color' !== button.dataset.kcpOptionGroup ) {
			return;
		}

		const variation = findPresetVariation();

		if ( variation?.image?.src || variation?.image?.full_src ) {
			updateProductGalleryImage( form, variation );
			return;
		}

		const thumb = button.querySelector( '.kcp-option-bar__thumb img' );

		if ( thumb?.src ) {
			updateProductGalleryImageUrl( form, thumb.src, thumb.alt || button.textContent.trim() );
		}
	};

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
		updatePresetGalleryImage( button );

		if ( group === 'height' ) {
			updateHeightPriceLabels( root, getHeightPrice );
		}
	} );

	document.querySelectorAll( '.kcp-single-product__summary .price .kcp-price, .kcp-product-sticky__price .kcp-price' ).forEach( ( node ) => {
		node.classList.add( 'kcp-live-price' );
	} );

	updatePrices();
	updateHeightPriceLabels( root, getHeightPrice );

	const activeColor = root.querySelector( '[data-kcp-option-group="color"].kcp-option-bar--active' );

	if ( activeColor ) {
		updatePresetGalleryImage( activeColor );
	}
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
		const parsedVariations = JSON.parse( form.dataset.productVariations || '[]' );
		variations = Array.isArray( parsedVariations ) ? parsedVariations : [];
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
		updateProductGalleryImage( form, variation );
		syncActiveFromSelects();
	} );

	$form.on( 'reset_data', () => {
		updatePrices( root.dataset.basePrice );
		resetProductGalleryImage( form );
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

function initPartEditOptions( root ) {
	const itemInput = document.getElementById( 'kcp-selected-part-item' );

	initOptionButtons( root, ( button ) => {
		if ( itemInput ) {
			itemInput.value = button.dataset.kcpOptionId || '';
		}
	} );
}

document.addEventListener( 'DOMContentLoaded', () => {
	initQuantityStepper();

	const partEditRoot = document.querySelector( '[data-kcp-part-edit]' );

	if ( partEditRoot ) {
		initPartEditOptions( partEditRoot );
		return;
	}

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
