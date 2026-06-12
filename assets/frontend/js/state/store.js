/**
 * Lightweight pub/sub store.
 */

/**
 * @param {object} initial Initial state.
 * @returns {object}
 */
export function createStore( initial = {} ) {
	let state = { ...initial };
	const listeners = new Set();

	return {
		getState() {
			return state;
		},

		setState( partial ) {
			state = { ...state, ...partial };
			listeners.forEach( ( fn ) => fn( state ) );
		},

		patchConfig( partial ) {
			state = {
				...state,
				config: {
					...state.config,
					...partial,
				},
			};
			listeners.forEach( ( fn ) => fn( state ) );
		},

		updateCabinet( index, partial ) {
			const cabinets = [ ...state.config.cabinets ];
			cabinets[ index ] = { ...cabinets[ index ], ...partial };
			this.patchConfig( { cabinets } );
		},

		updateCabinetDimensions( index, dimensions ) {
			const cabinets = [ ...state.config.cabinets ];
			cabinets[ index ] = {
				...cabinets[ index ],
				dimensions: { ...cabinets[ index ].dimensions, ...dimensions },
			};
			this.patchConfig( { cabinets } );
		},

		updateGlobalOptions( partial ) {
			this.patchConfig( {
				global_options: {
					...state.config.global_options,
					...partial,
				},
			} );
		},

		subscribe( fn ) {
			listeners.add( fn );
			return () => listeners.delete( fn );
		},
	};
}

export const STEPS = [
	'layout',
	'cabinets',
	'finishes',
	'extras',
	'summary',
];

/**
 * Create empty cabinet item from catalog entity.
 *
 * @param {object} cabinet Cabinet catalog item.
 * @param {number} index   Position index.
 * @returns {object}
 */
export function createCabinetItem( cabinet, index = 0 ) {
	return {
		cabinet_id: cabinet.id,
		material_id: null,
		color_id: null,
		handle_id: null,
		dimensions: {
			width: cabinet.default_width,
			height: cabinet.default_height,
			depth: cabinet.default_depth,
		},
		position: { x: index * 650, y: 0, rotation: 0 },
		accessories: [],
	};
}

/**
 * Build API payload from store config.
 *
 * @param {object} config Store config object.
 * @returns {object}
 */
export function buildPayload( config ) {
	return {
		schema_version: config.schema_version || '1.0',
		layout_id: config.layout_id,
		title: config.title || '',
		cabinets: config.cabinets,
		global_options: config.global_options || {},
	};
}
