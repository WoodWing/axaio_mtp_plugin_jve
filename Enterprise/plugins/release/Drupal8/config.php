<?php

/**
 * Drupal Sites configuration.
 *
 * These site configurations are available in the brand setup pages and can be selected when configuring a Drupal
 * instance for multi-channel publisihing.
 *
 * The configuration array takes the following structure.
 *
 * $sites = array(
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
 * );
 *
 * Multiple instances can be configured by adding configurations to the array beyond the first. In the brand setup pages
 * these instances are represented in a drop down box, the configured labels will be used to represent configuration
 * options.
 *
 * - For the url, specify the full URL to your Drupal instance, including a trailing slash.
 * - For the username, enter a valid Drupal user for the instance here.
 * - For the password, enter the password belonging to the specified username.
 */
$sites = array(
	'label_of_the_first_instance' => array( // Site label.
		'url' => '', // Url of the Drupal instance.
	    'username' => '', // The username used for importing / publishing.
	    'password'=> '', // The password of the selected user.
	),
);

define('DRUPAL8_SITES', serialize( $sites ));