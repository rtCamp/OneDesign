/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { renderIcon } from '@/components/Dashicons';

describe( 'renderIcon', () => {
	it( 'renders the warning icon when a site health check failed', () => {
		const { container } = render(
			renderIcon( {
				sitesHealthCheckResult: { 1: { success: false } },
				id: 1,
			} )
		);
		expect(
			container.querySelector( '.dashicons-warning' )
		).toBeInTheDocument();
	} );

	it( 'renders the success icon when the health check passed', () => {
		const { container } = render(
			renderIcon( {
				sitesHealthCheckResult: { 1: { success: true } },
				id: 1,
			} )
		);
		expect(
			container.querySelector( '.dashicons-yes-alt' )
		).toBeInTheDocument();
	} );

	it( 'renders the success icon when there is no result for the site', () => {
		const { container } = render(
			renderIcon( { sitesHealthCheckResult: {}, id: 1 } )
		);
		expect(
			container.querySelector( '.dashicons-yes-alt' )
		).toBeInTheDocument();
	} );
} );
