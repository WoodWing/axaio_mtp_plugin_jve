<?php

// The available WordPress sites.
$sites = array();

/* Site definitions. One or more sites can be defined. The site array itself uses a unique name for
 * a site as keys. The specified WordPress user account is used to publish content and therefore needs to have Administrator rights.
 * The first site defined is the default site, which is used when no matching Brand/Publication Channel is
 * found in the $config (WORDPRESS_CONFIG) setting. To add a site, add the following array structure:
$sites['{unique_name}'] = array(                   // Logical name of the WordPress site. Must be unique within this array. This must be 10 characters or less when normalized.
		'url'        => '{WordPress_url}',         // URL to the WordPress site. Must not end with a slash / (e.g. http://mysite/wordpress)
		'username'   => '{WordPress_username}',    // WordPress username. The user should have the 'Administrator' role in WordPress.
		'password'   => '{WordPress_password}',    // WordPress password. This should be the password of the user mentioned above
		'certificate'   => '{Wordpress_certificate}', // Optional, required for HTTPS (SSL) connections only. The full file path of the local CA certificate file (in PEM format).
	);
*/

$sites[''] = array(
	'url'        => '',
	'username'   => '',
	'password'   => '',
	'certificate' => ''
);

if( !defined('WORDPRESS_SITES') ) {
	define( 'WORDPRESS_SITES', serialize( $sites ) );
}