<?php
// Variable that holds the options defined on a global level
$options = array(
	'layout_rendition' => '', // Options: thumb, preview, native, output. Default: output
	'writer'           => '', // Options: publisher, writer, modifier, none. Default: publisher. If the writer is none the drupal user will be used
	'title_fallback'   => '', // Options: description, name, name/description, description/name. Default: description/name
	'components'       => array( // Map the drupal text components to Enterprise text components as {drupal_component} => {enterprise_component}
							'title' 	=> 'head',
							'teaser' 	=> 'intro',
							'body' 		=> 'body',
						)
);

/* Variable that holds the mapping of metadata fields to drupal fields
 *
 * Drupal options are: pagetitle, urlpath, description, keywords, comments, sticky, promote and taxonomy
$mapping = array(
	'{drupal_option}' => '{metadata_field}'
	['taxonomy'	=> array('{drupal_taxonomy}' => '{metadata_field}')] // optional to map fields to drupal taxonomies
);
*/
$mapping = array(
	'pagetitle'   => '', // Metadata type string - If set, the <title> tag in Drupal is different from headline
	'urlpath'     => '', // Metadata type string - If set, the url path can be set (for SEO purposes)
	'description' => '', // Metadata type string - If set, this string is used for the HTML metatag  (for SEO purposes)
	'keywords'    => '', // Metadata type multistring - If set, this string is used for the HTML metatag  (for SEO purposes)
	'comments'    => '', // Metadata type list with options: disable, read, read/write. Default: Drupal content type default
	'sticky'      => '', // Metadata type boolean - Default: Drupal content type default
	'promote'     => '', // Metadata type boolean - Default: Drupal content type default
	'taxonomy'    => array('' => ''), // Metadata type multistring - If set, this string is used to map keywords to drupal taxonomy
);

/* Site definitions. One or more sites can be defined. The site array itself uses a unique name for 
 * a site as keys. The specified Drupal user account is used to publish content and therefore needs 
 * to have rights to create, update and delete (image) nodes on Drupal.
 * The first site defined is the default site, which is used when no matching Brand/Publication Channel is
 * found in the $config (DRUPAL_CONFIG) setting. To add a site, add the following array structure:
$sites['unique_name'] = array(                 // Logical name of the Drupal site. Must be unique within this array.
		'url'        => '{drupal_url}',         // URL to the Drupal server. Must end with a forward slash / (e.g. http://localhost/Drupal/) 
		'username'   => '{drupal_username}',    // Drupal username.
		'password'   => '{drupal_password}',    // Drupal password.
		'local_cert' => '{drupal_certificate}', // Full file path of the local CA certificate file (in PEM format).
		                                        // Required for HTTPS (SSL) connections only. See SSL SDK how to generate certificates.
		'options'    => array('{options_key}' => '{options_value}'), // Optional: Overrules options in the options array
		'mapping'    => array('{mapping_key}' => '{mapping_value}'), // Optional: Overrules mappings in the mapping array
	);
*/

$sites['Drupal'] = array(
		'url'        => '',
		'username'   => '',
		'password'   => '',
		'local_cert' => BASEDIR.'/config/encryptkeys/cacert.pem',
	);


// Overrule the site configuration per publication channel
$config = array();

// In this configuration brand and site are required. Channel and issue are optional. If you want to set the issue the channel is required.
// e.g. $config[] = array('brand' => '{brand_name or brand_id}', 'channel' => '{channel_name or channel_id}', 'issue' => '{issue_name or issue_id}', 'site' => '{unique_name}');
// EXAMPLES
//$config[] = array('brand' => 'WW News', 'channel' => 'Web', 'site' => 'Drupal');
//$config[] = array('brand' => 'WW News', 'channel' => 'Web 2', 'site' => 'Drupal test');

// Define the three arrays as serialized constants
define('DRUPAL_SITES',   serialize( $sites ));
define('DRUPAL_CONFIG',  serialize(( isset($config) && !empty($config) ) ? $config : array() ));
define('DRUPAL_OPTIONS', serialize(( isset($options) && !empty($options) ) ? $options : array() ));
define('DRUPAL_MAPPING', serialize(( isset($mapping) && !empty($mapping) ) ? $mapping : array() ));
