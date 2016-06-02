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
 * Lucene Search integration - The indexer
 *
 * Call this script from crontab to optimize Enterprise documents that are not indexed yet.
 * One call will index LUCENE_INDEX_MAXDOCS documents to prevent timeouts.
 */

require_once '../../../config/config.php';
require_once BASEDIR.'/server/authorizationmodule.php';
require_once dirname(__FILE__) . '/config.php'; // LUCENE_INDEX_MAXDOCS
require_once dirname(__FILE__) . '/Lucene.class.php';

$index = !(bool)isset($_GET['unindex']);

set_time_limit( LUCENE_INDEX_MAXDOCS*3 ); // Set high timeout. Default: 100 * 3 = 300 sec = 5 minutes

LogHandler::Log( 'Lucene', 'DEBUG', 'Cron Indexer started' );

$lucene = new Lucene;
if( $index ) {
	$treated = $lucene->indexObjectsFromDB( LUCENE_INDEX_MAXDOCS );
	LogHandler::Log( 'Lucene', 'DEBUG', "Lucene: $i objects indexed" );
	print("Lucene: $treated object(s) indexed"); // Print some output to see results in browser or terminal
} else {
	$treated = $lucene->unindexObjectsFromDB( LUCENE_INDEX_MAXDOCS );
	LogHandler::Log( 'Lucene', 'DEBUG', "Lucene: $i objects unindexed" );
	print("Lucene: $treated object(s) unindexed"); // Print some output to see results in browser or terminal
}
$todo = $lucene->countObjectsToIndex( $index );
print(", $todo object(s) still to do");

LogHandler::Log( 'Lucene', 'DEBUG', 'Cron Indexer ended' );
