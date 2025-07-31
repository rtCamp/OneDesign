import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

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
const Category = ( {
	activeCategory,
	setActiveCategory,
	isOpen,
	basePatterns,
} ) => {
	const [ categories, setCategories ] = useState( [] );
	const [ categoryError, setCategoryError ] = useState( '' );

	const fetchPatternCategories = useCallback( async () => {
		try {
			if ( ! basePatterns || basePatterns.length === 0 ) {
				setCategoryError( __( 'No patterns available', 'onedesign' ) );
				setCategories( [] );
				return;
			}

			const baseSiteFetch = await apiFetch( {
				path: `/onedesign/v1/pattern-categories`,
			} );
			const baseSitePatternCategories = baseSiteFetch;

			const patternCategoriesSet = new Set(
				basePatterns.flatMap( ( pattern ) =>
					Array.isArray( pattern.categories ) ? pattern.categories : [],
				),
			);

			// Filter categories that are actually used in `basePatterns`.
			const categoriesWithPatterns = baseSitePatternCategories.categories.filter( ( category ) =>
				patternCategoriesSet.has( category.name ),
			);

			setCategories( categoriesWithPatterns );
		} catch ( error ) {
			setCategoryError( __( 'Error fetching pattern categories', 'onedesign' ) );
		}
	}, [ basePatterns ] );

	useEffect( () => {
		if ( isOpen ) {
			fetchPatternCategories();
		}
	}, [ isOpen, fetchPatternCategories ] );

	return (
		<div className="library-sidebar">
			<div className="category-list">
				<div className={ `category-item column-heading` }>
					{ __( 'Pattern Categories', 'onedesign' ) }
				</div>
				<Button
					className={ `category-item ${ activeCategory === 'All' ? 'active' : '' }` }
					onClick={ () => setActiveCategory( 'All' ) }
				>
					{ __( 'All', 'onedesign' ) }
				</Button>
				{ categories && categories.length > 0 ? (
					categories.map( ( category ) => (
						<Button
							key={ category.name }
							className={ `category-item ${ activeCategory === category.name ? 'active' : '' }` }
							onClick={ () => setActiveCategory( category.name ) }
						>
							{ category.label }
						</Button>
					) )
				) : (
					<div className="category-item">{ categoryError }</div>
				) }
			</div>
		</div>
	);
};

export default Category;
