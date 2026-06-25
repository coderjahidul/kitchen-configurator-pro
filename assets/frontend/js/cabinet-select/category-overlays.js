/**
 * Category cabinet colour overlays — matched to cabinet-group-selection-img-holder-handle.
 */

import { escapeHtml } from '../utils/helpers.js';

/**
 * @return {string}
 */
export function renderCategoryClipDefs() {
	return `
		<svg class="kcp-cs-overlay-defs" aria-hidden="true" focusable="false">
			<defs>
				<clipPath id="kcp-cs-clip-2-cabinet" clipPathUnits="userSpaceOnUse">
					<path d="M151.5 0L110 8V351L0 358.5V359L97 352V584.5L152 589L227 569L226 45.5L151.5 0Z"></path>
				</clipPath>
				<clipPath id="kcp-cs-clip-2-skirt" clipPathUnits="userSpaceOnUse">
					<path d="M0 40V11L80 29.5L85 29V15.4821L140 20L216 0V32.5L149 54L0 40Z"></path>
				</clipPath>
				<clipPath id="kcp-cs-clip-3-cabinet" clipPathUnits="userSpaceOnUse">
					<path d="M168 0.5L122.7 8.5V252.653L0 273.8V275L60.5 287.5L64.5 287V285.5L211.5 274.5L216 276L221 275.5L220 32.5L168 0.5Z"></path>
				</clipPath>
				<clipPath id="kcp-cs-clip-4-cabinet" clipPathUnits="userSpaceOnUse">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M0 245.255V14.3784L52.8175 13.3247V0.118298L57.2592 0L140.061 7.03249L141.431 228.15L57.6294 249.988L0 245.255Z"></path>
				</clipPath>
				<clipPath id="kcp-cs-clip-4-skirt" clipPathUnits="userSpaceOnUse">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M0.930664 14.2787L83.6213 32.7642L89.5689 32.0219V18.1606L147.014 23.1113L229 0.894287V35.7009L157.821 58.6585L0.930664 43.1066V14.2787Z"></path>
				</clipPath>
			</defs>
		</svg>
	`;
}

/**
 * @param {object|null} selection Selected option.
 * @param {object}      options  Render options.
 * @return {string}
 */
function renderFill( selection, options = {} ) {
	if ( ! selection ) {
		return '';
	}

	const mediaClass = options.mediaClass || '';
	const fillClass = options.fillClass || mediaClass.replace( '__media', '__fill' );
	const useHex = options.preferHex && selection.hex;

	if ( ! useHex && selection.image_url ) {
		return `<img class="kcp-cs-overlay__media${ mediaClass }" src="${ escapeHtml( selection.image_url ) }" alt="" loading="lazy" decoding="async" />`;
	}

	if ( selection.hex ) {
		return `<div class="kcp-cs-overlay__fill${ fillClass }" style="background-color:${ escapeHtml( selection.hex ) }"></div>`;
	}

	if ( selection.image_url ) {
		return `<img class="kcp-cs-overlay__media${ mediaClass }" src="${ escapeHtml( selection.image_url ) }" alt="" loading="lazy" decoding="async" />`;
	}

	return '';
}

/**
 * @param {object|null} selection Selected option.
 * @return {string}
 */
function fallbackHexAttr( selection ) {
	const hex = String( selection?.hex || '' ).trim();

	return hex ? ` data-fallback-hex="${ escapeHtml( hex ) }"` : '';
}

/**
 * @param {string}       modifier BEM modifier.
 * @param {object|null}  selection Selected option.
 * @param {object}       options  Layer options.
 * @return {string}
 */
function renderMaskedFront( modifier, selection, options ) {
	const fill = renderFill( selection, {
		mediaClass: options.mediaClass,
		preferHex: true,
	} );

	if ( ! fill || ! options.maskUrl ) {
		return '';
	}

	return `
		<div
			class="kcp-cs-overlay kcp-cs-overlay--front kcp-cs-overlay--${ modifier }"
			style="--kcp-cs-mask:url('${ escapeHtml( options.maskUrl ) }')"
			${ fallbackHexAttr( selection ) }
		>
			${ fill }
		</div>
	`;
}

/**
 * @param {string}       modifier BEM modifier.
 * @param {object|null}  selection Selected option.
 * @param {string}       clipId   SVG clip path id.
 * @param {object}       options  Layer options.
 * @return {string}
 */
function renderClippedLayer( modifier, selection, clipId, options = {} ) {
	const fill = renderFill( selection, { preferHex: Boolean( options.preferHex ) } );

	if ( ! fill ) {
		return '';
	}

	return `
		<div
			class="kcp-cs-overlay kcp-cs-overlay--${ modifier }"
			style="--kcp-cs-clip:url(#${ escapeHtml( clipId ) })"
			${ fallbackHexAttr( selection ) }
		>
			<div class="kcp-cs-overlay__clip">${ fill }</div>
		</div>
	`;
}

/**
 * @param {object|null} selection Handle selection.
 * @return {string}
 */
function renderHandle( selection ) {
	if ( ! selection?.image_url ) {
		return '';
	}

	return `
		<div class="kcp-cs-overlay kcp-cs-overlay--handle">
			<img class="kcp-cs-overlay__handle-media" src="${ escapeHtml( selection.image_url ) }" alt="" loading="lazy" decoding="async" />
		</div>
	`;
}

/**
 * @param {number} position Visual stack position (2=hoge, 3=boven, 4=onder).
 * @param {object} selections Zone selections.
 * @param {object} masks      Mask URLs for this category.
 * @param {object} options    Render options.
 * @return {string}
 */
export function renderCategoryOverlays( position, selections, masks = {}, options = {} ) {
	const group = Number( position );
	const front = selections.front || null;
	const cabinet = selections.cabinet || null;
	const plinth = selections.plinth || null;
	const handle = selections.handle_strip || null;
	const showHandle = false !== options.showHandle;
	const parts = [];

	if ( 2 === group ) {
		parts.push( renderMaskedFront( 'topdoor', front, {
			maskUrl: masks.front_top || '',
			mediaClass: ' kcp-cs-overlay__media--skew-top',
		} ) );
		parts.push( renderMaskedFront( 'bottomdoor', front, {
			maskUrl: masks.front_bottom || '',
			mediaClass: ' kcp-cs-overlay__media--skew-bottom',
		} ) );
		parts.push( renderClippedLayer( 'cabinet', cabinet, 'kcp-cs-clip-2-cabinet', { preferHex: false } ) );
		parts.push( renderClippedLayer( 'skirt', plinth, 'kcp-cs-clip-2-skirt', { preferHex: false } ) );
		if ( showHandle ) {
			parts.push( renderHandle( handle ) );
		}
	} else if ( 3 === group ) {
		parts.push( renderMaskedFront( 'door', front, {
			maskUrl: masks.front || '',
			mediaClass: ' kcp-cs-overlay__media--skew-wall',
		} ) );
		parts.push( renderClippedLayer( 'cabinet', cabinet, 'kcp-cs-clip-3-cabinet', { preferHex: false } ) );
		if ( showHandle ) {
			parts.push( renderHandle( handle ) );
		}
	} else if ( 4 === group ) {
		parts.push( renderMaskedFront( 'door', front, {
			maskUrl: masks.front || '',
			mediaClass: ' kcp-cs-overlay__media--skew-base',
		} ) );
		parts.push( renderClippedLayer( 'cabinet', cabinet, 'kcp-cs-clip-4-cabinet', { preferHex: false } ) );
		parts.push( renderClippedLayer( 'skirt', plinth, 'kcp-cs-clip-4-skirt', { preferHex: false } ) );
		if ( showHandle ) {
			parts.push( renderHandle( handle ) );
		}
	}

	const markup = parts.filter( Boolean ).join( '' );

	if ( ! markup ) {
		return '';
	}

	return `
		<div class="kcp-cs-overlays kcp-cs-overlays--pos-${ group }" aria-hidden="true">
			${ markup }
		</div>
	`;
}
