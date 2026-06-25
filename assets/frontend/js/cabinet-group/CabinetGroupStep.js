/**
 * Cabinet group step UI — subcategory grid matched to reference layout.
 */

import { escapeHtml } from '../utils/helpers.js';
import {
	loadDesignSelections,
	loadKitchenType,
	syncKitchenTypeFromUrl,
} from '../design/design-selection-storage.js';
import {
	kitchenTypeLabel,
	normalizeKitchenType,
	showsHandleStrip,
} from '../design/kitchen-type-config.js';

export class CabinetGroupStep {
	constructor( root, config ) {
		this.root = root;
		this.config = config;
	}

	init() {
		document.body.classList.add( 'kcp-cabinet-select-active', 'kcp-cabinet-group-active' );
		syncKitchenTypeFromUrl();
		this.render();
		this.bindPageRestore();
	}

	bindPageRestore() {
		if ( this.pageRestoreBound ) {
			return;
		}

		window.addEventListener( 'pageshow', ( event ) => {
			if ( event.persisted ) {
				syncKitchenTypeFromUrl();
				this.render();
			}
		} );
		this.pageRestoreBound = true;
	}

	getKitchenType() {
		return normalizeKitchenType(
			loadKitchenType() || this.config.default_kitchen_type || ''
		);
	}

	findCatalogOption( options, selection ) {
		if ( ! Array.isArray( options ) || ! selection ) {
			return null;
		}

		if ( selection.id ) {
			const byId = options.find( ( option ) => Number( option.id ) === Number( selection.id ) );
			if ( byId ) {
				return byId;
			}
		}

		const name = String( selection.name || '' ).trim().toLowerCase();
		if ( ! name ) {
			return null;
		}

		return options.find( ( option ) => {
			return String( option.name || '' ).trim().toLowerCase() === name;
		} ) || null;
	}

	resolveSelections( stored ) {
		const catalog = this.config.catalog_options || {};
		const resolved = { ...( stored || {} ) };

		Object.keys( resolved ).forEach( ( zoneId ) => {
			const selection = resolved[ zoneId ];
			const options = catalog[ zoneId ];
			const fresh = this.findCatalogOption( options, selection );

			if ( fresh ) {
				resolved[ zoneId ] = { ...selection, ...fresh };
			}
		} );

		return resolved;
	}

	summaryLabel( zone ) {
		if ( 'handle_strip' === zone.id ) {
			return 'Greep/knop';
		}
		if ( 'front' === zone.id ) {
			return 'Front';
		}
		if ( 'cabinet' === zone.id ) {
			return 'Kastkleur';
		}
		if ( 'plinth' === zone.id ) {
			return 'Plintkleur';
		}
		const label = String( zone.label || '' );
		return label.charAt( 0 ).toUpperCase() + label.slice( 1 );
	}

	renderBreadcrumbs( config ) {
		const parentUrl = String( config.breadcrumb_parent_url || '' ).trim();
		const parentLabel = String( config.breadcrumb_parent || '' ).trim();
		const middleUrl = String( config.breadcrumb_middle_url || '' ).trim();
		const middleLabel = String( config.breadcrumb_middle || '' ).trim();
		const currentLabel = String( config.breadcrumb_current || config.heading || '' ).trim();

		if ( ! parentLabel && ! middleLabel && ! currentLabel ) {
			return '';
		}

		return `
			<nav class="kcp-cabinet-select__breadcrumbs" aria-label="Breadcrumb">
				<ul class="kcp-cabinet-select__breadcrumb-list">
					${ parentLabel
						? `<li>${ parentUrl
							? `<a href="${ escapeHtml( parentUrl ) }">${ escapeHtml( parentLabel ) }</a>`
							: escapeHtml( parentLabel ) }</li>`
						: '' }
					${ middleLabel
						? `<li>${ middleUrl
							? `<a href="${ escapeHtml( middleUrl ) }">${ escapeHtml( middleLabel ) }</a>`
							: escapeHtml( middleLabel ) }</li>`
						: '' }
					${ currentLabel
						? `<li><span aria-current="page">${ escapeHtml( currentLabel ) }</span></li>`
						: '' }
				</ul>
			</nav>
		`;
	}

	renderSummary( selections, kitchenType ) {
		const zones = Array.isArray( this.config.design_zones ) ? this.config.design_zones : [];
		const showHandles = showsHandleStrip( kitchenType );
		const lines = [];

		lines.push(
			`<span class="kcp-cabinet-select__summary-item"><strong>${ escapeHtml( 'Keukentype' ) }:</strong> ${ escapeHtml( kitchenTypeLabel( kitchenType ) ) }</span>`
		);

		zones.forEach( ( zone ) => {
			if ( 'handle_strip' === zone.id && ! showHandles ) {
				return;
			}

			const selection = selections[ zone.id ];
			if ( ! selection?.name ) {
				return;
			}

			lines.push(
				`<span class="kcp-cabinet-select__summary-item"><strong>${ escapeHtml( this.summaryLabel( zone ) ) }:</strong> ${ escapeHtml( selection.name ) }</span>`
			);
		} );

		if ( lines.length <= 1 ) {
			return '';
		}

		return `
			<div class="kcp-cabinet-group__summary">
				<h4 class="kcp-cabinet-select__summary-title">${ escapeHtml( 'Jouw ontwerp' ) }</h4>
				<p class="kcp-cabinet-select__design-parts">${ lines.join( '' ) }</p>
			</div>
		`;
	}

	renderItems( items ) {
		if ( ! items.length ) {
			return `<p class="kcp-cabinet-select__empty">${ escapeHtml( 'Geen kasttypes beschikbaar.' ) }</p>`;
		}

		return `
			<ul class="kcp-cabinet-group__grid">
				${ items.map( ( item ) => {
					const href = String( item.url || '' ).trim();
					const imageUrl = String( item.image_url || '' ).trim();
					const tag = href ? 'a' : 'div';
					const attrs = href ? `href="${ escapeHtml( href ) }"` : '';

					return `
						<li class="kcp-cabinet-group__item">
							<${ tag } class="kcp-cabinet-group__card" ${ attrs }>
								<span class="kcp-cabinet-group__card-media${ imageUrl ? '' : ' kcp-cabinet-group__card-media--placeholder' }">
									${ imageUrl
										? `<img src="${ escapeHtml( imageUrl ) }" alt="" loading="lazy" decoding="async" />`
										: '' }
								</span>
								<h4 class="kcp-cabinet-group__card-title">${ escapeHtml( item.name || '' ) }</h4>
							</${ tag }>
						</li>
					`;
				} ).join( '' ) }
			</ul>
		`;
	}

	render() {
		const config = this.config;
		const kitchenType = this.getKitchenType();
		const selections = this.resolveSelections( loadDesignSelections() );
		const items = Array.isArray( config.items ) ? config.items : [];
		const backUrl = String( config.back_url || '' ).trim();

		this.root.innerHTML = `
			<div class="kcp-cabinet-group__page">
				${ this.renderBreadcrumbs( config ) }
				${ backUrl
					? `<a class="kcp-cabinet-select__link kcp-cabinet-select__link--back" href="${ escapeHtml( backUrl ) }">${ escapeHtml( config.back_label || 'terug naar kasten' ) }</a>`
					: '' }

				<div class="kcp-cabinet-group__header">
					<h1 class="kcp-cabinet-select__title kcp-cabinet-group__title">${ escapeHtml( config.heading || '' ) }</h1>
				</div>

				${ this.renderItems( items ) }
				${ this.renderSummary( selections, kitchenType ) }
			</div>
		`;
	}
}
