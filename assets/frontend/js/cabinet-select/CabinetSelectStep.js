/**
 * Cabinet select step UI — structure matched to cabinet-group-selection-page.
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
import {
	renderCategoryClipDefs,
	renderCategoryOverlays,
} from './category-overlays.js';

export class CabinetSelectStep {
	constructor( root, config ) {
		this.root = root;
		this.config = config;
		this.visualScaleObserver = null;
	}

	init() {
		document.body.classList.add( 'kcp-cabinet-select-active' );
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

	resolveCategoryAssets( category, kitchenType ) {
		const type = normalizeKitchenType( kitchenType );
		const imageUrls = category.image_urls || {};
		const maskSets = category.category_masks_by_type || {};

		return {
			image_url: String( imageUrls[ type ] || category.image_url || '' ).trim(),
			category_masks: maskSets[ type ] || category.category_masks || {},
		};
	}

	prepareCategories( categories, kitchenType ) {
		return categories.map( ( category ) => ( {
			...category,
			...this.resolveCategoryAssets( category, kitchenType ),
		} ) );
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

	sortCategories( categories ) {
		return [ ...categories ].sort( ( a, b ) => {
			return Number( b.visual_position || 0 ) - Number( a.visual_position || 0 );
		} );
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
		const parentUrl = String( config.breadcrumb_parent_url || config.back_url || '' ).trim();
		const parentLabel = String( config.breadcrumb_parent || '' ).trim();
		const currentLabel = String( config.breadcrumb_current || config.heading || '' ).trim();

		if ( ! parentLabel && ! currentLabel ) {
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
			return `<p class="kcp-cabinet-select__design-parts kcp-cabinet-select__design-parts--empty">${ escapeHtml( 'Nog geen kleuren geselecteerd.' ) }</p>`;
		}

		return `<p class="kcp-cabinet-select__design-parts">${ lines.join( '' ) }</p>`;
	}

	buildCategoryUrl( category ) {
		const base = String( this.config.category_list_url || '' ).trim();
		if ( ! base ) {
			return '';
		}
		const url = new URL( base, window.location.origin );
		url.searchParams.set( 'kcp_category', String( category.id ) );
		if ( category.slug ) {
			url.searchParams.set( 'kcp_category_slug', category.slug );
		}
		return url.toString();
	}

	renderCategories( categories ) {
		if ( ! categories.length ) {
			return `<p class="kcp-cabinet-select__empty">${ escapeHtml( 'Geen kastgroepen beschikbaar. Voeg categorieën toe in het admin panel.' ) }</p>`;
		}

		return `
			<ul class="kcp-cabinet-select__categories">
				${ categories.map( ( category ) => {
					const href = this.buildCategoryUrl( category );
					const position = Number( category.visual_position || 0 );
					const tag = href ? 'a' : 'button';
					const attrs = href
						? `href="${ escapeHtml( href ) }"`
						: `type="button"`;

					return `
						<li>
							<${ tag }
								class="kcp-cabinet-select__category kcp-cabinet-select__category--pos-${ position }"
								${ attrs }
								data-category-id="${ Number( category.id ) }"
								data-category-slug="${ escapeHtml( category.slug || '' ) }"
								data-position="${ position }"
							>
								${ escapeHtml( category.name || '' ) }
							</${ tag }>
						</li>
					`;
				} ).join( '' ) }
			</ul>
		`;
	}

	hasDesignTints( selections, kitchenType ) {
		const zones = showsHandleStrip( kitchenType )
			? [ 'front', 'cabinet', 'plinth', 'handle_strip' ]
			: [ 'front', 'cabinet', 'plinth' ];

		return zones.some( ( zoneId ) => {
			const selection = selections[ zoneId ];
			return Boolean( selection?.hex || selection?.image_url );
		} );
	}

	renderVisualTints( selections, category, kitchenType ) {
		const position = Number( category.visual_position || 0 );
		const masks = category.category_masks || {};

		return renderCategoryOverlays( position, selections, masks, {
			showHandle: showsHandleStrip( kitchenType ),
		} );
	}

	renderVisuals( categories, selections, kitchenType ) {
		const withImages = categories.filter( ( category ) => String( category.image_url || '' ).trim() );
		const showTints = this.hasDesignTints( selections, kitchenType );

		if ( ! withImages.length ) {
			const previewUrl = String( this.config.preview_image_url || '' ).trim();
			return previewUrl
				? `<img class="kcp-cabinet-select__fallback-image" src="${ escapeHtml( previewUrl ) }" alt="" loading="lazy" decoding="async" />`
				: '<div class="kcp-cabinet-select__fallback-image kcp-cabinet-select__fallback-image--placeholder"></div>';
		}

		return `
			<div class="kcp-cabinet-select__visual-scaler">
				<div class="kcp-cabinet-select__visuals${ showTints ? ' kcp-cabinet-select__visuals--tinted' : '' }" data-kcp-visuals>
					${ showTints ? renderCategoryClipDefs() : '' }
					${ withImages.map( ( category ) => {
						const position = Number( category.visual_position || 0 );
						const href = this.buildCategoryUrl( category );
						const tag = href ? 'a' : 'div';
						const attrs = href ? `href="${ escapeHtml( href ) }"` : '';

						return `
							<${ tag }
								class="kcp-cabinet-select__visual kcp-cabinet-select__visual--pos-${ position }"
								${ attrs }
								data-position="${ position }"
								data-category-id="${ Number( category.id ) }"
							>
								<span class="kcp-cabinet-select__visual-inner">
									<img src="${ escapeHtml( category.image_url ) }" alt="" loading="lazy" decoding="async" />
									${ showTints ? this.renderVisualTints( selections, category, kitchenType ) : '' }
								</span>
							</${ tag }>
						`;
					} ).join( '' ) }
				</div>
			</div>
		`;
	}

	bindHoverSync() {
		const visuals = this.root.querySelector( '[data-kcp-visuals]' );
		if ( ! visuals ) {
			return;
		}

		const setHover = ( position ) => {
			const value = String( position || '' );
			visuals.classList.toggle( 'button-is-hovering', Boolean( value ) );

			this.root.querySelectorAll( '[data-position]' ).forEach( ( element ) => {
				const matches = value && element.getAttribute( 'data-position' ) === value;
				const isCategory = element.classList.contains( 'kcp-cabinet-select__category' );
				const isVisual = element.classList.contains( 'kcp-cabinet-select__visual' );

				if ( isCategory ) {
					element.classList.toggle( 'button-is-hovering', Boolean( matches ) );
				}
				if ( isVisual ) {
					element.classList.toggle( 'kcp-cabinet-select__visual--hover', Boolean( matches ) );
				}
			} );
		};

		const interactive = this.root.querySelectorAll(
			'.kcp-cabinet-select__category[data-position], .kcp-cabinet-select__visual[data-position]'
		);

		interactive.forEach( ( element ) => {
			element.addEventListener( 'mouseenter', () => {
				setHover( element.getAttribute( 'data-position' ) );
			} );
			element.addEventListener( 'mouseleave', () => {
				setHover( '' );
			} );
			element.addEventListener( 'focus', () => {
				setHover( element.getAttribute( 'data-position' ) );
			} );
			element.addEventListener( 'blur', () => {
				setHover( '' );
			} );
		} );
	}

	bindVisualScale() {
		const stage = this.root.querySelector( '.kcp-cabinet-select__stage' );
		const scaler = this.root.querySelector( '.kcp-cabinet-select__visual-scaler' );
		const visuals = this.root.querySelector( '.kcp-cabinet-select__visuals' );

		if ( ! stage || ! scaler || ! visuals ) {
			return;
		}

		const baseWidth = 650;
		const baseHeight = 730;

		const update = () => {
			const available = stage.clientWidth;
			const scale = Math.min( 1, available / baseWidth );

			visuals.style.transform = `scale(${ scale })`;
			visuals.style.transformOrigin = window.matchMedia( '(max-width: 767px)' ).matches
				? 'top center'
				: 'top right';
			scaler.style.width = `${ baseWidth * scale }px`;
			scaler.style.height = `${ baseHeight * scale }px`;
		};

		update();

		if ( this.visualScaleObserver ) {
			this.visualScaleObserver.disconnect();
		}

		if ( 'undefined' !== typeof ResizeObserver ) {
			this.visualScaleObserver = new ResizeObserver( update );
			this.visualScaleObserver.observe( stage );
		} else {
			window.addEventListener( 'resize', update );
		}
	}

	bindOverlayFallbacks() {
		this.root.querySelectorAll( '.kcp-cs-overlay__media, .kcp-cs-overlay__handle-media' ).forEach( ( img ) => {
			img.addEventListener( 'error', () => {
				const overlay = img.closest( '.kcp-cs-overlay' );
				const hex = overlay?.dataset?.fallbackHex;

				if ( hex && ! img.classList.contains( 'kcp-cs-overlay__handle-media' ) ) {
					const fill = document.createElement( 'div' );
					fill.className = img.className.replace( '__media', '__fill' );
					fill.style.backgroundColor = hex;
					img.replaceWith( fill );
					return;
				}

				if ( overlay?.classList.contains( 'kcp-cs-overlay--handle' ) ) {
					overlay.remove();
					return;
				}

				overlay?.remove();
			}, { once: true } );
		} );
	}

	render() {
		const config = this.config;
		const kitchenType = this.getKitchenType();
		const selections = this.resolveSelections( loadDesignSelections() );
		const categories = this.sortCategories(
			this.prepareCategories(
				Array.isArray( config.categories ) ? config.categories : [],
				kitchenType
			)
		);
		const backUrl = String( config.back_url || '' ).trim();
		const editUrl = String( config.design_edit_url || config.back_url || '' ).trim();
		const summaryHeading = String( config.summary_heading || 'Jouw ontwerp' ).trim() || 'Jouw ontwerp';
		const editLabel = String( config.design_edit_label || 'wijzigen' ).trim() || 'wijzigen';

		this.root.innerHTML = `
			<div class="kcp-cabinet-select__page">
				${ this.renderBreadcrumbs( config ) }
				${ backUrl
					? `<a class="kcp-cabinet-select__link kcp-cabinet-select__link--back" href="${ escapeHtml( backUrl ) }">${ escapeHtml( config.back_label || 'terug naar het ontwerp' ) }</a>`
					: '' }

				<div class="kcp-cabinet-select__holder">
					<div class="kcp-cabinet-select__content">
						<h1 class="kcp-cabinet-select__title">${ escapeHtml( config.heading || 'selecteer kasten' ) }</h1>
						<p class="kcp-cabinet-select__description">${ escapeHtml( config.description || '' ) }</p>
						${ this.renderCategories( categories ) }
						<div class="kcp-cabinet-select__summary">
							<h4 class="kcp-cabinet-select__summary-title">${ escapeHtml( summaryHeading ) }</h4>
							${ this.renderSummary( selections, kitchenType ) }
							${ editUrl
								? `<a class="kcp-cabinet-select__link kcp-cabinet-select__link--edit" href="${ escapeHtml( editUrl ) }">${ escapeHtml( editLabel ) }</a>`
								: '' }
						</div>
					</div>

					<div class="kcp-cabinet-select__stage">
						${ this.renderVisuals( categories, selections, kitchenType ) }
					</div>
				</div>
			</div>
		`;

		this.bindHoverSync();
		this.bindVisualScale();
		this.bindOverlayFallbacks();
	}
}
