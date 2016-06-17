<?php

/**
 * @package 	FlickrBulkImport for Enterprise
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Configuration file
 */

// Define enterprise
define ('FLICKR_WW_USERNAME',	'woodwing');	// User name
define ('FLICKR_WW_USERPWD',	'ww');			// User pwd
define ('FLICKR_WW_BRAND',		'WW News');		// Brand
define ('FLICKR_WW_CATEGORY',	'News');		// Category
define ('FLICKR_WW_STATUS',		'Images');		// State
define ('FLICKR_SOURCEID', 		'FS');			// Flickr content source id
define ('FLICKR_UPLOAD_DATE',	'2009-04-04');	// Flickr photo upload date, use for first time import, any Flickr photo upload date later this will get import
define ('FLICKR_MAX_IMPORT', 	'100');			// Number of photos to be import, MAX = 500

/*
	In order to use the Flickr web services, we must have an API key.
	Create a new account on www.Flickr.com
	To obtain a key, please visit the URL, http://www.flickr.com/services/api/ under API Keys.
	Define the FLICKR_API_KEY, once obtained the API KEY.
	Define the FLICKRSEARCH_API_SECRET, once obtained the API Secret.
*/
define ('FLICKRIMPORT_USR_ACC', 	'' );		// User Account Name
define ('FLICKRIMPORT_API_KEY', 	'' );		// API Key
define ('FLICKRIMPORT_API_SECRET',	'' );		// API Secret
define ('FLICKRIMPORT_TOKEN',		'' );		// Token