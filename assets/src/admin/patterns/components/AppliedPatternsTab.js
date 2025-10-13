/**
 * WordPress dependencies
 */
import { memo, useCallback, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Notice, Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import MemoizedPatternPreview from './MemoizedPatternPreview';

/**
 * AppliedPatternsTab component displays a list of applied patterns with options to remove them.
 *
 * @param {Object}   props                        - Component properties.
 * @param {boolean}  props.isLoadingApplied       - Indicates if applied patterns are loading.
 * @param {Array}    props.appliedPatterns        - List of applied patterns.
 * @param {number}   props.visibleAppliedCount    - Number of applied patterns currently visible.
 * @param {Function} props.applySelectedPatterns  - Function to apply selected patterns.
 * @param {Function} props.setVisibleAppliedCount - Function to set the number of visible applied patterns.
 * @param {Object}   props.siteInfo               - Information about the site (used for applying patterns).
 * @return {JSX.Element} Rendered component.
 */
const AppliedPatternsTab = memo( ( {
	isLoadingApplied,
	appliedPatterns,
	visibleAppliedCount,
	applySelectedPatterns,
	setVisibleAppliedCount,
	siteInfo,
} ) => {
	const uniquePatterns = new Map();

	// Loop through appliedPatterns and only keep the last occurrence of each pattern name
	appliedPatterns?.forEach( ( pattern ) => {
		uniquePatterns.set( pattern.name, pattern );
	} );

	// Convert the Map values back to an array if needed
	const uniquePatternsArray = Array.from( uniquePatterns.values() );

	const [ selectedPatternsToRemove, setSelectedPatternsToRemove ] = useState( [] );
	const [ isRemovalModalOpen, setIsRemovalModalOpen ] = useState( false );
	const [ isRemoving, setIsRemoving ] = useState( false );
	const [ feedbackMessage, setFeedbackMessage ] = useState( null );

	useEffect( () => {
		// Reset selected patterns to remove when applied patterns change
		setSelectedPatternsToRemove( [] );
	}, [ siteInfo ] );

	const handlePatternRemoval = ( patternName ) => {
		setSelectedPatternsToRemove( ( prevSelected ) => {
			const newSelected = prevSelected.includes( patternName )
				? prevSelected.filter( ( name ) => name !== patternName )
				: [ ...prevSelected, patternName ];

			return newSelected;
		} );
	};

	const loadMoreUniquePatterns = useCallback( () => {
		setVisibleAppliedCount( ( prevCount ) => prevCount + visibleAppliedCount );
	}, [ visibleAppliedCount, setVisibleAppliedCount ] );

	const removeSelectedPatterns = useCallback( () => {
		// Open the confirmation modal when the button is clicked
		setIsRemovalModalOpen( true );
	}, [] );

	const confirmAndRemovePatterns = useCallback( () => {
		// Close the modal and set removing state
		setIsRemovalModalOpen( false );
		setIsRemoving( true );

		// Show feedback that patterns are being removed
		setFeedbackMessage( {
			type: 'info',
			message: __( 'Removing patterns…', 'onedesign' ),
		} );

		// Apply selected patterns and handle the result
		const result = applySelectedPatterns(
			selectedPatternsToRemove,
			siteInfo?.value,
		);

		// If it's a Promise, handle success and error cases
		if ( result && typeof result.then === 'function' ) {
			result
				.then( () => {
					// On success, show success message
					setFeedbackMessage( {
						type: 'success',
						message: __( 'Patterns removed successfully!', 'onedesign' ),
					} );

					// Clear the success message after 2 seconds
					setTimeout( () => {
						setFeedbackMessage( null );
						setSelectedPatternsToRemove( [] );
					}, 2000 );

					setIsRemoving( false );
				} )
				.catch( ( error ) => {
					// On error, show error message
					setFeedbackMessage( {
						type: 'error',
						message:
							error.message ||
							__( 'Failed to remove patterns. Please try again.', 'onedesign' ),
					} );

					setIsRemoving( false );
				} );
		} else {
			setIsRemoving( false );
		}
	}, [ selectedPatternsToRemove, applySelectedPatterns, siteInfo ] );

	if ( isLoadingApplied ) {
		return (
			<div className="od-pattern-loading od-applied-patterns">
				<p>{ __( 'Loading applied patterns…', 'onedesign' ) }</p>
			</div>
		);
	}

	return (
		<div>
			{ feedbackMessage && (
				<Notice
					status={ feedbackMessage.type }
					isDismissible={ feedbackMessage.type !== 'info' }
					onRemove={ () => {
						setFeedbackMessage( null );
					} }
					className="od-pattern-feedback-message"
				>
					{ feedbackMessage.message }
				</Notice>
			) }

			<div className="od-pattern-modal od-applied-patterns">
				{ appliedPatterns === null ||
				appliedPatterns?.length === 0 ||
				uniquePatternsArray?.length === 0
					? ( <div className="od-no-patterns">
						<p>{ __( 'No patterns found', 'onedesign' ) }</p>
						<p className="od-no-patterns-subtitle">
							{ __(
								'Try adjusting your search criteria or add patterns to this site.',
								'onedesign',
							) }
						</p>
					</div>
					) : (
						uniquePatternsArray
							?.slice( 0, visibleAppliedCount )
							.map( ( pattern ) => (
								<MemoizedPatternPreview
									key={ pattern?.name }
									pattern={ pattern }
									isCheckBoxRequired={ true }
									providerSite={ pattern?.providerSite }
									isSelected={ selectedPatternsToRemove.includes( pattern?.name ) }
									onSelect={ () => handlePatternRemoval( pattern?.name ) }
								/>
							) )
					) }
			</div>

			<div className="od-pattern-footer">
				<div className="od-selection-info">
					{ selectedPatternsToRemove.length > 0 && (
						<div className="od-selected-count">
							<span className="od-count-badge">
								{ selectedPatternsToRemove.length }
							</span>
							<span className="od-count-text">
								{ selectedPatternsToRemove.length === 1
									? __( 'pattern selected', 'onedesign' )
									: __( 'patterns selected', 'onedesign' ) }
							</span>
						</div>
					) }
				</div>

				<div>
					{ uniquePatternsArray &&
						uniquePatternsArray.length > visibleAppliedCount && (
						<Button
							variant="secondary"
							onClick={ loadMoreUniquePatterns }
							style={ { marginRight: '10px' } }
						>
							{ __( 'Show More Patterns', 'onedesign' ) } ({ visibleAppliedCount }/
							{ uniquePatternsArray.length })
						</Button>
					) }

					<Button
						onClick={ removeSelectedPatterns }
						variant="secondary"
						isDestructive
						disabled={ selectedPatternsToRemove.length === 0 || isRemoving }
					>
						{ isRemoving
							? __( 'Removing…', 'onedesign' )
							: __( 'Remove Selected Patterns', 'onedesign' ) }
					</Button>
				</div>
			</div>

			{ isRemovalModalOpen && (
				<Modal
					title={ __( 'Confirm Pattern Removal', 'onedesign' ) }
					onRequestClose={ () => setIsRemovalModalOpen( false ) }
					className="od-pattern-removal-modal"
				>
					<div className="od-pattern-removal-modal-content">
						<p>
							{ __(
								'Are you sure you want to remove the selected patterns? This action cannot be undone.',
								'onedesign',
							) }
						</p>
						<div className="od-pattern-removal-modal-actions">
							<Button
								variant="secondary"
								onClick={ () => setIsRemovalModalOpen( false ) }
							>
								{ __( 'Cancel', 'onedesign' ) }
							</Button>
							<Button
								variant="primary"
								isDestructive
								onClick={ confirmAndRemovePatterns }
							>
								{ __( 'Yes, Remove Patterns', 'onedesign' ) }
							</Button>
						</div>
					</div>
				</Modal>
			) }
		</div>
	);
} );

export default AppliedPatternsTab;
