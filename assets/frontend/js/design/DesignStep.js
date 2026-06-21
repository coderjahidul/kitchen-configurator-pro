/**
 * Design step UI.
 */

import { escapeHtml } from '../utils/helpers.js';
import { SelectionModal } from './SelectionModal.js';

export class DesignStep {
	/**
	 * @param {HTMLElement} root  Root element.
	 * @param {object}      store Design store.
	 */
	constructor( root, store ) {
		this.root = root;
		this.store = store;
		this.modalRoot = document.createElement( 'div' );
		this.modalRoot.className = 'kcp-design-modal';
		this.modal = new SelectionModal( this.modalRoot, store );
		this.keydownBound = false;
	}

	init() {
		document.body.classList.add( 'kcp-design-active' );
		document.body.appendChild( this.modalRoot );
		this.render( this.store.getState() );
		this.store.subscribe( ( state ) => this.render( state ) );

		if ( ! this.keydownBound ) {
			document.addEventListener( 'keydown', this.handleKeydown );
			this.keydownBound = true;
		}
	}

	renderLegendSwatch( selection ) {
		if ( ! selection ) {
			return '<span class="kcp-design__legend-swatch kcp-design__legend-swatch--empty"></span>';
		}

		if ( selection.image_url ) {
			return `<span class="kcp-design__legend-swatch"><img src="${ escapeHtml( selection.image_url ) }" alt="" /></span>`;
		}

		return `<span class="kcp-design__legend-swatch" style="background:${ escapeHtml( selection.hex || '#ffffff' ) }"></span>`;
	}

	renderNavLink( url, label ) {
		if ( ! url ) {
			return '';
		}

		return `<a class="kcp-design__link" href="${ escapeHtml( url ) }">${ escapeHtml( label || '' ) }</a>`;
	}

	render( state ) {
		const config = state.config;
		const imageUrl = ( config.base_image_url || '' ).trim();
		const zones = Array.isArray( config.zones ) ? config.zones : [];

		this.root.innerHTML = `
			<div class="kcp-design__page">
				<section class="kcp-design__intro">
					<header class="kcp-design__intro-header">
						<h1 class="kcp-design__title">${ escapeHtml( config.heading || config.breadcrumb || '' ) }</h1>
						${ config.back_url
							? `<a class="kcp-design__link kcp-design__link--back" href="${ escapeHtml( config.back_url ) }">${ escapeHtml( config.back_label || '' ) }</a>`
							: '' }
					</header>

					<p class="kcp-design__description">${ escapeHtml( config.description || '' ) }</p>

					<div class="kcp-design__legend">
						${ zones.map( ( zone ) => {
							const selection = state.selections[ zone.id ] || null;

							return `
								<button type="button" class="kcp-design__legend-item" data-zone-id="${ escapeHtml( zone.id ) }">
									${ this.renderLegendSwatch( selection ) }
									<span class="kcp-design__legend-label">${ escapeHtml( zone.label ) }</span>
								</button>
							`;
						} ).join( '' ) }
					</div>
				</section>

				<section class="kcp-design__stage">
					<div class="kcp-design__cabinet">
						<div class="kcp-design__visual">
							${ imageUrl
								? `<img class="kcp-design__image" src="${ escapeHtml( imageUrl ) }" alt="" loading="lazy" decoding="async" />`
								: '<div class="kcp-design__image kcp-design__image--placeholder"></div>' }
							<div class="kcp-design__hotspots">
								${ zones.map( ( zone ) => `
									<button
										type="button"
										class="kcp-design__hotspot${ state.activeZoneId === zone.id ? ' is-active' : '' }"
										data-zone-id="${ escapeHtml( zone.id ) }"
										style="top:${ Number( zone.top ) }%;left:${ Number( zone.left ) }%;"
										aria-label="${ escapeHtml( zone.label ) }"
									>
										<span class="kcp-design__hotspot-icon" aria-hidden="true">&#9998;</span>
									</button>
								` ).join( '' ) }
							</div>
						</div>
						${ ( config.back_url || config.skip_url )
							? `<div class="kcp-design__nav">
								${ this.renderNavLink( config.back_url, config.back_label ) }
								${ this.renderNavLink( config.skip_url, config.skip_label ) }
							</div>`
							: '' }
					</div>
				</section>
			</div>
		`;

		this.modal.render( state );

		this.root.querySelectorAll( '[data-zone-id]' ).forEach( ( element ) => {
			element.addEventListener( 'click', () => {
				this.store.setActiveZone( element.getAttribute( 'data-zone-id' ) );
			} );
		} );
	}

	handleKeydown = ( event ) => {
		if ( 'Escape' === event.key ) {
			this.store.closeModal();
		}
	};
}
