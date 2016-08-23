<?php

/**
 * Drupal Sites configuration.
 *
 * The site configurations that are defined below are available in the "Publication Channel 
 * Maintenance" page for the "Web Site" field. This is shown for Drupal channels only for 
 * which the "Publication Channel Type" option should be set to "web" and the "Publish System" 
 * option should be set to "Drupal 8 - Publish Forms".
 *
 * The configuration takes the following structure:
 *
 * define( 'DRUPAL8_SITES', serialize( array(
 *   'label_of_the_first_instance' => array( // Site label.
 *     'url' => 'http://url_to_drupal_instance', // Url of the Drupal instance.
 *     'username' => 'username', // The username used for importing / publishing.
 *     'password'=> 'password', // The password of the selected user.
 *   ),
 *   'label_of_a_second_instance_optional' => array(
 *     'url' => 'http://url_to_drupal_instance',
 *     'username' => 'username',
 *     'password'=> 'password',
 *   )
 * ));
 *
 * Multiple instances can be configured by adding configurations to the array beyond the first. In the brand setup pages
 * these instances are represented in a drop down box, the configured labels will be used to represent configuration
 * options.
 */

if( !defined('DRUPAL8_SITES') ) {
	define( 'DRUPAL8_SITES', serialize( array( 
		'label_of_the_first_instance' => array( // site label, as shown on the "Publication Channel Maintenance" page
			'url' => '', // specify the full URL to your Drupal instance, including a trailing slash
			'username' => '', // enter a valid Drupal user for the instance here
			'password'=> '', // enter the password belonging to the specified username
		)
	)));
}