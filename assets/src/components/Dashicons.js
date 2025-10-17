/**
 * Render the appropriate dashicon based on health check result.
 *
 * @param {Object} props                        - Component props.
 * @param {Object} props.sitesHealthCheckResult - Object containing health check results for sites.
 * @param {number} props.id                     - Site ID.
 * @return {JSX.Element} The rendered dashicon element.
 */
const renderIcon = ( { sitesHealthCheckResult, id } ) => {
	return (
		sitesHealthCheckResult?.[ id ] && ! sitesHealthCheckResult?.[ id ]?.success ? (
			<span className="dashicons dashicons-warning"></span>
		) : (
			<span className="dashicons dashicons-yes-alt"></span>
		)
	);
};

export {
	renderIcon,
};
