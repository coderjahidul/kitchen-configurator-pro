/**
 * Brand landing sidebar toggles.
 */
(function () {
	'use strict';

	function initSection(section) {
		const toggle = section.querySelector('.kcp-brand-sidebar__toggle');
		const children = section.querySelector('.kcp-brand-sidebar__children');

		if (!toggle || !children) {
			return;
		}

		const setOpen = (open) => {
			children.classList.toggle('is-open', open);
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			section.classList.toggle('is-expanded', open);
		};

		if (section.classList.contains('is-active')) {
			setOpen(true);
		}

		toggle.addEventListener('click', (event) => {
			event.preventDefault();
			setOpen(!children.classList.contains('is-open'));
		});
	}

	function initHeroVideo(visual) {
		const video = visual.querySelector('.kcp-brand-hero-banner__video');

		if (!(video instanceof HTMLVideoElement)) {
			return;
		}

		const playPromise = video.play();

		if (playPromise && typeof playPromise.catch === 'function') {
			playPromise.catch(() => {});
		}
	}

	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('.kcp-brand-sidebar__section.has-children').forEach(initSection);
		document.querySelectorAll('.kcp-brand-hero-banner__visual').forEach(initHeroVideo);
	});
})();
