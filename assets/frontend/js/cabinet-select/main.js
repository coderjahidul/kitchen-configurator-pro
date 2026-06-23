import { CabinetSelectStep } from './CabinetSelectStep.js';

document.addEventListener( 'DOMContentLoaded', () => {
	const root = document.getElementById( 'kcp-cabinet-select-root' );
	if ( ! root ) {
		return;
	}
	const app = new CabinetSelectStep( root, window.kcpCabinetSelect || {} );
	app.init();
} );
