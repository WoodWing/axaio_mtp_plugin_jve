<?php
/****************************************************************************
   Copyright 2008-2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

/**
 * @package 	Enterprise Demo Plugins
 * @subpackage 	FlickrSearch
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Configuration file
 */

// Define name of NameQuery
define ('FLICKRSEARCH_NAMEDQUERY', 'Flickr Search');

// Terms to use in UI for query:
define ('FLICKRSEARCH_SEARCH_BY_TAG',	'By Tag'	);
define ('FLICKRSEARCH_SEARCH_BY_USER',	'By User'	);

// Define the number of items per page
define ('FLICKRSEARCH_ITEMS_PER_PAGE', 50);

/*
	In order to use the Flickr web services, we must have an API key.
	Create a new account on www.Flickr.com
	To obtain a key, please visit the URL, http://www.flickr.com/services/api/ under API Keys.
	Define the FLICKR_API_KEY, once obtained the API KEY.
	Define the FLICKRSEARCH_API_SECRET, once obtained the API Secret.
	
*/
define ('FLICKRSEARCH_USR_ACC', 	'' ); // User Account Id
define ('FLICKRSEARCH_API_KEY', 	'' ); // API Key
define ('FLICKRSEARCH_API_SECRET',	'' ); // Secret
define ('FLICKRSEARCH_TOKEN',		'' ); // Token