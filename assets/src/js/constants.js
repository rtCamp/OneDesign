/**
 * PHP consts for JS usage.
 *
 * @package
 */

let settings = {};

if ( typeof window.OneDesignSettings !== 'undefined' ) {
	settings = window.OneDesignSettings;
} else if ( typeof window.patternSyncData !== 'undefined' ) {
	settings = window.patternSyncData;
} else if ( typeof window.TemplateLibraryData !== 'undefined' ) {
	settings = window.TemplateLibraryData;
}

const ONEDESIGN_REST_NAME = 'onedesign';
const ONEDESIGN_REST_VERSION = 'v1';

const API_NAMESPACE = settings?.restUrl ? settings.restUrl + `/${ ONEDESIGN_REST_NAME }/${ ONEDESIGN_REST_VERSION }` : '';
const NONCE = settings?.restNonce ? settings.restNonce : '';
const API_KEY = settings?.apiKey ? settings.apiKey : '';
const SETTINGS_LINK = settings?.settingsLink ? settings.settingsLink : '';
const PER_PAGE = 9;

export {
	API_NAMESPACE,
	NONCE,
	API_KEY,
	SETTINGS_LINK,
	PER_PAGE,
};
