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

// API Key for Guardian API. Get one via http://guardian.mashery.com/
// Approval process could take a few days.
// For more info, see http://www.guardian.co.uk/open-platform
define( 'GNL_API_KEY', 	'' );

// Define the number of items per page
define ('GNL_ITEMS_PER_PAGE', 25);

// Define Brand, Category and status for objects imported into the system.
// When any of these don't exist, the first available Brand/Category/Status will be used.
define( 'GNL_BRAND', 			'WW News' );
define( 'GNL_CATEGORY', 		'News' );
define( 'GNL_STATUS', 			'Draft text' );

// Name of the Query to be shown in the User Interface:
define( 'GNL_QUERY_NAME', 			'Guardian' );

// Unique ID's for this Content Source. Make sure to change this if you create your own
// Content Source based on this example plugin
define('GNL_CONTENTSOURCEID', 		'GNL' );
define('GNL_CONTENTSOURCEPREFIX', 	'_GNL_' );