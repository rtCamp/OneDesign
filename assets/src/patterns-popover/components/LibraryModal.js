/**
 * WordPress dependencies
 */
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { Modal, SearchControl, TabPanel, Spinner, IconButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import BasePatternsTab from './BasePatternsTab';
import AppliedPatternsTab from './AppliedPatternsTab';
import Category from './Category';

/**
 * Fetch all consumer site patterns
 *
 * @return {Promise<Array>} A promise that resolves to an array of patterns.
 */
function fetchAllConsumerSitePatterns() {
	return apiFetch( {
		path: `/onedesign/v1/get-all-consumer-site-patterns?timestamp=${ Date.now() }`,
	} )
		.then( ( data ) => {
			if ( data.success ) {
				return data.patterns || [];
				// eslint-disable-next-line no-else-return
			} else {
				throw new Error( 'Failed to fetch patterns' );
			}
		} )
		.catch( ( error ) => {
			console.error( 'Error fetching consumer site patterns:', error ); // eslint-disable-line no-console
			return [];
		} );
}

/**
 * LibraryModal component
 * Displays the patterns library modal with tabs for base patterns and applied patterns.
 * Allows users to search, filter, and apply patterns across different consumer sites.
 *
 * @return {JSX.Element} The rendered modal component.
 */
const LibraryModal = () => {
	const [ basePatterns, setBasePatterns ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const [ isLoadingApplied, setIsLoadingApplied ] = useState( true );
	const [ activeCategory, setActiveCategory ] = useState( 'All' );
	const [ allConsumerSitePatterns, setAllConsumerSitePatterns ] = useState( [] );
	const [ activeTab, setActiveTab ] = useState( 'basePatterns' );

	// Access the global pattern store
	const { sitePatterns } = useSelect( ( select ) => {
		return {
			sitePatterns: select( 'onedesign/site-patterns' ).getSitePatterns(),
		};
	} );

	const patternStore = useDispatch( 'onedesign/site-patterns' );
	const [ siteOptions, setSiteOptions ] = useState( [] );
	const [ isOpen, setIsOpen ] = useState( true );

	// Pattern display settings
	const patternsPerPage = 9;
	const [ visibleCount, setVisibleCount ] = useState( patternsPerPage );
	const [ visibleAppliedCount, setVisibleAppliedCount ] =
		useState( patternsPerPage );
	const [ selectedPatterns, setSelectedPatterns ] = useState( [] );

	const consumerSites = useSelect( ( select ) => {
		const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		return meta?.consumer_site || [];
	} );

	const fetchSites = async () => {
		try {
			const response = await apiFetch( {
				path: `/onedesign/v1/configured-sites`,
			} );

			const data = response;
			setSiteOptions( data );
		} catch ( fetchError ) {
			console.error( 'Error fetching consumer sites:', fetchError ); // eslint-disable-line no-console
			setSiteOptions( [] );
		} finally {
			setIsLoading( false );
		}
	};

	useEffect( () => {
		fetchSites();

		// Fetch site patterns using the global store action
		patternStore.fetchSitePatterns();
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	useEffect( () => {
		setIsLoadingApplied( true );
		const fetchPatterns = async () => {
			try {
				// If we already have site patterns in the global store, use those
				if ( Object.keys( sitePatterns ).length > 0 ) {
					setAllConsumerSitePatterns( sitePatterns );
				} else {
					// Otherwise fetch them directly and update both states
					const patterns = await fetchAllConsumerSitePatterns();
					setAllConsumerSitePatterns( patterns );
					patternStore.setSitePatterns( patterns );
				}
			} catch ( error ) {
				console.error( 'Error fetching consumer site patterns:', error ); // eslint-disable-line no-console
			}
			setIsLoadingApplied( false );
		};
		fetchPatterns();
	}, [ patternStore ] ); // Include patternStore in dependencies

	const { editPost } = useDispatch( 'core/editor' );

	// Filter patterns based on search term
	const filteredBasePatterns = useMemo( () => {
		const categoryFiltered =
			activeCategory === 'All'
				? basePatterns
				: basePatterns.filter( ( pattern ) =>
					pattern.categories?.includes( activeCategory ),
				);

		if ( ! searchTerm.trim() ) {
			return categoryFiltered;
		}

		const searchLower = searchTerm.toLowerCase();
		return categoryFiltered.filter( ( pattern ) => {
			const title = ( pattern.title || pattern.name || '' ).toLowerCase();
			return title.includes( searchLower );
		} );
	}, [ basePatterns, searchTerm, activeCategory ] );

	// Memoize derived values
	const hasMorePatterns = filteredBasePatterns.length > visibleCount;

	// Use callbacks for event handlers to prevent recreating functions on each render
	const loadMorePatterns = useCallback( () => {
		setVisibleCount( ( prevCount ) =>
			Math.min( prevCount + patternsPerPage, filteredBasePatterns.length ),
		);
	}, [ filteredBasePatterns.length ] );

	const handlePatternSelection = ( patternId ) => {
		setSelectedPatterns( ( prevSelectedPatterns ) => {
			if ( prevSelectedPatterns.includes( patternId ) ) {
				return prevSelectedPatterns.filter( ( pattern ) => pattern !== patternId );
			}
			return [ ...prevSelectedPatterns, patternId ];
		} );
		editPost( { meta: { selected_patterns: selectedPatterns } } );
	};

	const handleSearchChange = useCallback( ( value ) => {
		setSearchTerm( value );
		// Reset visible counts when search term changes
		setVisibleCount( patternsPerPage );
	}, [] );

	const handleTabSelect = ( tab ) => {
		setActiveTab( tab );
		setActiveCategory( 'All' );
	};

	// Fetch base site patterns only when needed
	useEffect( () => {
		const fetchPatterns = async () => {
			setIsLoading( true );
			try {
				const baseSiteFetch = await apiFetch( {
					path: `/onedesign/v1/local-patterns`,
				} );
				setBasePatterns( baseSiteFetch );
			} catch ( error ) {
				console.error( 'Error fetching patterns:', error ); // eslint-disable-line no-console
			} finally {
				setIsLoading( false );
			}
		};

		fetchPatterns();
	}, [] );

	// Reset visible counts and search when modal closes
	useEffect( () => {
		setVisibleCount( patternsPerPage );
		setSearchTerm( '' );
	}, [] );

	const [ tabs, setTabs ] = useState( [
		{
			name: 'basePatterns',
			title: __( 'Current Site Patterns', 'onedesign' ),
			className: 'od-base-patterns-tab',
		},
	] );

	useEffect( () => {
		if ( siteOptions ) {
			const newTabs = [
				{
					name: 'basePatterns',
					title: __( 'Current Site Patterns', 'onedesign' ),
					className: 'od-base-patterns-tab',
				},
			];

			// add all sites except base site
			Object.values( siteOptions ).forEach( ( site ) => {
				newTabs.push( {
					name: site.id,
					title: site.name,
					className: 'od-applied-patterns-tab',
					value: site.id,
				} );
			} );

			setTabs( newTabs );
		}
	}, [ siteOptions ] );

	const filteredAppliedPatterns = useMemo( () => {
		const currentTabAppliedPatterns = allConsumerSitePatterns[ activeTab ] || [];
		const categoryFiltered =
			activeCategory === 'All'
				? currentTabAppliedPatterns
				: currentTabAppliedPatterns.filter( ( pattern ) =>
					pattern.categories?.includes( activeCategory ),
				);

		if ( ! searchTerm.trim() ) {
			return categoryFiltered;
		}

		const searchLower = searchTerm.toLowerCase();
		return categoryFiltered.filter( ( pattern ) => {
			const title = ( pattern.title || pattern.name || '' ).toLowerCase();
			return title.includes( searchLower );
		} );
	}, [ allConsumerSitePatterns, searchTerm, activeCategory ] );

	// Search results indicator
	const renderSearchResults = () => {
		if ( ! searchTerm.trim() ) {
			return null;
		}

		return (
			<div className="onedesign-search-results">
				<p>
					{ __( 'Here are patterns with', 'onedesign' ) } &quot;{ searchTerm }
					&quot;
				</p>
			</div>
		);
	};

	const applySelectedPatterns = async () => {
		if ( selectedPatterns.length > 0 && consumerSites.length > 0 ) {
			const data = {
				pattern_names: selectedPatterns,
				target_site_ids: consumerSites,
			};

			try {
				const request = await apiFetch( {
					path: `/onedesign/v1/push-patterns`,
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify( data ),
				} );

				// Check if all site operations were successful
				const hasFailures = Object.values( request ).some(
					( site ) => ! site.success,
				);

				if ( ! hasFailures ) {
					// Fetch patterns again to update the state
					const patterns = await fetchAllConsumerSitePatterns();
					setAllConsumerSitePatterns( patterns );

					// Return success for the BasePatternsTab to use
					return { success: true };
				}

				// Build error message showing which sites failed
				const failedSites = Object.entries( request )
					.filter( ( [ , result ] ) => ! result.success )
					.map( ( [ id, result ] ) => {
						// Find the site name in siteOptions
						const site = siteOptions.find( ( s ) => s.id === id );
						return {
							name: site ? site.name : id,
							message: result.message,
						};
					} );

				const errorMessage =
					__( 'Failed to apply patterns to some sites:', 'onedesign' ) +
					failedSites
						.map( ( site ) => `\n• ${ site.name }: ${ site.message }` )
						.join( '' );

				throw new Error( errorMessage );
			} catch ( error ) {
				console.error( 'Error applying patterns:', error ); // eslint-disable-line no-console
				// Re-throw the error so BasePatternsTab can catch it
				throw error;
			}
		}

		// Return failure if no patterns or consumer sites selected
		return {
			success: false,
			message: __( 'No patterns or sites selected', 'onedesign' ),
		};
	};

	const getFilteredPatterns = ( tab ) => {
		const patternsToBeApplied = allConsumerSitePatterns[ tab.name ];

		// First, filter by search term if one exists
		if ( searchTerm.trim() ) {
			return patternsToBeApplied?.filter( ( pattern ) => {
				const title = ( pattern.title || pattern.name || '' ).toLowerCase();
				return title.includes( searchTerm.toLowerCase() );
			} );
		}

		// Otherwise, filter by category if not "All"
		if ( activeCategory !== 'All' ) {
			return patternsToBeApplied?.filter( ( pattern ) =>
				pattern.categories?.includes( activeCategory ),
			);
		}

		// Return all patterns if no filters apply
		return patternsToBeApplied;
	};

	const hasMoreAppliedPatterns =
		filteredAppliedPatterns.length > visibleAppliedCount;

	const loadMoreAppliedPatterns = useCallback( () => {
		setVisibleAppliedCount( ( prevCount ) =>
			Math.min( prevCount + patternsPerPage, filteredAppliedPatterns.length ),
		);
	}, [ filteredAppliedPatterns.length ] );

	const removeSelectedPatterns = useCallback(
		( patternNames, siteId ) => {
			// Return a promise that resolves or rejects based on the API result
			return new Promise( ( resolve, reject ) => {
				const removePatterns = async () => {
					try {
						const response = await apiFetch( {
							path: `/onedesign/v1/request-remove-consumer-site-patterns`,
							method: 'DELETE',
							headers: {
								'Content-Type': 'application/json',
							},
							body: JSON.stringify( {
								pattern_names: patternNames,
								site_id: siteId,
							} ),
						} );

						if ( response.success ) {
							// Delay closing to show success message
							setTimeout( () => {
								setSelectedPatterns( [] );
								editPost( { meta: { selected_patterns: [] } } );
							}, 2000 );

							// Fetch patterns again to update the state
							const patterns = await fetchAllConsumerSitePatterns();
							setAllConsumerSitePatterns( patterns );

							// Resolve the promise with success
							resolve( response );
						} else {
							const error = new Error(
								response.message ||
									__( 'Failed to remove patterns', 'onedesign' ),
							);
							throw error;
						}
					} catch ( error ) {
						// eslint-disable-next-line no-console
						console.error( 'Error removing patterns:', error );
						// Reject the promise with the error
						reject( error );
					}
				};
				removePatterns();
			} );
		},
		[ editPost ],
	);

	return (
		<>
			<div className="onedesign-loader">
				<Spinner />
				{ __( 'Loading…', 'onedesign' ) }
			</div>
			{ isOpen && (
				<Modal
					title={ __( 'Patterns Library', 'onedesign' ) }
					onRequestClose={ () => {
						setIsOpen( false );
						// Take user back to previous page
						window.history.back();
					} }
					isOpen={ isOpen }
					isFullScreen
					className="onedesign-modal-wrapper"
				>
					<div className="onedesign-modal-content">
						<Category
							activeCategory={ activeCategory }
							setActiveCategory={ setActiveCategory }
							isOpen={ isOpen }
							basePatterns={
								activeTab === 'basePatterns'
									? basePatterns
									: allConsumerSitePatterns[ activeTab ]
							}
						/>

						<div className="onedesign-modal-pattern-content">
							<div className="onedesign-modal-header">
								<SearchControl
									className="onedesign-search"
									value={ searchTerm }
									onChange={ handleSearchChange }
									placeholder={ __( 'Search patterns…', 'onedesign' ) }
								/>
								{ renderSearchResults() }
							</div>

							<div className="onedesign-modal-tabs">
								<TabPanel
									className="onedesign-tabs"
									activeClass="active-tab"
									tabs={ tabs }
									onSelect={ handleTabSelect }
								>
									{ ( tab ) => {
										if ( tab.name === 'basePatterns' ) {
											return (
												<BasePatternsTab
													isLoading={ isLoading }
													basePatterns={ filteredBasePatterns }
													visibleCount={ visibleCount }
													handlePatternSelection={ handlePatternSelection }
													hasMorePatterns={ hasMorePatterns }
													loadMorePatterns={ loadMorePatterns }
													searchTerm={ searchTerm }
													setSelectedPatterns={ setSelectedPatterns }
													selectedPatterns={ selectedPatterns }
													applySelectedPatterns={ applySelectedPatterns }
													consumerSites={ consumerSites }
													sitePatterns={ allConsumerSitePatterns }
												/>
											);
										}
										// based on tab name show applied patterns
										return (
											<AppliedPatternsTab
												isLoadingApplied={ isLoadingApplied }
												appliedPatterns={ getFilteredPatterns( tab ) }
												visibleAppliedCount={ visibleAppliedCount }
												selectedPatterns={ selectedPatterns }
												hasMoreAppliedPatterns={ hasMoreAppliedPatterns }
												loadMoreAppliedPatterns={ loadMoreAppliedPatterns }
												applySelectedPatterns={ removeSelectedPatterns }
												setVisibleAppliedCount={ setVisibleAppliedCount }
												siteInfo={ tab }
											/>
										);
									} }
								</TabPanel>
								{ /* Add icon button to redirect user to onedesign-settings page */ }
								<IconButton
									icon="admin-generic"
									label={ __( 'Go to OneDesign Settings', 'onedesign' ) }
									onClick={ () => {
										window.location.href = '/wp-admin/admin.php?page=onedesign-settings';
									} }
									className="onedesign-settings-button"
								/>
							</div>
						</div>
					</div>
				</Modal>
			) }
		</>
	);
};

export default LibraryModal;
