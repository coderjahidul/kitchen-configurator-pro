/**
 * Price summary sidebar component.
 */

import { escapeHtml, formatMoney } from '../utils/helpers.js';

export class PriceSummary {
	/**
	 * @param {HTMLElement} container Mount element.
	 * @param {object}      i18n      Translations.
	 * @param {string}      currency  Currency code.
	 */
	constructor( container, i18n, currency ) {
		this.container = container;
		this.i18n = i18n;
		this.currency = currency;
	}

	/**
	 * Render price panel.
	 *
	 * @param {object} state Store state.
	 */
	render( state ) {
		const { pricing, pricingLoading, pricingError } = state;
		const subtotal = pricing?.subtotal ?? 0;
		const tax = pricing?.tax ?? 0;
		const total = pricing?.total ?? 0;

		this.container.innerHTML = `
			<div class="kcp-price">
				<h3 class="kcp-price__title">${ escapeHtml( this.i18n.total ) }</h3>
				${
					pricingLoading
						? `<p class="kcp-price__status">${ escapeHtml( this.i18n.calculating ) }</p>`
						: pricingError
							? `<p class="kcp-price__error">${ escapeHtml( pricingError ) }</p>`
							: `
					<dl class="kcp-price__rows">
						<div class="kcp-price__row">
							<dt>${ escapeHtml( this.i18n.subtotal ) }</dt>
							<dd>${ formatMoney( subtotal, this.currency ) }</dd>
						</div>
						<div class="kcp-price__row">
							<dt>${ escapeHtml( this.i18n.tax ) }</dt>
							<dd>${ formatMoney( tax, this.currency ) }</dd>
						</div>
						<div class="kcp-price__row kcp-price__row--total">
							<dt>${ escapeHtml( this.i18n.total ) }</dt>
							<dd>${ formatMoney( total, this.currency ) }</dd>
						</div>
					</dl>
					${
						Array.isArray( pricing?.line_items ) && pricing.line_items.length
							? `<ul class="kcp-price__lines">${ pricing.line_items
								.slice( 0, 6 )
								.map(
									( item ) =>
										`<li><span>${ escapeHtml( item.label ) }</span><span>${ formatMoney( item.subtotal, this.currency ) }</span></li>`
								)
								.join( '' ) }</ul>`
							: ''
					}
				`
				}
			</div>
		`;
	}
}
