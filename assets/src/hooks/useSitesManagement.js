/**
 * WordPress dependencies
 */
import { useState, useCallback, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Custom hook for managing sites info and health check state
 *
 * @param {Object} props               - Properties including NONCE for authentication
 * @param {string} props.NONCE         - Nonce for secure API requests
 * @param {string} props.API_NAMESPACE - API namespace for REST endpoints
 *
 * @return {Object} State and methods for site management
 */
const useSitesManagement = ( { NONCE, API_NAMESPACE } ) => {
	const [ siteInfo, setSiteInfo ] = useState( {} );
	const [ sitesHealthCheckResult, setSitesHealthCheckResult ] = useState( undefined );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ isInitialized, setIsInitialized ] = useState( false );

	// Perform health check on all configured sites
	const performHealthCheckOnSites = useCallback( async () => {
		setError( null );

		try {
			for ( const siteId of Object.keys( siteInfo ) ) {
				const siteUrl = siteInfo[ siteId ]?.url;
				const siteApiKey = siteInfo[ siteId ]?.api_key;

				if ( siteUrl ) {
					try {
						const response = await fetch(
							`${ siteUrl }/wp-json/onedesign/v1/health-check?timestamp=${ Date.now() }`,
							{
								method: 'GET',
								headers: {
									'Content-Type': 'application/json',
									'X-OneDesign-Token': siteApiKey,
								},
							},
						);
						const data = await response.json();

						if ( ! data.success ) {
							setSitesHealthCheckResult( ( prevResults ) => ( {
								...prevResults,
								[ siteInfo[ siteId ]?.id ]: {
									success: false,
									message: data.message || __( 'Health check failed.', 'onedesign' ),
								},
							} ) );
							continue;
						}

						setSitesHealthCheckResult( ( prevResults ) => ( {
							...( prevResults || {} ),
							[ siteInfo[ siteId ]?.id ]: data.success ? data : {
								success: false,
								message: data.message || __( 'Health check failed.', 'onedesign' ),
							},
						} ) );
					} catch ( err ) {
						setSitesHealthCheckResult( ( prevResults ) => ( {
							...( prevResults || {} ),
							[ siteInfo[ siteId ]?.id ]: {
								success: false,
								message: __( 'Failed to reach the site.', 'onedesign' ),
							},
						} ) );
					}
				}
			}
		} catch ( err ) {
			setError( err.message );
		} finally {
			setIsLoading( false );
		}
	}, [ siteInfo ] );

	// Fetch brand sites information from the REST API
	const fetchBrandSitesInfo = useCallback( async () => {
		setError( null );

		try {
			const response = await fetch(
				`${ API_NAMESPACE }/configured-sites?timestamp=${ Date.now() }`,
				{
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': NONCE,
					},
				},
			);
			const data = await response.json();
			setSiteInfo( data || {} );
		} catch ( err ) {
			setError( err.message );
		} finally {
		}
	}, [ API_NAMESPACE, NONCE ] );

	// On component mount, fetch the sites info
	useEffect( () => {
		fetchBrandSitesInfo();
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	// Perform health check when sites are loaded only once
	useEffect( () => {
		if ( ! isInitialized && Object.keys( siteInfo ).length > 0 ) {
			performHealthCheckOnSites();
			setIsInitialized( true );
		}
	}, [ siteInfo, isInitialized, performHealthCheckOnSites ] );

	const reset = useCallback( () => {
		setSiteInfo( {} );
		setSitesHealthCheckResult( {} );
		setError( null );
		setIsLoading( false );
		setIsInitialized( false );
	}, [] );

	return {

		// State values
		siteInfo,
		sitesHealthCheckResult,
		isLoading,
		error,

		// Setters to update state
		setSiteInfo,
		setSitesHealthCheckResult,

		// Methods to perform actions
		performHealthCheckOnSites,
		fetchBrandSitesInfo,
		reset,
	};
};

export default useSitesManagement;
