import { __, _n, sprintf } from '@wordpress/i18n';
import MemoizedTemplatePreview from './MemoizedTemplatePreview';
import { Button, Notice } from '@wordpress/components';
import { useCallback, useState } from '@wordpress/element';

const REST_NAMESPACE = TemplateLibraryData?.restUrl;
const NONCE = TemplateLibraryData?.nonce;

const BrandSiteTemplates = ( { filteredTemplates, currentPage, PER_PAGE, selectedTemplates, handleTemplateSelection, setCurrentPage, currentSiteId, fetchConnectedSitesTemplates, setSelectedTemplates } ) => {
	const [ isProcessing, setIsProcessing ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	const handleRemoveTemplates = useCallback( async () => {
		setIsProcessing( true );
		try {
			const response = await fetch(
				`${ REST_NAMESPACE }/templates/remove`,
				{
					method: 'DELETE',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': NONCE,
					},
					body: JSON.stringify( {
						template_ids: selectedTemplates,
						site: currentSiteId,
					} ),
				},
			);
			const data = await response.json();
			if ( data.success ) {
				fetchConnectedSitesTemplates();
				setNotice( {
					type: 'success',
					message: sprintf(
						/* translators: %d: Number of templates removed. */
						_n(
							'%d template removed successfully.',
							'%d templates removed successfully.',
							selectedTemplates.length,
							'onedesign',
						),
						selectedTemplates.length,
					),
				} );
				setSelectedTemplates( [] );
			} else {
				setNotice( {
					type: 'error',
					message: __( 'Failed to remove templates.', 'onedesign' ),
				} );
			}
		} catch ( error ) {
			setNotice( {
				type: 'error',
				message: error.message || __( 'An error occurred while removing templates.', 'onedesign' ),
			} );
		} finally {
			setIsProcessing( false );
		}
	}, [ selectedTemplates, currentSiteId, fetchConnectedSitesTemplates, setSelectedTemplates ] );

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
						isDestructive
						disabled={ selectedTemplates.length === 0 || isProcessing }
						isBusy={ isProcessing }
						onClick={ () => {
							handleRemoveTemplates();
						} }
					>
						{ selectedTemplates.length === 0 ? __( 'Select Template First', 'onedesign' ) : __( 'Remove Template', 'onedesign' ) }
					</Button>
				</div>
			</div>
		);
	};

	const renderTemplates = () => {
		if ( filteredTemplates.length === 0 ) {
			return <p>{ __( 'No templates found.', 'onedesign' ) }</p>;
		}

		return (
			<div className="onedesign-templates-grid">
				{ filteredTemplates.slice( 0, ( currentPage * PER_PAGE ) ).map( ( template ) => {
					return (
						<MemoizedTemplatePreview
							key={ template?.name }
							template={ template }
							isCheckBoxRequired={ true }
							onSelect={ () => handleTemplateSelection( template?.id ) }
							isSelected={ selectedTemplates.includes( template?.id ) }
						/>
					);
				},
				) }
			</div>
		);
	};

	return (
		<>
			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }
			{ renderTemplates() }
			{ renderPagination() }
		</>
	);
};

export default BrandSiteTemplates;
