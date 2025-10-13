/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { Spinner, Notice, Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { getInitials } from '../../../js/utils';

/**
 * Component to render the consumer site selection with enhanced UX.
 *
 * @param {Object}   props                   - Component properties.
 * @param {Function} props.setIsSiteSelected - Function to set the site selection state.
 * @param {Array}    props.selectedPatterns  - Array of selected pattern names.
 * @param {Array}    props.basePatterns      - Array of base patterns for the current site.
 * @param {Object}   props.sitePatterns      - Object mapping site IDs to their patterns.
 *
 * @return {JSX.Element} JSX Element
 */
const SiteSelection = ( {
	setIsSiteSelected,
	selectedPatterns = [],
	basePatterns = [],
	sitePatterns = {},
} ) => {
	/**
	 * Get the current value of the consumer_site meta field.
	 */
	const { consumerSite } = useSelect( ( select ) => {
		const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		return {
			consumerSite: meta?.consumer_site || [],
		};
	} );

	/**
	 * Dispatch the action to update the consumer_site meta field.
	 */
	const { editPost } = useDispatch( 'core/editor' );

	const [ siteOptions, setSiteOptions ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	const onConsumerSiteChange = ( siteId ) => {
		const newConsumerSite = consumerSite.includes( siteId )
			? consumerSite.filter( ( site ) => site !== siteId )
			: [ ...consumerSite, siteId ];
		setIsSiteSelected( newConsumerSite.length > 0 );
		editPost( { meta: { consumer_site: newConsumerSite } } );
	};

	const selectAllSites = () => {
		// Get IDs of sites that don't already have all patterns (not disabled)
		const selectableSiteIds = siteOptions
			.filter( ( site ) => {
				// Skip if site has all patterns already
				if ( selectedPatterns.length > 0 && sitePatterns[ site.id ] ) {
					const sitePatternsArray = sitePatterns[ site.id ] || [];
					const presentPatterns = selectedPatterns.filter( ( patternName ) =>
						sitePatternsArray.some(
							( pattern ) => pattern.name === patternName.replace( /\//g, '' ),
						),
					);

					// If all patterns are present, exclude this site
					return ! (
						presentPatterns.length === selectedPatterns.length &&
						selectedPatterns.length > 0
					);
				}
				return true;
			} )
			.map( ( site ) => site.id );

		editPost( { meta: { consumer_site: selectableSiteIds } } );
	};

	const deselectAllSites = () => {
		editPost( { meta: { consumer_site: [] } } );
	};

	const retryFetch = () => {
		setIsLoading( true );
		setError( null );
		fetchSites();
	};

	const fetchSites = async () => {
		try {
			const response = await apiFetch( {
				path: `/onedesign/v1/configured-sites`,
			} );

			const data = response;
			setSiteOptions( data );
			setError( null );
		} catch ( fetchError ) {
			setError( {
				message: __(
					'Failed to load consumer sites. Please check your connection and try again.',
					'onedesign',
				),
				details: fetchError.message,
			} );
		} finally {
			setIsLoading( false );
		}
	};

	useEffect( () => {
		fetchSites();
		// Reset error state
		setError( null );
		// Reset loading state
		setIsLoading( true );
		// Clear consumer site selection on mount
		editPost( { meta: { consumer_site: [] } } );

		// Ensure dashicons are loaded
		if ( document.querySelector( 'body' ).classList.contains( 'wp-admin' ) ) {
			// Already in admin, dashicons should be loaded
		} else {
			// Load dashicons if not in admin context
			const link = document.createElement( 'link' );
			link.rel = 'stylesheet';
			link.href = '/wp-includes/css/dashicons.min.css';
			document.head.appendChild( link );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	const totalCount = siteOptions.length;

	// Calculate the number of sites that don't have all patterns already
	const selectableSites = siteOptions.filter( ( site ) => {
		if ( selectedPatterns.length > 0 && sitePatterns[ site.id ] ) {
			const sitePatternsArray = sitePatterns[ site.id ] || [];
			const presentPatterns = selectedPatterns.filter( ( patternName ) =>
				sitePatternsArray.some(
					( pattern ) => pattern.name === patternName.replace( /\//g, '' ),
				),
			);

			// If all patterns are present, site is not selectable
			return ! (
				presentPatterns.length === selectedPatterns.length &&
				selectedPatterns.length > 0
			);
		}
		return true;
	} );

	const selectableSiteCount = selectableSites.length;
	const selectedSelectableSiteCount = consumerSite.filter( ( siteId ) =>
		selectableSites.some( ( site ) => site.id === siteId ),
	).length;

	const selectedCount = consumerSite.length;

	if ( isLoading ) {
		return (
			<div className="od-site-loading">
				<div className="od-loading-content">
					<Spinner />
					<p>{ __( 'Loading consumer sites…', 'onedesign' ) }</p>
				</div>
			</div>
		);
	}

	if ( error ) {
		return (
			<div className="od-site-error">
				<Notice status="error" isDismissible={ false }>
					<p>{ error.message }</p>
					{ error.details && (
						<details className="od-error-details">
							<summary>{ __( 'Technical details', 'onedesign' ) }</summary>
							<p>{ error.details }</p>
						</details>
					) }
				</Notice>
				<Button
					variant="secondary"
					onClick={ retryFetch }
					className="od-retry-button"
				>
					{ __( 'Retry', 'onedesign' ) }
				</Button>
			</div>
		);
	}

	if ( siteOptions.length === 0 ) {
		return (
			<div className="od-no-sites">
				<Notice status="warning" isDismissible={ false }>
					<p>{ __( 'No consumer sites configured.', 'onedesign' ) }</p>
					<p>
						{ __(
							'Please configure consumer sites first to apply patterns.',
							'onedesign',
						) }
					</p>
				</Notice>
			</div>
		);
	}

	return (
		<div className="od-consumer-site-selection">
			<div className="od-selection-header">
				<div className="od-selection-summary">
					<h4>{ __( 'Select Consumer Sites', 'onedesign' ) }</h4>
					<span className="od-selection-count">
						{ selectedCount > 0
							? /* translators: %1$d: number of selected sites, %2$d: total number of selectable sites */ sprintf(
								__( '%1$d of %2$d selected', 'onedesign' ),
								selectedCount,
								selectableSiteCount,
							)
							: /* translators: %1$d: number of selectable sites, %2$d: total number of sites */ sprintf(
								__( '%1$d of %2$d sites available', 'onedesign' ),
								selectableSiteCount,
								totalCount,
							) }
					</span>
				</div>

				{ totalCount > 1 && (
					<div className="od-bulk-actions">
						<Button
							variant="link"
							onClick={ selectAllSites }
							disabled={
								selectedSelectableSiteCount === selectableSiteCount ||
								selectableSiteCount === 0
							}
							className="od-bulk-action"
						>
							{ __( 'Select All', 'onedesign' ) }
						</Button>
						<span className="od-bulk-separator">|</span>
						<Button
							variant="link"
							onClick={ deselectAllSites }
							disabled={ selectedCount === 0 }
							className="od-bulk-action"
						>
							{ __( 'Deselect All', 'onedesign' ) }
						</Button>
					</div>
				) }
			</div>

			{ /* Message explaining disabled sites if there are any */ }
			{ selectedPatterns.length > 0 && (
				<div className="od-selection-hint">
					<p>
						<span className="dashicons dashicons-info"></span>
						{ totalCount !== selectableSiteCount
							? sprintf(
								/* translators: %1$d: number of non-selectable sites, %2$d: total sites */
								__(
									'%1$d of %2$d sites already have all selected patterns and are disabled.',
									'onedesign',
								),
								totalCount - selectableSiteCount,
								totalCount,
							)
							: __(
								'Sites that already have all selected patterns are disabled.',
								'onedesign',
							) }
					</p>
				</div>
			) }

			<div className="od-sites-list od-sites-grid">
				{ siteOptions.map( ( { id, name, url, logo } ) => {
					const isSelected = consumerSite?.includes( id );

					// Check if all selected patterns are already present on this site
					let hasAllPatterns = false;
					let isDisabled = false;

					if ( selectedPatterns.length > 0 && sitePatterns[ id ] ) {
						const sitePatternsArray = sitePatterns[ id ] || [];
						const presentPatterns = selectedPatterns.filter( ( patternName ) =>
							sitePatternsArray.some(
								( pattern ) => pattern.name === patternName.replace( /\//g, '' ),
							),
						);

						// If all selected patterns are already present, disable the site
						hasAllPatterns =
							presentPatterns.length === selectedPatterns.length &&
							selectedPatterns.length > 0;
						isDisabled = hasAllPatterns && ! isSelected;
					}

					return (
						<div
							key={ id }
							className={ `od-site-item ${ isSelected ? 'od-site-selected' : '' } ${ isDisabled ? 'od-site-disabled' : '' }` }
							onClick={ () => ! isDisabled && onConsumerSiteChange( id ) }
							onKeyDown={ ( e ) => {
								if ( ! isDisabled && ( e.key === 'Enter' || e.key === ' ' ) ) {
									onConsumerSiteChange( id );
								}
							} }
							tabIndex={ isDisabled ? -1 : 0 }
							role="checkbox"
							aria-checked={ isSelected }
							aria-disabled={ isDisabled }
						>
							<div className="od-site-inner">
								{ isSelected && (
									<div className="od-site-selected-indicator">
										<span className="dashicons dashicons-yes-alt"></span>
									</div>
								) }
								{ isDisabled && ! isSelected && (
									<div
										className="od-site-disabled-indicator"
										title={ __(
											'This site already has all selected patterns',
											'onedesign',
										) }
									>
										<span className="dashicons dashicons-yes"></span>
									</div>
								) }
								<div className="od-site-logo">
									{ logo ? (
										<img src={ logo } alt={ name } />
									) : (
										<div className="od-site-initials">
											{ name ? getInitials( name ) : '?' }
										</div>
									) }
								</div>
								<span className="od-site-name">{ name }</span>
								{ url && <span className="od-site-url">{ url }</span> }

								{ /* Pattern sync status */ }
								{ selectedPatterns.length > 0 && sitePatterns[ id ] && (
									<div className="od-pattern-status">
										{ ( () => {
											const sitePatternsArray = sitePatterns[ id ] || [];
											// Remove forward slash from selected patterns names before comparison
											const presentPatterns = selectedPatterns.filter(
												( patternName ) =>
													sitePatternsArray.some(
														( pattern ) =>
															pattern.name === patternName.replace( /\//g, '' ),
													),
											);
											const presentCount = presentPatterns.length;
											const allPatternsPresent =
												presentCount === selectedPatterns.length &&
												selectedPatterns.length > 0;

											if ( presentCount === 0 ) {
												return (
													<span className="od-onedesign-info">
														{ __( 'All patterns will be synced', 'onedesign' ) }
													</span>
												);
											}

											if ( allPatternsPresent ) {
												return (
													<span className="od-onedesign-info od-all-patterns-present">
														{ __(
															'All selected patterns are already present',
															'onedesign',
														) }
													</span>
												);
											}

											const toSyncPatterns = selectedPatterns.filter(
												( patternName ) =>
													! sitePatternsArray.some(
														( pattern ) =>
															pattern.name === patternName.replace( /\//g, '' ),
													),
											);

											const toSyncPatternsTitles = toSyncPatterns.map(
												( patternName ) =>
													basePatterns.find(
														( pattern ) => pattern.name === patternName,
													)?.title,
											);

											// Limit toSyncPatternsTitles to 5 items for display
											if ( toSyncPatternsTitles.length > 5 ) {
												toSyncPatternsTitles.length = 5;
												toSyncPatternsTitles.push( '…' );
											}

											return (
												<>
													<span
														className="od-onedesign-info"
														data-tooltip={
															toSyncPatterns.length > 0
																? __( 'Patterns to sync:', 'onedesign' ) +
																	' ' +
																	toSyncPatternsTitles.join( ', ' )
																: __(
																	'All selected patterns are already on this site',
																	'onedesign',
																)
														}
													>
														{ presentCount } { __( 'of', 'onedesign' ) }{ ' ' }
														{ selectedPatterns.length }{ ' ' }
														{ __(
															'selected patterns are already present',
															'onedesign',
														) }
													</span>
												</>
											);
										} )() }
									</div>
								) }
							</div>
						</div>
					);
				} ) }
			</div>
		</div>
	);
};

export default SiteSelection;
