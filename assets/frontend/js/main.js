/**
 * Kitchen Configurator Pro — frontend entry point.
 */

import { KcpApi } from './api/client.js';
import { createStore } from './state/store.js';
import { App } from './components/App.js';

document.addEventListener( 'DOMContentLoaded', () => {
	const root = document.getElementById( 'kcp-configurator-root' );

	if ( ! root ) {
		return;
	}

	const boot = window.kcpConfigurator || {};
	const api = new KcpApi( boot );

	const store = createStore( {
		loading: true,
		error: null,
		step: 0,
		catalog: null,
		config: {
			schema_version: '1.0',
			layout_id: null,
			title: root.dataset.title || '',
			cabinets: [],
			global_options: {},
		},
		pricing: null,
		pricingLoading: false,
		pricingError: null,
		savedUuid: root.dataset.uuid || null,
		savedConfigurations: [],
		saving: false,
		saveMessage: null,
		saveError: null,
	} );

	const app = new App( root, store, api, boot );
	app.init();
} );
