/**
 * Cabinet child-list step entry point.
 */

import { CabinetListStep } from './CabinetListStep.js?v=4';
import { syncKitchenTypeFromUrl } from '../design/design-selection-storage.js?v=3';

document.addEventListener( 'DOMContentLoaded', () => {
	const root = document.getElementById( 'kcp-cabinet-list-root' );
	if ( ! root ) {
		return;
	}

	syncKitchenTypeFromUrl();

	const app = new CabinetListStep( root, window.kcpCabinetList || {} );
	app.init();
} );
