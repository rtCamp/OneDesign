import DOMPurify from 'dompurify';

/**
 * Helper function to extract initials from a name.
 *
 * @param {string} name - The name to extract initials from.
 * @return {string} The extracted initials (up to 2 characters).
 */
const getInitials = ( name ) => {
	// Handle empty or invalid names
	if ( ! name || typeof name !== 'string' ) {
		return '?';
	}

	// Trim the name and convert to proper case
	const trimmedName = name.trim();
	if ( ! trimmedName ) {
		return '?';
	}

	// Split the name by spaces and other separators
	const parts = trimmedName
		.split( /[\s-_,.]+/ )
		.filter( ( part ) => part.length > 0 );

	// For single word names
	if ( parts.length === 1 ) {
		// If name is a single character, return that character
		if ( parts[ 0 ].length === 1 ) {
			return parts[ 0 ].toUpperCase();
		}
		// Otherwise return first two characters
		return parts[ 0 ].substring( 0, 2 ).toUpperCase();
	}

	// For multi-word names, take first letter of first two parts
	return (
		parts[ 0 ].charAt( 0 ) + ( parts[ 1 ] ? parts[ 1 ].charAt( 0 ) : '' )
	).toUpperCase();
};

const isURL = ( str ) => {
	const pattern = new RegExp(
		'^https?:\\/\\/' +
		'(?:[a-z\\d](?:[a-z\\d-]*[a-z\\d])?\\.)?' +
		'[a-z\\d](?:[a-z\\d-]*[a-z\\d])?\\.' +
		'[a-z]{2,}' +
		'(?::\\d+)?' +
		'(?:\\/[^\\s]*)?' +
		'$', 'i',
	);
	return pattern.test( str );
};

const isValidUrl = ( url ) => {
	try {
		const parsedUrl = new URL( url );
		return isURL( parsedUrl.href );
	} catch ( e ) {
		return false;
	}
};

const PurifyElement = ( item ) => {
	return DOMPurify.sanitize( item, { ALLOWED_TAGS: [] } );
};

export {
	getInitials,
	isURL,
	isValidUrl,
	PurifyElement,
};
