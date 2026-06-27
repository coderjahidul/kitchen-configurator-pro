/**
 * Cabinet detail step UI — fully dynamic from server config.
 */

import { escapeHtml } from '../utils/helpers.js';
import { KcpApi } from '../api/client.js';
import { syncKitchenTypeFromUrl } from '../design/design-selection-storage.js';

export class CabinetDetailStep {
	constructor( root, config ) {
		this.root = root;
		this.config = config;
		this.labels = config.labels || {};
		this.api = new KcpApi( config.api || {} );
		this.selections = this.buildInitialSelections( config );
		this.displayPrice = Number( config.display_price ?? config.base_price ?? 0 );
		this.pricingRequestId = 0;
		this.cartAdding = false;
		this.cartMessage = '';
		this.cartError = '';
	}

	buildInitialSelections( config ) {
		const defaults = config.selections || {};

		return {
			width: Number( defaults.width || config.dimensions?.width?.value || 0 ),
			height: Number( defaults.height || config.dimensions?.height?.value || 0 ),
			depth: Number( defaults.depth || config.dimensions?.depth?.value || 0 ),
			plinth: Number( defaults.plinth || config.plinth?.value || 0 ),
			upsells: Array.isArray( defaults.upsells ) ? [ ...defaults.upsells ] : [],
			quantity: Math.max( 1, Number( defaults.quantity || 1 ) ),
		};
	}

	init() {
		document.body.classList.add( 'kcp-cabinet-select-active', 'kcp-cabinet-detail-active' );
		syncKitchenTypeFromUrl();
		this.render();
		this.bindEvents();
		this.refreshPrice();
		this.bindPageRestore();
	}

	bindPageRestore() {
		if ( this.pageRestoreBound ) {
			return;
		}

		window.addEventListener( 'pageshow', ( event ) => {
			if ( event.persisted ) {
				syncKitchenTypeFromUrl();
				this.selections = this.buildInitialSelections( this.config );
				this.render();
				this.refreshPrice();
			}
		} );
		this.pageRestoreBound = true;
	}

	label( key, fallback = '' ) {
		return String( this.labels[ key ] ?? fallback ).trim();
	}

	formatPrice( value ) {
		const amount = Number( value || 0 );
		return `${ amount.toLocaleString( 'nl-NL', { minimumFractionDigits: 0, maximumFractionDigits: 0 } ) },-`;
	}

	canAddToCart() {
		if ( ! this.config.cart_enabled ) {
			return false;
		}

		if ( this.cartAdding ) {
			return false;
		}

		return (
			Number( this.config.cabinet_id || 0 ) > 0
			&& Number( this.config.layout_id || 0 ) > 0
			&& Number( this.selections.width || 0 ) > 0
			&& Number( this.selections.height || 0 ) > 0
			&& Number( this.selections.depth || 0 ) > 0
		);
	}

	updateCartButtonState() {
		const button = this.root.querySelector( '[data-kcp-add-to-cart]' );

		if ( ! button ) {
			return;
		}

		const enabled = this.canAddToCart();
		button.disabled = ! enabled;
		button.setAttribute( 'aria-disabled', enabled ? 'false' : 'true' );
		button.textContent = this.cartAdding
			? this.label( 'adding_to_cart', 'Toevoegen…' )
			: this.label( 'add_to_cart', 'Voeg toe aan winkelwagen' );
	}

	updateCartFeedback() {
		const successEl = this.root.querySelector( '[data-kcp-cart-success]' );
		const errorEl = this.root.querySelector( '[data-kcp-cart-error]' );

		if ( successEl ) {
			successEl.textContent = this.cartMessage;
			successEl.hidden = '' === this.cartMessage;
		}

		if ( errorEl ) {
			errorEl.textContent = this.cartError;
			errorEl.hidden = '' === this.cartError;
		}
	}

	buildConfigurationPayload() {
		const cabinetId = Number( this.config.cabinet_id || 0 );

		if ( cabinetId <= 0 ) {
			return null;
		}

		const globalOptions = {};
		const plinthHeight = Number( this.selections.plinth || 0 );

		if ( plinthHeight > 0 ) {
			globalOptions.plinth_height = plinthHeight;
		}

		return {
			schema_version: '1.0',
			layout_id: Number( this.config.layout_id || 0 ),
			title: String( this.config.heading || '' ).trim(),
			cabinets: [
				{
					cabinet_id: cabinetId,
					dimensions: {
						width: Number( this.selections.width || 0 ),
						height: Number( this.selections.height || 0 ),
						depth: Number( this.selections.depth || 0 ),
					},
					accessories: [ ...this.selections.upsells ],
				},
			],
			...( Object.keys( globalOptions ).length ? { global_options: globalOptions } : {} ),
		};
	}

	async refreshWooCartFragments() {
		if ( typeof window.jQuery !== 'undefined' ) {
			window.jQuery( document.body ).trigger( 'wc_fragment_refresh' );
			return;
		}

		const ajaxUrl = window.wc_cart_fragments_params?.wc_ajax_url;

		if ( ! ajaxUrl ) {
			return;
		}

		try {
			const response = await fetch(
				String( ajaxUrl ).replace( '%%endpoint%%', 'get_refreshed_fragments' ),
				{ credentials: 'same-origin' }
			);
			const data = await response.json();

			if ( ! data?.fragments ) {
				return;
			}

			Object.entries( data.fragments ).forEach( ( [ selector, html ] ) => {
				document.querySelectorAll( selector ).forEach( ( node ) => {
					const template = document.createElement( 'div' );
					template.innerHTML = String( html );
					const replacement = template.firstElementChild;

					if ( replacement ) {
						node.replaceWith( replacement );
					}
				} );
			} );
		} catch ( error ) {
			// Fragment refresh is best-effort; cart add already succeeded.
		}
	}

	async addToCart() {
		if ( ! this.canAddToCart() ) {
			return;
		}

		const payload = this.buildConfigurationPayload();

		if ( ! payload ) {
			this.cartError = this.label( 'cart_error', 'Kon product niet toevoegen aan winkelwagen. Probeer het opnieuw.' );
			this.updateCartFeedback();
			return;
		}

		this.cartAdding = true;
		this.cartMessage = '';
		this.cartError = '';
		this.updateCartButtonState();
		this.updateCartFeedback();

		try {
			const { data } = await this.api.createConfiguration( payload );
			const uuid = String( data?.uuid || '' ).trim();

			if ( ! uuid ) {
				throw new Error( this.label( 'cart_error', 'Kon product niet toevoegen aan winkelwagen. Probeer het opnieuw.' ) );
			}

			const { meta } = await this.api.addToCart( uuid, this.selections.quantity );
			const redirect = String( meta?.redirect || '' ).trim();

			if ( redirect && this.config.cart_redirect ) {
				window.location.href = redirect;
				return;
			}

			await this.refreshWooCartFragments();
			this.cartMessage = this.label( 'cart_success', 'Product toegevoegd aan je winkelwagen.' );
		} catch ( error ) {
			this.cartError = error?.message || this.label( 'cart_error', 'Kon product niet toevoegen aan winkelwagen. Probeer het opnieuw.' );
		} finally {
			this.cartAdding = false;
			this.updateCartButtonState();
			this.updateCartFeedback();
		}
	}

	bindEvents() {
		if ( this.eventsBound ) {
			return;
		}

		this.eventsBound = true;

		this.root.addEventListener( 'click', ( event ) => {
			const addToCart = event.target.closest( '[data-kcp-add-to-cart]' );

			if ( addToCart ) {
				event.preventDefault();
				this.addToCart();
				return;
			}

			const dec = event.target.closest( '[data-kcp-qty-dec]' );
			const inc = event.target.closest( '[data-kcp-qty-inc]' );

			if ( dec ) {
				this.selections.quantity = Math.max( 1, this.selections.quantity - 1 );
				this.syncQuantityInput();
				this.refreshPrice();
				return;
			}

			if ( inc ) {
				this.selections.quantity += 1;
				this.syncQuantityInput();
				this.refreshPrice();
			}
		} );

		this.root.addEventListener( 'change', ( event ) => {
			const target = event.target;

			if ( ! ( target instanceof HTMLSelectElement ) && ! ( target instanceof HTMLInputElement ) ) {
				return;
			}

			if ( target.matches( '[data-kcp-dimension]' ) ) {
				const axis = target.dataset.kcpDimension;
				this.selections[ axis ] = Number( target.value || 0 );
				this.refreshPrice();
				this.updateCartButtonState();
				return;
			}

			if ( target.matches( '[data-kcp-qty-input]' ) ) {
				this.selections.quantity = Math.max( 1, parseInt( target.value, 10 ) || 1 );
				this.syncQuantityInput();
				this.refreshPrice();
				return;
			}

			if ( target.matches( '[data-kcp-upsell]' ) ) {
				const upsellId = Number( target.value || 0 );

				if ( target.checked ) {
					if ( ! this.selections.upsells.includes( upsellId ) ) {
						this.selections.upsells.push( upsellId );
					}
				} else {
					this.selections.upsells = this.selections.upsells.filter( ( id ) => id !== upsellId );
				}

				this.refreshPrice();
			}
		} );
	}

	syncQuantityInput() {
		const input = this.root.querySelector( '[data-kcp-qty-input]' );
		if ( input ) {
			input.value = String( this.selections.quantity );
		}
	}

	updatePriceDisplay() {
		const priceEl = this.root.querySelector( '[data-kcp-price]' );
		if ( priceEl ) {
			priceEl.textContent = this.formatPrice( this.displayPrice );
		}
	}

	buildPricingPayload() {
		const cabinetId = Number( this.config.cabinet_id || 0 );

		if ( cabinetId <= 0 ) {
			return null;
		}

		const globalOptions = {};

		return {
			schema_version: '1.0',
			layout_id: Number( this.config.layout_id || 0 ),
			cabinets: [
				{
					cabinet_id: cabinetId,
					dimensions: {
						width: Number( this.selections.width || 0 ),
						height: Number( this.selections.height || 0 ),
						depth: Number( this.selections.depth || 0 ),
					},
					accessories: [ ...this.selections.upsells ],
				},
			],
			...( Object.keys( globalOptions ).length ? { global_options: globalOptions } : {} ),
		};
	}

	async refreshPrice() {
		const payload = this.buildPricingPayload();
		const fallback = Number( this.config.base_price || 0 );

		if ( ! payload || payload.layout_id <= 0 ) {
			this.displayPrice = fallback;
			this.updatePriceDisplay();
			return;
		}

		const requestId = ++this.pricingRequestId;

		try {
			const { data } = await this.api.calculatePricing( payload );

			if ( requestId !== this.pricingRequestId ) {
				return;
			}

			this.displayPrice = Number( data?.total ?? this.config.base_price ?? 0 );
		} catch ( error ) {
			if ( requestId !== this.pricingRequestId ) {
				return;
			}

			this.displayPrice = fallback;
		}

		this.updatePriceDisplay();
	}

	renderSelect( id, label, options, axis ) {
		if ( ! options.length ) {
			return '';
		}

		const selectedValue = Number( this.selections[ axis ] || 0 );

		return `
			<div class="kcp-cabinet-detail__field">
				<label class="kcp-cabinet-detail__field-label" for="${ escapeHtml( id ) }">${ escapeHtml( label ) }</label>
				<div class="kcp-cabinet-detail__select">
					<select id="${ escapeHtml( id ) }" name="${ escapeHtml( id ) }" data-kcp-dimension="${ escapeHtml( axis ) }" aria-label="${ escapeHtml( label ) }">
						${ options.map( ( option ) => {
							const value = Number( option.value || 0 );
							const isSelected = value === selectedValue || ( ! selectedValue && option.selected );
							return `<option value="${ value }"${ isSelected ? ' selected' : '' }>${ escapeHtml( option.label || '' ) }</option>`;
						} ).join( '' ) }
					</select>
				</div>
			</div>
		`;
	}

	renderBreadcrumbs( config ) {
		const crumbs = Array.isArray( config.breadcrumbs ) ? config.breadcrumbs : [];

		if ( ! crumbs.length ) {
			return '';
		}

		return `
			<nav class="kcp-cabinet-select__breadcrumbs" aria-label="Breadcrumb">
				<ul class="kcp-cabinet-select__breadcrumb-list">
					${ crumbs.map( ( crumb ) => {
						const label = String( crumb.label || '' ).trim();
						const url = String( crumb.url || '' ).trim();
						return `<li>${ url
							? `<a href="${ escapeHtml( url ) }">${ escapeHtml( label ) }</a>`
							: escapeHtml( label ) }</li>`;
					} ).join( '' ) }
				</ul>
			</nav>
		`;
	}

	renderGallery( images, heading ) {
		const items = Array.isArray( images ) ? images.filter( ( item ) => item?.url ) : [];

		if ( ! items.length ) {
			return `
				<div class="kcp-cabinet-detail__gallery">
					<div class="kcp-cabinet-detail__image-frame kcp-cabinet-detail__image-frame--placeholder"></div>
				</div>
			`;
		}

		const primary = items[ 0 ];
		const alt = String( primary.alt || heading || '' ).trim();

		return `
			<div class="kcp-cabinet-detail__gallery">
				<div class="kcp-cabinet-detail__image-frame">
					<img class="kcp-cabinet-detail__image" src="${ escapeHtml( primary.url ) }" alt="${ escapeHtml( alt ) }" loading="lazy" decoding="async" />
				</div>
				${ items.length > 1 || primary.thumb !== false
					? `<div class="kcp-cabinet-detail__thumb">
						<div class="kcp-cabinet-detail__image-frame kcp-cabinet-detail__image-frame--thumb">
							<img class="kcp-cabinet-detail__image" src="${ escapeHtml( primary.url ) }" alt="" loading="lazy" decoding="async" />
						</div>
					</div>`
					: '' }
			</div>
		`;
	}

	renderProductInfo( items ) {
		if ( ! items.length ) {
			return '';
		}

		return `
			<div class="kcp-cabinet-detail__info">
				<h4 class="kcp-cabinet-detail__block-title">${ escapeHtml( this.label( 'product_info', 'Productinformatie' ) ) }</h4>
				<ul class="kcp-cabinet-detail__info-list">
					${ items.map( ( item ) => `<li>${ escapeHtml( item ) }</li>` ).join( '' ) }
				</ul>
			</div>
		`;
	}

	renderUpsells( upsells ) {
		if ( ! upsells.length ) {
			return '';
		}

		return `
			<div class="kcp-cabinet-detail__upsell">
				<h4 class="kcp-cabinet-detail__block-title">${ escapeHtml( this.label( 'upsell_heading', 'Maak je aankoop compleet' ) ) }</h4>
				${ upsells.map( ( upsell ) => {
					const id = Number( upsell.id || 0 );
					const checked = this.selections.upsells.includes( id );
					return `
						<label class="kcp-cabinet-detail__upsell-option">
							<input type="checkbox" value="${ id }" data-kcp-upsell${ checked ? ' checked' : '' } />
							<span>${ escapeHtml( upsell.label || upsell.name || '' ) }</span>
						</label>
					`;
				} ).join( '' ) }
			</div>
		`;
	}

	render() {
		const config = this.config;
		const dimensions = config.dimensions || {};
		const plinthOptions = Array.isArray( config.plinth?.options ) ? config.plinth.options : [];
		const productInfo = Array.isArray( config.product_info ) ? config.product_info : [];
		const upsells = Array.isArray( config.upsells ) ? config.upsells : [];
		const backUrl = String( config.back_url || '' ).trim();
		const ctaDisabled = ! this.canAddToCart();

		this.root.innerHTML = `
			<div class="kcp-cabinet-detail__page">
				${ this.renderBreadcrumbs( config ) }

				<div class="kcp-cabinet-detail__intro">
					<h1 class="kcp-cabinet-select__title kcp-cabinet-detail__title">${ escapeHtml( config.heading || '' ) }</h1>
					${ backUrl
						? `<a class="kcp-cabinet-select__link kcp-cabinet-select__link--back" href="${ escapeHtml( backUrl ) }">${ escapeHtml( config.back_label || this.label( 'back', 'terug naar kasten' ) ) }</a>`
						: '' }
				</div>

				<div class="kcp-cabinet-detail__content">
					<div class="kcp-cabinet-detail__visual">
						${ this.renderGallery( config.images, config.heading ) }
					</div>

					<div class="kcp-cabinet-detail__panel">
						<div class="kcp-cabinet-detail__panel-table">
							<div class="kcp-cabinet-detail__panel-grid">
								<div class="kcp-cabinet-detail__formats">
									<h4 class="kcp-cabinet-detail__block-title">${ escapeHtml( this.label( 'select_format', 'Selecteer formaat' ) ) }</h4>
									${ this.renderSelect( 'kcp_width', this.label( 'width', 'Breedte' ), dimensions.width?.options || [], 'width' ) }
									${ this.renderSelect( 'kcp_height', this.label( 'height', 'Hoogte' ), dimensions.height?.options || [], 'height' ) }
									${ this.renderSelect( 'kcp_depth', this.label( 'depth', 'Diepte' ), dimensions.depth?.options || [], 'depth' ) }
									${ this.renderSelect( 'kcp_plinth', this.label( 'plinth', 'Plinthoogte' ), plinthOptions, 'plinth' ) }
									${ this.renderUpsells( upsells ) }
								</div>

								${ this.renderProductInfo( productInfo ) }
							</div>

							<div class="kcp-cabinet-detail__footer">
								<div class="kcp-cabinet-detail__quantity-block">
									<h4 class="kcp-cabinet-detail__block-title">${ escapeHtml( this.label( 'select_quantity', 'Selecteer aantal' ) ) }</h4>
									<div class="kcp-cabinet-detail__quantity-row">
										<div class="kcp-cabinet-detail__quantity">
											<button type="button" class="kcp-cabinet-detail__qty-btn" data-kcp-qty-dec aria-label="${ escapeHtml( this.label( 'decrease_qty', 'Minder' ) ) }">-</button>
											<input type="number" class="kcp-cabinet-detail__qty-input" data-kcp-qty-input value="${ this.selections.quantity }" min="1" aria-label="${ escapeHtml( this.label( 'quantity', 'Aantal' ) ) }" />
											<button type="button" class="kcp-cabinet-detail__qty-btn" data-kcp-qty-inc aria-label="${ escapeHtml( this.label( 'increase_qty', 'Meer' ) ) }">+</button>
										</div>
										<p class="kcp-cabinet-detail__unit-price">
											<span class="kcp-cabinet-detail__price" data-kcp-price>${ escapeHtml( this.formatPrice( this.displayPrice ) ) }</span>
											<span class="kcp-cabinet-detail__unit-label">${ escapeHtml( this.label( 'per_unit', 'per stuk' ) ) }</span>
										</p>
									</div>
								</div>

								<div class="kcp-cabinet-detail__actions">
									<button type="button" class="kcp-cabinet-detail__cta" data-kcp-add-to-cart${ ctaDisabled ? ' disabled' : '' }${ ctaDisabled ? ' aria-disabled="true"' : '' }>
										${ escapeHtml( this.cartAdding ? this.label( 'adding_to_cart', 'Toevoegen…' ) : this.label( 'add_to_cart', 'Voeg toe aan winkelwagen' ) ) }
									</button>
									<p class="kcp-cabinet-detail__cart-message kcp-cabinet-detail__cart-message--success" data-kcp-cart-success role="status"${ this.cartMessage ? '' : ' hidden' }>${ escapeHtml( this.cartMessage ) }</p>
									<p class="kcp-cabinet-detail__cart-message kcp-cabinet-detail__cart-message--error" data-kcp-cart-error role="alert"${ this.cartError ? '' : ' hidden' }>${ escapeHtml( this.cartError ) }</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;

		this.updateCartButtonState();
	}
}
