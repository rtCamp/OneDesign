import { __ } from '@wordpress/i18n';
import { Button, Notice } from '@wordpress/components';
import { getInitials } from '../../../js/utils';

const SiteSelection = ( { siteInfo, isApplying, setIsApplying, onApply, setIsApplyModalOpen, setSelectedSites, selectedSites, notice } ) => {
	const handleSiteSelection = ( siteId ) => {
		setSelectedSites( ( prevSelected ) => {
			if ( prevSelected.includes( siteId ) ) {
				return prevSelected.filter( ( id ) => id !== siteId );
			}
			return [ ...prevSelected, siteId ];
		} );
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
					return (
						<div
							onClick={ () => handleSiteSelection( id ) }
							role="checkbox"
							key={ id }
							className="onedesign-site-item"
							tabIndex="0"
							onKeyDown={ ( e ) => {
								if ( e.key === 'Enter' || e.key === ' ' ) {
									handleSiteSelection( id );
								}
							} }
							aria-checked={ selectedSites.includes( id ) }
						>
							{ selectedSites.includes( id ) && (
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
