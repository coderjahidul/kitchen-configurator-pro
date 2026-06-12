/**
 * Step 1: Layout selection.
 */

import { escapeHtml } from '../utils/helpers.js';

export class LayoutStep {
	/**
	 * @param {HTMLElement} container Mount element.
	 * @param {object}      store     Store.
	 * @param {object}      i18n      Translations.
	 */
	constructor( container, store, i18n ) {
		this.container = container;
		this.store = store;
		this.i18n = i18n;
	}

	render( state ) {
		const layouts = state.catalog?.layouts || [];
		const selected = state.config.layout_id;

		this.container.innerHTML = `
			<section class="kcp-step">
				<h2 class="kcp-step__title">${ escapeHtml( this.i18n.selectLayout ) }</h2>
				<div class="kcp-grid kcp-grid--layouts">
					${ layouts
						.map(
							( layout ) => `
						<button type="button" class="kcp-card ${ selected === layout.id ? 'kcp-card--active' : '' }" data-layout-id="${ layout.id }">
							${
								layout.thumbnail_url
									? `<img src="${ escapeHtml( layout.thumbnail_url ) }" alt="" class="kcp-card__img" loading="lazy">`
									: `<div class="kcp-card__placeholder"></div>`
							}
							<span class="kcp-card__label">${ escapeHtml( layout.name ) }</span>
						</button>`
						)
						.join( '' ) }
				</div>
			</section>
		`;

		this.container.querySelectorAll( '[data-layout-id]' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', () => {
				this.store.patchConfig( { layout_id: Number( btn.dataset.layoutId ) } );
			} );
		} );
	}
}
