/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MemoizedTemplatePreview from './MemoizedTemplatePreview';

/**
 * BaseSiteTemplates component.
 *
 * @param {Object}   props                         - Component props.
 * @param {Array}    props.filteredTemplates       - Array of filtered templates to display.
 * @param {number}   props.currentPage             - Current page number for pagination.
 * @param {number}   props.PER_PAGE                - Number of templates to display per page.
 * @param {Array}    props.selectedTemplates       - Array of selected template IDs.
 * @param {Function} props.handleTemplateSelection - Function to handle template selection.
 * @return {JSX.Element} The rendered component.
 */
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
