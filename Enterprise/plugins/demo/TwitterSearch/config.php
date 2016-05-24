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

// List of languages the user can select. Empty list to suppress the language selector
define ('TWS_LANGUAGES', serialize( array( // list of arrays (extension, mime type, object type)
	'Any Language' 	=> '',
	'English'		=> 'en',
	'Dutch'			=> 'nl',
	'French'		=> 'fr',
	'German'		=> 'de',
	'Japanese'		=> 'ja',
	'Portuguese'	=> 'pt',
	'Spanish'		=> 'es',
)));

// Define Brand, Category and status for objects imported into the system.
// When any of these don't exist, the first available Brand/Category/Status will be used.
define( 'TWS_BRAND', 			'WW News' );
define( 'TWS_CATEGORY', 		'News' );
define( 'TWS_STATUS', 			'Hyperlinks' );

// Name of the Query to be shown in the User Interface, empty name to skip it
define( 'TWS_QUERY_SEARCH', 	'Twitter Search' );
define( 'TWS_QUERY_TRENDS',		'Twitter Trends' );

// Unique ID's for this Content Source. Make sure to change this if you create your own
// Content Source based on this example plugin
define('TWS_CONTENTSOURCEID', 		'TWS' );
define('TWS_CONTENTSOURCEPREFIX', 	'_TWS_' );