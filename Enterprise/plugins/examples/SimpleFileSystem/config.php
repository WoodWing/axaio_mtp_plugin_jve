<?php
/****************************************************************************
   Copyright 2008-2013 WoodWing Software BV

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

// Root folder of the content we want to make available to Enterprise.
// Inside this folder there should be sub-folders with inside the images.
if( !defined('SFS_LOCALCONTENTFOLDER') ) {
   define( 'SFS_LOCALCONTENTFOLDER', '/Demo/My Disk/' );  // Should end with /, for example '/Demo/My Disk/'
}

// Define Brand, Category and status for objects imported into the system.
// When any of these don't exist, the first available Brand/Category/Status will be used.
if( !defined('SFS_BRAND') ) {
   define( 'SFS_BRAND', 'WW News' );
}
if( !defined('SFS_CATEGORY') ) {
   define( 'SFS_CATEGORY', 'News' );
}
if( !defined('SFS_STATUS') ) {
   define( 'SFS_STATUS', 'Images' );
}

// Name of the Query to be shown in the User Interface:
if( !defined('SFS_QUERY_NAME') ) {
   define( 'SFS_QUERY_NAME', 'Image Library' );
}

// Whether or not to allow access to the files that were introduced by the SFS Content Source.
// Specifiy one of the following values:
// - true: everyone has access
// - false: nobody has access (experimental)
// - null: respect the Enterprise access rights configuration (default)
if( !defined('SFS_ALLOW_ACCESS') ) {
   define( 'SFS_ALLOW_ACCESS', null );
}

// Should preview and thumbs be cached? 
// If so, a preview and thumb subfolder will be created inside the image folders.
// Once a preview/thumb is available it will be used, there is no check if the original file has been modified
if( !defined('SFS_PREVIEW_CACHE') ) {
   define( 'SFS_PREVIEW_CACHE', true );
}
// Unique ID's for this Content Source. Make sure to change this if you create your own
// Content Source based on this example plugin
if( !defined('SFS_CONTENTSOURCEID') ) {
   define( 'SFS_CONTENTSOURCEID', 'WWSCS' );
}
if( !defined('SFS_CONTENTSOURCEPREFIX') ) {
   define( 'SFS_CONTENTSOURCEPREFIX', '_WWSCS_' );
}