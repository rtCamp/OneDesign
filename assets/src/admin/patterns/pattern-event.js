/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * This File contains the code to add the Design Library Button.
 */

window.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	const DesignLibraryGutenbergApp = {
		libraryCacheElements() {
			this.libraryCache = {};
			this.libraryCache.gutenberg = document.getElementById( 'editor' );
			this.libraryCache.gutenbergEditorHeader = document.querySelector( '.edit-post-layout' );
			this.libraryCache.switchModeTemplate = document.getElementById(
				'design-library-gutenberg-button',
			).innerHTML;
			this.libraryCache.switchMode = this.createElementFromHTML(
				this.libraryCache.switchModeTemplate,
			);
			this.libraryCache.switchModeButton = this.libraryCache.switchMode.querySelector(
				'#design-library-main-button',
			);
			this.addCustomEventOnButtonClick();

			wp.data.subscribe( () => {
				setTimeout( () => {
					this.buildButton();
				}, 1 );
			} );
		},
		createElementFromHTML( htmlString ) {
			const div = document.createElement( 'div' );
			div.innerHTML = htmlString.trim();
			return div.firstChild;
		},
		buildButton() {
			if ( ! this.libraryCache.gutenberg.querySelector( '#design-library-button' ) ) {
				this.libraryCache?.gutenberg
					?.querySelector( '.edit-post-header-toolbar' )
					?.appendChild( this.libraryCache.switchMode );
			}
		},
		init() {
			this.libraryCacheElements();
		},
		addCustomEventOnButtonClick() {
			this.libraryCache.switchModeButton.addEventListener( 'click', () => {
				window.console.log( 'Firing Custom Event' );
				const designLibraryModalOpenEvent = new CustomEvent( 'designLibraryModalOpen', {
					detail: { message: __( 'Open the Design Library Modal!', 'onedesign' ) },
				} );

				document.dispatchEvent( designLibraryModalOpenEvent );
			} );
		},
	};

	DesignLibraryGutenbergApp.init();
} );
