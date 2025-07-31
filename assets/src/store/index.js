/**
 * WordPress dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Pattern Sync Store
 */
const DEFAULT_STATE = {
	sitePatterns: {},
	isLoadingSitePatterns: false,
	error: null,
};

const actions = {
	setSitePatterns( sitePatterns ) {
		return {
			type: 'SET_SITE_PATTERNS',
			sitePatterns,
		};
	},
	setIsLoadingSitePatterns( isLoading ) {
		return {
			type: 'SET_IS_LOADING_SITE_PATTERNS',
			isLoading,
		};
	},
	setError( error ) {
		return {
			type: 'SET_ERROR',
			error,
		};
	},
	*fetchSitePatterns() {
		try {
			yield { type: 'SET_IS_LOADING_SITE_PATTERNS', isLoading: true };
			yield { type: 'SET_ERROR', error: null };

			const response = yield apiFetch( {
				path: `/onedesign/v1/get-all-consumer-site-patterns?timestamp=${ Date.now() }`,
			} );

			if ( response.success ) {
				yield {
					type: 'SET_SITE_PATTERNS',
					sitePatterns: response.patterns || {},
				};
			} else {
				yield {
					type: 'SET_ERROR',
					error: new Error( 'Failed to fetch site patterns' ),
				};
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error fetching site patterns:', error );
			yield {
				type: 'SET_ERROR',
				error,
			};
		} finally {
			yield { type: 'SET_IS_LOADING_SITE_PATTERNS', isLoading: false };
		}
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case 'SET_SITE_PATTERNS':
			return {
				...state,
				sitePatterns: action.sitePatterns,
			};
		case 'SET_IS_LOADING_SITE_PATTERNS':
			return {
				...state,
				isLoadingSitePatterns: action.isLoading,
			};
		case 'SET_ERROR':
			return {
				...state,
				error: action.error,
			};
		default:
			return state;
	}
};

const selectors = {
	getSitePatterns( state ) {
		return state.sitePatterns;
	},
	isLoadingSitePatterns( state ) {
		return state.isLoadingSitePatterns;
	},
	getError( state ) {
		return state.error;
	},
};

const store = createReduxStore( 'onedesign/site-patterns', {
	reducer,
	actions,
	selectors,
} );

register( store );

export default store;
