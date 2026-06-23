/**
 * Cabinet preview overlay helpers.
 * Matched to configurator.keukenkastenfabriek.nl selected-* layers.
 */

import { escapeHtml } from '../utils/helpers.js';

/**
 * @return {string}
 */
export function renderPreviewClipDefs() {
	return `
		<svg class="kcp-design__preview-defs" aria-hidden="true" focusable="false">
			<defs>
				<clipPath id="kcp-design-clip-cabinet" clipPathUnits="userSpaceOnUse">
					<path d="M318 306L207.5 335.5L0.5 318.5V22.5L200 18.5V0.659806L207 0.5L317.5 10L318 306Z"></path>
				</clipPath>
				<clipPath id="kcp-design-clip-plinth" clipPathUnits="userSpaceOnUse">
					<path d="M211.688 78L0.5 56.7273V12.1558L197.141 29.3766L307.5 0V46.5974L211.688 78Z"></path>
				</clipPath>
			</defs>
		</svg>
	`;
}

/**
 * @param {object|null} selection Selected catalog option.
 * @param {object}      options  Render options.
 * @return {string}
 */
export function renderSelectionFill( selection, options = {} ) {
	if ( ! selection ) {
		return '';
	}

	const clipClass = options.clip ? ' kcp-design__overlay-clip' : '';
	const frontSkewClass = options.frontSkew
		? ( selection.image_url && ! options.preferHex ? ' kcp-design__overlay-media-item--front-skew' : ' kcp-design__overlay-fill--front-skew' )
		: '';

	const useHex = options.preferHex && selection.hex;

	if ( ! useHex && selection.image_url ) {
		return `<img class="kcp-design__overlay-media-item${ clipClass }${ frontSkewClass }" src="${ escapeHtml( selection.image_url ) }" alt="" loading="lazy" decoding="async" />`;
	}

	if ( selection.hex ) {
		return `<div class="kcp-design__overlay-fill${ clipClass }${ frontSkewClass }" style="background-color:${ escapeHtml( selection.hex ) }"></div>`;
	}

	if ( selection.image_url ) {
		return `<img class="kcp-design__overlay-media-item${ clipClass }${ frontSkewClass }" src="${ escapeHtml( selection.image_url ) }" alt="" loading="lazy" decoding="async" />`;
	}

	return '';
}

/**
 * @param {string}       zoneId    Design zone id.
 * @param {object|null}  selection Selected option.
 * @param {string}       maskUrl   CSS mask image URL.
 * @return {string}
 */
export function renderFrontOverlay( zoneId, selection, maskUrl ) {
	if ( ! selection || ! maskUrl ) {
		return '';
	}

	const fill = renderSelectionFill( selection, { frontSkew: true, preferHex: true } );
	if ( ! fill ) {
		return '';
	}

	return `
		<div
			class="kcp-design__overlay kcp-design__overlay--front"
			data-overlay-zone="${ escapeHtml( zoneId ) }"
			style="--kcp-design-mask:url('${ escapeHtml( maskUrl ) }')"
		>
			<div class="kcp-design__overlay-inner">${ fill }</div>
		</div>
	`;
}

/**
 * @param {string}       zoneId    Design zone id.
 * @param {object|null}  selection Selected option.
 * @param {string}       modifier  BEM modifier class.
 * @param {string}       clipId    SVG clip path id.
 * @return {string}
 */
export function renderClippedOverlay( zoneId, selection, modifier, clipId ) {
	if ( ! selection ) {
		return '';
	}

	const fill = renderSelectionFill( selection, { clip: true } );
	if ( ! fill ) {
		return '';
	}

	return `
		<div
			class="kcp-design__overlay kcp-design__overlay--${ modifier }"
			data-overlay-zone="${ escapeHtml( zoneId ) }"
			style="--kcp-design-clip:url(#${ escapeHtml( clipId ) })"
		>
			<div class="kcp-design__overlay-media">${ fill }</div>
		</div>
	`;
}

/**
 * @param {object|null} selection Selected handle option.
 * @return {string}
 */
export function renderHandleOverlay( selection ) {
	if ( ! selection?.image_url ) {
		return '';
	}

	return `
		<div class="kcp-design__overlay kcp-design__overlay--handle" data-overlay-zone="handle_strip">
			<img src="${ escapeHtml( selection.image_url ) }" alt="" loading="lazy" decoding="async" />
		</div>
	`;
}

/**
 * @param {object} selections Current zone selections.
 * @param {object} masks      Mask image URLs keyed by zone.
 * @return {string}
 */
export function renderCabinetOverlaysInner( selections, masks ) {
	const front = renderFrontOverlay( 'front', selections.front, masks.front || '' );
	const cabinet = renderClippedOverlay( 'cabinet', selections.cabinet, 'cabinet', 'kcp-design-clip-cabinet' );
	const plinth = renderClippedOverlay( 'plinth', selections.plinth, 'plinth', 'kcp-design-clip-plinth' );
	const handle = renderHandleOverlay( selections.handle_strip );

	return `${ front }${ cabinet }${ plinth }${ handle }`;
}

/**
 * @param {object} selections Current zone selections.
 * @param {object} masks      Mask image URLs keyed by zone.
 * @return {string}
 */
export function renderCabinetOverlays( selections, masks ) {
	return `
		<div class="kcp-design__overlays" aria-hidden="true">
			${ renderCabinetOverlaysInner( selections, masks ) }
		</div>
	`;
}
