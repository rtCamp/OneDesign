/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * This File contains the code to add the Pattern Library Button.
 */

window.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	const TemplateLibrary = {
		libraryCacheElements() {
			this.libraryCache = {};
			this.libraryCache.gutenberg = document.getElementById( 'editor' );
			this.libraryCache.gutenbergEditorHeader = document.querySelector( '.edit-post-layout' );
			this.libraryCache.switchModeTemplate = document.getElementById(
				'onedesign-template-button',
			).innerHTML;
			this.libraryCache.switchMode = this.createElementFromHTML(
				this.libraryCache.switchModeTemplate,
			);
			this.libraryCache.switchModeButton = this.libraryCache.switchMode.querySelector(
				'#template-main-button',
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
			if ( ! this.libraryCache.gutenberg.querySelector( '#onedesign-template-render' ) ) {
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
				window.console.log( 'Template event fired...' );
				const TemplateLibraryOpenEvent = new CustomEvent( 'TemplateLibraryOpen', {
					detail: { message: __( 'Open the Pattern Library Modal!', 'onedesign' ) },
				} );

				document.dispatchEvent( TemplateLibraryOpenEvent );
			} );
		},
	};

	TemplateLibrary.init();
} );
