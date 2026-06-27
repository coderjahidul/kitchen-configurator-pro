/**
 * REST API client for kcp/v1.
 */

const SESSION_KEY = 'kcp_session_id';

export class KcpApi {
	/**
	 * @param {object} config Boot config from wp_localize_script.
	 */
	constructor( config = {} ) {
		this.baseUrl = ( config.apiUrl || '/wp-json/kcp/v1' ).replace( /\/$/, '' );
		this.nonce = config.nonce || '';
		this.isLoggedIn = Boolean( config.isLoggedIn );
		this.sessionId = localStorage.getItem( SESSION_KEY ) || '';

		if ( ! this.isLoggedIn && ! this.sessionId ) {
			this.sessionId = this.generateSessionId();
			this.setSessionId( this.sessionId );
		}
	}

	/**
	 * Generate a client-side guest session ID.
	 *
	 * @returns {string}
	 */
	generateSessionId() {
		if ( typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function' ) {
			return crypto.randomUUID();
		}

		return `kcp-${ Date.now() }-${ Math.random().toString( 36 ).slice( 2, 14 ) }`;
	}

	/**
	 * Persist guest session ID.
	 *
	 * @param {string} sessionId Session ID.
	 */
	setSessionId( sessionId ) {
		if ( ! sessionId ) {
			return;
		}
		this.sessionId = sessionId;
		localStorage.setItem( SESSION_KEY, sessionId );
	}

	/**
	 * Build request headers.
	 *
	 * @returns {Record<string, string>}
	 */
	getHeaders() {
		const headers = {
			'Content-Type': 'application/json',
			Accept: 'application/json',
		};

		if ( this.isLoggedIn && this.nonce ) {
			headers[ 'X-WP-Nonce' ] = this.nonce;
		} else if ( this.sessionId ) {
			headers[ 'X-KCP-Session-Id' ] = this.sessionId;
		}

		return headers;
	}

	/**
	 * Perform fetch and parse envelope.
	 *
	 * @param {string} path    API path.
	 * @param {object} options Fetch options.
	 * @returns {Promise<{data: *, meta: object}>}
	 */
	async request( path, options = {} ) {
		const url = `${ this.baseUrl }${ path.startsWith( '/' ) ? path : `/${ path }` }`;
		const response = await fetch( url, {
			credentials: 'same-origin',
			...options,
			headers: {
				...this.getHeaders(),
				...( options.headers || {} ),
			},
		} );

		const json = await response.json().catch( () => ( {} ) );

		if ( ! response.ok || json.success === false ) {
			const message =
				json.error?.message ||
				json.message ||
				`Request failed (${ response.status })`;
			const err = new Error( message );
			err.code = json.error?.code || 'kcp_request_failed';
			err.details = json.error?.details || null;
			err.status = response.status;
			throw err;
		}

		if ( json.meta?.session_id ) {
			this.setSessionId( json.meta.session_id );
		}

		return { data: json.data, meta: json.meta || {} };
	}

	/** @returns {Promise<object>} */
	getCatalog() {
		return this.request( '/catalog' );
	}

	/**
	 * @param {object} payload Configuration payload.
	 * @returns {Promise<object>}
	 */
	calculatePricing( payload ) {
		return this.request( '/pricing/calculate', {
			method: 'POST',
			body: JSON.stringify( payload ),
		} );
	}

	/**
	 * @param {number} page Page.
	 * @param {number} perPage Per page.
	 * @returns {Promise<object>}
	 */
	listConfigurations( page = 1, perPage = 20 ) {
		return this.request( `/configurations?page=${ page }&per_page=${ perPage }` );
	}

	/**
	 * @param {string} uuid Configuration UUID.
	 * @returns {Promise<object>}
	 */
	getConfiguration( uuid ) {
		return this.request( `/configurations/${ uuid }` );
	}

	/**
	 * @param {object} payload Configuration payload.
	 * @returns {Promise<object>}
	 */
	createConfiguration( payload ) {
		return this.request( '/configurations', {
			method: 'POST',
			body: JSON.stringify( payload ),
		} );
	}

	/**
	 * @param {string} uuid Configuration UUID.
	 * @param {object} payload Configuration payload.
	 * @returns {Promise<object>}
	 */
	updateConfiguration( uuid, payload ) {
		return this.request( `/configurations/${ uuid }`, {
			method: 'PUT',
			body: JSON.stringify( payload ),
		} );
	}

	/**
	 * @param {string} uuid Configuration UUID.
	 * @param {number} quantity Line item quantity.
	 * @returns {Promise<object>}
	 */
	addToCart( uuid, quantity = 1 ) {
		return this.request( '/cart/add', {
			method: 'POST',
			body: JSON.stringify( {
				uuid,
				quantity: Math.max( 1, Number( quantity ) || 1 ),
			} ),
		} );
	}
}
