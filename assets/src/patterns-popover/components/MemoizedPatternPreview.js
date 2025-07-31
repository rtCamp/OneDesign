/**
 * WordPress dependencies
 */
import { memo, useMemo } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { parse } from '@wordpress/blocks';
import { BlockPreview } from '@wordpress/block-editor';

// Separate memoized component for the preview
const PatternPreviewContent = memo( ( { parsedBlocks } ) => {
	return (
		<div className="od-pattern-preview">
			<BlockPreview blocks={parsedBlocks} viewportWidth={1200} />
		</div>
	);
} );

// Separate memoized component for pattern categories
const PatternCategories = memo( ( { categories } ) => {
	if ( ! categories || typeof categories !== 'object' || Array.isArray( categories ) ) {
		return null;
	}

	const categoryValues = Object.values( categories ).filter( Boolean );

	if ( ! categoryValues.length ) {
		return null;
	}

	return (
		<div className="od-pattern-categories">
			<p>{__("Categories:", "onedesign")}</p>
			{categoryValues.map((category, i) => (
				<span key={`${category}-${i}`} className="od-pattern-category">
					{category}
				</span>
			))}
		</div>
	);
} );

// Separate memoized component for provider site
const ProviderSiteInfo = memo( ( { providerSite } ) => {
	if ( ! providerSite ) {
		return null;
	}

	return (
		<div className="od-pattern-provider-site-name">
			<p>
				{__("Provider Site: ", "onedesign")}
				<span className="od-provider-site-name">{providerSite}</span>
			</p>
		</div>
	);
} );

/**
 * Category component displays a list of pattern categories
 *
 * @param {Object}   props                   - Component properties.
 * @param {string}   props.activeCategory    - Currently active category.
 * @param {Function} props.setActiveCategory - Function to set the active category.
 * @param {boolean}  props.isOpen            - Indicates if the category list is open.
 * @param {Array}    props.basePatterns      - List of base patterns to filter categories.
 * @return {JSX.Element} Rendered component.
 */
const MemoizedPatternPreview = memo(
	( { pattern, isSelected, onSelect, isCheckBoxRequired = true, providerSite = false } ) => {
		// Parse blocks only once when the component mounts
		const parsedBlocks = useMemo( () => parse( pattern?.content ), [ pattern?.content ] );

		// Get pattern title
		const patternTitle = pattern?.title ?? pattern?.name;

		return (
			<div
				className="od-pattern-wrapper"
				onClick={() => onSelect(pattern)}
				role="button"
				tabIndex={0}
				onKeyDown={(e) => {
					if (e.key === "Enter" || e.key === " ") {
						onSelect(pattern);
					}
				}}
			>
				<div className="od-pattern-title-wrapper">
					{isCheckBoxRequired && <CheckboxControl checked={isSelected} />}
					<span className="od-pattern-title">{patternTitle}</span>
				</div>

				{/* The preview that shouldn't re-render */}
				<PatternPreviewContent parsedBlocks={parsedBlocks} />

				{/* Other info that can re-render if needed */}
				<ProviderSiteInfo providerSite={providerSite} />
				<PatternCategories categories={pattern?.category_labels} />
			</div>
		);
	},
	( prevProps, nextProps ) => {
		// Only re-render if these specific properties change
		return (
			prevProps.pattern.name === nextProps.pattern.name &&
			prevProps.pattern.content === nextProps.pattern.content &&
			prevProps.isSelected === nextProps.isSelected &&
			prevProps.isCheckBoxRequired === nextProps.isCheckBoxRequired &&
			prevProps.providerSite === nextProps.providerSite
		);
	},
);

export default MemoizedPatternPreview;
