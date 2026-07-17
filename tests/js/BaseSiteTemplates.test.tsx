/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import BaseSiteTemplates from '@/admin/templates/components/BaseSiteTemplates';

// MemoizedTemplatePreview pulls in the block editor.
jest.mock( '@wordpress/block-editor', () => ( {
	BlockPreview: () => null,
} ) );
jest.mock( '@wordpress/blocks', () => ( {
	parse: jest.fn( () => [] ),
} ) );

const baseProps = {
	currentPage: 1,
	PER_PAGE: 9,
	selectedTemplates: [],
	handleTemplateSelection: jest.fn(),
};

describe( 'BaseSiteTemplates', () => {
	it( 'shows the empty state when there are no templates', () => {
		render(
			<BaseSiteTemplates { ...baseProps } filteredTemplates={ [] } />
		);
		expect( screen.getByText( 'No templates found.' ) ).toBeInTheDocument();
	} );

	it( 'renders a preview per template when present', () => {
		render(
			<BaseSiteTemplates
				{ ...baseProps }
				filteredTemplates={ [
					{ id: 1, name: 'tpl-1', title: 'Template One' },
				] }
			/>
		);
		expect(
			screen.queryByText( 'No templates found.' )
		).not.toBeInTheDocument();
	} );
} );
