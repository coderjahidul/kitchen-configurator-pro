/**
 * Step 2: Cabinet selection and dimensions.
 */

import { createCabinetItem } from '../state/store.js';
import { escapeHtml, findById } from '../utils/helpers.js';

export class CabinetsStep {
	/**
	 * @param {HTMLElement} container Mount element.
	 * @param {object}      store     Store.
	 * @param {object}      i18n      Translations.
	 */
	constructor( container, store, i18n ) {
		this.container = container;
		this.store = store;
		this.i18n = i18n;
		this.activeIndex = 0;
	}

	render( state ) {
		const cabinets = state.catalog?.cabinets || [];
		const categories = state.catalog?.cabinet_categories || [];
		const items = state.config.cabinets || [];
		const active = items[ this.activeIndex ] || null;
		const activeCabinet = active ? findById( cabinets, active.cabinet_id ) : null;

		this.container.innerHTML = `
			<section class="kcp-step">
				<div class="kcp-step__header">
					<h2 class="kcp-step__title">${ escapeHtml( this.i18n.stepCabinets ) }</h2>
					<div class="kcp-step__actions">
						<select class="kcp-select" id="kcp-add-cabinet-select">
							<option value="">${ escapeHtml( this.i18n.addCabinet ) }…</option>
							${ categories
								.map(
									( cat ) => `
								<optgroup label="${ escapeHtml( cat.name ) }">
									${ cabinets
										.filter( ( c ) => c.category_id === cat.id )
										.map(
											( c ) =>
												`<option value="${ c.id }">${ escapeHtml( c.name ) }</option>`
										)
										.join( '' ) }
								</optgroup>`
								)
								.join( '' ) }
						</select>
					</div>
				</div>

				${
					items.length
						? `
					<div class="kcp-tabs">
						${ items
							.map( ( item, index ) => {
								const cab = findById( cabinets, item.cabinet_id );
								return `<button type="button" class="kcp-tab ${ index === this.activeIndex ? 'kcp-tab--active' : '' }" data-index="${ index }">${ escapeHtml( cab?.name || `${ this.i18n.cabinet } ${ index + 1 }` ) }</button>`;
							} )
							.join( '' ) }
					</div>

					${
						activeCabinet
							? `
						<div class="kcp-dimensions">
							<h3>${ escapeHtml( activeCabinet.name ) }</h3>
							<div class="kcp-dimensions__grid">
								${ this.dimensionField( 'width', activeCabinet.min_width, activeCabinet.max_width, activeCabinet.width_step, active?.dimensions?.width ) }
								${ this.dimensionField( 'height', activeCabinet.min_height, activeCabinet.max_height, activeCabinet.height_step, active?.dimensions?.height ) }
								${ this.dimensionField( 'depth', activeCabinet.min_depth, activeCabinet.max_depth, activeCabinet.depth_step, active?.dimensions?.depth ) }
							</div>
							<p class="kcp-hint">${ escapeHtml( activeCabinet.description || '' ) }</p>
							<button type="button" class="kcp-btn kcp-btn--danger" data-remove="${ this.activeIndex }">${ escapeHtml( this.i18n.removeCabinet ) }</button>
						</div>`
							: ''
					}`
						: `<p class="kcp-empty">${ escapeHtml( this.i18n.noCabinets ) }</p>`
				}
			</section>
		`;

		const select = this.container.querySelector( '#kcp-add-cabinet-select' );
		select?.addEventListener( 'change', () => {
			const id = Number( select.value );
			if ( ! id ) {
				return;
			}
			const cabinet = findById( cabinets, id );
			if ( ! cabinet ) {
				return;
			}
			const newItems = [ ...items, createCabinetItem( cabinet, items.length ) ];
			this.store.patchConfig( { cabinets: newItems } );
			this.activeIndex = newItems.length - 1;
			select.value = '';
		} );

		this.container.querySelectorAll( '.kcp-tab' ).forEach( ( tab ) => {
			tab.addEventListener( 'click', () => {
				this.activeIndex = Number( tab.dataset.index );
				this.render( this.store.getState() );
			} );
		} );

		this.container.querySelectorAll( '[data-dimension]' ).forEach( ( input ) => {
			input.addEventListener( 'input', () => {
				this.store.updateCabinetDimensions( this.activeIndex, {
					[ input.dataset.dimension ]: Number( input.value ),
				} );
			} );
		} );

		this.container.querySelector( '[data-remove]' )?.addEventListener( 'click', () => {
			const index = Number( this.container.querySelector( '[data-remove]' ).dataset.remove );
			const newItems = items.filter( ( _, i ) => i !== index );
			this.activeIndex = Math.max( 0, index - 1 );
			this.store.patchConfig( { cabinets: newItems } );
		} );
	}

	dimensionField( axis, min, max, step, value ) {
		const label = this.i18n[ axis ] || axis;
		return `
			<label class="kcp-field">
				<span>${ escapeHtml( label ) }</span>
				<input type="number" min="${ min }" max="${ max }" step="${ step }" value="${ value ?? min }" data-dimension="${ axis }">
				<small>${ min }–${ max } mm</small>
			</label>
		`;
	}
}
