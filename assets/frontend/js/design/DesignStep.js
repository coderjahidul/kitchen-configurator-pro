/**
 * Design step UI.
 */

import { escapeHtml } from '../utils/helpers.js';
import { SelectionModal } from './SelectionModal.js';
import { saveDesignSelections } from './design-selection-storage.js';
import { renderCabinetOverlays, renderCabinetOverlaysInner, renderPreviewClipDefs } from './cabinet-preview.js';
import { bindPreviewStageSync } from './cabinet-preview-stage.js';
import { showsHandleStrip } from './kitchen-type-config.js';

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
		this.resizeBound = false;
		this.mounted = false;
		this.handleResize = () => bindPreviewStageSync( this.root );
	}

	init() {
		document.body.classList.add( 'kcp-design-active' );
		document.body.appendChild( this.modalRoot );
		this.render( this.store.getState() );
		this.mounted = true;
		this.store.subscribe( ( state ) => this.updateView( state ) );

		if ( ! this.keydownBound ) {
			document.addEventListener( 'keydown', this.handleKeydown );
			this.keydownBound = true;
		}
	}

	renderNavLink( url, label, modifier = '' ) {
		if ( ! url ) {
			return '';
		}
		const className = modifier
			? `kcp-design__link ${ modifier }`
			: 'kcp-design__link';
		return `<a class="${ className }" href="${ escapeHtml( url ) }">${ escapeHtml( label || '' ) }</a>`;
	}

	renderBreadcrumb( config ) {
		const label = String( config.breadcrumb || config.heading || '' ).trim();
		if ( ! label ) {
			return '';
		}

		return `
			<nav class="kcp-design__breadcrumbs" aria-label="Breadcrumb">
				<ul class="kcp-design__breadcrumb-list">
					<li><span aria-current="page">${ escapeHtml( label ) }</span></li>
				</ul>
			</nav>
		`;
	}

	renderFooter( config ) {
		const cabinetUrl = String( config.cabinet_select_url || '' ).trim();
		const cabinetLabel = String( config.cabinet_select_label || '' ).trim();
		const cabinetCta = cabinetUrl
			? `<a class="kcp-design__cta" href="${ escapeHtml( cabinetUrl ) }" data-kcp-design-cabinet-select>${ escapeHtml( cabinetLabel ) }</a>`
			: '';
		const backLink = this.renderNavLink( config.back_url, config.back_label, 'kcp-design__link--muted' );
		const skipUrl = String( config.skip_url || '' ).trim();
		const skipLabel = String( config.skip_label || '' ).trim();
		const skipLink = skipUrl
			? `<a class="kcp-design__link kcp-design__link--muted" href="${ escapeHtml( skipUrl ) }" data-kcp-design-skip>${ escapeHtml( skipLabel ) }</a>`
			: '';
		const navLinks = [ backLink, skipLink ].filter( Boolean );

		if ( ! cabinetCta && ! navLinks.length ) {
			return '';
		}

		return `
			<footer class="kcp-design__footer">
				${ cabinetCta }
				${ navLinks.length ? `<div class="kcp-design__nav">${ navLinks.join( '' ) }</div>` : '' }
			</footer>
		`;
	}

	getHotspotClass( zoneId ) {
		const map = {
			front: 'kcp-design__hotspot--front',
			handle_strip: 'kcp-design__hotspot--handle-strip',
			cabinet: 'kcp-design__hotspot--cabinet',
			plinth: 'kcp-design__hotspot--plinth',
		};

		return map[ zoneId ] || '';
	}

	usesReferenceHotspotLayout( zoneId ) {
		return Boolean( this.getHotspotClass( zoneId ) );
	}

	renderLegendCheckbox( selection ) {
		if ( ! selection ) {
			return `
				<span class="kcp-design__legend-check" aria-hidden="true">
					<span class="kcp-design__legend-checkmark">&#10003;</span>
				</span>
			`;
		}

		const swatch = selection.image_url
			? `<img src="${ escapeHtml( selection.image_url ) }" alt="" loading="lazy" decoding="async" />`
			: `<span class="kcp-design__legend-swatch-color" style="background-color:${ escapeHtml( selection.hex || '#ffffff' ) }"></span>`;

		return `
			<span class="kcp-design__legend-check is-checked" aria-hidden="true">
				<span class="kcp-design__legend-swatch">${ swatch }</span>
				<span class="kcp-design__legend-checkmark">&#10003;</span>
			</span>
		`;
	}

	renderHotspot( zone, selection, activeZoneId ) {
		const isSelected = Boolean( selection );
		const isActive = activeZoneId === zone.id;
		const icon = isSelected ? '&#10003;' : '&#9998;';
		const hotspotClass = this.getHotspotClass( zone.id );
		const positionStyle = hotspotClass
			? ''
			: `style="top:${ Number( zone.top ) }%;left:${ Number( zone.left ) }%;"`;

		return `
			<button
				type="button"
				class="kcp-design__hotspot${ hotspotClass ? ` ${ hotspotClass }` : '' }${ isActive ? ' is-active' : '' }${ isSelected ? ' is-selected' : '' }"
				data-zone-id="${ escapeHtml( zone.id ) }"
				${ positionStyle }
				aria-label="${ escapeHtml( zone.label ) }"
			>
				<span class="kcp-design__hotspot-icon" aria-hidden="true">${ icon }</span>
			</button>
		`;
	}

	bindInteractions() {
		this.root.querySelectorAll( '[data-zone-id]' ).forEach( ( element ) => {
			if ( element._kcpZoneBound ) {
				return;
			}

			element.addEventListener( 'click', () => {
				this.store.setActiveZone( element.getAttribute( 'data-zone-id' ) );
			} );
			element._kcpZoneBound = true;
		} );

		const skip = this.root.querySelector( '[data-kcp-design-skip]' );
		if ( skip && ! skip._kcpSkipBound ) {
			skip.addEventListener( 'click', () => {
				saveDesignSelections( this.store.getState().selections );
			} );
			skip._kcpSkipBound = true;
		}

		const cabinet = this.root.querySelector( '[data-kcp-design-cabinet-select]' );
		if ( cabinet && ! cabinet._kcpCabinetBound ) {
			cabinet.addEventListener( 'click', () => {
				saveDesignSelections( this.store.getState().selections );
			} );
			cabinet._kcpCabinetBound = true;
		}
	}

	updateLegend( state ) {
		this.root.querySelectorAll( '.kcp-design__legend-item' ).forEach( ( element ) => {
			const zoneId = element.getAttribute( 'data-zone-id' );
			const selection = state.selections[ zoneId ] || null;
			const label = element.querySelector( '.kcp-design__legend-label' );

			element.classList.toggle( 'is-selected', Boolean( selection ) );

			const existing = element.querySelector( '.kcp-design__legend-check' );
			const markup = this.renderLegendCheckbox( selection );
			const template = document.createElement( 'template' );
			template.innerHTML = markup.trim();

			if ( existing ) {
				existing.replaceWith( template.content.firstChild );
			} else if ( label ) {
				label.insertAdjacentElement( 'beforebegin', template.content.firstChild );
			}
		} );
	}

	updateHotspots( state ) {
		const zones = this.store.getVisibleZones();

		zones.forEach( ( zone ) => {
			const element = this.root.querySelector( `.kcp-design__hotspot[data-zone-id="${ zone.id }"]` );
			if ( ! element ) {
				return;
			}

			const selection = state.selections[ zone.id ] || null;

			element.classList.toggle( 'is-selected', Boolean( selection ) );
			element.classList.toggle( 'is-active', state.activeZoneId === zone.id );

			const icon = element.querySelector( '.kcp-design__hotspot-icon' );
			if ( icon ) {
				icon.innerHTML = selection ? '&#10003;' : '&#9998;';
			}
		} );
	}

	updateOverlays( state ) {
		const container = this.root.querySelector( '.kcp-design__overlays' );
		if ( ! container ) {
			return;
		}

		const markup = renderCabinetOverlaysInner(
			state.selections,
			state.config.preview_masks || {},
			{ showHandle: showsHandleStrip( state.kitchenType ) }
		);

		container.classList.add( 'is-updating' );
		container.innerHTML = markup;
		requestAnimationFrame( () => {
			container.classList.remove( 'is-updating' );
		} );
	}

	updateView( state ) {
		if ( ! this.root.querySelector( '.kcp-design__page' ) ) {
			this.render( state );
			return;
		}

		this.updateLegend( state );
		this.updateHotspots( state );
		this.updateOverlays( state );
		this.modal.render( state );
		this.bindInteractions();
		bindPreviewStageSync( this.root );
	}

	render( state ) {
		const config = state.config;
		const imageUrl = ( config.base_image_url || '' ).trim();
		const zones = this.store.getVisibleZones();
		const showHandles = showsHandleStrip( state.kitchenType );

		this.root.innerHTML = `
			<div class="kcp-design__page">
				${ this.renderBreadcrumb( config ) }

				<section class="kcp-design__intro">
					<header class="kcp-design__intro-header">
						<h1 class="kcp-design__title">${ escapeHtml( config.heading || config.breadcrumb || '' ) }</h1>
						${ config.back_url
							? `<a class="kcp-design__link kcp-design__link--back" href="${ escapeHtml( config.back_url ) }">${ escapeHtml( config.back_label || '' ) }</a>`
							: '' }
					</header>

					<p class="kcp-design__description">${ escapeHtml( config.description || '' ) }</p>
				</section>

				<section class="kcp-design__content">
					<div class="kcp-design__legend">
						${ zones.map( ( zone ) => {
							const selection = state.selections[ zone.id ] || null;

							return `
								<button type="button" class="kcp-design__legend-item${ selection ? ' is-selected' : '' }" data-zone-id="${ escapeHtml( zone.id ) }">
									${ this.renderLegendCheckbox( selection ) }
									<span class="kcp-design__legend-label">${ escapeHtml( zone.label ) }</span>
								</button>
							`;
						} ).join( '' ) }
					</div>

					<div class="kcp-design__cabinet kcp-design__cabinet--handle${ showHandles ? '' : ' kcp-design__cabinet--greeploos' }">
						<div class="kcp-design__visual">
							${ renderPreviewClipDefs() }
							${ imageUrl
								? `<img class="kcp-design__image" src="${ escapeHtml( imageUrl ) }" alt="" loading="lazy" decoding="async" />`
								: '<div class="kcp-design__image kcp-design__image--placeholder"></div>' }
							<div class="kcp-design__preview-stage">
								${ renderCabinetOverlays( state.selections, config.preview_masks || {}, { showHandle: showHandles } ) }
								<div class="kcp-design__hotspots">
									${ zones.map( ( zone ) => this.renderHotspot(
										zone,
										state.selections[ zone.id ] || null,
										state.activeZoneId
									) ).join( '' ) }
								</div>
							</div>
							${ this.renderFooter( config ) }
						</div>
					</div>
				</section>
			</div>
		`;

		this.modal.render( state );
		this.bindInteractions();
		bindPreviewStageSync( this.root );

		if ( ! this.resizeBound ) {
			window.addEventListener( 'resize', this.handleResize );
			this.resizeBound = true;
		}
	}

	handleKeydown = ( event ) => {
		if ( 'Escape' === event.key ) {
			this.store.closeModal();
		}
	};
}
