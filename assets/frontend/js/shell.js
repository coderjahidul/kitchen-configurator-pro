/**
 * Kitchen Configurator Pro — header/footer shell interactions.
 */

( function () {
	const nav = document.querySelector( '.kcp-shell-nav' );
	const mobile = document.getElementById( 'kcp-shell-mobile' );
	const menuToggle = document.querySelector( '.kcp-shell-nav__menu-toggle' );
	const themeToggle = document.querySelector( '[data-kcp-theme-toggle]' );

	if ( nav ) {
		const onScroll = () => {
			nav.classList.toggle( 'is-scrolled', window.scrollY > 24 );
		};

		onScroll();
		window.addEventListener( 'scroll', onScroll, { passive: true } );
	}

	const setMobileMenuOpen = ( isOpen ) => {
		if ( ! mobile || ! menuToggle ) {
			return;
		}

		mobile.classList.toggle( 'is-open', isOpen );
		menuToggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		menuToggle.setAttribute(
			'aria-label',
			isOpen
				? menuToggle.dataset.closeLabel || 'Menu sluiten'
				: menuToggle.dataset.openLabel || 'Menu openen'
		);
		document.body.classList.toggle( 'kcp-shell-menu-open', isOpen );
		mobile.setAttribute( 'aria-hidden', isOpen ? 'false' : 'true' );
	};

	if ( menuToggle && mobile ) {
		if ( ! menuToggle.dataset.openLabel ) {
			menuToggle.dataset.openLabel = menuToggle.getAttribute( 'aria-label' ) || 'Menu openen';
		}

		menuToggle.dataset.closeLabel = menuToggle.dataset.closeLabel || 'Menu sluiten';

		menuToggle.addEventListener( 'click', () => {
			setMobileMenuOpen( ! mobile.classList.contains( 'is-open' ) );
		} );

		mobile.querySelectorAll( 'a' ).forEach( ( link ) => {
			link.addEventListener( 'click', () => {
				setMobileMenuOpen( false );
			} );
		} );

		document.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' && mobile.classList.contains( 'is-open' ) ) {
				setMobileMenuOpen( false );
				menuToggle.focus();
			}
		} );
	}

	if ( themeToggle ) {
		const stored = window.localStorage.getItem( 'kcp-theme' );

		if ( stored === 'dark' ) {
			document.body.classList.remove( 'light-mode' );
			document.body.classList.add( 'dark-mode' );
			themeToggle.checked = true;
		}

		themeToggle.addEventListener( 'change', () => {
			const isDark = themeToggle.checked;
			document.body.classList.toggle( 'dark-mode', isDark );
			document.body.classList.toggle( 'light-mode', ! isDark );
			window.localStorage.setItem( 'kcp-theme', isDark ? 'dark' : 'light' );
		} );
	}

	document.querySelectorAll( '.kcp-shell-footer__column-toggle' ).forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			const column = button.closest( '.kcp-shell-footer__column' );

			if ( ! column ) {
				return;
			}

			const isOpen = column.classList.toggle( 'is-open' );
			button.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		} );
	} );

	document.querySelectorAll( '.kcp-shell-mobile__section-toggle' ).forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			const section = button.closest( '.kcp-shell-mobile__section' );

			if ( ! section ) {
				return;
			}

			const isOpen = section.classList.toggle( 'is-open' );
			button.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		} );
	} );
}() );
