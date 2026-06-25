/**
 * Cabinet select step entry point.
 */

import { CabinetSelectStep } from './CabinetSelectStep.js';
import { syncKitchenTypeFromUrl } from '../design/design-selection-storage.js';

document.addEventListener( 'DOMContentLoaded', () => {
	const root = document.getElementById( 'kcp-cabinet-select-root' );
	if ( ! root ) {
		return;
	}

	syncKitchenTypeFromUrl();

	const app = new CabinetSelectStep( root, window.kcpCabinetSelect || {} );
	app.init();
} );
