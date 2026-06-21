/**
 * Catalog-style selection modal.
 */

import { escapeHtml } from '../utils/helpers.js';
import { getModalCopy, renderPriceDots, resolvePriceClass } from './modal-copy.js';

export class SelectionModal {
	/**
	 * @param {HTMLElement} root  Modal root.
	 * @param {object}      store Design store.
	 */
	constructor( root, store ) {
		this.root = root;
		this.store = store;
	}

	/**
	 * @param {object}   option     Catalog option.
	 * @param {object[]} allOptions All zone options (for price class).
	 * @param {object[]} options   Options shown in this section.
	 * @param {number}   rank       Display rank (0 = hidden).
	 * @param {number|null} selectedId Selected option id.
	 * @param {object}   copy       Modal copy.
	 * @return {string}
	 */
	renderCard( option, allOptions, rank, selectedId, copy ) {
		const selected = selectedId != null && selectedId === option.id;
		const image = option.image_url
			? `<img src="${ escapeHtml( option.image_url ) }" alt="" loading="lazy" decoding="async" />`
			: `<span class="kcp-design-modal__card-fallback" style="background:${ escapeHtml( option.hex || '#ffffff' ) }"></span>`;
		const priceClass = resolvePriceClass( option, allOptions );
		const hasInfo = Boolean( ( option.description || '' ).trim() );

		return `
			<article class="kcp-design-modal__card${ selected ? ' is-selected' : '' }">
				<div class="kcp-design-modal__card-media">
					${ rank > 0 ? `<span class="kcp-design-modal__rank">${ rank }</span>` : '' }
					<button
						type="button"
						class="kcp-design-modal__card-image"
						data-option-id="${ Number( option.id ) }"
						aria-label="${ escapeHtml( copy.selectLabel ) } — ${ escapeHtml( option.name || '' ) }"
					>${ image }</button>
				</div>
				<h3 class="kcp-design-modal__card-name">${ escapeHtml( option.name || '' ) }</h3>
				<div class="kcp-design-modal__price-class">
					<span class="kcp-design-modal__price-class-label">prijsklasse</span>
					<span class="kcp-design-modal__dots" aria-hidden="true">${ renderPriceDots( priceClass ) }</span>
				</div>
				${ hasInfo
					? `<button type="button" class="kcp-design-modal__info" data-info-id="${ Number( option.id ) }">
						<span class="kcp-design-modal__info-icon" aria-hidden="true">i</span>
						<span class="kcp-design-modal__info-label">informatie</span>
					</button>
					<p class="kcp-design-modal__info-text" data-info-panel="${ Number( option.id ) }" hidden>${ escapeHtml( option.description ) }</p>`
					: '' }
				<button type="button" class="kcp-design-modal__select" data-option-id="${ Number( option.id ) }">
					${ escapeHtml( copy.selectLabel ) }
				</button>
			</article>
		`;
	}

	/**
	 * @param {object[]} subset     Options in this section.
	 * @param {object[]} allOptions All zone options.
	 * @param {object}   copy       Modal copy.
	 * @param {number|null} selectedId Selected option id.
	 * @param {boolean}  featured   Whether cards belong to the featured block.
	 * @return {string}
	 */
	renderCards( subset, allOptions, copy, selectedId, featured ) {
		if ( ! subset.length ) {
			return '';
		}

		return `
			<div class="kcp-design-modal__cards">
				${ subset.map( ( option, index ) => this.renderCard(
					option,
					allOptions,
					featured ? index + 1 : 0,
					selectedId,
					copy
				) ).join( '' ) }
			</div>
		`;
	}

	render( state ) {
		const zone = state.activeZoneId ? this.store.getZone( state.activeZoneId ) : null;

		if ( ! state.modalOpen || ! zone ) {
			this.root.hidden = true;
			this.root.innerHTML = '';
			return;
		}

		this.root.hidden = false;

		const options = Array.isArray( zone.colors ) ? zone.colors : [];
		const selectedId = state.selections[ zone.id ]?.id ?? null;
		const copy = getModalCopy( zone.id );
		const config = state.config || {};
		const topOptions = options.slice( 0, 5 );
		const moreOptions = options.slice( 5 );
		const promoText = config.monsterbox_promo || 'Wil jij eerst de kleur thuis goed bekijken?';
		const promoLabel = config.monsterbox_label || 'bestel onze monsterbox';
		const promoUrl = ( config.monsterbox_url || '' ).trim();

		this.root.innerHTML = `
			<div class="kcp-design-modal__backdrop" data-kcp-design-close></div>
			<div class="kcp-design-modal__panel" role="dialog" aria-modal="true" aria-labelledby="kcp-design-modal-title">
				<button type="button" class="kcp-design-modal__close" data-kcp-design-close aria-label="Close">&times;</button>

				<header class="kcp-design-modal__head">
					<h2 class="kcp-design-modal__title" id="kcp-design-modal-title">${ escapeHtml( copy.title ) }</h2>
					<p class="kcp-design-modal__promo">
						${ escapeHtml( promoText ) }
						${ promoUrl
							? `<a class="kcp-design-modal__promo-link" href="${ escapeHtml( promoUrl ) }">${ escapeHtml( promoLabel ) }</a>`
							: `<span class="kcp-design-modal__promo-link">${ escapeHtml( promoLabel ) }</span>` }
					</p>
				</header>

				${ options.length
					? `
						<section class="kcp-design-modal__featured">
							<h3 class="kcp-design-modal__section-title">${ escapeHtml( copy.topHeading ) }</h3>
							${ this.renderCards( topOptions, options, copy, selectedId, true ) }
						</section>
						${ moreOptions.length
							? `<section class="kcp-design-modal__more">
								<h3 class="kcp-design-modal__section-title">${ escapeHtml( copy.moreHeading ) }</h3>
								${ this.renderCards( moreOptions, options, copy, selectedId, false ) }
							</section>`
							: '' }
					`
					: `<p class="kcp-design-modal__empty">Geen opties beschikbaar voor dit onderdeel.</p>` }
			</div>
		`;

		this.root.querySelectorAll( '[data-kcp-design-close]' ).forEach( ( element ) => {
			element.addEventListener( 'click', () => this.store.closeModal() );
		} );

		this.root.querySelectorAll( '[data-option-id]' ).forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				const optionId = Number( button.getAttribute( 'data-option-id' ) );
				const option = options.find( ( item ) => item.id === optionId );

				if ( option ) {
					this.store.selectColor( zone.id, option );
				}
			} );
		} );

		this.root.querySelectorAll( '[data-info-id]' ).forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				const infoId = button.getAttribute( 'data-info-id' );
				const panel = this.root.querySelector( `[data-info-panel="${ infoId }"]` );

				if ( panel ) {
					panel.hidden = ! panel.hidden;
				}
			} );
		} );
	}
}
