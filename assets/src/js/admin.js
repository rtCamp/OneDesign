/**
 * OneDesign admin JS file.
 * Handles media uploads for site logos.
 */
import { __ } from '@wordpress/i18n';

// Wait for DOM to be ready
document.addEventListener( 'DOMContentLoaded', function() {
	// Child site logo uploaders
	let childLogoUploader = null;

	// Handle dynamically added child site logo buttons
	document.addEventListener( 'click', function( e ) {
		if ( e.target.classList.contains( 'child-logo-upload' ) ) {
			e.preventDefault();

			const button = e.target;

			initChildLogoUploader( button );

			if ( childLogoUploader ) {
				childLogoUploader.open();
			}
		}
	} );

	// Handle remove logo buttons for child sites
	document.addEventListener( 'click', function( e ) {
		if ( e.target.classList.contains( 'child-logo-remove' ) ) {
			e.preventDefault();

			const siteId = e.target.getAttribute( 'data-id' );
			const childSiteLogoInput = document.getElementById(
				'child_site_logo_' + siteId,
			);
			const childLogoPreview = document.getElementById(
				'child-logo-preview-' + siteId,
			);

			if ( childSiteLogoInput ) {
				childSiteLogoInput.value = '';
			}

			if ( childLogoPreview ) {
				childLogoPreview.style.display = 'none';
				childLogoPreview.innerHTML = '';
			}

			e.target.style.display = 'none';
		}
	} );

	/**
	 * Setup a child site logo uploader
	 *
	 * @param {HTMLElement} button The upload button element
	 */
	function initChildLogoUploader( button ) {
		const siteId = button.getAttribute( 'data-id' );
		const siteLogoId = button.getAttribute( 'data-logo-id' );
		button.classList.add( 'initialized' );

		// Create the media frame
		childLogoUploader = wp.media( {
			title: 'Select or Upload Site Logo',
			button: {
				text: 'Use this image as logo',
			},
			multiple: false,
		} );

		childLogoUploader.on( 'open', function() {
			// If a logo ID is provided, select that image
			if ( siteLogoId ) {
				const selection = childLogoUploader.state().get(
					'selection',
				);
				const attachment = wp.media.attachment( siteLogoId );
				attachment.fetch();
				selection.add( attachment ? [ attachment ] : [] );
			}
		} );

		// When an image is selected, run a callback
		childLogoUploader.on( 'select', function() {
			const attachment = childLogoUploader
				.state()
				.get( 'selection' )
				.first()
				.toJSON();

			const childSiteLogoInput = document.getElementById(
				'child_site_logo_' + siteId,
			);
			const childLogoPreview = document.getElementById(
				'child-logo-preview-' + siteId,
			);
			const childLogoRemoveBtn = document.querySelector(
				'.child-logo-remove[data-id="' + siteId + '"]',
			);
			const childLogoUploadBtn = document.querySelector(
				'.child-logo-upload[data-id="' + siteId + '"]',
			);

			if ( childSiteLogoInput ) {
				childSiteLogoInput.value = attachment.url;
			}

			if ( childLogoPreview ) {
				childLogoPreview.innerHTML =
					'<img src="' + attachment.url + '" alt="Site Logo" />';
				childLogoPreview.style.display = 'block';
			}

			if ( childLogoUploadBtn ) {
				childLogoUploadBtn.setAttribute(
					'data-logo-id',
					attachment.id,
				);
			}

			if ( childLogoRemoveBtn ) {
				childLogoRemoveBtn.style.display = '';
			}
		} );
	}

	document
		.getElementById( 'site_type' )
		.addEventListener( 'change', toggleSiteTypeOptions );

	document
		.querySelector( '.add-site' )
		.addEventListener( 'click', addSiteRow );

	document
		.querySelectorAll( '.delete-site' )
		.forEach( ( button ) => {
			button.addEventListener( 'click', deleteSite );
		} );
} );

/**
 * Toggle visibility of site type options based on selected site type.
 *
 * @return {void}
 */
function toggleSiteTypeOptions() {
	const siteType = document.getElementById( 'site_type' ).value;
	const consumerOptions = document.getElementById( 'consumer-options' );
	const dashboardOptions = document.getElementById( 'dashboard-options' );

	if ( siteType === 'consumer' ) {
		consumerOptions.style.display = '';
		dashboardOptions.style.display = 'none';
	} else {
		consumerOptions.style.display = 'none';
		dashboardOptions.style.display = '';
	}
}

/**
 * Add a new child site row to the table.
 *
 * @return {void}
 */
function addSiteRow() {
	const tableBody = document.getElementById( 'child-sites-table' );
	const noSitesRow = document.getElementById( 'no-sites-row' );

	// Remove the "no sites" row if it exists.
	if ( noSitesRow ) {
		noSitesRow.remove();
	}

	// Generate a unique ID (timestamp + random).
	const uniqueId = Date.now() + '_' + Math.random().toString( 36 ).substr( 2, 9 );

	const newRow = document.createElement( 'tr' );
	newRow.setAttribute( 'data-id', uniqueId );
	newRow.innerHTML = `
					<td>
						<input type="text" name="child_sites[${ uniqueId }][name]" value="" class="regular-text" placeholder="${ __( 'Site Name', 'onedesign' ) }" required />
						<input type="hidden" name="child_sites[${ uniqueId }][id]" value="${ uniqueId }" required />
					</td>
					<td>
						<input type="url" name="child_sites[${ uniqueId }][url]" value="" class="regular-text" placeholder="https://example.com" required />
					</td>
					<td>
						<div class="logo-container">
							<div class="logo-preview" id="child-logo-preview-${ uniqueId }" style="display:none;">
							</div>
						</div>
						<input type="button" class="button button-secondary logo-upload-button child-logo-upload" data-id="${ uniqueId }" value="${ __( 'Upload Logo', 'onedesign' ) }" />
						<input type="button" class="button button-link logo-remove-button child-logo-remove" data-id="${ uniqueId }" value="${ __( 'Remove Logo', 'onedesign' ) }" style="display:none;" />
						<input type="hidden" name="child_sites[${ uniqueId }][logo]" class="child-site-logo-url" id="child_site_logo_${ uniqueId }" value="" />
					</td>
					<td>
						<input type="text" name="child_sites[${ uniqueId }][api_key]" value="" class="regular-text" placeholder="${ __( 'API Key', 'onedesign' ) }" required />
					</td>
					<td>
						<button type="button" class="button button-small delete-site">${ __( 'Delete', 'onedesign' ) }</button>
					</td>
				`;
	newRow.querySelector( '.delete-site' ).addEventListener( 'click', deleteSite );

	tableBody.appendChild( newRow );
}

/**
 * Delete a child site row from the table.
 *
 * @param {Event} e The click event.
 * @return {void}
 */
function deleteSite( e ) {
	const button = e.target;
	// Confirm deletion
	// eslint-disable-next-line no-alert
	if ( ! confirm( __( 'Are you sure you want to delete this site?', 'onedesign' ) ) ) {
		return;
	}

	const row = button.closest( 'tr' );
	const tableBody = document.getElementById( 'child-sites-table' );

	row.remove();

	// Show "no sites" row if no sites left
	if ( tableBody.children.length === 0 ) {
		const noSitesRow = document.createElement( 'tr' );
		noSitesRow.id = 'no-sites-row';
		noSitesRow.innerHTML = `<td colspan="4">${ __( 'No child sites configured yet.', 'onedesign' ) }</td>`;
		tableBody.appendChild( noSitesRow );
	}
}
