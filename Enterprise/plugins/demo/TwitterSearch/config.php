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
if( !defined('TWS_LANGUAGES') ) {
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
}

// Define Brand, Category and status for objects imported into the system.
// When any of these don't exist, the first available Brand/Category/Status will be used.
if( !defined('TWS_BRAND') ) {
	define( 'TWS_BRAND', 'WW News' );
}
if( !defined('TWS_CATEGORY') ) {
	define( 'TWS_CATEGORY', 'News' );
}
if( !defined('TWS_STATUS') ) {
	define( 'TWS_STATUS', 'Hyperlinks' );
}

// Name of the Query to be shown in the User Interface, empty name to skip it
if( !defined('TWS_QUERY_SEARCH') ) {
	define( 'TWS_QUERY_SEARCH', 'Twitter Search' );
}
if( !defined('TWS_QUERY_TRENDS') ) {
	define( 'TWS_QUERY_TRENDS', 'Twitter Trends' );
}

// Unique ID's for this Content Source. Make sure to change this if you create your own
// Content Source based on this example plugin
if( !defined('TWS_CONTENTSOURCEID') ) {
	define( 'TWS_CONTENTSOURCEID', 'TWS' );
}
if( !defined('TWS_CONTENTSOURCEPREFIX') ) {
	define( 'TWS_CONTENTSOURCEPREFIX', '_TWS_' );
}