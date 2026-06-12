/**
 * Saved configurations panel.
 */

import { escapeHtml, formatMoney } from '../utils/helpers.js';

export class ProjectPanel {
	/**
	 * @param {HTMLElement} container Mount element.
	 * @param {object}      store     Store.
	 * @param {object}      api       API client.
	 * @param {object}      i18n      Translations.
	 * @param {string}      currency  Currency.
	 * @param {Function}    onLoad    Load callback.
	 */
	constructor( container, store, api, i18n, currency, onLoad ) {
		this.container = container;
		this.store = store;
		this.api = api;
		this.i18n = i18n;
		this.currency = currency;
		this.onLoad = onLoad;
	}

	/**
	 * Load and render saved configurations.
	 */
	async refresh() {
		try {
			const { data } = await this.api.listConfigurations();
			this.store.setState( { savedConfigurations: Array.isArray( data ) ? data : [] } );
		} catch {
			this.store.setState( { savedConfigurations: [] } );
		}
	}

	/**
	 * Render panel.
	 *
	 * @param {object} state Store state.
	 */
	render( state ) {
		const items = state.savedConfigurations || [];

		this.container.innerHTML = `
			<div class="kcp-projects">
				<div class="kcp-projects__header">
					<h3>${ escapeHtml( this.i18n.myProjects ) }</h3>
					<button type="button" class="kcp-btn kcp-btn--ghost" data-action="new">${ escapeHtml( this.i18n.newProject ) }</button>
				</div>
				${
					items.length
						? `<ul class="kcp-projects__list">${ items
							.map(
								( item ) => `
							<li>
								<div>
									<strong>${ escapeHtml( item.title ) }</strong>
									<span>${ formatMoney( item.total_price, this.currency ) }</span>
								</div>
								<button type="button" class="kcp-btn kcp-btn--small" data-action="load" data-uuid="${ escapeHtml( item.uuid ) }">${ escapeHtml( this.i18n.loadProject ) }</button>
							</li>`
							)
							.join( '' ) }</ul>`
						: `<p class="kcp-projects__empty">${ escapeHtml( this.i18n.noProjects || 'No saved configurations yet.' ) }</p>`
				}
			</div>
		`;

		this.container.querySelector( '[data-action="new"]' )?.addEventListener( 'click', () => {
			this.onLoad( null );
		} );

		this.container.querySelectorAll( '[data-action="load"]' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', () => {
				this.onLoad( btn.dataset.uuid );
			} );
		} );
	}
}
