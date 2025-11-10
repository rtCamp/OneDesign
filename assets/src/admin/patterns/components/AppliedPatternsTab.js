/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, Modal, Notice } from '@wordpress/components';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import MemoizedPatternPreview from './MemoizedPatternPreview';

/**
 * AppliedPatternsTab component.
 *
 * @param {Object}   props                        - Component props.
 * @param {Array}    props.appliedPatterns        - Array of applied patterns to display.
 * @param {number}   props.currentPage            - Current page number for pagination.
 * @param {number}   props.PER_PAGE               - Number of patterns to display per page.
 * @param {Array}    props.selectedPatterns       - Array of selected pattern names.
 * @param {Function} props.handlePatternSelection - Function to handle pattern selection.
 * @param {Function} props.setCurrentPage         - Function to set the current page number.
 * @param {Object}   props.siteInfo               - Current site information.
 * @param {Function} props.applySelectedPatterns  - Function to apply/remove selected patterns.
 * @param {Function} props.setSelectedPatterns    - Function to set selected patterns.
 * @param {Object}   props.notice                 - Notice object containing type and message.
 * @param {Function} props.setNotice              - Function to set the notice state.
 *
 * @return {JSX.Element} The rendered component.
 */
const AppliedPatternsTab = ( {
	appliedPatterns,
	currentPage,
	PER_PAGE,
	selectedPatterns,
	handlePatternSelection,
	setCurrentPage,
	siteInfo,
	applySelectedPatterns,
	setSelectedPatterns,
	notice,
	setNotice,
} ) => {
	const [ isProcessing, setIsProcessing ] = useState( false );
	const [ isRemoveModalOpen, setIsRemoveModalOpen ] = useState( false );

	// Get unique patterns
	const uniquePatterns = new Map();
	appliedPatterns?.forEach( ( pattern ) => {
		uniquePatterns.set( pattern.name, pattern );
	} );
	const filteredPatterns = Array.from( uniquePatterns.values() );

	const handleRemovePatterns = useCallback( async () => {
		setIsProcessing( true );
		try {
			const result = await applySelectedPatterns(
				selectedPatterns,
				siteInfo?.value,
			);

			if ( result ) {
				const count = selectedPatterns.length;

				setNotice( {
					type: 'success',
					message: sprintf(
						/* translators: %d: Number of patterns removed. */
						_n(
							'%d pattern removed successfully.',
							'%d patterns removed successfully.',
							count,
							'onedesign',
						),
						count,
					),
				} );
				setSelectedPatterns( [] );
			} else {
				setNotice( {
					type: 'error',
					message: __( 'Failed to remove patterns.', 'onedesign' ),
				} );
			}
		} catch ( error ) {
			setNotice( {
				type: 'error',
				message: error.message || __( 'An error occurred while removing patterns.', 'onedesign' ),
			} );
		} finally {
			setIsProcessing( false );
		}
	}, [ selectedPatterns, siteInfo, applySelectedPatterns, setSelectedPatterns, setNotice ] );

	const renderPagination = () => {
		return (
			<div className="onedesign-pagination">
				<div className="onedesign-selected-patterns-info">
					{ selectedPatterns.length > 0 && (
						<div className="onedesign-selected-patterns-count-info">
							<span className="onedesign-selected-patterns-count">
								{ selectedPatterns.length }
							</span>
							<span className="onedesign-selected-patterns-text">
								{ selectedPatterns.length === 1
									? __( 'Pattern selected', 'onedesign' )
									: __( 'Patterns selected', 'onedesign' ) }
							</span>
						</div>
					) }
				</div>
				<div style={ { display: 'flex', gap: '12px', flexDirection: 'row' } }>
					<Button
						variant="secondary"
						disabled={ ( currentPage * PER_PAGE ) >= filteredPatterns.length }
						onClick={ () => setCurrentPage( ( prevPage ) => prevPage + 1 ) }
					>
						{ __( 'Show More', 'onedesign' ) } { ( Math.min( currentPage * PER_PAGE, filteredPatterns.length ) ) }/{ filteredPatterns.length }
					</Button>
					<Button
						variant="primary"
						isDestructive
						disabled={ selectedPatterns.length === 0 }
						onClick={ () => {
							setIsRemoveModalOpen( true );
						} }
					>
						{ __( 'Remove Pattern', 'onedesign' ) }
					</Button>
				</div>
			</div>
		);
	};

	const renderPatterns = () => {
		if ( filteredPatterns.length === 0 ) {
			return (
				<div className="onedesign-no-patterns">
					<p>{ __( 'No patterns found.', 'onedesign' ) }</p>
				</div>
			);
		}

		return (
			<div className="onedesign-patterns-grid">
				{ filteredPatterns.slice( 0, ( currentPage * PER_PAGE ) ).map( ( pattern ) => {
					return (
						<MemoizedPatternPreview
							key={ pattern?.name }
							pattern={ pattern }
							isCheckBoxRequired={ true }
							providerSite={ pattern?.providerSite }
							onSelect={ () => handlePatternSelection( pattern?.name ) }
							isSelected={ selectedPatterns.includes( pattern?.name ) }
						/>
					);
				} ) }
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
			{ renderPatterns() }
			{ renderPagination() }
			{ isRemoveModalOpen && (
				<Modal
					title={ __( 'Remove Pattern', 'onedesign' ) }
					onRequestClose={ () => {
						setIsRemoveModalOpen( false );
					} }
					size="medium"
				>
					<p>
						{ __( 'Are you sure you want to remove selected patterns?', 'onedesign' ) }
						<br />
						{ __( 'Once you remove a pattern you won\'t be able to use it on brand sites, so please check and confirm you don\'t require it.', 'onedesign' ) }
					</p>

					<div
						style={ {
							display: 'flex',
							flexDirection: 'row',
							gap: '12px',
							justifyContent: 'flex-end',
							alignItems: 'center',
						} }
					>
						<Button
							variant="secondary"
							onClick={ () => {
								setIsRemoveModalOpen( false );
							} }
							label={ __( 'Cancel', 'onedesign' ) }
						>
							{ __( 'Cancel', 'onedesign' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ () => {
								handleRemovePatterns();
								setIsRemoveModalOpen( false );
							} }
							isDestructive
							isBusy={ isProcessing }
							disabled={ isProcessing }
							label={ __( 'Remove Patterns', 'onedesign' ) }
						>
							{ __( 'Remove Patterns', 'onedesign' ) }
						</Button>
					</div>

				</Modal>
			) }
		</>
	);
};

export default AppliedPatternsTab;
