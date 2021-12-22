<?php
/**
 * JWT Auth integration
 *
 * @package SeattleWebCo\LearnDashHistory
 */

namespace SeattleWebCo\LearnDashHistory\Integrations;

/**
 * Whitelist our endpoints with the JWT Auth plugin
 *
 * @param array $endpoints Default whitelisted endpoints.
 * @return array
 */
function jwt_auth_whitelist( $endpoints ) {
	$your_endpoints = array(
		'/wp-json/learndashhistory/*',
	);

	return array_unique( array_merge( $endpoints, $your_endpoints ) );
}
\add_filter( 'jwt_auth_whitelist', __NAMESPACE__ . '\jwt_auth_whitelist' );
