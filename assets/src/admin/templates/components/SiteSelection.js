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
const SiteSelection = ( {
	siteInfo,
	isApplying,
	setIsApplying,
	onApply,
	setIsApplyModalOpen,
	setSelectedSites,
	selectedSites,
	notice,
	brandSiteTemplates,
	selectedTemplates,
} ) => {
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

	const selectAllSites = () => {
		// Get IDs of sites that don't already have all templates (not disabled)
		const selectableSiteIds = siteInfo
			.filter( ( site ) => ! areAllTemplatesPresent( site.id ) )
			.map( ( site ) => site.id );

		setSelectedSites( selectableSiteIds );
	};

	const deselectAllSites = () => {
		setSelectedSites( [] );
	};

	const totalCount = siteInfo.length;

	// Calculate the number of sites that don't have all templates already
	const selectableSites = siteInfo.filter( ( site ) => ! areAllTemplatesPresent( site.id ) );
	const selectableSiteCount = selectableSites.length;
	const selectedSelectableSiteCount = selectedSites.filter( ( siteId ) =>
		selectableSites.some( ( site ) => site.id === siteId ),
	).length;

	const selectedCount = selectedSites.length;

	if ( siteInfo.length === 0 ) {
		return (
			<div className="od-no-sites">
				<Notice status="warning" isDismissible={ false }>
					<p>{ __( 'No connected sites found.', 'onedesign' ) }</p>
					<p>
						{ __(
							'Please configure brand sites first to apply templates.',
							'onedesign',
						) }
					</p>
				</Notice>
			</div>
		);
	}

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

			<div className="od-brand-site-modal-content">
				<div className="od-site-selection-wrapper">
					<div className="od-brand-site-selection">

						<div className="od-selection-header">
							<div className="od-selection-summary">
								<h4>{ __( 'Select Brand Sites', 'onedesign' ) }</h4>
								<span className="od-selection-count">
									{ selectedCount > 0
										? sprintf(
											/* translators: %1$d: Number of selected sites, %2$d: Total number of sites. */
											__( '%1$d of %2$d selected', 'onedesign' ),
											selectedCount,
											selectableSiteCount,
										)
										: sprintf(
											/* translators: %1$d: Number of available sites, %2$d: Total number of sites. */
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
						{ selectedTemplates.length > 0 && totalCount !== selectableSiteCount && (
							<div className="od-selection-hint">
								<p>
									<span className="dashicons dashicons-info"></span>
									{ sprintf(
										/* translators: %1$d: Number of sites that already have all selected templates. %2$d: Total number of sites. */
										__(
											'%1$d of %2$d sites already have all selected templates and are disabled.',
											'onedesign',
										),
										totalCount - selectableSiteCount,
										totalCount,
									) }
								</p>
							</div>
						) }

						<div className="od-sites-list od-sites-grid">
							{ siteInfo.map( ( { id, name, url, logo } ) => {
								const isSelected = selectedSites.includes( id );
								const isDisabled = areAllTemplatesPresent( id ) && ! isSelected;

								return (
									<div
										key={ id }
										className={ `od-site-item ${ isSelected ? 'od-site-selected' : '' } ${ isDisabled ? 'od-site-disabled' : '' }` }
										onClick={ () => ! isDisabled && handleSiteSelection( id ) }
										onKeyDown={ ( e ) => {
											if ( ! isDisabled && ( e.key === 'Enter' || e.key === ' ' ) ) {
												handleSiteSelection( id );
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
														'This site already has all selected templates',
														'onedesign',
													) }
												>
													<span className="dashicons dashicons-yes"></span>
												</div>
											) }
											<div className="od-site-logo">
												{ logo ? (
													<img src={ logo } alt={ name } loading="lazy" />
												) : (
													<div className="od-site-initials">
														{ getInitials( name ) }
													</div>
												) }
											</div>
											<span className="od-site-name">{ name }</span>
											{ url && <span className="od-site-url">{ url }</span> }

											{ /* Template sync status */ }
											{ selectedTemplates.length > 0 && brandSiteTemplates[ id ] !== undefined && (
												<div className="od-template-status">
													{ ( () => {
														const availableTemplateIds = Object.values( brandSiteTemplates[ id ] ).map(
															( template ) => template.original_id,
														);

														const alreadyPresentCount = selectedTemplates.filter(
															( templateId ) => availableTemplateIds.includes( templateId ),
														).length;

														const totalSelected = selectedTemplates.length;

														if ( alreadyPresentCount === 0 ) {
															return (
																<span className="od-onedesign-info">
																	{ __( 'All templates will be synced', 'onedesign' ) }
																</span>
															);
														}

														if ( alreadyPresentCount === totalSelected ) {
															return (
																<span className="od-onedesign-info od-all-templates-present">
																	{ __(
																		'All selected templates are already present',
																		'onedesign',
																	) }
																</span>
															);
														}

														return (
															<span className="od-onedesign-info">
																{
																	sprintf(
																		/* translators: %1$d: Number of selected templates already present. %2$d: Total number of selected templates. */
																		__( '%1$d of %2$d selected templates are already present', 'onedesign' ),
																		alreadyPresentCount,
																		totalSelected,
																	)
																}
															</span>
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
				</div>

				<div className="od-modal-actions">
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
