/**
 * Design step entry point.
 */

import { createDesignStore } from './design-store.js';
import { DesignStep } from './DesignStep.js';
import { syncKitchenTypeFromUrl } from './design-selection-storage.js';

document.addEventListener( 'DOMContentLoaded', () => {
	const root = document.getElementById( 'kcp-design-root' );

	if ( ! root ) {
		return;
	}

	syncKitchenTypeFromUrl();

	const config = window.kcpDesignStep || {};
	const store = createDesignStore( config );
	const app = new DesignStep( root, store );

	app.init();
} );
