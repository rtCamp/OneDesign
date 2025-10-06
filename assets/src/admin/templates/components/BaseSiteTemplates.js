import { __ } from '@wordpress/i18n';
import MemoizedTemplatePreview from './MemoizedTemplatePreview';

const BaseSiteTemplates = ( { filteredTemplates, currentPage, PER_PAGE, selectedTemplates, handleTemplateSelection } ) => {
	const renderTemplates = () => {
		if ( filteredTemplates.length === 0 ) {
			return (
				<div className="onedesign-no-templates">
					<p>{ __( 'No templates found.', 'onedesign' ) }</p>
				</div>
			);
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

	return renderTemplates();
};

export default BaseSiteTemplates;
