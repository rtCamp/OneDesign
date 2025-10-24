/**
 * WordPress dependencies.
 */
import {
	Button,
	Modal,
	CheckboxControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * PHP consts for JS usage.
 */
import { API_NAMESPACE, NONCE, CURRENT_SITE_ID } from '../js/constants';

/**
 * MultiSites component to manage brand sites from multisite network.
 *
 * @param {Object}   props               - Component properties.
 * @param {Function} props.setBrandSites - Function to set brand sites in parent component.
 * @param {Array}    props.brandSites    - Current list of brand sites.
 * @param {Function} props.setNotice     - Function to set notice messages.
 *
 * @return {JSX.Element} Rendered component.
 */
const MultiSites = ( { setBrandSites, brandSites, setNotice } ) => {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ selectedSites, setSelectedSites ] = useState( [] );
	const [ isApplying, setIsApplying ] = useState( false );
	const [ sites, setSites ] = useState();

	const openModal = () => setIsOpen( true );
	const closeModal = () => setIsOpen( false );

	const toggleSiteSelection = ( siteId ) => {
		setSelectedSites( ( prevSelected ) => {
			if ( prevSelected.includes( siteId ) ) {
				return prevSelected.filter( ( id ) => id !== siteId );
			}
			return [ ...prevSelected, siteId ];
		} );
	};

	const fetchSelectedBrandSites = useCallback( async () => {
		try {
			const response = await fetch( `${ API_NAMESPACE }/shared-sites`, {
				headers: {
					'Content-Type': 'application/json',
					'X-WP-NONCE': NONCE,
				},
			} );

			if ( response.ok ) {
				const data = await response.json();
				setBrandSites( data.shared_sites || [] );

				// if shared_sites length is 1 meaning
				if ( data?.shared_sites?.length > 0 && brandSites?.length === 0 ) {
					window.location.reload();
				}
			} else {
				setNotice( {
					type: 'error',
					message: __( 'Failed to fetch selected brand sites. Please try again.', 'onedesign' ),
				} );
			}
		} catch ( error ) {
			setNotice( {
				type: 'error',
				message: __( 'An error occurred while fetching selected brand sites. Please try again.', 'onedesign' ),
			} );
		}
	}, [ brandSites, setBrandSites, setNotice ] );

	const fetchBrandSites = useCallback( async () => {
		try {
			const response = await fetch( `${ API_NAMESPACE }/multisite/sites`, {
				headers: {
					'Content-Type': 'application/json',
					'X-WP-NONCE': NONCE,
				},
			} );

			if ( response.ok ) {
				const data = await response.json();
				setSites( data.sites || [] );
			} else {
				setNotice( {
					type: 'error',
					message: __( 'Failed to fetch brand sites. Please try again.', 'onedesign' ),
				} );
				return [];
			}
		} catch ( error ) {
			setNotice( {
				type: 'error',
				message: __( 'An error occurred while fetching brand sites. Please try again.', 'onedesign' ),
			} );
			return [];
		}
	}, [ setNotice ] );

	const handleMultiSiteAdd = useCallback( async ( selectedMUSites ) => {
		setIsApplying( true );
		try {
			const response = await fetch(
				`${ API_NAMESPACE }/multisite/add-sites`,
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-NONCE': NONCE,
					},
					body: JSON.stringify( { site_ids: selectedMUSites } ),
				},
			);

			if ( ! response.ok ) {
				setNotice( {
					type: 'error',
					message: __( 'Failed to add selected sites. Please try again.', 'onedesign' ),
				} );
				setIsApplying( false );
				return;
			}

			const data = await response.json();

			if ( data.success ) {
				setNotice( {
					type: 'success',
					message: __( 'Selected sites added successfully.', 'onedesign' ),
				} );
				fetchSelectedBrandSites();
				setSelectedSites( [] );
			} else {
				setNotice( {
					type: 'error',
					message: data.message || __( 'Failed to add selected sites. Please try again.', 'onedesign' ),
				} );
			}
		} catch ( error ) {
			setNotice( {
				type: 'error',
				message: __( 'An error occurred while adding selected sites. Please try again.', 'onedesign' ),
			} );
		} finally {
			setIsApplying( false );
		}
	}, [ setNotice ] ); // eslint-disable-line react-hooks/exhaustive-deps

	useEffect( () => {
		fetchBrandSites();
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	return (
		<>
			<Button
				variant="secondary"
				onClick={ openModal }
			>
				{ __( 'Add Brand Sites From Current MU', 'onedesign' ) }
			</Button>

			{ isOpen && (
				<Modal
					title={ __( 'Multi-Sites Information', 'onedesign' ) }
					onRequestClose={ closeModal }
					size="medium"
				>

					{ /* create multi select checkbox list of sites excluding current site */ }
					{ sites?.length > 0 ? (
						<div style={ { maxHeight: '400px', overflowY: 'auto', padding: '4px 4px' } }>
							{ sites?.filter( ( site ) => String( site.id ) !== CURRENT_SITE_ID &&
                                    ! brandSites?.some( ( brandSite ) => String( brandSite.id ) === String( site.id ) ) )
								.map( ( site ) => (
									<CheckboxControl
										key={ site.id }
										label={ `${ site.name } ( ${ site?.url } )` }
										checked={ selectedSites.includes( site.id ) }
										onChange={ () => toggleSiteSelection( site.id ) }
									/>
								) ) }
						</div>
					) : (
						<p>{ __( 'No other sites available in this multisite network.', 'onedesign' ) }</p>
					) }

					{ sites?.length > 0 && sites?.filter( ( site ) => String( site.id ) !== CURRENT_SITE_ID && ! brandSites?.some( ( brandSite ) => String( brandSite.id ) === String( site.id ) ) ).length === 0 && (
						<p>{ __( 'All sites in this multisite network have already been added as brand sites.', 'onedesign' ) }</p>
					) }

					<div style={ { marginTop: '20px', display: 'flex', justifyContent: 'flex-end', gap: '10px' } }>
						<Button
							variant="secondary"
							onClick={ closeModal }
						>
							{ __( 'Cancel', 'onedesign' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ () => {
								handleMultiSiteAdd( selectedSites );
								closeModal();
							} }
							disabled={ selectedSites.length === 0 }
							isBusy={ isApplying }
						>
							{ __( 'Add Selected Sites', 'onedesign' ) }
						</Button>
					</div>

				</Modal>
			) }
		</>
	);
};

export default MultiSites;
