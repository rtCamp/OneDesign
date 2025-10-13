import { useEffect, useState, useCallback } from '@wordpress/element';
import {
	TextareaControl,
	Button,
	Card,
	Notice,
	Spinner,
	CardHeader,
	CardBody,
	TextControl,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const API_NAMESPACE = OneDesignSettings.restUrl + '/onedesign/v1';
const NONCE = OneDesignSettings.restNonce;
const API_KEY = OneDesignSettings.apiKey;

const SiteSettings = () => {
	const [ apiKey, setApiKey ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ notice, setNotice ] = useState( null );
	const [ governingSite, setGoverningSite ] = useState( '' );
	const [ showDisconectionModal, setShowDisconectionModal ] = useState( false );

	const fetchApiKey = useCallback( async () => {
		try {
			setIsLoading( true );
			const response = await fetch( API_NAMESPACE + '/secret-key', {
				method: 'GET',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': NONCE,
					'X-OneDesign-Token': API_KEY,
				},
			} );
			if ( ! response.ok ) {
				throw new Error( 'Network response was not ok' );
			}
			const data = await response.json();
			setApiKey( data?.secret_key || '' );
		} catch ( error ) {
			setNotice( {
				type: 'error',
				message: __( 'Failed to fetch api key. Please try again later.', 'onedesign' ),
			} );
		} finally {
			setIsLoading( false );
		}
	}, [] );

	// regenerate api key using REST endpoint.
	const regenerateApiKey = useCallback( async () => {
		try {
			const response = await fetch( API_NAMESPACE + '/secret-key', {
				method: 'POST',
				headers: {
					'X-WP-Nonce': NONCE,
					'X-OneDesign-Token': API_KEY,
				},
			} );
			if ( ! response.ok ) {
				throw new Error( 'Network response was not ok' );
			}
			const data = await response.json();
			if ( data?.secret_key ) {
				setApiKey( data.secret_key );
				setNotice( {
					type: 'warning',
					message: __( 'API key regenerated successfully. Please update your old key with this newly generated key to make sure plugin works properly.', 'onedesign' ),
				} );
			} else {
				setNotice( {
					type: 'error',
					message: __( 'Failed to regenerate api key. Please try again later.', 'onedesign' ),
				} );
			}
		} catch ( error ) {
			setNotice( {
				type: 'error',
				message: __( 'Error regenerating api key. Please try again later.', 'onedesign' ),
			} );
		}
	}, [] );

	const fetchCurrentGoverningSite = useCallback( async () => {
		setIsLoading( true );
		try {
			const response = await fetch(
				`${ API_NAMESPACE }/governing-site?${ new Date().getTime() }`,
				{
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': NONCE,
						'X-OneDesign-Token': apiKey,
					},
				},
			);
			if ( ! response.ok ) {
				throw new Error( 'Network response was not ok' );
			}
			const data = await response.json();
			setGoverningSite( data?.governing_site_url || '' );
		} catch ( error ) {
			setNotice( {
				type: 'error',
				message: __( 'Failed to fetch governing site. Please try again later.', 'onedesign' ),
			},
			);
		} finally {
			setIsLoading( false );
		}
	}, [ apiKey ] );

	const deleteGoverningSiteConnection = useCallback( async () => {
		try {
			const response = await fetch(
				`${ API_NAMESPACE }/governing-site`,
				{
					method: 'DELETE',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': NONCE,
						'X-OneDesign-Token': apiKey,
					},
				},
			);
			if ( ! response.ok ) {
				throw new Error( 'Network response was not ok' );
			}
			setGoverningSite( '' );
			setNotice( {
				type: 'success',
				message: __( 'Governing site disconnected successfully.', 'onedesign' ),
			} );
		} catch ( error ) {
			setNotice( {
				type: 'error',
				message: __( 'Failed to disconnect governing site. Please try again later.', 'onedesign' ),
			} );
		} finally {
			setShowDisconectionModal( false );
		}
	}, [ apiKey ] );

	const handleDisconnectGoverningSite = useCallback( async () => {
		setShowDisconectionModal( true );
	}, [] );

	useEffect( () => {
		fetchApiKey();
		fetchCurrentGoverningSite();
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	if ( isLoading ) {
		return <Spinner />;
	}

	return (
		<>

			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible={ true }
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<Card className="brand-site-settings"
				style={ { marginTop: '30px' } }
			>
				<CardHeader>
					<h2>{ __( 'API Key', 'onedesign' ) }</h2>
					<div>
						{ /* Copy to clipboard button */ }
						<Button
							variant="primary"
							onClick={ () => {
								navigator?.clipboard?.writeText( apiKey )
									.then( () => {
										setNotice( {
											type: 'success',
											message: __( 'API key copied to clipboard.', 'onedesign' ),
										} );
									} )
									.catch( ( error ) => {
										setNotice( {
											type: 'error',
											message: __( 'Failed to copy api key. Please try again.', 'onedesign' ) + ' ' + error,
										} );
									} );
							} }
						>
							{ __( 'Copy API Key', 'onedesign' ) }
						</Button>
						{ /* Regenerate key button */ }
						<Button
							variant="secondary"
							onClick={ regenerateApiKey }
							style={ { marginLeft: '10px' } }
						>
							{ __( 'Regenerate API Key', 'onedesign' ) }
						</Button>
					</div>
				</CardHeader>
				<CardBody>
					<div>
						<TextareaControl
							value={ apiKey }
							disabled={ true }
							help={ __( 'This key is used for secure communication with the Governing site.', 'onedesign' ) }
						/>
					</div>
				</CardBody>

			</Card>
			<Card className="governing-site-connection"
				style={ { marginTop: '30px' } }
			>
				<CardHeader>
					<h2>{ __( 'Governing Site Connection', 'onedesign' ) }</h2>
					<Button
						variant="secondary"
						isDestructive
						onClick={ handleDisconnectGoverningSite }
						disabled={ governingSite?.trim().length === 0 || isLoading }
					>
						{ __( 'Disconnect Governing Site', 'onedesign' ) }
					</Button>
				</CardHeader>
				<CardBody>
					<TextControl
						label={ __( 'Governing Site URL', 'onedesign' ) }
						value={ governingSite }
						disabled={ true }
						help={ __( 'This is the URL of the Governing site this Brand site is connected to.', 'onedesign' ) }
					/>
				</CardBody>
			</Card>

			{ showDisconectionModal && (
				<Modal
					title={ __( 'Disconnect Governing Site', 'onedesign' ) }
					onRequestClose={ () => setShowDisconectionModal( false ) }
					shouldCloseOnClickOutside={ true }
				>
					<p>{ __( 'Are you sure you want to disconnect from the governing site? This action cannot be undone.', 'onedesign' ) }</p>
					<div style={ { display: 'flex', justifyContent: 'flex-end', marginTop: '20px', gap: '16px' } }>
						<Button
							variant="secondary"
							onClick={ () => setShowDisconectionModal( false ) }
						>
							{ __( 'Cancel', 'onedesign' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ deleteGoverningSiteConnection }
						>
							{ __( 'Disconnect', 'onedesign' ) }
						</Button>
					</div>
				</Modal>
			) }
		</>
	);
};

export default SiteSettings;
