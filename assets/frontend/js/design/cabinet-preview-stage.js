/**
 * Preview stage alignment for the cabinet design step.
 * Matches reference kitchen-cabinet-buttons (349×390, centered on holder).
 */

export const PREVIEW_STAGE = {
	naturalWidth: 1201,
	naturalHeight: 558,
	width: 349,
	height: 390,
};

/**
 * Position the interactive preview stage on top of the base cabinet image.
 *
 * @param {HTMLElement} root Design step root element.
 */
export function syncPreviewStage( root ) {
	const image = root.querySelector( '.kcp-design__image:not(.kcp-design__image--placeholder)' );
	const stage = root.querySelector( '.kcp-design__preview-stage' );

	if ( ! image || ! stage ) {
		return;
	}

	const naturalWidth = image.naturalWidth || PREVIEW_STAGE.naturalWidth;

	if ( ! naturalWidth ) {
		return;
	}

	const scale = image.getBoundingClientRect().width / naturalWidth;

	stage.style.width = `${ PREVIEW_STAGE.width }px`;
	stage.style.height = `${ PREVIEW_STAGE.height }px`;
	stage.style.left = '50%';
	stage.style.top = '0';

	if ( scale >= 0.98 && scale <= 1.02 ) {
		stage.style.transform = 'translateX(-50%)';
		return;
	}

	stage.style.transform = `translateX(-50%) scale(${ scale })`;
	stage.style.transformOrigin = 'top center';
}

/**
 * Bind resize/load listeners so the preview stage tracks the base image.
 *
 * @param {HTMLElement} root Design step root element.
 */
export function bindPreviewStageSync( root ) {
	const image = root.querySelector( '.kcp-design__image:not(.kcp-design__image--placeholder)' );

	const runSync = () => {
		requestAnimationFrame( () => syncPreviewStage( root ) );
	};

	runSync();

	if ( image && ! image.complete ) {
		image.addEventListener( 'load', runSync, { once: true } );
	}

	if ( image && 'ResizeObserver' in window ) {
		if ( image._kcpPreviewObserver ) {
			image._kcpPreviewObserver.disconnect();
		}

		const observer = new ResizeObserver( runSync );
		observer.observe( image );
		image._kcpPreviewObserver = observer;
	}
}
