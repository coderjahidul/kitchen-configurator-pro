/**
 * Main configurator application.
 */

import { STEPS, buildPayload } from '../state/store.js';
import { debounce } from '../utils/helpers.js';
import { CabinetsStep } from './CabinetsStep.js';
import { FinishesStep } from './FinishesStep.js';
import { ExtrasStep } from './ExtrasStep.js';
import { SummaryStep } from './SummaryStep.js';
import { PriceSummary } from './PriceSummary.js';
import { ProjectPanel } from './ProjectPanel.js';

export class App {
	/**
	 * @param {HTMLElement} root  Root element.
	 * @param {object}      store Store.
	 * @param {object}      api   API client.
	 * @param {object}      boot  Boot config.
	 */
	constructor( root, store, api, boot ) {
		this.root = root;
		this.store = store;
		this.api = api;
		this.boot = boot;
		this.i18n = boot.i18n || {};
		this.currency = boot.currency || 'EUR';

		this.steps = [
			{ id: 'cabinets', label: this.i18n.stepCabinets },
			{ id: 'finishes', label: this.i18n.stepFinishes },
			{ id: 'extras', label: this.i18n.stepExtras },
			{ id: 'summary', label: this.i18n.stepSummary },
		];

		this.schedulePricing = debounce( () => this.fetchPricing(), 450 );
	}

	async init() {
		this.renderShell();
		this.mountComponents();
		this.store.subscribe( ( state ) => this.onStateChange( state ) );

		try {
			const { data } = await this.api.getCatalog();
			const state = this.store.getState();
			const layoutId = state.config.layout_id || this.defaultLayoutId( data );

			this.store.setState( {
				catalog: data,
				loading: false,
				error: null,
				config: {
					...state.config,
					layout_id: layoutId,
				},
			} );

			const uuid = this.root.dataset.uuid;
			if ( uuid ) {
				await this.loadConfiguration( uuid );
			}

			await this.projectPanel.refresh();
		} catch ( err ) {
			this.store.setState( {
				loading: false,
				error: err.message || this.i18n.error,
			} );
		}
	}

	renderShell() {
		this.root.innerHTML = `
			<div class="kcp-app">
				<aside class="kcp-app__sidebar">
					<nav class="kcp-steps" aria-label="Configuration steps"></nav>
					<div class="kcp-app__projects"></div>
				</aside>
				<main class="kcp-app__main">
					<div class="kcp-app__content"></div>
					<footer class="kcp-app__footer">
						<button type="button" class="kcp-btn kcp-btn--ghost" data-nav="back">${ this.i18n.back }</button>
						<button type="button" class="kcp-btn kcp-btn--primary" data-nav="next">${ this.i18n.next }</button>
					</footer>
				</main>
				<aside class="kcp-app__price"></aside>
			</div>
		`;

		this.stepsNav = this.root.querySelector( '.kcp-steps' );
		this.contentEl = this.root.querySelector( '.kcp-app__content' );
		this.priceEl = this.root.querySelector( '.kcp-app__price' );
		this.projectsEl = this.root.querySelector( '.kcp-app__projects' );

		this.root.querySelector( '[data-nav="back"]' ).addEventListener( 'click', () => this.goStep( -1 ) );
		this.root.querySelector( '[data-nav="next"]' ).addEventListener( 'click', () => this.goStep( 1 ) );
	}

	mountComponents() {
		this.cabinetsStep = new CabinetsStep( this.contentEl, this.store, this.i18n );
		this.finishesStep = new FinishesStep( this.contentEl, this.store, this.i18n );
		this.extrasStep = new ExtrasStep( this.contentEl, this.store, this.i18n );
		this.summaryStep = new SummaryStep( this.contentEl, this.store, this.api, this.i18n, this.currency, this.boot );
		this.priceSummary = new PriceSummary( this.priceEl, this.i18n, this.currency );
		this.projectPanel = new ProjectPanel(
			this.projectsEl,
			this.store,
			this.api,
			this.i18n,
			this.currency,
			( uuid ) => ( uuid ? this.loadConfiguration( uuid ) : this.resetConfiguration() )
		);
	}

	onStateChange( state ) {
		if ( state.loading ) {
			this.contentEl.innerHTML = `<p class="kcp-loading">${ this.i18n.loading }</p>`;
			return;
		}

		if ( state.error ) {
			this.contentEl.innerHTML = `<p class="kcp-message kcp-message--error">${ state.error }</p>`;
			return;
		}

		this.renderStepsNav( state );
		this.renderActiveStep( state );
		this.priceSummary.render( state );
		this.projectPanel.render( state );

		if ( state.config.layout_id && state.config.cabinets?.length ) {
			this.schedulePricing();
		}
	}

	defaultLayoutId( catalog ) {
		const layouts = catalog?.layouts || [];

		return layouts[0]?.id || null;
	}

	renderStepsNav( state ) {
		this.stepsNav.innerHTML = this.steps
			.map(
				( step, index ) => `
			<button type="button" class="kcp-steps__item ${ index === state.step ? 'kcp-steps__item--active' : '' } ${ index < state.step ? 'kcp-steps__item--done' : '' }" data-step="${ index }">
				<span class="kcp-steps__num">${ index + 1 }</span>
				<span class="kcp-steps__label">${ step.label }</span>
			</button>`
			)
			.join( '' );

		this.stepsNav.querySelectorAll( '[data-step]' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', () => {
				const target = Number( btn.dataset.step );
				if ( target <= state.step || this.canAdvance( state, state.step ) ) {
					this.store.setState( { step: target } );
				}
			} );
		} );
	}

	renderActiveStep( state ) {
		const stepId = STEPS[ state.step ] || 'cabinets';

		switch ( stepId ) {
			case 'cabinets':
				this.cabinetsStep.render( state );
				break;
			case 'finishes':
				this.finishesStep.render( state );
				break;
			case 'extras':
				this.extrasStep.render( state );
				break;
			case 'summary':
				this.summaryStep.render( state );
				break;
		}

		const backBtn = this.root.querySelector( '[data-nav="back"]' );
		const nextBtn = this.root.querySelector( '[data-nav="next"]' );

		backBtn.disabled = state.step === 0;
		nextBtn.textContent = state.step === STEPS.length - 1 ? this.i18n.stepSummary : this.i18n.next;
		nextBtn.style.visibility = state.step === STEPS.length - 1 ? 'hidden' : 'visible';
	}

	goStep( delta ) {
		const state = this.store.getState();
		const next = state.step + delta;

		if ( delta > 0 && ! this.canAdvance( state, state.step ) ) {
			return;
		}

		if ( next >= 0 && next < STEPS.length ) {
			this.store.setState( { step: next } );
		}
	}

	canAdvance( state, stepIndex ) {
		const stepId = STEPS[ stepIndex ];

		if ( stepId === 'cabinets' ) {
			return ( state.config.cabinets?.length || 0 ) > 0;
		}

		return true;
	}

	async fetchPricing() {
		const { config } = this.store.getState();

		if ( ! config.layout_id || ! config.cabinets?.length ) {
			return;
		}

		this.store.setState( { pricingLoading: true, pricingError: null } );

		try {
			const { data } = await this.api.calculatePricing( buildPayload( config ) );
			this.store.setState( { pricing: data, pricingLoading: false } );
		} catch ( err ) {
			this.store.setState( {
				pricingError: err.details?.errors?.join( ' ' ) || err.message,
				pricingLoading: false,
			} );
		}
	}

	async loadConfiguration( uuid ) {
		try {
			const { data } = await this.api.getConfiguration( uuid );
			const cfg = data.configuration || {};

			this.store.setState( {
				config: {
					schema_version: cfg.schema_version || '1.0',
					layout_id: data.layout_id || cfg.layout_id,
					title: data.title || cfg.title || '',
					cabinets: cfg.cabinets || [],
					global_options: cfg.global_options || {},
				},
				savedUuid: data.uuid,
				pricing: data.pricing || null,
				step: 0,
				saveMessage: null,
				saveError: null,
			} );
		} catch ( err ) {
			this.store.setState( { error: err.message } );
		}
	}

	resetConfiguration() {
		const catalog = this.store.getState().catalog;

		this.store.setState( {
			config: {
				schema_version: '1.0',
				layout_id: this.defaultLayoutId( catalog ),
				title: this.root.dataset.title || '',
				cabinets: [],
				global_options: {},
			},
			savedUuid: null,
			pricing: null,
			step: 0,
			saveMessage: null,
			saveError: null,
		} );
	}
}
