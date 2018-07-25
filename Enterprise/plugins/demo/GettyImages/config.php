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

/**
 * @subpackage 	GettyImages
 * @since 		v8.2.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Configuration file
 */

// Define name of NameQuery
if( !defined('GETTYIMAGES_NAMEDQUERY') ) {
   define( 'GETTYIMAGES_NAMEDQUERY', 'Getty Images' );
}

/**
 * These are the participant System ID and Password of the Getty Images API crendentials.
 * By default, it use WoodWing as the participant system. User need not need to configure the below section.
 */
if( !defined('GETTYIMAGES_SYS_ID') ) {
   define( 'GETTYIMAGES_SYS_ID', '3159' ); // System ID
}
if( !defined('GETTYIMAGES_SYS_PWD') ) {
   define( 'GETTYIMAGES_SYS_PWD', 'AL+BK5vEYRjhSjD7Ev4mFXfLAyFkVEwP7rdpUY9tZJQ=' ); // System Password
}

/**
 * In order to use the Getty Images Connect API services, you have to enter your Getty Images API credentials.
 *
 */
if( !defined('GETTYIMAGES_USER_NAME') ) {
   define( 'GETTYIMAGES_USER_NAME', '' ); // User Name
}
if( !defined('GETTYIMAGES_USER_PWD') ) {
   define( 'GETTYIMAGES_USER_PWD', '' ); // User Password
}

/**
 * Define the refinement that will be excluded from the facets list
 */
if( !defined('EXCLUDE_REFINEMENTS') ) {
   define ('EXCLUDE_REFINEMENTS' , serialize( array( 'Age',
                                         'Gender',
   												  ) ) );
}