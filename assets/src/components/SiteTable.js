/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { Button, Card, CardHeader, CardBody, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getInitials } from '../js/utils';

/**
 * SiteTable component to display and manage brand sites.
 *
 * @param {Object}   props              - Component properties.
 * @param {Array}    props.sites        - List of brand sites.
 * @param {Function} props.onEdit       - Function to handle editing a site.
 * @param {Function} props.onDelete     - Function to handle deleting a site.
 * @param {Function} props.setFormData  - Function to set form data for editing.
 * @param {Function} props.setShowModal - Function to show/hide the modal for adding/editing a site.
 * @return {JSX.Element} Rendered component.
 */
const SiteTable = ( { sites, onEdit, onDelete, setFormData, setShowModal } ) => {
	const [ showDeleteModal, setShowDeleteModal ] = useState( false );
	const [ deleteIndex, setDeleteIndex ] = useState( null );

	const handleDeleteClick = ( index ) => {
		setDeleteIndex( index );
		setShowDeleteModal( true );
	};

	const handleDeleteConfirm = () => {
		onDelete( deleteIndex );
		setShowDeleteModal( false );
		setDeleteIndex( null );
	};

	const handleDeleteCancel = () => {
		setShowDeleteModal( false );
		setDeleteIndex( null );
	};

	return (
		<Card style={ { marginTop: '30px' } }>
			<CardHeader>
				<h3>{ __( 'Brand Sites', 'onedesign' ) }</h3>
				<Button
					style={ { width: 'fit-content' } }
					variant="primary"
					onClick={ () => setShowModal( true ) }
				>
					{ __( 'Add Brand Site', 'onedesign' ) }
				</Button>
			</CardHeader>
			<CardBody>
				<table className="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>{ __( 'Site Name', 'onedesign' ) }</th>
							<th>{ __( 'Site URL', 'onedesign' ) }</th>
							<th>{ __( 'Logo', 'onedesign' ) }</th>
							<th>{ __( 'API Key', 'onedesign' ) }</th>
							<th>{ __( 'Actions', 'onedesign' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ sites.length === 0 && (
							<tr>
								<td colSpan="5" style={ { textAlign: 'center' } }>
									{ __( 'No Brand Sites found.', 'onedesign' ) }
								</td>
							</tr>
						) }
						{ sites?.map( ( site, index ) => (
							<tr key={ index }>
								<td>{ site?.name }</td>
								<td>{ site?.url }</td>
								<td>
									{ site?.logo ? (
										<img
											src={ site.logo }
											alt={ __( 'Site Logo', 'onedesign' ) }
											style={ { maxWidth: '100px', maxHeight: '50px' } }
											loading="lazy"
											decoding="async"
										/>
									) : (
										<span className="onedesign-site-initials">{ getInitials( site?.name ) }</span>
									) }
								</td>
								<td><code>{ site?.api_key.substring( 0, 10 ) }...</code></td>
								<td>
									<Button
										variant="secondary"
										onClick={ () => {
											setFormData( site );
											onEdit( index );
											setShowModal( true );
										} }
										style={ { marginRight: '8px' } }
									>
										{ __( 'Edit', 'onedesign' ) }
									</Button>
									<Button
										variant="secondary"
										isDestructive
										onClick={ () => handleDeleteClick( index ) }
									>
										{ __( 'Delete', 'onedesign' ) }
									</Button>
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			</CardBody>
			{ showDeleteModal && (
				<DeleteConfirmationModal
					onConfirm={ handleDeleteConfirm }
					onCancel={ handleDeleteCancel }
				/>
			) }
		</Card>
	);
};

/**
 * DeleteConfirmationModal component for confirming site deletion.
 *
 * @param {Object}   props           - Component properties.
 * @param {Function} props.onConfirm - Function to call on confirmation.
 * @param {Function} props.onCancel  - Function to call on cancellation.
 * @return {JSX.Element} Rendered component.
 */
const DeleteConfirmationModal = ( { onConfirm, onCancel } ) => (
	<Modal
		title={ __( 'Delete Brand Site', 'onedesign' ) }
		onRequestClose={ onCancel }
		isDismissible={ true }
	>
		<p>{ __( 'Are you sure you want to delete this Brand Site? This action cannot be undone.', 'onedesign' ) }</p>
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
				{ __( 'Delete', 'onedesign' ) }
			</Button>
		</div>
	</Modal>
);

export default SiteTable;
