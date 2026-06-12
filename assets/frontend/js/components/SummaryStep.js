/**
 * Step 5: Summary and save.
 */

import { escapeHtml, formatMoney, findById } from '../utils/helpers.js';

export class SummaryStep {
	/**
	 * @param {HTMLElement} container Mount element.
	 * @param {object}      store     Store.
	 * @param {object}      api       API client.
	 * @param {object}      i18n      Translations.
	 * @param {string}      currency  Currency.
	 */
	constructor( container, store, api, i18n, currency ) {
		this.container = container;
		this.store = store;
		this.api = api;
		this.i18n = i18n;
		this.currency = currency;
	}

	render( state ) {
		const { config, pricing, savedUuid, saveMessage, saveError, saving } = state;
		const layout = findById( state.catalog?.layouts || [], config.layout_id );

		this.container.innerHTML = `
			<section class="kcp-step">
				<h2 class="kcp-step__title">${ escapeHtml( this.i18n.stepSummary ) }</h2>

				<label class="kcp-field kcp-field--full">
					<span>${ escapeHtml( this.i18n.projectTitle ) }</span>
					<input type="text" id="kcp-project-title" value="${ escapeHtml( config.title || '' ) }" placeholder="${ escapeHtml( layout?.name || 'My Kitchen' ) }">
				</label>

				<div class="kcp-summary">
					<p><strong>Layout:</strong> ${ escapeHtml( layout?.name || '—' ) }</p>
					<p><strong>Cabinets:</strong> ${ config.cabinets?.length || 0 }</p>
					${
						pricing
							? `<p class="kcp-summary__total"><strong>${ escapeHtml( this.i18n.total ) }:</strong> ${ formatMoney( pricing.total, this.currency ) }</p>`
							: ''
					}
				</div>

				${
					Array.isArray( pricing?.line_items )
						? `<ul class="kcp-summary__lines">${ pricing.line_items
							.map(
								( item ) =>
									`<li><span>${ escapeHtml( item.label ) }</span><span>${ formatMoney( item.subtotal, this.currency ) }</span></li>`
							)
							.join( '' ) }</ul>`
						: ''
				}

				<div class="kcp-summary__actions">
					<button type="button" class="kcp-btn kcp-btn--primary" id="kcp-save-btn" ${ saving ? 'disabled' : '' }>
						${ escapeHtml( saving ? this.i18n.loading : savedUuid ? this.i18n.save : this.i18n.save ) }
					</button>
				</div>

				${ saveMessage ? `<p class="kcp-message kcp-message--success">${ escapeHtml( saveMessage ) }</p>` : '' }
				${ saveError ? `<p class="kcp-message kcp-message--error">${ escapeHtml( saveError ) }</p>` : '' }
				${ savedUuid ? `<p class="kcp-hint">ID: <code>${ escapeHtml( savedUuid ) }</code></p>` : '' }
			</section>
		`;

		this.container.querySelector( '#kcp-project-title' )?.addEventListener( 'input', ( e ) => {
			this.store.patchConfig( { title: e.target.value } );
		} );

		this.container.querySelector( '#kcp-save-btn' )?.addEventListener( 'click', () => this.save() );
	}

	async save() {
		const state = this.store.getState();
		const payload = {
			schema_version: '1.0',
			layout_id: state.config.layout_id,
			title: state.config.title || 'My Kitchen',
			cabinets: state.config.cabinets,
			global_options: state.config.global_options,
		};

		this.store.setState( { saving: true, saveError: null, saveMessage: null } );

		try {
			let data;
			if ( state.savedUuid ) {
				const result = await this.api.updateConfiguration( state.savedUuid, payload );
				data = result.data;
				this.store.setState( {
					saveMessage: this.i18n.updated,
					saving: false,
				} );
			} else {
				const result = await this.api.createConfiguration( payload );
				data = result.data;
				this.store.setState( {
					savedUuid: data.uuid,
					saveMessage: this.i18n.saved,
					saving: false,
				} );
			}

			if ( data?.pricing ) {
				this.store.setState( { pricing: data.pricing } );
			}
		} catch ( err ) {
			this.store.setState( {
				saveError: err.message || this.i18n.error,
				saving: false,
			} );
		}
	}
}
