/**
 * WordPress dependencies
 */
import { useState, useEffect, createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardHeader, CardBody, Notice, Button, SelectControl } from '@wordpress/components';

/**
 * Global variable from PHP
 */
const API_NAMESPACE = OneDesignSettings.restUrl + '/onedesign/v1';
const NONCE = OneDesignSettings.restNonce;
const API_KEY = OneDesignSettings.apiKey;

const SiteTypeSelector = ( { value, setSiteType } ) => (
	<SelectControl
		label={ __( 'Site Type', 'onedesign' ) }
		value={ value }
		help={ __( 'Choose your site\'s primary purpose. This setting cannot be changed later and affects available features and configurations.', 'onedesign' ) }
		onChange={ ( v ) => {
			setSiteType( v );
		} }
		options={ [
			{ label: __( 'Select…', 'onedesign' ), value: '' },
			{ label: __( 'Brand Site', 'onedesign' ), value: 'brand-site' },
			{ label: __( 'Governing Site', 'onedesign' ), value: 'governing-site' },
		] }
	/>
);

const OneDesignSiteTypeSelection = () => {
	const [ siteType, setSiteType ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );

	useEffect( () => {
		const token = ( NONCE );

		const fetchData = async () => {
			try {
				const [ siteTypeRes ] = await Promise.all( [
					fetch( `${ API_NAMESPACE }/site-type`, {
						headers: {
							'Content-Type': 'application/json',
							'X-WP-NONCE': token,
							'X-OneDesign-Token': API_KEY,
						},
					} ),
				] );

				const siteTypeData = await siteTypeRes.json();

				if ( siteTypeData?.site_type ) {
					setSiteType( siteTypeData.site_type );
				}
			} catch {
				setNotice( {
					type: 'error',
					message: __( 'Error fetching site type or Brand sites.', 'onedesign' ),
				} );
			}
		};

		fetchData();
	}, [] );

	const handleSiteTypeChange = async ( value ) => {
		setSiteType( value );
		const token = ( NONCE );
		setIsSaving( true );

		try {
			const response = await fetch( `${ API_NAMESPACE }/site-type`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-NONCE': token,
					'X-OneDesign-Token': API_KEY,
				},
				body: JSON.stringify( { site_type: value } ),
			} );

			if ( ! response.ok ) {
				setNotice( {
					type: 'error',
					message: __( 'Error setting site type.', 'onedesign' ),
				} );
				return;
			}

			const data = await response.json();
			if ( data?.site_type ) {
				setSiteType( data.site_type );

				// redirect user to setup page.
				window.location.href = OneDesignSettings.setupUrl;
			}
		} catch {
			setNotice( {
				type: 'error',
				message: __( 'Error setting site type.', 'onedesign' ),
			} );
		} finally {
			setIsSaving( false );
		}
	};

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
					<SiteTypeSelector value={ siteType } setSiteType={ setSiteType } />
					<Button
						variant="primary"
						onClick={ () => handleSiteTypeChange( siteType ) }
						disabled={ isSaving || siteType.trim().length === 0 }
						style={ { marginTop: '1.5rem' } }
						className={ isSaving ? 'is-busy' : '' }
					>
						{ __( 'Select Current Site Type', 'onedesign' ) }
					</Button>
				</CardBody>
			</Card>
		</>
	);
};

// Render to Gutenberg admin page with ID: onedesign-site-selection-modal
const target = document.getElementById( 'onedesign-site-selection-modal' );
if ( target ) {
	const root = createRoot( target );
	root.render( <OneDesignSiteTypeSelection /> );
}
