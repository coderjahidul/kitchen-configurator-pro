/**
 * Cabinet group step entry point.
 */

import { CabinetGroupStep } from './CabinetGroupStep.js';
import { syncKitchenTypeFromUrl } from '../design/design-selection-storage.js';

document.addEventListener( 'DOMContentLoaded', () => {
	const root = document.getElementById( 'kcp-cabinet-group-root' );
	if ( ! root ) {
		return;
	}

	syncKitchenTypeFromUrl();

	const app = new CabinetGroupStep( root, window.kcpCabinetGroup || {} );
	app.init();
} );
