/**
 * DOM and formatting utilities.
 */

/**
 * Escape HTML for safe insertion.
 *
 * @param {string} str Raw string.
 * @returns {string}
 */
export function escapeHtml( str ) {
	const div = document.createElement( 'div' );
	div.textContent = String( str ?? '' );
	return div.innerHTML;
}

/**
 * Format money amount.
 *
 * @param {number|string} amount   Amount.
 * @param {string}        currency Currency code.
 * @returns {string}
 */
export function formatMoney( amount, currency = 'EUR' ) {
	const value = Number( amount ) || 0;

	try {
		return new Intl.NumberFormat( undefined, {
			style: 'currency',
			currency,
		} ).format( value );
	} catch {
		return `${ currency } ${ value.toFixed( 2 ) }`;
	}
}

/**
 * Create element from HTML string.
 *
 * @param {string} html HTML string.
 * @returns {HTMLElement}
 */
export function html( html ) {
	const tpl = document.createElement( 'template' );
	tpl.innerHTML = html.trim();
	return tpl.content.firstElementChild;
}

/**
 * Debounce function calls.
 *
 * @param {Function} fn  Function.
 * @param {number}   ms  Delay ms.
 * @returns {Function}
 */
export function debounce( fn, ms = 400 ) {
	let timer;
	return ( ...args ) => {
		clearTimeout( timer );
		timer = setTimeout( () => fn( ...args ), ms );
	};
}

/**
 * Find entity by ID in catalog array.
 *
 * @param {Array} arr Catalog array.
 * @param {number|null} id Entity ID.
 * @returns {object|null}
 */
export function findById( arr, id ) {
	if ( ! id || ! Array.isArray( arr ) ) {
		return null;
	}
	return arr.find( ( item ) => item.id === id ) || null;
}

/**
 * Filter colors by material ID.
 *
 * @param {Array} colors     All colors.
 * @param {number|null} materialId Material ID.
 * @returns {Array}
 */
export function colorsForMaterial( colors, materialId ) {
	if ( ! materialId ) {
		return [];
	}
	return colors.filter( ( c ) => c.material_id === materialId );
}

/**
 * Filter materials by type.
 *
 * @param {Array} materials All materials.
 * @param {string} type Material type.
 * @returns {Array}
 */
export function materialsByType( materials, type ) {
	return materials.filter( ( m ) => m.material_type === type );
}
