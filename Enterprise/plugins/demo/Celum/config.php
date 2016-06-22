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
if( !defined('CELUM_URL') ) {
   define( 'CELUM_URL', 'http://woodwing.at.celumimagine.net/api/' ); // End with a slash /, include the API sub-folder
}

// User account and password:
if( !defined('CELUM_USER') ) {
   define( 'CELUM_USER', '' );
}
if( !defined('CELUM_PASSWORD') ) {
   define( 'CELUM_PASSWORD', '' );
}

// Define Brand, Category and status for objects imported into the system.
// When any of these don't exist, the first available Brand/Category/Status will be used.
if( !defined('CELUM_BRAND') ) {
   define( 'CELUM_BRAND', 'WW News' );
}
if( !defined('CELUM_CATEGORY') ) {
   define( 'CELUM_CATEGORY', 'News' );
}
if( !defined('CELUM_STATUS') ) {
   define( 'CELUM_STATUS', 'Draft text' );
}

// Name of the Query to be shown in the User Interface:
if( !defined('CELUM_QUERY_NAME') ) {
   define( 'CELUM_QUERY_NAME', 'Celum' );
}

// Unique ID's for this Content Source. Make sure to change this if you create your own
// Content Source based on this example plugin
if( !defined('CELUM_CONTENTSOURCEID') ) {
   define( 'CELUM_CONTENTSOURCEID', 'CELUM' );
}
if( !defined('CELUM_CONTENTSOURCEPREFIX') ) {
   define( 'CELUM_CONTENTSOURCEPREFIX', '_CELUM_' );
}