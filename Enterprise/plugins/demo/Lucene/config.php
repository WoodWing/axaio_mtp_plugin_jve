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
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Lucene Search integration - The configuration file
 */

/*
	Whether or not articles should be indexed synchronously.

	To give you an idea; On MacBookPro 2.33Ghz Intel Core 2 Duo it typically takes 0.1 seconds to 
	index a 2400 character (400 words) article. Doing this synchronously will let the users wait a bit.
	To avoid this, set the LUCENE_SYNCHRONOUS_INDEX option is set to false. But, that requires a setup
	of scheduled jobs that call the cron_index.php file periodically.

	IMPORTANT: For production always use asynchronous mode! That is, LUCENE_SYNCHRONOUS_INDEX set to false.
	Synchronous mode is only for demo purposes (to ease installation).

	In any case you will need to call cron_optimize.php periodically.
*/
if( !defined('LUCENE_SYNCHRONOUS_INDEX') ) {
   define( 'LUCENE_SYNCHRONOUS_INDEX', true ); // true for demo, false for 'production'
}

/*
	The work folder of the Lucene integration to store temporary files.
	
	IMPORTANT: The Lucene indexes are saved at the LUCENE_DIRECTORY folder.
	However, indexes should never be on a Networked file system.
	In other terms, the LUCENE_DIRECTORY should NOT point to a mounted folder of a remote machine!

	Implementation details:
	- Zend_Search_Lucene uses flock() to provide concurrent searching, index updating and optimization. 
	- According to the PHP documentation [http://www.php.net/manual/en/function.flock.php], "flock() will 
	  not work on NFS and many other networked file systems". 
	- Do not use networked file systems with Zend_Search_Lucene. 
*/
if( !defined('LUCENE_DIRECTORY') ) {
   define( 'LUCENE_DIRECTORY', ATTACHMENTDIRECTORY.'/_LUCENE_' ); // no ending '/'
}

// Name of NameQuery as shown at end user.
if( !defined('LUCENE_NAMEDQUERY') ) {
   define( 'LUCENE_NAMEDQUERY', 'Lucene Search' );
}

// The number of Enterprise objects to index at Lucine. This is the maximum count of
// objects treated for each cron_index.php call. You can temporary increase this number
// for very large databases to limit the number of cron_index.php calls to do manually.
if( !defined('LUCENE_INDEX_MAXDOCS') ) {
   define( 'LUCENE_INDEX_MAXDOCS', 100 );
}