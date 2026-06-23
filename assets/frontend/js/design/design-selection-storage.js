/**
 * Persist design step selections across pages.
 */

export const DESIGN_SELECTIONS_KEY = 'kcp_design_selections';

export function saveDesignSelections( selections ) {
	try {
		sessionStorage.setItem( DESIGN_SELECTIONS_KEY, JSON.stringify( selections ) );
	} catch ( error ) {
		// Ignore storage errors.
	}
}

export function loadDesignSelections() {
	try {
		const raw = sessionStorage.getItem( DESIGN_SELECTIONS_KEY );
		if ( ! raw ) {
			return {};
		}
		const parsed = JSON.parse( raw );
		return parsed && 'object' === typeof parsed ? parsed : {};
	} catch ( error ) {
		return {};
	}
}

export function saveDesignSelection( zoneId, option ) {
	const selections = loadDesignSelections();
	selections[ zoneId ] = option;
	saveDesignSelections( selections );
}
