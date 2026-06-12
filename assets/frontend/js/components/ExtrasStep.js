/**
 * Step 4: Accessories, worktop, and plinth.
 */

import { escapeHtml, findById, colorsForMaterial, materialsByType } from '../utils/helpers.js';

export class ExtrasStep {
	constructor( container, store, i18n ) {
		this.container = container;
		this.store = store;
		this.i18n = i18n;
		this.activeIndex = 0;
	}

	render( state ) {
		const items = state.config.cabinets || [];
		const global = state.config.global_options || {};
		const accessories = state.catalog?.accessories || [];
		const worktops = state.catalog?.worktops || [];
		const plinths = state.catalog?.plinths || [];
		const worktopMaterials = materialsByType( state.catalog?.materials || [], 'worktop' );
		const worktopColors = colorsForMaterial( state.catalog?.colors || [], global.worktop_material_id );
		const active = items[ this.activeIndex ];
		const selectedWorktop = findById( worktops, global.worktop_id );
		const selectedPlinth = findById( plinths, global.plinth_id );

		this.container.innerHTML = `
			<section class="kcp-step">
				<h2 class="kcp-step__title">${ escapeHtml( this.i18n.stepExtras ) }</h2>

				<div class="kcp-panel">
					<h3>${ escapeHtml( this.i18n.worktop ) }</h3>
					<select class="kcp-select" data-global="worktop_id">
						<option value="">${ escapeHtml( '— None —' ) }</option>
						${ worktops.map( ( w ) => `<option value="${ w.id }" ${ global.worktop_id === w.id ? 'selected' : '' }>${ escapeHtml( w.name ) }</option>` ).join( '' ) }
					</select>
					${
						selectedWorktop
							? `
						<div class="kcp-dimensions__grid">
							<label class="kcp-field"><span>${ escapeHtml( this.i18n.length ) }</span><input type="number" data-global="worktop_length" min="${ selectedWorktop.min_length }" max="${ selectedWorktop.max_length }" step="${ selectedWorktop.length_step }" value="${ global.worktop_length || selectedWorktop.default_length }"></label>
							<label class="kcp-field"><span>${ escapeHtml( this.i18n.depth ) }</span><input type="number" data-global="worktop_depth" min="${ selectedWorktop.min_depth }" max="${ selectedWorktop.max_depth }" step="${ selectedWorktop.depth_step }" value="${ global.worktop_depth || selectedWorktop.default_depth }"></label>
						</div>
						<select class="kcp-select" data-global="worktop_material_id"><option value="">Finish material</option>${ worktopMaterials.map( ( m ) => `<option value="${ m.id }" ${ global.worktop_material_id === m.id ? 'selected' : '' }>${ escapeHtml( m.name ) }</option>` ).join( '' ) }</select>
						<select class="kcp-select" data-global="worktop_color_id"><option value="">Finish color</option>${ worktopColors.map( ( c ) => `<option value="${ c.id }" ${ global.worktop_color_id === c.id ? 'selected' : '' }>${ escapeHtml( c.name ) }</option>` ).join( '' ) }</select>
					`
							: ''
					}
				</div>

				<div class="kcp-panel">
					<h3>${ escapeHtml( this.i18n.plinth ) }</h3>
					<select class="kcp-select" data-global="plinth_id">
						<option value="">${ escapeHtml( '— None —' ) }</option>
						${ plinths.map( ( p ) => `<option value="${ p.id }" ${ global.plinth_id === p.id ? 'selected' : '' }>${ escapeHtml( p.name ) }</option>` ).join( '' ) }
					</select>
					${
						selectedPlinth
							? `
						<div class="kcp-dimensions__grid">
							<label class="kcp-field"><span>${ escapeHtml( this.i18n.length ) }</span><input type="number" data-global="plinth_length" min="${ selectedPlinth.min_length }" max="${ selectedPlinth.max_length }" step="${ selectedPlinth.length_step }" value="${ global.plinth_length || selectedPlinth.default_length }"></label>
							<label class="kcp-field"><span>${ escapeHtml( this.i18n.height ) }</span><input type="number" data-global="plinth_height" min="${ selectedPlinth.min_height }" max="${ selectedPlinth.max_height }" step="${ selectedPlinth.height_step }" value="${ global.plinth_height || selectedPlinth.default_height }"></label>
						</div>
					`
							: ''
					}
				</div>

				${
					items.length
						? `
					<div class="kcp-panel">
						<h3>${ escapeHtml( this.i18n.accessories ) }</h3>
						<div class="kcp-tabs">
							${ items
								.map( ( item, index ) => {
									const cab = findById( state.catalog?.cabinets || [], item.cabinet_id );
									return `<button type="button" class="kcp-tab ${ index === this.activeIndex ? 'kcp-tab--active' : '' }" data-index="${ index }">${ escapeHtml( cab?.name || `${ this.i18n.cabinet } ${ index + 1 }` ) }</button>`;
								} )
								.join( '' ) }
						</div>
						<div class="kcp-checklist">
							${ accessories
								.filter( ( a ) => a.is_per_cabinet )
								.map( ( acc ) => {
									const checked = ( active?.accessories || [] ).includes( acc.id );
									return `
									<label class="kcp-check">
										<input type="checkbox" data-accessory="${ acc.id }" ${ checked ? 'checked' : '' }>
										<span>${ escapeHtml( acc.name ) }</span>
									</label>`;
								} )
								.join( '' ) }
						</div>
						<div class="kcp-checklist">
							<h4>Kitchen-wide</h4>
							${ accessories
								.filter( ( a ) => ! a.is_per_cabinet )
								.map( ( acc ) => {
									const globalAcc = global.accessories || [];
									const checked = globalAcc.includes( acc.id );
									return `
									<label class="kcp-check">
										<input type="checkbox" data-global-accessory="${ acc.id }" ${ checked ? 'checked' : '' }>
										<span>${ escapeHtml( acc.name ) }</span>
									</label>`;
								} )
								.join( '' ) }
						</div>
					</div>`
						: ''
				}
			</section>
		`;

		this.container.querySelectorAll( '[data-global]' ).forEach( ( el ) => {
			const key = el.dataset.global;
			const handler = () => {
				const value = el.type === 'number' ? Number( el.value ) : Number( el.value ) || null;
				const patch = { [ key ]: el.tagName === 'SELECT' && ! el.value ? null : value };
				if ( key === 'worktop_material_id' ) {
					patch.worktop_color_id = null;
				}
				this.store.updateGlobalOptions( patch );
				if ( el.tagName === 'SELECT' && ( key === 'worktop_id' || key === 'plinth_id' || key === 'worktop_material_id' ) ) {
					this.render( this.store.getState() );
				}
			};
			el.addEventListener( el.tagName === 'SELECT' ? 'change' : 'input', handler );
		} );

		this.container.querySelectorAll( '[data-accessory]' ).forEach( ( input ) => {
			input.addEventListener( 'change', () => {
				const id = Number( input.dataset.accessory );
				const current = [ ...( items[ this.activeIndex ]?.accessories || [] ) ];
				const next = input.checked
					? [ ...new Set( [ ...current, id ] ) ]
					: current.filter( ( x ) => x !== id );
				this.store.updateCabinet( this.activeIndex, { accessories: next } );
			} );
		} );

		this.container.querySelectorAll( '[data-global-accessory]' ).forEach( ( input ) => {
			input.addEventListener( 'change', () => {
				const id = Number( input.dataset.globalAccessory );
				const current = [ ...( global.accessories || [] ) ];
				const next = input.checked
					? [ ...new Set( [ ...current, id ] ) ]
					: current.filter( ( x ) => x !== id );
				this.store.updateGlobalOptions( { accessories: next } );
			} );
		} );

		this.container.querySelectorAll( '.kcp-tab' ).forEach( ( tab ) => {
			tab.addEventListener( 'click', () => {
				this.activeIndex = Number( tab.dataset.index );
				this.render( this.store.getState() );
			} );
		} );
	}
}
