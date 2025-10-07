/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	Notice,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getInitials } from '../../../js/utils';

/**
 * SiteSelection component.
 *
 * @param {Object}   props                     - Component props.
 * @param {Array}    props.siteInfo            - Array of connected site information.
 * @param {boolean}  props.isApplying          - Boolean indicating if templates are being applied.
 * @param {Function} props.setIsApplying       - Function to set the isApplying state.
 * @param {Function} props.onApply             - Function to handle applying templates to selected sites.
 * @param {Function} props.setIsApplyModalOpen - Function to control the visibility of the apply modal.
 * @param {Function} props.setSelectedSites    - Function to set the selected site IDs.
 * @param {Array}    props.selectedSites       - Array of selected site IDs.
 * @param {Object}   props.notice              - Notice object containing type and message.
 * @param {Array}    props.brandSiteTemplates  - Array of templates available for brand sites.
 * @param {Array}    props.selectedTemplates   - Array of selected template IDs.
 * @return {JSX.Element} The rendered component.
 */
const SiteSelection = ( { siteInfo, isApplying, setIsApplying, onApply, setIsApplyModalOpen, setSelectedSites, selectedSites, notice, brandSiteTemplates, selectedTemplates } ) => {
	const handleSiteSelection = ( siteId ) => {
		setSelectedSites( ( prevSelected ) => {
			if ( prevSelected.includes( siteId ) ) {
				return prevSelected.filter( ( id ) => id !== siteId );
			}
			return [ ...prevSelected, siteId ];
		} );
	};

	// Helper function to check if all templates are already present
	const areAllTemplatesPresent = ( siteId ) => {
		if ( selectedTemplates.length === 0 || brandSiteTemplates[ siteId ] === undefined ) {
			return false;
		}

		const availableTemplateIds = Object.values( brandSiteTemplates[ siteId ] ).map(
			( template ) => template.original_id,
		);

		return selectedTemplates.every( ( templateId ) => availableTemplateIds.includes( templateId ) );
	};

	return (
		<>
			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible
					onRemove={ () => {} }
				>
					{ notice.message }
				</Notice>
			) }
			<div className="onedesign-site-selection">
				{ siteInfo.length === 0 && (
					<p>{ __( 'No connected sites found.', 'onedesign' ) }</p>
				) }
				<div className="onedesign-site-grid">
					{ siteInfo.length > 0 &&
					siteInfo.map( ( { id, name, url, logo } ) => {
						const isDisabled = areAllTemplatesPresent( id );

						return (
							<div
								onClick={ () => ! isDisabled && handleSiteSelection( id ) }
								role="checkbox"
								key={ id }
								className={ `onedesign-site-item ${ isDisabled ? 'onedesign-site-disabled' : '' }` }
								tabIndex={ isDisabled ? -1 : 0 }
								onKeyDown={ ( e ) => {
									if ( ! isDisabled && ( e.key === 'Enter' || e.key === ' ' ) ) {
										handleSiteSelection( id );
									}
								} }
								aria-checked={ selectedSites.includes( id ) }
								aria-disabled={ isDisabled }
								style={ isDisabled ? {
									opacity: 0.7,
									cursor: 'not-allowed',
									backgroundColor: '#f9f9f9',
								} : {} }
							>
								{ ( selectedSites.includes( id ) || isDisabled ) && (
									<div className="onedesign-site-selected-indicator">
										<span className="dashicons dashicons-yes-alt"></span>
									</div>
								) }
								<div className="onedesign-site-inner">
									<div className="onedesign-site-logo">
										{ logo ? (
											<img src={ logo } alt={ name } loading="lazy" />
										) : (
											<div className="onedesign-site-initials">
												{ getInitials( name ) }
											</div>
										) }
									</div>
									<span className="onedesign-site-name">{ name }</span>
									<span className="onedesign-site-url">{ url }</span>
									{
										selectedTemplates.length > 0 && brandSiteTemplates[ id ] !== undefined && (
											( () => {
												// Get array of original_ids from brandSiteTemplates[id]
												const availableTemplateIds = Object.values( brandSiteTemplates[ id ] ).map(
													( template ) => template.original_id,
												);

												// Check how many selected templates are already present
												const alreadyPresentCount = selectedTemplates.filter(
													( templateId ) => availableTemplateIds.includes( templateId ),
												).length;

												const totalSelected = selectedTemplates.length;

												// All selected templates are already present
												if ( alreadyPresentCount === totalSelected ) {
													return (
														<span className="onedesign-site-template-status onedesign-templates-available">
															{ __( 'All selected templates are already present.', 'onedesign' ) }
														</span>
													);
												} else if ( alreadyPresentCount > 0 ) {
													return (
														<span className="onedesign-site-template-status onedesign-templates-partial">
															{
																sprintf(
																	/* translators: 1: Number of templates already present. 2: Total number of selected templates. */
																	__( '%1$d of %2$d selected templates are already present.', 'onedesign' ),
																	alreadyPresentCount,
																	totalSelected,
																)
															}
														</span>
													);
												}

												return (
													<span className="onedesign-site-template-status onedesign-templates-not-available">
														{ __( 'All templates will be synced.', 'onedesign' ) }
													</span>
												);
											} )()
										)
									}
								</div>
							</div>
						);
					} )
					}
				</div>
				<div className="onedesign-site-selection-actions">
					<Button
						variant="secondary"
						onClick={ () => {
							setIsApplyModalOpen( false );
							setSelectedSites( [] );
						} }
					>
						{ __( 'Cancel', 'onedesign' ) }
					</Button>
					<Button
						variant="primary"
						disabled={ selectedSites.length === 0 || isApplying }
						isBusy={ isApplying }
						onClick={ () => {
							setIsApplying( true );
							onApply( selectedSites );
						} }
					>
						{ isApplying ? __( 'Applying…', 'onedesign' ) : __( 'Apply Templates', 'onedesign' ) }
					</Button>
				</div>
			</div>
		</>
	);
};

export default SiteSelection;
