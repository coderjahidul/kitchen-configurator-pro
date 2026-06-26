/**
 * Cabinet child-list step UI — matched to reference hoge-kasten-143cm-hoog page.
 */

import { escapeHtml } from '../utils/helpers.js';
import { syncKitchenTypeFromUrl } from '../design/design-selection-storage.js';

export class CabinetListStep {
	constructor( root, config ) {
		this.root = root;
		this.config = config;
	}

	init() {
		document.body.classList.add( 'kcp-cabinet-select-active', 'kcp-cabinet-group-active', 'kcp-cabinet-list-active' );
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

	renderBreadcrumbs( config ) {
		const crumbs = Array.isArray( config.breadcrumbs ) ? config.breadcrumbs : [
			{
				label: String( config.breadcrumb_parent || '' ).trim(),
				url: String( config.breadcrumb_parent_url || '' ).trim(),
			},
			{
				label: String( config.breadcrumb_middle || '' ).trim(),
				url: String( config.breadcrumb_middle_url || '' ).trim(),
			},
			{
				label: String( config.breadcrumb_group || '' ).trim(),
				url: String( config.breadcrumb_group_url || '' ).trim(),
			},
		].filter( ( crumb ) => crumb.label );

		if ( ! crumbs.length ) {
			return '';
		}

		return `
			<nav class="kcp-cabinet-select__breadcrumbs" aria-label="Breadcrumb">
				<ul class="kcp-cabinet-select__breadcrumb-list">
					${ crumbs.map( ( crumb, index ) => {
						const isLast = index === crumbs.length - 1;
						const label = String( crumb.label || '' ).trim();
						const url = String( crumb.url || '' ).trim();

						if ( isLast ) {
							return `<li><span aria-current="page">${ escapeHtml( label ) }</span></li>`;
						}

						return `<li>${ url
							? `<a href="${ escapeHtml( url ) }">${ escapeHtml( label ) }</a>`
							: escapeHtml( label ) }</li>`;
					} ).join( '' ) }
				</ul>
			</nav>
		`;
	}

	renderItems( items ) {
		if ( ! items.length ) {
			return `<p class="kcp-cabinet-select__empty">${ escapeHtml( 'Geen kasten beschikbaar.' ) }</p>`;
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
		const items = Array.isArray( config.items ) ? config.items : [];
		const backUrl = String( config.back_url || '' ).trim();

		this.root.innerHTML = `
			<div class="kcp-cabinet-group__page kcp-cabinet-list__page">
				<div class="kcp-cabinet-list__intro">
					${ this.renderBreadcrumbs( config ) }
					${ backUrl
						? `<a class="kcp-cabinet-select__link kcp-cabinet-select__link--back" href="${ escapeHtml( backUrl ) }">${ escapeHtml( config.back_label || 'terug naar kasten' ) }</a>`
						: '' }
					<div class="kcp-cabinet-group__header kcp-cabinet-list__header">
						<h1 class="kcp-cabinet-select__title kcp-cabinet-group__title">${ escapeHtml( config.heading || '' ) }</h1>
					</div>
				</div>

				${ this.renderItems( items ) }
			</div>
		`;
	}
}
