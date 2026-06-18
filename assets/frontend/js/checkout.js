/**
 * KKF checkout step controls.
 */

( () => {
	const ready = ( callback ) => {
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', callback );
			return;
		}

		callback();
	};

	ready( () => {
		const form = document.querySelector( '.kcp-checkout' );

		if ( ! form ) {
			return;
		}

		const showPayment = form.querySelector( '[data-kcp-show-payment]' );
		const showDetails = form.querySelector( '[data-kcp-show-details]' );

		if ( ! showPayment || ! showDetails ) {
			return;
		}

		document.body.classList.add( 'kcp-checkout-has-steps' );

		const goToPayment = () => {
			const invalidField = Array.from( form.querySelectorAll( '[required], .validate-required input' ) ).find( ( field ) => {
				const detailsSection = field.closest( '[data-kcp-checkout-payment]' );

				if ( detailsSection || field.disabled ) {
					return false;
				}

				if ( 'checkbox' === field.type && field.closest( '.validate-required' ) && ! field.checked ) {
					field.required = true;
					return true;
				}

				return 'function' === typeof field.checkValidity && ! field.checkValidity();
			} );

			if ( invalidField ) {
				if ( 'function' === typeof invalidField.reportValidity ) {
					invalidField.reportValidity();
				} else {
					invalidField.focus();
				}

				return;
			}

			form.classList.add( 'is-payment-step' );
			window.scrollTo( { top: 0, behavior: 'smooth' } );
		};

		form.addEventListener( 'click', ( event ) => {
			if ( event.target.closest( '[data-kcp-show-payment]' ) ) {
				goToPayment();
				return;
			}

			if ( event.target.closest( '[data-kcp-show-details]' ) ) {
				form.classList.remove( 'is-payment-step' );
				window.scrollTo( { top: 0, behavior: 'smooth' } );
			}
		} );
	} );
} )();
