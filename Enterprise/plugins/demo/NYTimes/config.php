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

// API Key for NY Times Article Search API
define( 'NYT_API_ARTICLESEARCH_KEY', 	'' );
define( 'NYT_API_NEWSWIRE_KEY', 		'' );

// Define Brand, Category and status for objects imported into the system.
// When any of these don't exist, the first available Brand/Category/Status will be used.
define( 'NYT_BRAND', 			'WW News' );
define( 'NYT_CATEGORY', 		'News' );
define( 'NYT_STATUS', 			'Hyperlinks' );

// Name of the Query to be shown in the User Interface:
define( 'NYT_QUERY_ARTICLESEARCH_NAME', 	'NY Times Archive' );
define( 'NYT_QUERY_NEWSWIRE_NAME', 			'NY Times Newswire' );

// Unique ID's for this Content Source. Make sure to change this if you create your own
// Content Source based on this example plugin
define('NYT_CONTENTSOURCEID', 		'NYTAS' );
define('NYT_CONTENTSOURCEPREFIX', 	'_NYTAS_' );