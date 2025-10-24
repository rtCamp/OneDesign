/**
 * WordPress dependencies
 */
import { useState, useEffect, createRoot, useCallback, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardHeader, CardBody, Notice, Button, SelectControl } from '@wordpress/components';

/**
 * Global variable from PHP
 */
import { MULTISITES, API_NAMESPACE, NONCE } from '../../js/constants';

/**
 * SiteTypeSelector component for selecting site type.
 *
 * @param {Object}   props                  - Component properties.
 * @param {string}   props.value            - Current selected value.
 * @param {Function} props.setGoverningSite - Function to set governing site.
 *
 * @return {JSX.Element} Rendered component.
 */
const SiteTypeSelector = ( { value, setGoverningSite } ) => (
	<SelectControl
		label={ __( 'Select Governing Site', 'onedesign' ) }
		value={ value }
		help={ __( 'Choose governing site from current multisite network. Other sites will be set as brand sites. This setting cannot be changed later and affects available features and configurations.', 'onedesign' ) }
		onChange={ ( v ) => {
			setGoverningSite( v );
		} }
		options={ [
			{ label: __( 'Select…', 'onedesign' ), value: '' },
			...MULTISITES.map( ( site ) => ( { label: site.name, value: site.id } ) ),
		] }
	/>
);

/**
 * Site type selection component for OneDesign Multisite setup.
 *
 * @return {JSX.Element} Rendered component.
 */
const OneDesignMultisiteGoverningSiteSelection = () => {
	const [ governingSite, setGoverningSite ] = useState( '' );
	const currentGoverningSiteID = useRef( '' );
	const [ notice, setNotice ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );

	const fetchCurrentGoverningSite = useCallback( async () => {
		try {
			const response = await fetch(
				`${ API_NAMESPACE }/multisite/governing-site`,
				{
					headers: {
						'Content-Type': 'application/json',
						'X-WP-NONCE': NONCE,
					},
				},
			);

			if ( ! response.ok ) {
				setNotice( {
					type: 'error',
					message: __( 'Error fetching current governing site.', 'onedesign' ),
				} );
				return;
			}

			const data = await response.json();
			if ( data?.governing_site ) {
				setGoverningSite( data.governing_site );
				currentGoverningSiteID.current = data.governing_site;
			}
			return;
		} catch {
			setNotice( {
				type: 'error',
				message: __( 'Error fetching current governing site.', 'onedesign' ),
			} );
		}
	}, [] );

	useEffect( () => {
		fetchCurrentGoverningSite();
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	const handleGoverningSiteChange = useCallback( async ( value ) => {
		setGoverningSite( value );
		currentGoverningSiteID.current = value;
		setIsSaving( true );

		try {
			const response = await fetch( `${ API_NAMESPACE }/multisite/governing-site`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-NONCE': NONCE,
				},
				body: JSON.stringify( { governing_site_id: value } ),
			} );

			if ( ! response.ok ) {
				setNotice( {
					type: 'error',
					message: __( 'Error setting governing site.', 'onedesign' ),
				} );
				setIsSaving( false );
				return;
			}

			setNotice( {
				type: 'success',
				message: __( 'Governing site updated successfully.', 'onedesign' ),
			} );

			setTimeout( () => {
				setIsSaving( false );
				window.location.reload();
			}, 1000 );
			return;
		} catch {
			setNotice( {
				type: 'error',
				message: __( 'Error setting governing site.', 'onedesign' ),
			} );
		} finally {
			setIsSaving( false );
		}
	}, [] );

	return (
		<>
			<Card>
				<>
					{ notice?.message?.length > 0 &&
					<Notice
						status={ notice?.type ?? 'success' }
						isDismissible={ true }
						onRemove={ () => setNotice( null ) }
					>
						{ notice?.message }
					</Notice>
					}
				</>
				<CardHeader>
					<h2>{ __( 'OneDesign', 'onedesign' ) }</h2>
				</CardHeader>
				<CardBody>
					<SiteTypeSelector value={ governingSite } setGoverningSite={ setGoverningSite } />
					<Button
						variant="primary"
						onClick={ () => handleGoverningSiteChange( governingSite ) }
						disabled={ isSaving || governingSite.trim().length === 0 || governingSite === currentGoverningSiteID.current }
						style={ { marginTop: '1.5rem' } }
						isBusy={ isSaving }
					>
						{ __( 'Select Governing Site', 'onedesign' ) }
					</Button>
				</CardBody>
			</Card>
		</>
	);
};

// Render to Gutenberg admin page with ID: onedesign-multisite-selection-modal
const target = document.getElementById( 'onedesign-multisite-selection-modal' );
if ( target ) {
	const root = createRoot( target );
	root.render( <OneDesignMultisiteGoverningSiteSelection /> );
}
