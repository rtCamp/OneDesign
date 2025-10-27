/**
 * WordPress dependencies
 */
import {
	useState,
	useEffect,
	useCallback,
	useMemo,
} from '@wordpress/element';
import {
	Modal,
	SearchControl,
	TabPanel,
	Spinner,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { cog } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import BasePatternsTab from './BasePatternsTab';
import AppliedPatternsTab from './AppliedPatternsTab';
import Category from './Category';
import { SETTINGS_LINK as SettingLink } from '../../../js/constants';

/**
 * Fetch all brand site patterns
 *
 * @return {Promise<Array>} A promise that resolves to an array of patterns.
 */
function fetchAllBrandSitePatterns() {
	return apiFetch( {
		path: `/onedesign/v1/get-all-brand-site-patterns?timestamp=${ Date.now() }`,
	} )
		.then( ( data ) => {
			if ( data.success ) {
				return data.patterns || [];
			}
			throw new Error( 'Failed to fetch patterns' );
		} )
		.catch( ( error ) => {
			console.error( 'Error fetching brand site patterns:', error ); // eslint-disable-line no-console
			return [];
		} );
}

/**
 * PatternModal component
 * Displays the patterns library modal with tabs for base patterns and applied patterns.
 * Allows users to search, filter, and apply patterns across different brand sites.
 *
 * @return {JSX.Element} The rendered modal component.
 */
const PatternModal = () => {
	const [ basePatterns, setBasePatterns ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const [ activeCategory, setActiveCategory ] = useState( 'All' );
	const [ allBrandSitePatterns, setAllBrandSitePatterns ] = useState( [] );
	const [ activeTab, setActiveTab ] = useState( 'basePatterns' );
	const [ notice, setNotice ] = useState( null );

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
	const PER_PAGE = 9;
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const [ currentAppliedPage, setCurrentAppliedPage ] = useState( 1 );
	const [ selectedPatterns, setSelectedPatterns ] = useState( [] );
	const [ selectedAppliedPatterns, setSelectedAppliedPatterns ] = useState( [] );

	const BrandSites = useSelect( ( select ) => {
		const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		return meta?.brand_site || [];
	} );

	const fetchSites = async () => {
		try {
			const response = await apiFetch( {
				path: `/onedesign/v1/configured-sites`,
			} );

			const data = response;
			setSiteOptions( data );
		} catch ( fetchError ) {
			console.error( 'Error fetching brand sites:', fetchError ); // eslint-disable-line no-console
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
		const fetchPatterns = async () => {
			try {
				// If we already have site patterns in the global store, use those
				if ( Object.keys( sitePatterns ).length > 0 ) {
					setAllBrandSitePatterns( sitePatterns );
				} else {
					// Otherwise fetch them directly and update both states
					const patterns = await fetchAllBrandSitePatterns();
					setAllBrandSitePatterns( patterns );
					patternStore.setSitePatterns( patterns );
				}
			} catch ( error ) {
				console.error( 'Error fetching brand site patterns:', error ); // eslint-disable-line no-console
			}
		};
		fetchPatterns();
	// eslint-disable-next-line react-hooks/exhaustive-deps
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

	// Use callbacks for event handlers to prevent recreating functions on each render
	const handlePatternSelection = ( patternId ) => {
		setSelectedPatterns( ( prevSelectedPatterns ) => {
			if ( prevSelectedPatterns.includes( patternId ) ) {
				return prevSelectedPatterns.filter( ( pattern ) => pattern !== patternId );
			}
			return [ ...prevSelectedPatterns, patternId ];
		} );
		editPost( { meta: { selected_patterns: selectedPatterns } } );
	};

	const handleAppliedPatternSelection = ( patternName ) => {
		setSelectedAppliedPatterns( ( prevSelected ) => {
			if ( prevSelected.includes( patternName ) ) {
				return prevSelected.filter( ( name ) => name !== patternName );
			}
			return [ ...prevSelected, patternName ];
		} );
	};

	const handleSearchChange = useCallback( ( value ) => {
		setSearchTerm( value );
		// Reset pages when search term changes
		setCurrentPage( 1 );
		setCurrentAppliedPage( 1 );
	}, [] );

	const handleTabSelect = ( tab ) => {
		setActiveTab( tabs.find( ( t ) => t.name === tab )?.value || 'basePatterns' );
		setActiveCategory( 'All' );
		setCurrentPage( 1 );
		setCurrentAppliedPage( 1 );
		setSelectedPatterns( [] );
		setSelectedAppliedPatterns( [] );
		setNotice( null );
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

	// Reset when modal closes
	useEffect( () => {
		setCurrentPage( 1 );
		setSearchTerm( '' );
	}, [] );

	const [ tabs, setTabs ] = useState( [
		{
			name: 'basePatterns',
			title: __( 'Current Site Patterns', 'onedesign' ),
			className: 'onedesign-base-patterns-tab',
		},
	] );

	useEffect( () => {
		if ( siteOptions ) {
			const newTabs = [
				{
					name: 'basePatterns',
					title: __( 'Current Site Patterns', 'onedesign' ),
					className: 'onedesign-base-patterns-tab',
					value: 'basePatterns',
				},
			];

			// add all sites except base site
			Object.values( siteOptions ).forEach( ( site ) => {
				newTabs.push( {
					name: site.name,
					title: site.name,
					className: 'onedesign-applied-patterns-tab',
					value: site.id,
				} );
			} );

			setTabs( newTabs );
		}
	}, [ siteOptions ] );

	const filteredAppliedPatterns = useMemo( () => {
		const currentTabAppliedPatterns = allBrandSitePatterns[ activeTab ] || [];
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
	}, [ allBrandSitePatterns, searchTerm, activeCategory, activeTab ] );

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
		if ( selectedPatterns.length > 0 && BrandSites.length > 0 ) {
			const data = {
				pattern_names: selectedPatterns,
				target_site_ids: BrandSites,
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
					const patterns = await fetchAllBrandSitePatterns();
					setAllBrandSitePatterns( patterns );

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

		// Return failure if no patterns or brand sites selected
		return {
			success: false,
			message: __( 'No patterns or sites selected', 'onedesign' ),
		};
	};

	const removeSelectedPatterns = useCallback(
		async ( patternNames, siteId ) => {
			try {
				const response = await apiFetch( {
					path: `/onedesign/v1/request-remove-brand-site-patterns`,
					method: 'DELETE',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify( {
						pattern_names: patternNames,
						site_id: siteId.toString(),
					} ),
				} );

				if ( response.success ) {
					// Fetch patterns again to update the state
					const patterns = await fetchAllBrandSitePatterns();
					setAllBrandSitePatterns( patterns );

					return response;
				}

				throw new Error(
					response.message || __( 'Failed to remove patterns', 'onedesign' ),
				);
			} catch ( error ) {
				console.error( 'Error removing patterns:', error ); // eslint-disable-line no-console
				throw error;
			}
		},
		[],
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
					headerActions={
						<div
							style={ {
								display: 'flex',
								gap: '8px',
								flexDirection: 'row',
								alignItems: 'center',
							} }
						>

							{ SettingLink && (
								<Button
									icon={ cog }
									variant="secondary"
									onClick={ () => {
										window.location.href = SettingLink;
									} }
									label={ __( 'Go to OneDesign Settings', 'onedesign' ) }
								/>
							) }
						</div>
					}
				>
					<div className="onedesign-modal-content">
						<Category
							activeCategory={ activeCategory }
							setActiveCategory={ setActiveCategory }
							isOpen={ isOpen }
							basePatterns={
								activeTab === 'basePatterns'
									? basePatterns
									: allBrandSitePatterns[ activeTab ]
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
													visibleCount={ currentPage * PER_PAGE }
													handlePatternSelection={ handlePatternSelection }
													hasMorePatterns={ filteredBasePatterns.length > currentPage * PER_PAGE }
													loadMorePatterns={ () => setCurrentPage( ( prev ) => prev + 1 ) }
													searchTerm={ searchTerm }
													setSelectedPatterns={ setSelectedPatterns }
													selectedPatterns={ selectedPatterns }
													applySelectedPatterns={ applySelectedPatterns }
													sitePatterns={ allBrandSitePatterns }
													siteOptions={ siteOptions }
													BrandSites={ BrandSites }
												/>
											);
										}
										// based on tab name show applied patterns
										return (
											<AppliedPatternsTab
												appliedPatterns={ filteredAppliedPatterns }
												currentPage={ currentAppliedPage }
												PER_PAGE={ PER_PAGE }
												selectedPatterns={ selectedAppliedPatterns }
												handlePatternSelection={ handleAppliedPatternSelection }
												setCurrentPage={ setCurrentAppliedPage }
												siteInfo={ tab }
												applySelectedPatterns={ removeSelectedPatterns }
												setSelectedPatterns={ setSelectedAppliedPatterns }
												notice={ notice }
												setNotice={ setNotice }
											/>
										);
									} }
								</TabPanel>
							</div>
						</div>
					</div>
				</Modal>
			) }
		</>
	);
};

export default PatternModal;
