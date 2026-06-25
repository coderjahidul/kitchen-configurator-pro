/**
 * Design step state helpers.
 */

import {
	loadConfiguratorState,
	saveConfiguratorState,
	saveDesignSelection,
} from './design-selection-storage.js';
import { showsHandleStrip } from './kitchen-type-config.js';

export function createDesignStore( initialConfig ) {
	const listeners = new Set();
	const persisted = loadConfiguratorState();
	const state = {
		config: initialConfig,
		selections: persisted.selections,
		kitchenType: persisted.kitchen_type,
		activeZoneId: null,
		modalOpen: false,
	};

	const getState = () => ( {
		...state,
		selections: { ...state.selections },
		kitchenType: state.kitchenType,
	} );

	const persist = () => {
		saveConfiguratorState( {
			kitchen_type: state.kitchenType,
			selections: state.selections,
		} );
	};

	const emit = () => {
		listeners.forEach( ( listener ) => listener( getState() ) );
	};

	return {
		subscribe( listener ) {
			listeners.add( listener );
			return () => listeners.delete( listener );
		},
		getState,
		getKitchenType() {
			return state.kitchenType;
		},
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
			persist();
			emit();
		},
		getZone( zoneId ) {
			return ( state.config.zones || [] ).find( ( zone ) => zone.id === zoneId ) || null;
		},
		getSelection( zoneId ) {
			return state.selections[ zoneId ] || null;
		},
		getVisibleZones() {
			return ( state.config.zones || [] ).filter( ( zone ) => {
				if ( 'handle_strip' === zone.id ) {
					return showsHandleStrip( state.kitchenType );
				}

				return true;
			} );
		},
	};
}
