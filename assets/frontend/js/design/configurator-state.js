/**
 * Cross-step configurator state (selections + kitchen type).
 * Persists in localStorage for refresh/back-forward; mirrors to sessionStorage.
 */

import {
	DEFAULT_KITCHEN_TYPE,
	KITCHEN_TYPE_PARAM,
	normalizeKitchenType,
} from './kitchen-type-config.js';

export const CONFIGURATOR_STATE_KEY = 'kcp_configurator_state';
export const DESIGN_SELECTIONS_KEY = 'kcp_design_selections';

/**
 * @return {{ kitchen_type: string, selections: object }}
 */
function emptyState() {
	return {
		kitchen_type: DEFAULT_KITCHEN_TYPE,
		selections: {},
	};
}

/**
 * @param {unknown} raw Parsed storage value.
 * @return {{ kitchen_type: string, selections: object }}
 */
function normalizeState( raw ) {
	if ( ! raw || 'object' !== typeof raw ) {
		return emptyState();
	}

	const selections = raw.selections && 'object' === typeof raw.selections
		? { ...raw.selections }
		: { ...raw };

	if ( selections.kitchen_type ) {
		delete selections.kitchen_type;
	}

	return {
		kitchen_type: normalizeKitchenType( raw.kitchen_type ),
		selections,
	};
}

/**
 * @return {{ kitchen_type: string, selections: object }}
 */
export function loadConfiguratorState() {
	try {
		const localRaw = localStorage.getItem( CONFIGURATOR_STATE_KEY );

		if ( localRaw ) {
			return normalizeState( JSON.parse( localRaw ) );
		}
	} catch ( error ) {
		// Fall through to sessionStorage.
	}

	try {
		const sessionRaw = sessionStorage.getItem( DESIGN_SELECTIONS_KEY );

		if ( sessionRaw ) {
			const parsed = JSON.parse( sessionRaw );

			if ( parsed && 'object' === typeof parsed && parsed.selections ) {
				return normalizeState( parsed );
			}

			return {
				kitchen_type: DEFAULT_KITCHEN_TYPE,
				selections: parsed && 'object' === typeof parsed ? parsed : {},
			};
		}
	} catch ( error ) {
		// Ignore parse errors.
	}

	return emptyState();
}

/**
 * @param {{ kitchen_type?: string, selections?: object }} state
 */
export function saveConfiguratorState( state ) {
	const payload = {
		kitchen_type: normalizeKitchenType( state?.kitchen_type || DEFAULT_KITCHEN_TYPE ),
		selections: state?.selections && 'object' === typeof state.selections
			? { ...state.selections }
			: {},
	};

	try {
		localStorage.setItem( CONFIGURATOR_STATE_KEY, JSON.stringify( payload ) );
	} catch ( error ) {
		// Ignore storage errors.
	}

	try {
		sessionStorage.setItem( DESIGN_SELECTIONS_KEY, JSON.stringify( payload.selections ) );
	} catch ( error ) {
		// Ignore storage errors.
	}
}

/**
 * @return {object}
 */
export function loadDesignSelections() {
	return { ...loadConfiguratorState().selections };
}

/**
 * @param {object} selections Zone selections keyed by zone id.
 */
export function saveDesignSelections( selections ) {
	const current = loadConfiguratorState();

	saveConfiguratorState( {
		kitchen_type: current.kitchen_type,
		selections,
	} );
}

/**
 * @param {string} zoneId   Design zone id.
 * @param {object} option   Selected catalog option.
 */
export function saveDesignSelection( zoneId, option ) {
	const current = loadConfiguratorState();
	const selections = { ...current.selections };

	selections[ zoneId ] = option;
	saveConfiguratorState( {
		kitchen_type: current.kitchen_type,
		selections,
	} );
}

/**
 * @return {string}
 */
export function loadKitchenType() {
	return loadConfiguratorState().kitchen_type;
}

/**
 * @param {string} kitchenType Kitchen type slug.
 */
export function saveKitchenType( kitchenType ) {
	const current = loadConfiguratorState();

	saveConfiguratorState( {
		kitchen_type: normalizeKitchenType( kitchenType ),
		selections: current.selections,
	} );
}

/**
 * Read `kcp_kitchen_type` from the current URL and persist when present.
 */
export function syncKitchenTypeFromUrl() {
	try {
		const params = new URLSearchParams( window.location.search );
		const fromUrl = params.get( KITCHEN_TYPE_PARAM );

		if ( fromUrl ) {
			saveKitchenType( fromUrl );
		}
	} catch ( error ) {
		// Ignore URL parsing errors.
	}
}

/**
 * @param {string} kitchenType Kitchen type slug.
 * @return {string}
 */
export function withKitchenTypeParam( url, kitchenType ) {
	const value = normalizeKitchenType( kitchenType );

	if ( ! url ) {
		return '';
	}

	try {
		const parsed = new URL( url, window.location.origin );
		parsed.searchParams.set( KITCHEN_TYPE_PARAM, value );
		return parsed.toString();
	} catch ( error ) {
		return url;
	}
}
