<?php
/**
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * This configuration file belongs to the SmartArchive server plug-ins which should
 * installed at the production server. It allows system admin users to adjust default 
 * settings used to connect to the archive server and to customize the archive search 
 * parameters used at the production server.
 * Note that the production server and archive server are both Enterprise Server instances.
 */

// -------------------------------------------------------
// Workflow SOAP entry point (URL) to the archive server
// -------------------------------------------------------
if( !defined('SMARTARCHIVE_SERVERURL') ) {
	define( 'SMARTARCHIVE_SERVERURL', 'http://127.0.0.1:8888/SCE702B70_2/index.php' );
}

// Define Brand, Category and status for objects from Archive Server
/* 
* Create a publication in Production Server (Enterprise).
* Brand = 'Smart Archive'
* Category = 'Archived' 
*
* Definition below should be the same as the one defined in Production Server of Enterprise.
*/
if( !defined('SA_BRAND') ) {
	define( 'SA_BRAND', 'Smart Archive' );
}
if( !defined('SA_CATEGORY') ) {
	define( 'SA_CATEGORY', 'Archived' );
}

// -------------------------------------------------------
// Defined name query field query function
// -------------------------------------------------------
if( !defined('SMARTARCHIVE_FILTER_FIELDS') ) {
	define('SMARTARCHIVE_FILTER_FIELDS', serialize( array(
				'Search' //Do not specify OBJECT TYPE HERE, define it below under SMARTARCHIVE_SEARCH_OBJECT_TYPES
	)));
}
											
// -------------------------------------------------------
// Object types listed at "Type" filter field of the archive search queries
// Currently only supports 'Article' and 'Image'
// -------------------------------------------------------
if( !defined('SMARTARCHIVE_SEARCH_OBJECT_TYPES') ) {
	define('SMARTARCHIVE_SEARCH_OBJECT_TYPES', serialize( array(
				'Article', 'Image'
	)));
}

// -------------------------------------------------------
// System internals
// ===> DO NOT MAKE CHANGES TO THE FOLLOWING SECTION
// -------------------------------------------------------
define( 'SMARTARCHIVE_CONTENTSOURCEID', 'Archive' );
define( 'SMARTARCHIVE_CONTENTSOURCEPREFIX', '_Archive_' );