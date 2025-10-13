/**
 * WordPress dependencies
 */
import { useState, useEffect, createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Snackbar } from '@wordpress/components';

/**
 * Internal dependencies
 */
import SiteTable from '../../components/SiteTable';
import SiteModal from '../../components/SiteModal';
import SiteSettings from '../../components/SiteSettings';

/**
 * Global variable from PHP
 */
const API_NAMESPACE = OneDesignSettings.restUrl + '/onedesign/v1';
const NONCE = OneDesignSettings.restNonce;

/**
 * Settings page component for OneDesign plugin.
 *
 * @return {JSX.Element} Rendered component.
 */
const OneDesignSettingsPage = () => {
	const [ siteType, setSiteType ] = useState( '' );
	const [ showModal, setShowModal ] = useState( false );
	const [ editingIndex, setEditingIndex ] = useState( null );
	const [ sites, setSites ] = useState( [] );
	const [ formData, setFormData ] = useState( { name: '', url: '', api_key: '' } );
	const [ notice, setNotice ] = useState( {
		type: 'success',
		message: '',
	} );

	useEffect( () => {
		const token = ( NONCE );

		const fetchData = async () => {
			try {
				const [ siteTypeRes, sitesRes ] = await Promise.all( [
					fetch( `${ API_NAMESPACE }/site-type`, {
						headers: { 'Content-Type': 'application/json', 'X-WP-NONCE': token },
					} ),
					fetch( `${ API_NAMESPACE }/shared-sites`, {
						headers: { 'Content-Type': 'application/json', 'X-WP-NONCE': token },
					} ),
				] );

				const siteTypeData = await siteTypeRes.json();
				const sitesData = await sitesRes.json();

				if ( siteTypeData?.site_type ) {
					setSiteType( siteTypeData?.site_type );
				}
				if ( Array.isArray( sitesData?.shared_sites ) ) {
					setSites( sitesData?.shared_sites );
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

	const handleFormSubmit = async () => {
		const updated = editingIndex !== null
			? sites.map( ( item, i ) => ( i === editingIndex ? formData : item ) )
			: [ ...sites, formData ];

		const token = ( NONCE );
		try {
			const response = await fetch( `${ API_NAMESPACE }/shared-sites`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-NONCE': token,
				},
				body: JSON.stringify( { sites_data: updated } ),
			} );
			if ( ! response.ok ) {
				console.error( 'Error saving Brand site:', response.statusText ); // eslint-disable-line no-console
				return response;
			}

			if ( sites.length === 0 ) {
				window.location.reload();
			}

			setSites( updated );
			setNotice( {
				type: 'success',
				message: __( 'Brand Site saved successfully.', 'onedesign' ),
			} );
		} catch {
			setNotice( {
				type: 'error',
				message: __( 'Error saving Brand site. Please try again later.', 'onedesign' ),
			} );
		}

		setFormData( { name: '', url: '', api_key: '' } );
		setShowModal( false );
		setEditingIndex( null );
	};

	const handleDelete = async ( index ) => {
		const updated = sites.filter( ( _, i ) => i !== index );
		const token = ( NONCE );

		try {
			const response = await fetch( `${ API_NAMESPACE }/shared-sites`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-NONCE': token,
				},
				body: JSON.stringify( { sites_data: updated } ),
			} );
			if ( ! response.ok ) {
				setNotice( {
					type: 'error',
					message: __( 'Failed to delete Brand site. Please try again.', 'onedesign' ),
				} );
				return;
			}
			setNotice( {
				type: 'success',
				message: __( 'Brand Site deleted successfully.', 'onedesign' ),
			} );
			setSites( updated );
			if ( updated.length === 0 ) {
				window.location.reload();
			} else {
				document.body.classList.remove( 'onedesign-missing-brand-sites' );
			}
		} catch {
			setNotice( {
				type: 'error',
				message: __( 'Error deleting Brand site. Please try again later.', 'onedesign' ),
			} );
		}
	};

	return (
		<>
			<>
				{ notice?.message?.length > 0 &&
					<Snackbar
						status={ notice?.type ?? 'success' }
						isDismissible={ true }
						onRemove={ () => setNotice( null ) }
						className={ notice?.type === 'error' ? 'onedesign-error-notice' : 'onedesign-success-notice' }
					>
						{ notice?.message }
					</Snackbar>
				}
			</>

			{
				siteType === 'brand-site' && (
					<SiteSettings />
				)
			}

			{ siteType === 'governing-site' && (
				<SiteTable sites={ sites } onEdit={ setEditingIndex } onDelete={ handleDelete } setFormData={ setFormData } setShowModal={ setShowModal } />
			) }

			{ showModal && (
				<SiteModal
					formData={ formData }
					setFormData={ setFormData }
					onSubmit={ handleFormSubmit }
					onClose={ () => {
						setShowModal( false );
						setEditingIndex( null );
						setFormData( { name: '', url: '', api_key: '' } );
					} }
					editing={ editingIndex !== null }
					originalData={ sites[ editingIndex ] }
				/>
			) }
		</>
	);
};

// Render to Gutenberg admin page with ID: onedesign-settings-page
const target = document.getElementById( 'onedesign-settings-page' );
if ( target ) {
	const root = createRoot( target );
	root.render( <OneDesignSettingsPage /> );
}
