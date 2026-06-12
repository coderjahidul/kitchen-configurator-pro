/**
 * Step 3: Material, color, and handle selection per cabinet.
 */

import { escapeHtml, findById, colorsForMaterial, materialsByType } from '../utils/helpers.js';

export class FinishesStep {
	constructor( container, store, i18n ) {
		this.container = container;
		this.store = store;
		this.i18n = i18n;
		this.activeIndex = 0;
	}

	render( state ) {
		const items = state.config.cabinets || [];
		const materials = materialsByType( state.catalog?.materials || [], 'front' );
		const colors = state.catalog?.colors || [];
		const handles = state.catalog?.handles || [];
		const active = items[ this.activeIndex ];

		if ( ! items.length ) {
			this.container.innerHTML = `<p class="kcp-empty">${ escapeHtml( this.i18n.noCabinets ) }</p>`;
			return;
		}

		const materialColors = colorsForMaterial( colors, active?.material_id );

		this.container.innerHTML = `
			<section class="kcp-step">
				<h2 class="kcp-step__title">${ escapeHtml( this.i18n.stepFinishes ) }</h2>
				<div class="kcp-tabs">
					${ items
						.map( ( item, index ) => {
							const cab = findById( state.catalog?.cabinets || [], item.cabinet_id );
							return `<button type="button" class="kcp-tab ${ index === this.activeIndex ? 'kcp-tab--active' : '' }" data-index="${ index }">${ escapeHtml( cab?.name || `${ this.i18n.cabinet } ${ index + 1 }` ) }</button>`;
						} )
						.join( '' ) }
				</div>

				<div class="kcp-picker">
					<h3>${ escapeHtml( this.i18n.material ) }</h3>
					<div class="kcp-grid kcp-grid--swatches">
						${ materials
							.map(
								( m ) => `
							<button type="button" class="kcp-swatch ${ active?.material_id === m.id ? 'kcp-swatch--active' : '' }" data-material="${ m.id }" title="${ escapeHtml( m.name ) }">
								${ m.thumbnail_url ? `<img src="${ escapeHtml( m.thumbnail_url ) }" alt="">` : `<span>${ escapeHtml( m.name.charAt( 0 ) ) }</span>` }
								<small>${ escapeHtml( m.name ) }</small>
							</button>`
							)
							.join( '' ) }
					</div>
				</div>

				<div class="kcp-picker">
					<h3>${ escapeHtml( this.i18n.color ) }</h3>
					<div class="kcp-grid kcp-grid--swatches">
						${
							materialColors.length
								? materialColors
									.map(
										( c ) => `
								<button type="button" class="kcp-swatch ${ active?.color_id === c.id ? 'kcp-swatch--active' : '' }" data-color="${ c.id }" title="${ escapeHtml( c.name ) }">
									<span class="kcp-swatch__color" style="background:${ escapeHtml( c.hex_code || '#ccc' ) }"></span>
									<small>${ escapeHtml( c.name ) }</small>
								</button>`
									)
									.join( '' )
								: `<p class="kcp-hint">${ escapeHtml( 'Select a material first.' ) }</p>`
						}
					</div>
				</div>

				<div class="kcp-picker">
					<h3>${ escapeHtml( this.i18n.handle ) }</h3>
					<div class="kcp-grid kcp-grid--swatches">
						${ handles
							.map(
								( h ) => `
							<button type="button" class="kcp-swatch ${ active?.handle_id === h.id ? 'kcp-swatch--active' : '' }" data-handle="${ h.id }" title="${ escapeHtml( h.name ) }">
								${ h.thumbnail_url ? `<img src="${ escapeHtml( h.thumbnail_url ) }" alt="">` : `<span>${ escapeHtml( h.name.charAt( 0 ) ) }</span>` }
								<small>${ escapeHtml( h.name ) }</small>
							</button>`
							)
							.join( '' ) }
					</div>
				</div>
			</section>
		`;

		this.bindTabs();
		this.bindSwatches( 'material', ( id ) => {
			this.store.updateCabinet( this.activeIndex, { material_id: id, color_id: null } );
		} );
		this.bindSwatches( 'color', ( id ) => {
			this.store.updateCabinet( this.activeIndex, { color_id: id } );
		} );
		this.bindSwatches( 'handle', ( id ) => {
			this.store.updateCabinet( this.activeIndex, { handle_id: id } );
		} );
	}

	bindTabs() {
		this.container.querySelectorAll( '.kcp-tab' ).forEach( ( tab ) => {
			tab.addEventListener( 'click', () => {
				this.activeIndex = Number( tab.dataset.index );
				this.render( this.store.getState() );
			} );
		} );
	}

	bindSwatches( type, callback ) {
		this.container.querySelectorAll( `[data-${ type }]` ).forEach( ( btn ) => {
			btn.addEventListener( 'click', () => {
				callback( Number( btn.dataset[ type ] ) );
			} );
		} );
	}
}
