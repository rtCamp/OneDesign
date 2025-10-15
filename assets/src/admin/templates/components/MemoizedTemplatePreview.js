/**
 * WordPress dependencies
 */
import { memo, useMemo } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { parse } from '@wordpress/blocks';
import { BlockPreview } from '@wordpress/block-editor';

/**
 * Separate memoized component for the block preview content
 *
 * @param {Object} props              - Component props.
 * @param {Array}  props.parsedBlocks - Parsed blocks to render in the preview.
 * @return {JSX.Element} The rendered block preview content.
 */
const TemplatePreviewContent = memo( ( { parsedBlocks } ) => {
	return (
		<div className="od-template-preview">
			<BlockPreview blocks={ parsedBlocks } viewportWidth={ 1200 } />
		</div>
	);
} );

/**
 * Separate memoized component for template categories
 *
 * @param {Object} props            - Component props.
 * @param {Object} props.categories - Categories object from the template.
 * @return {JSX.Element|null} The rendered categories or null if none.
 */
const TemplateCategories = memo( ( { categories } ) => {
	if ( ! categories || typeof categories !== 'object' || Array.isArray( categories ) ) {
		return null;
	}

	const categoryValues = Object.values( categories ).filter( Boolean );

	if ( ! categoryValues.length ) {
		return null;
	}

	return (
		<div className="od-template-categories">
			<p>{ __( 'Categories:', 'onedesign' ) }</p>
			{ categoryValues.map( ( category, i ) => (
				<span key={ `${ category }-${ i }` } className="od-template-category">
					{ category }
				</span>
			) ) }
		</div>
	);
} );

/**
 * Separate memoized component for provider site info
 *
 * @param {Object} props              - Component props.
 * @param {string} props.providerSite - Name of the provider site.
 * @return {JSX.Element|null} The rendered provider site info or null if none.
 */
const ProviderSiteInfo = memo( ( { providerSite } ) => {
	if ( ! providerSite ) {
		return null;
	}

	return (
		<div className="od-template-provider-site-name">
			<p>
				{ __( 'Provider Site:', 'onedesign' ) }
				<span className="od-provider-site-name">{ providerSite }</span>
			</p>
		</div>
	);
} );

/**
 * Category component displays a list of template categories
 *
 * @param {Object}   props                   - Component properties.
 * @param {string}   props.activeCategory    - Currently active category.
 * @param {Function} props.setActiveCategory - Function to set the active category.
 * @param {boolean}  props.isOpen            - Indicates if the category list is open.
 * @param {Array}    props.baseTemplates     - List of base templates to filter categories.
 * @return {JSX.Element} Rendered component.
 */
const MemoizedTemplatePreview = memo(
	( { template, isSelected, onSelect, isCheckBoxRequired = true, providerSite = false } ) => {
		// Parse blocks only once when the component mounts
		const parsedBlocks = useMemo( () => parse( template?.content ), [ template?.content ] );

		// Get template title
		const templateTitle = template?.title ?? template?.name;

		return (
			<div
				className="od-template-wrapper"
				onClick={ ( e ) => {
					e.preventDefault();
					onSelect( template );
				} }
				role="button"
				tabIndex={ 0 }
				onKeyDown={ ( e ) => {
					if ( e.code === 'Enter' || e.code === 'Space' ) {
						e.preventDefault();
						onSelect( template );
					}
				} }
			>
				<div className="od-template-title-wrapper">
					{ isCheckBoxRequired &&
					<CheckboxControl
						checked={ isSelected }
						onChange={ () => onSelect( template ) }
						onClick={ ( e ) => {
							e.stopPropagation();
						} }
					/> }
					<span className="od-template-title">{ templateTitle }</span>
				</div>

				{ /* The preview that shouldn't re-render */ }
				<TemplatePreviewContent parsedBlocks={ parsedBlocks } />

				{ /* Other info that can re-render if needed */ }
				<ProviderSiteInfo providerSite={ providerSite } />
				<TemplateCategories categories={ template?.category_labels } />
			</div>
		);
	},
	( prevProps, nextProps ) => {
		// Only re-render if these specific properties change
		return (
			prevProps.template.name === nextProps.template.name &&
            prevProps.template.content === nextProps.template.content &&
            prevProps.isSelected === nextProps.isSelected &&
            prevProps.isCheckBoxRequired === nextProps.isCheckBoxRequired &&
            prevProps.providerSite === nextProps.providerSite
		);
	},
);

export default MemoizedTemplatePreview;
