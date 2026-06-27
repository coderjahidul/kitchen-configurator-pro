/**
 * Cabinet detail step entry point.
 */

import { syncKitchenTypeFromUrl } from '../design/design-selection-storage.js';

function resolveModuleVersion() {
	const script = document.querySelector( 'script[src*="cabinet-detail/main.js"]' );
	const src = script?.getAttribute( 'src' ) || '';
	const match = src.match( /[?&]ver=([^&]+)/ );

	return match?.[ 1 ] || String( Date.now() );
}

document.addEventListener( 'DOMContentLoaded', async () => {
	const root = document.getElementById( 'kcp-cabinet-detail-root' );

	if ( ! root ) {
		return;
	}

	syncKitchenTypeFromUrl();

	const version = resolveModuleVersion();
	const { CabinetDetailStep } = await import( `./CabinetDetailStep.js?ver=${ encodeURIComponent( version ) }` );
	const app = new CabinetDetailStep( root, window.kcpCabinetDetail || {} );
	app.init();
} );
