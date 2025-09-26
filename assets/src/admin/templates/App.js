/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import TemplateModal from './components/TemplateModal';

/**
 * Registers the Pattern Sync Library plugin.
 *
 * @return {void}
 */
registerPlugin( 'onedesign-template-library', {
	render: () => {
		if ( typeof createRoot !== 'function' ) {
			return null;
		}

		const className = 'onedesign-template-library';
		const modalID = 'onedesign-template-library-modal';

		if ( document.getElementById( modalID ) ) {
			return null;
		}

		const modalWrap = document.createElement( 'div' );
		const modal = Object.assign( modalWrap, { id: modalID, className } );
		document.body?.appendChild( modal );
		createRoot( modal ).render( <TemplateModal /> );
	},
} );
