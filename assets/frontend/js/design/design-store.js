/**
 * Design step state helpers.
 */

import { saveDesignSelections, saveDesignSelection, loadDesignSelections } from './design-selection-storage.js';

export function createDesignStore( initialConfig ) {
	const listeners = new Set();
	const state = {
		config: initialConfig,
		selections: loadDesignSelections(),
		activeZoneId: null,
		modalOpen: false,
	};

	const getState = () => ( { ...state, selections: { ...state.selections } } );

	const emit = () => {
		listeners.forEach( ( listener ) => listener( getState() ) );
	};

	return {
		subscribe( listener ) {
			listeners.add( listener );
			return () => listeners.delete( listener );
		},
		getState,
		setActiveZone( zoneId ) {
			state.activeZoneId = zoneId;
			state.modalOpen = null !== zoneId;
			emit();
		},
		closeModal() {
			state.activeZoneId = null;
			state.modalOpen = false;
			emit();
		},
		selectColor( zoneId, color ) {
			state.selections[ zoneId ] = color;
			state.activeZoneId = null;
			state.modalOpen = false;
			saveDesignSelection( zoneId, color );
			saveDesignSelections( state.selections );
			emit();
		},
		getZone( zoneId ) {
			return ( state.config.zones || [] ).find( ( zone ) => zone.id === zoneId ) || null;
		},
		getSelection( zoneId ) {
			return state.selections[ zoneId ] || null;
		},
	};
}
