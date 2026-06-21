/**
 * Zone-specific modal copy (reference configurator).
 */

export const ZONE_MODAL_COPY = {
	front: {
		title: 'selecteer een frontkleur',
		topHeading: 'top 5 meest gekozen kleuren',
		moreHeading: 'meer kleuren',
		selectLabel: 'selecteer kleur',
	},
	handle_strip: {
		title: 'selecteer een greep of knop',
		topHeading: 'top 5 meest gekozen grepen',
		moreHeading: 'meer grepen',
		selectLabel: 'selecteer greep',
	},
	cabinet: {
		title: 'selecteer een kastkleur',
		topHeading: 'top 5 meest gekozen kastkleuren',
		moreHeading: 'meer kastkleuren',
		selectLabel: 'selecteer kastkleur',
	},
	plinth: {
		title: 'selecteer een plintkleur',
		topHeading: 'top 5 meest gekozen plintkleuren',
		moreHeading: 'meer plintkleuren',
		selectLabel: 'selecteer plintkleur',
	},
};

/**
 * @param {string} zoneId Zone identifier.
 * @return {object}
 */
export function getModalCopy( zoneId ) {
	return ZONE_MODAL_COPY[ zoneId ] || {
		title: 'selecteer een optie',
		topHeading: 'top 5 meest gekozen opties',
		moreHeading: 'meer opties',
		selectLabel: 'selecteer optie',
	};
}

/**
 * @param {object}   option  Catalog option.
 * @param {object[]} options All options in zone.
 * @param {number}   max     Max dots.
 * @return {number}
 */
export function resolvePriceClass( option, options, max = 6 ) {
	const values = options.map( ( item ) => Number( item.price_modifier ?? item.price ?? 0 ) );
	const price = Number( option.price_modifier ?? option.price ?? 0 );
	const maxVal = Math.max( ...values, 0 );
	const minVal = Math.min( ...values, 0 );

	if ( maxVal <= minVal ) {
		return Math.min( 2, max );
	}

	const ratio = ( price - minVal ) / ( maxVal - minVal );

	return Math.max( 1, Math.min( max, Math.round( ratio * ( max - 1 ) ) + 1 ) );
}

/**
 * @param {number} level Filled dots.
 * @param {number} max   Total dots.
 * @return {string}
 */
export function renderPriceDots( level, max = 6 ) {
	return Array.from( { length: max }, ( _, index ) => {
		const filled = index + 1 <= level;

		return `<span class="kcp-design-modal__dot${ filled ? ' is-filled' : '' }"></span>`;
	} ).join( '' );
}
