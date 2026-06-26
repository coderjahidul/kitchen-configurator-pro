/**
 * Cabinet detail step entry point.
 */

import { CabinetDetailStep } from './CabinetDetailStep.js';
import { syncKitchenTypeFromUrl } from '../design/design-selection-storage.js';

document.addEventListener( 'DOMContentLoaded', () => {
	const root = document.getElementById( 'kcp-cabinet-detail-root' );
	if ( ! root ) {
		return;
	}

	syncKitchenTypeFromUrl();

	const app = new CabinetDetailStep( root, window.kcpCabinetDetail || {} );
	app.init();
} );
