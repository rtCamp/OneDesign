import { useState, useMemo } from '@wordpress/element';
import {
	Modal,
	TextControl,
	TextareaControl,
	Button,
	Notice,
	BaseControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { isValidUrl } from '../js/utils';

const DeleteConfirmationModal = ( { onConfirm, onCancel } ) => (
	<Modal
		title={ __( 'Remove Site Logo', 'onedesign' ) }
		onRequestClose={ onCancel }
		isDismissible={ false }
		className="onedesign-delete-confirmation-modal"
	>
		<p>{ __( 'Are you sure you want to remove this logo? This action cannot be undone.', 'onedesign' ) }</p>
		<div style={ { display: 'flex', justifyContent: 'flex-end', marginTop: '20px', gap: '16px' } }>
			<Button
				variant="secondary"
				onClick={ onCancel }
			>
				{ __( 'Cancel', 'onedesign' ) }
			</Button>
			<Button
				variant="primary"
				isDestructive
				onClick={ onConfirm }
			>
				{ __( 'Remove', 'onedesign' ) }
			</Button>
		</div>
	</Modal>
);

const SiteModal = ( { formData, setFormData, onSubmit, onClose, editing, originalData = {} } ) => {
	const [ errors, setErrors ] = useState( {
		name: '',
		url: '',
		api_key: '',
		message: '',
	} );
	const [ showNotice, setShowNotice ] = useState( false );
	const [ isProcessing, setIsProcessing ] = useState( false );
	const [ showDeleteConfirm, setShowDeleteConfirm ] = useState( false );

	const handleSubmit = async () => {
		// Validate inputs
		let siteUrlError = '';
		if ( ! formData.url.trim() ) {
			siteUrlError = __( 'Site URL is required.', 'onedesign' );
		} else if ( ! isValidUrl( formData.url ) ) {
			siteUrlError = __( 'Enter a valid URL (must start with http or https).', 'onedesign' );
		}

		const newErrors = {
			name: ! formData.name.trim() ? __( 'Site Name is required.', 'onedesign' ) : '',
			url: siteUrlError,
			api_key: ! formData.api_key.trim() ? __( 'API Key is required.', 'onedesign' ) : '',
			message: '',
		};

		// Make sure site name is under 20 characters
		if ( formData.name.length > 20 ) {
			newErrors.name = __( 'Site Name must be under 20 characters.', 'onedesign' );
		}

		setErrors( newErrors );
		const hasErrors = Object.values( newErrors ).some( ( err ) => err );

		if ( hasErrors ) {
			setShowNotice( true );
			return;
		}

		// Start processing
		setIsProcessing( true );
		setShowNotice( false );

		try {
			// Perform health-check
			const healthCheck = await fetch(
				`${ formData.url }/wp-json/onedesign/v1/health-check`,
				{
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
						'X-OneDesign-Token': formData.api_key,
					},
				},
			);

			const healthCheckData = await healthCheck.json();
			if ( ! healthCheckData.success ) {
				setErrors( {
					...newErrors,
					message: __( 'Health check failed. Please ensure the site is accessible and the api key is correct.', 'onedesign' ),
				} );
				setShowNotice( true );
				setIsProcessing( false );
				return;
			}

			setShowNotice( false );
			const submitResponse = await onSubmit();

			if ( ! submitResponse.ok ) {
				const errorData = await submitResponse.json();
				setErrors( {
					...newErrors,
					message: errorData.message || __( 'An error occurred while saving the site. Please try again.', 'onedesign' ),
				} );
				setShowNotice( true );
			}
			if ( submitResponse?.data?.status === 400 ) {
				setErrors( {
					...newErrors,
					message: submitResponse?.message || __( 'An error occurred while saving the site. Please try again.', 'onedesign' ),
				} );
				setShowNotice( true );
			}
		} catch ( error ) {
			setErrors( {
				...newErrors,
				message: __( 'An unexpected error occurred. Please try again.', 'onedesign' ),
			} );
			setShowNotice( true );
			setIsProcessing( false );
			return;
		}

		setIsProcessing( false );
	};

	const handleLogoSelect = () => {
		// Create a media frame for single image selection
		const mediaFrame = wp.media( {
			title: __( 'Select Site Logo', 'onedesign' ),
			button: {
				text: __( 'Select Image', 'onedesign' ),
			},
			multiple: false, // Restrict to single image selection
			library: {
				type: [ 'image' ], // Only allow images
			},
		} );

		// When an image is selected, update the formData with the image data
		mediaFrame.on( 'select', () => {
			const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			setFormData( {
				...formData,
				logo: attachment.url,
				logo_id: attachment.id, // Store the attachment ID for future reference
			} );
		} );

		// If logo_id is already set, pre-select that image in the media library
		if ( formData.logo_id ) {
			mediaFrame.on( 'open', function() {
				const selection = mediaFrame.state().get( 'selection' );
				const attachment = wp.media.attachment( formData.logo_id );

				// Fetch attachment details
				attachment.fetch();

				// Add to selection
				if ( selection && attachment ) {
					selection.add( [ attachment ] );
				}
			} );
		}

		// Open the media modal
		mediaFrame.open();
	};

	const handleLogoRemove = ( e ) => {
		e.preventDefault();
		e.stopPropagation();
		setShowDeleteConfirm( true );
	};

	const confirmLogoRemove = ( e ) => {
		e.preventDefault();
		e.stopPropagation();
		setFormData( {
			...formData,
			logo: '',
			logo_id: null,
		} );
		setShowDeleteConfirm( false );
	};

	const cancelLogoRemove = ( e ) => {
		e.preventDefault();
		e.stopPropagation();
		setShowDeleteConfirm( false );
	};

	const handleMainModalClose = () => {
		if ( ! showDeleteConfirm ) {
			onClose();
		}
	};

	const hasChanges = useMemo( () => {
		if ( ! editing ) {
			return true;
		} // Always allow submission for new sites

		return (
			formData?.name !== originalData?.name ||
			formData?.url !== originalData?.url ||
			formData?.api_key !== originalData?.api_key ||
			formData?.logo !== originalData?.logo
		);
	}, [ editing, formData, originalData ] );

	// Button should be disabled if:
	// 1. Currently processing, OR
	// 2. Required fields are empty, OR
	// 3. In editing mode and no changes have been made
	const isButtonDisabled = isProcessing ||
		! formData.name ||
		! formData.url ||
		! formData.api_key ||
		( editing && ! hasChanges );

	return (
		<>
			{ ! showDeleteConfirm && (
				<Modal
					title={ editing ? __( 'Edit Brand Site', 'onedesign' ) : __( 'Add Brand Site', 'onedesign' ) }
					onRequestClose={ handleMainModalClose }
					size="medium"
				>
					{ showNotice && (
						<Notice
							status="error"
							isDismissible={ true }
							onRemove={ () => setShowNotice( false ) }
						>
							{ errors.message || errors.name || errors.url || errors.api_key }
						</Notice>
					) }

					<TextControl
						label={ __( 'Site Name*', 'onedesign' ) }
						value={ formData.name }
						onChange={ ( value ) => setFormData( { ...formData, name: value } ) }
						error={ errors.name }
						help={ __( 'This is the name of the site that will be registered.', 'onedesign' ) }
					/>
					<TextControl
						label={ __( 'Site URL*', 'onedesign' ) }
						value={ formData.url }
						onChange={ ( value ) => setFormData( { ...formData, url: value } ) }
						error={ errors.url }
						help={ __( 'It must start with http or https and end with /, like: https://onedesign.com/', 'onedesign' ) }
					/>

					{ /* Logo Media Selection */ }
					<BaseControl
						id="site-logo"
						label={ __( 'Site Logo', 'onedesign' ) }
						help={ __( 'Select a logo for this brand site.', 'onedesign' ) }
					>
						<div style={ { marginTop: '8px' } }>
							{ formData.logo && (
								<div style={ {
									marginBottom: '12px',
									padding: '12px',
									border: '1px solid #ddd',
									borderRadius: '4px',
									backgroundColor: '#f9f9f9',
								} }>
									<img
										src={ formData.logo }
										alt={ __( 'Site Logo', 'onedesign' ) }
										style={ {
											maxWidth: '150px',
											maxHeight: '100px',
											display: 'block',
											marginBottom: '8px',
										} }
									/>
									<div style={ { display: 'flex', gap: '8px' } }>
										<Button
											variant="secondary"
											onClick={ handleLogoSelect }
											size="small"
										>
											{ __( 'Replace Logo', 'onedesign' ) }
										</Button>
										<Button
											variant="secondary"
											onClick={ handleLogoRemove }
											size="small"
											isDestructive={ true }
										>
											{ __( 'Remove Logo', 'onedesign' ) }
										</Button>
									</div>
								</div>
							) }

							{ ! formData.logo && (
								<Button
									variant="secondary"
									onClick={ handleLogoSelect }
								>
									{ __( 'Select Logo', 'onedesign' ) }
								</Button>
							) }
						</div>
					</BaseControl>

					<TextareaControl
						label={ __( 'API Key*', 'onedesign' ) }
						value={ formData.api_key }
						onChange={ ( value ) => setFormData( { ...formData, api_key: value } ) }
						error={ errors.api_key }
						help={ __( 'This is the api key that will be used to authenticate the site for onedesign.', 'onedesign' ) }
					/>

					<Button
						variant="primary"
						onClick={ handleSubmit }
						className={ isProcessing ? 'is-busy' : '' }
						disabled={ isButtonDisabled }
						style={ { marginTop: '12px' } }
					>
						{ (
							editing ? __( 'Update Site', 'onedesign' ) : __( 'Add Site', 'onedesign' )
						) }
					</Button>
				</Modal>
			) }

			{ showDeleteConfirm && (
				<DeleteConfirmationModal
					onConfirm={ confirmLogoRemove }
					onCancel={ cancelLogoRemove }
				/>
			) }
		</>
	);
};

export default SiteModal;
