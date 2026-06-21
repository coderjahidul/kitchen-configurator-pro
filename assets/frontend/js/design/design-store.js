/**
 * Design step state helpers.
 */

export function createDesignStore( initialConfig ) {
	const listeners = new Set();
	const state = {
		config: initialConfig,
		selections: {},
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
