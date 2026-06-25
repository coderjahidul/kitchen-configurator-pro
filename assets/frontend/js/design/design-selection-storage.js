/**
 * Persist design step selections across pages.
 */

export {
	CONFIGURATOR_STATE_KEY,
	DESIGN_SELECTIONS_KEY,
	loadConfiguratorState,
	loadDesignSelections,
	loadKitchenType,
	saveConfiguratorState,
	saveDesignSelection,
	saveDesignSelections,
	saveKitchenType,
	syncKitchenTypeFromUrl,
	withKitchenTypeParam,
} from './configurator-state.js';
