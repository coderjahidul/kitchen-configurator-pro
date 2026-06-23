/**
 * Cabinet select step UI.
 */

import { escapeHtml } from '../utils/helpers.js';
import { loadDesignSelections } from '../design/design-selection-storage.js';

export class CabinetSelectStep {
	constructor( root, config ) {
		this.root = root;
		this.config = config;
	}

	init() {
		document.body.classList.add( 'kcp-cabinet-select-active' );
		this.render();
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

	renderSummary( selections ) {
		const zones = Array.isArray( this.config.design_zones ) ? this.config.design_zones : [];
		const parts = zones
			.map( ( zone ) => {
				const selection = selections[ zone.id ];
				if ( ! selection?.name ) {
					return '';
				}
				return `<span class="kcp-cabinet-select__summary-item"><strong>${ escapeHtml( this.summaryLabel( zone ) ) }:</strong> ${ escapeHtml( selection.name ) }</span>`;
			} )
			.filter( Boolean );

		if ( ! parts.length ) {
			return `<p class="kcp-cabinet-select__summary-lines kcp-cabinet-select__summary-lines--empty">${ escapeHtml( 'Nog geen kleuren geselecteerd.' ) }</p>`;
		}

		return `<p class="kcp-cabinet-select__summary-lines">${ parts.join( ' ' ) }</p>`;
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

	renderBreadcrumb( config ) {
		const items = [];

		if ( config.breadcrumb_parent ) {
			const parentUrl = String( config.breadcrumb_parent_url || '' ).trim();
			items.push(
				parentUrl
					? `<li><a href="${ escapeHtml( parentUrl ) }">${ escapeHtml( config.breadcrumb_parent ) }</a></li>`
					: `<li><span>${ escapeHtml( config.breadcrumb_parent ) }</span></li>`
			);
		}

		if ( config.breadcrumb_current ) {
			items.push( `<li><span aria-current="page">${ escapeHtml( config.breadcrumb_current ) }</span></li>` );
		}

		if ( ! items.length ) {
			return '';
		}

		return `
			<nav class="kcp-cabinet-select__breadcrumbs" aria-label="Breadcrumb">
				<ul class="kcp-cabinet-select__breadcrumb-list">
					${ items.join( '' ) }
				</ul>
			</nav>
		`;
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

	renderVisuals( categories ) {
		const withImages = categories.filter( ( category ) => String( category.image_url || '' ).trim() );

		if ( ! withImages.length ) {
			const previewUrl = String( this.config.preview_image_url || '' ).trim();
			return previewUrl
				? `<img class="kcp-cabinet-select__fallback-image" src="${ escapeHtml( previewUrl ) }" alt="" loading="lazy" decoding="async" />`
				: '<div class="kcp-cabinet-select__fallback-image kcp-cabinet-select__fallback-image--placeholder"></div>';
		}

		return `
			<div class="kcp-cabinet-select__visuals" data-kcp-visuals>
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
							</span>
						</${ tag }>
					`;
				} ).join( '' ) }
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
			visuals.classList.toggle( 'is-hovering', Boolean( value ) );

			this.root.querySelectorAll( '[data-position]' ).forEach( ( element ) => {
				const matches = value && element.getAttribute( 'data-position' ) === value;
				element.classList.toggle( 'is-active', Boolean( matches ) );
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

	bindCategoryButtons() {
		this.root.querySelectorAll( 'button[data-category-id]' ).forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				button.classList.add( 'is-active' );
			} );
		} );
	}

	render() {
		const config = this.config;
		const selections = loadDesignSelections();
		const categories = Array.isArray( config.categories ) ? config.categories : [];
		const backUrl = String( config.back_url || '' ).trim();
		const editUrl = String( config.design_edit_url || config.back_url || '' ).trim();

		this.root.innerHTML = `
			<div class="kcp-cabinet-select__page">
				${ this.renderBreadcrumb( config ) }

				<div class="kcp-cabinet-select__shell">
					${ backUrl
						? `<a class="kcp-cabinet-select__link kcp-cabinet-select__link--back" href="${ escapeHtml( backUrl ) }">${ escapeHtml( config.back_label || '' ) }</a>`
						: '' }

					<div class="kcp-cabinet-select__holder">
						<section class="kcp-cabinet-select__content">
							<h1 class="kcp-cabinet-select__title">${ escapeHtml( config.heading || '' ) }</h1>
							<p class="kcp-cabinet-select__description">${ escapeHtml( config.description || '' ) }</p>
							${ this.renderCategories( categories ) }
							<aside class="kcp-cabinet-select__summary">
								<h2 class="kcp-cabinet-select__summary-title">${ escapeHtml( config.summary_heading || '' ) }</h2>
								${ this.renderSummary( selections ) }
								${ editUrl
									? `<a class="kcp-cabinet-select__link kcp-cabinet-select__link--edit" href="${ escapeHtml( editUrl ) }">${ escapeHtml( config.design_edit_label || '' ) }</a>`
									: '' }
							</aside>
						</section>

						<section class="kcp-cabinet-select__stage" aria-hidden="true">
							${ this.renderVisuals( categories ) }
						</section>
					</div>
				</div>
			</div>
		`;

		this.bindHoverSync();
		this.bindCategoryButtons();
	}
}
