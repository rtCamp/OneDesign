/**
 * WordPress dependencies
 */
import {
	Button,
	Modal,
	SearchControl,
	Spinner,
	TabPanel,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useState, useCallback, useEffect, useMemo } from '@wordpress/element';
import { cog } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import BaseSiteTemplates from './BaseSiteTemplates';
import SiteSelection from './SiteSelection';
import BrandSiteTemplates from './BrandSiteTemplates';

// Global variable from PHP
const REST_NAMESPACE = TemplateLibraryData?.restUrl;
const NONCE = TemplateLibraryData?.nonce;
const SettingLink = TemplateLibraryData?.settingsLink;
const PER_PAGE = 9;

/**
 * TemplateModal component.
 *
 * @return {JSX.Element} The rendered component.
 */
const TemplateModal = () => {
	const [ templates, setTemplates ] = useState( [] );
	const [ isOpen, setIsOpen ] = useState( true );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ selectedTemplates, setSelectedTemplates ] = useState( [] );
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const [ activeTab, setActiveTab ] = useState( 'baseTemplate' );
	const [ siteInfo, setSiteInfo ] = useState( {} );
	const [ selectedSites, setSelectedSites ] = useState( [] );
	const [ connectedSitesTemplates, setConnectedSitesTemplates ] = useState( {} );
	const [ notice, setNotice ] = useState( null );
	const [ isReSyncing, setIsReSyncing ] = useState( false );
	const [ tabs, setTabs ] = useState( [
		{
			name: 'baseTemplate',
			title: __( 'Current Site Templates', 'onedesign' ),
			className: 'onedesign-base-templates-tab',
			value: 'baseTemplate',
		},
	] );
	const [ isApplyModalOpen, setIsApplyModalOpen ] = useState( false );
	const [ isApplying, setIsApplying ] = useState( false );

	const fetchConnectedSitesTemplates = useCallback( async () => {
		try {
			const response = await fetch(
				`${ REST_NAMESPACE }/templates/connected-sites?timestamp=${ Date.now() }`,
				{
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': NONCE,
					},
				},
			);
			const data = await response.json();
			if ( data.success ) {
				setConnectedSitesTemplates( data.templates || {} );
			}
		} catch ( error ) {
		}
	}, [] );

	const fetchTemplates = useCallback( async () => {
		setIsLoading( true );
		try {
			const response = await fetch(
				`${ REST_NAMESPACE }/templates/all?timestamp=${ Date.now() }`,
				{
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': NONCE,
					},
				},
			);
			const data = await response.json();
			if ( data.success ) {
				setTemplates( data.templates || [] );
			}
		} catch ( error ) {
		} finally {
			setIsLoading( false );
		}
	}, [] );

	const fetchBrandSitesInfo = useCallback( async () => {
		setIsLoading( true );
		try {
			const response = await fetch(
				`${ REST_NAMESPACE }/configured-sites?timestamp=${ Date.now() }`,
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
		} catch ( error ) {
		} finally {
			setIsLoading( false );
		}
	}, [] );

	const handleTemplateReSync = useCallback( async () => {
		setIsReSyncing( true );
		try {
			const idArray = Object.values( connectedSitesTemplates ).flat().map( ( template ) => template.id );
			const originalIdArray = Object.values( connectedSitesTemplates ).flat().map( ( template ) => template.original_id );
			const response = await fetch(
				`${ REST_NAMESPACE }/templates/resync`,
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': NONCE,
					},
					body: JSON.stringify(
						{
							sites: Array.of( activeTab ),
							templates: [ ...idArray, ...originalIdArray ],
						},
					),
				},
			);
			const data = await response.json();
			if ( data.success ) {
				fetchConnectedSitesTemplates();
				setNotice( {
					type: 'success',
					message: __( 'Templates re-synced successfully.', 'onedesign' ),
				} );
			} else {
				setNotice( {
					type: 'error',
					message: __( 'Failed to re-sync templates.', 'onedesign' ),
				} );
			}
		} catch ( error ) {
		} finally {
			setIsReSyncing( false );
		}
	}, [ fetchConnectedSitesTemplates, activeTab, connectedSitesTemplates ] );

	const handleApplyTemplates = useCallback( async () => {
		setIsApplying( true );
		try {
			const response = await fetch(
				`${ REST_NAMESPACE }/templates/apply`,
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': NONCE,
					},
					body: JSON.stringify( {
						templates: selectedTemplates,
						sites: selectedSites,
					} ),
				},
			);
			const data = await response.json();
			if ( data.success ) {
				// Handle success (e.g., show a success message)
				setSelectedTemplates( [] );
				setSelectedSites( [] );
				fetchConnectedSitesTemplates();
				setNotice( {
					type: 'success',
					message: sprintf(
						/* translators: %s site names. */
						__( 'Templates applied successfully to %s site.', 'onedesign' ),
						Object.values( siteInfo ).filter( ( site ) => selectedSites.includes( site.id ) ).map( ( site ) => site.name ).join( ', ' ),
					),
				} );
				setTimeout( () => {
					setNotice( null );
					setIsApplyModalOpen( false );
				}, 3000 );
			} else {
				setNotice( {
					type: 'error',
					message: __( 'Failed to apply templates.', 'onedesign' ),
				} );
			}
		} catch ( error ) {
			setNotice( {
				type: 'error',
				message: __( 'An error occurred while applying templates.', 'onedesign' ),
			} );
		} finally {
			setIsApplying( false );
			setTimeout( () => {
				setNotice( null );
			}, 3000 );
		}
	}, [ selectedTemplates, selectedSites, fetchConnectedSitesTemplates, siteInfo ] );

	// Fetch templates when the modal is opened
	useEffect( () => {
		fetchTemplates();
		fetchBrandSitesInfo();
		fetchConnectedSitesTemplates();
	// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// clear notice on tab change
	useEffect( () => {
		setNotice( null );
	}, [ activeTab ] );

	// create tabs based on siteInfo
	useEffect( () => {
		const newTabs = [ {
			name: 'baseTemplate',
			title: __( 'Current Site Templates', 'onedesign' ),
			className: 'onedesign-base-templates-tab',
			value: 'baseTemplate',
		} ];
		Object.values( siteInfo ).forEach( ( site ) => {
			if ( site?.id && site?.name ) {
				newTabs.push( {
					name: site.name,
					title: site.name,
					className: 'onedesign-templates-tab-brand-site',
					value: site.id,
				} );
			}
		} );
		setTabs( newTabs );
	}, [ siteInfo ] );

	const handleTemplateSelection = ( ( tId ) => {
		setSelectedTemplates( ( prevSelected ) => {
			const newSelected = prevSelected.includes( tId )
				? prevSelected.filter( ( id ) => id !== tId )
				: [ ...prevSelected, tId ];
			return newSelected;
		} );
	} );

	// filter templates based on search query
	const filteredTemplates = useMemo( () => {
		if ( searchQuery.trim() === '' ) {
			return templates;
		}
		return templates.filter( ( template ) =>
			template.title.toLowerCase().includes( searchQuery.toLowerCase() ) ||
            ( template.description && template.description.toLowerCase().includes( searchQuery.toLowerCase() ) ),
		);
	}, [ templates, searchQuery ] );

	const renderPagination = () => {
		// default will show 9 templates then will show load more button.
		return (
			<div className="onedesign-pagination">
				<div className="onedesign-selected-templates-info">
					{ selectedTemplates.length > 0 && (
						<div className="onedesign-selected-templates-count-info">
							<span className="onedesign-selected-templates-count">{ selectedTemplates.length }</span>
							<span className="onedesign-selected-templates-text">{ selectedTemplates.length === 1 ? __( 'Template selected', 'onedesign' ) : __( 'Templates selected', 'onedesign' ) }</span>
						</div>
					) }
				</div>
				<div style={ { display: 'flex', gap: '12px', flexDirection: 'row' } }>
					<Button
						variant="secondary"
						disabled={ ( currentPage * PER_PAGE ) >= filteredTemplates.length }
						onClick={ () => setCurrentPage( ( prevPage ) => prevPage + 1 ) }
					>
						{ __( 'Show More', 'onedesign' ) } { ( Math.min( currentPage * PER_PAGE, filteredTemplates.length ) ) }/{ filteredTemplates.length }
					</Button>
					<Button
						variant="primary"
						disabled={ selectedTemplates.length === 0 }
						onClick={ () => {
							setIsApplyModalOpen( true );
						} }
					>
						{ selectedTemplates.length === 0 ? __( 'Select Template First', 'onedesign' ) : __( 'Apply To Sites', 'onedesign' ) }
					</Button>
				</div>
			</div>
		);
	};

	const handleTabSelection = ( ( tab ) => {
		setActiveTab( tabs.find( ( t ) => t.name === tab )?.value || 'baseTemplate' );
		setSearchQuery( '' );
		setCurrentPage( 1 );
		setSelectedTemplates( [] );
	} );

	return (
		<>
			<div className="onedesign-loader">
				<Spinner />
				{ __( 'Loading…', 'onedesign' ) }
			</div>
			{ isOpen && (
				<Modal
					title={ __( 'Template Library', 'onedesign' ) }
					onRequestClose={ () => {
						setIsOpen( false );
						// take user to previous page
						window.history.back();
					} }
					className="onedesign-template-modal"
					headerActions={
						<div
							style={ {
								display: 'flex',
								gap: '8px',
								flexDirection: 'row',
								alignItems: 'center',
							} }
						>
							{ activeTab !== 'baseTemplate' && (
								<Button
									variant="primary"
									onClick={ () => {
										handleTemplateReSync();
									} }
									isBusy={ isReSyncing }
									disabled={ isReSyncing || Object.keys( connectedSitesTemplates )?.length === 0 || ( connectedSitesTemplates?.[ activeTab ] || [] )?.length === 0 }
									label={ __( 'Sync Shared Templates', 'onedesign' ) }
								>
									{ __( 'Sync Shared Templates', 'onedesign' ) }
								</Button>
							) }

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
					{ isLoading && (
						<div style={ { textAlign: 'center', padding: '20px' } }>
							<Spinner />
							<p>{ __( 'Loading templates…', 'onedesign' ) }</p>
						</div>
					) }
					{ ! isLoading && (
						<>
							<SearchControl
								value={ searchQuery }
								onChange={ ( value ) => setSearchQuery( value ) }
								placeholder={ __( 'Search Templates', 'onedesign' ) }
								className="onedesign-template-search"
							/>
							<TabPanel
								className="onedesign-template-tabs"
								activeClass="active-tab"
								onSelect={ handleTabSelection }
								tabs={ tabs }
							>
								{ ( tab ) => {
									if ( tab.name === 'baseTemplate' ) {
										return (
											<>
												<BaseSiteTemplates
													filteredTemplates={ filteredTemplates }
													currentPage={ currentPage }
													PER_PAGE={ PER_PAGE }
													selectedTemplates={ selectedTemplates }
													handleTemplateSelection={ handleTemplateSelection }
												/>
												{ renderPagination() }
											</>
										);
									}
									return (
										<BrandSiteTemplates
											filteredTemplates={ ( connectedSitesTemplates[ tab.value ] || [] ).filter( ( template ) =>
												template.title.toLowerCase().includes( searchQuery.toLowerCase() ) ||
											( template.description && template.description.toLowerCase().includes( searchQuery.toLowerCase() ) ),
											) }
											currentPage={ 1 }
											PER_PAGE={ PER_PAGE }
											selectedTemplates={ selectedTemplates }
											handleTemplateSelection={ handleTemplateSelection }
											setCurrentPage={ setCurrentPage }
											currentSiteId={ tab.value }
											fetchConnectedSitesTemplates={ fetchConnectedSitesTemplates }
											setSelectedTemplates={ setSelectedTemplates }
											allTemplates={ templates }
											notice={ notice }
											setNotice={ setNotice }
										/> );
								} }
							</TabPanel>
							{ isApplyModalOpen && (
								<Modal
									title={ __( 'Select Brand Sites', 'onedesign' ) }
									onRequestClose={ () => setIsApplyModalOpen( false ) }
									className="onedesign-apply-templates-modal"
									isFullScreen={ true }
								>
									<SiteSelection
										siteInfo={ siteInfo }
										isApplying={ isApplying }
										setIsApplying={ setIsApplying }
										onApply={ () => {
											handleApplyTemplates();
										} }
										setIsApplyModalOpen={ setIsApplyModalOpen }
										setSelectedSites={ setSelectedSites }
										selectedSites={ selectedSites }
										notice={ notice }
										brandSiteTemplates={ connectedSitesTemplates }
										selectedTemplates={ selectedTemplates }
									/>
								</Modal>
							) }

						</>
					) }
				</Modal>
			) }
		</>
	);
};

export default TemplateModal;
