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

// Base URL for Celum Imagine system:
define( 'CELUM_URL', 	'http://woodwing.at.celumimagine.net/api/' ); // End with a slash /, includ ethe API sub-folder

// User account and password:
define ('CELUM_USER', 		'sonnemans' );
define ('CELUM_PASSWORD', 	'sonnemans' );


// Define Brand, Category and status for objects imported into the system.
// When any of these don't exist, the first available Brand/Category/Status will be used.
define( 'CELUM_BRAND', 			'WW News' );
define( 'CELUM_CATEGORY', 		'News' );
define( 'CELUM_STATUS', 		'Draft text' );

// Name of the Query to be shown in the User Interface:
define( 'CELUM_QUERY_NAME', 	'Celum' );

// Unique ID's for this Content Source. Make sure to change this if you create your own
// Content Source based on this example plugin
define('CELUM_CONTENTSOURCEID', 	'CELUM' );
define('CELUM_CONTENTSOURCEPREFIX', '_CELUM_' );