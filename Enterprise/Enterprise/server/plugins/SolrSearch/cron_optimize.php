<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	2008-2009 WoodWing Software bv. All Rights Reserved.
 *
 * Search integration - The index optimizer
 *
 * Call this script from crontab to optimize the index.
 *
 */

require_once dirname(__FILE__) . '/../../../config/config.php';
require_once BASEDIR.'/config/config_solr.php';
require_once BASEDIR.'/server/plugins/SolrSearch/SolrSearchEngine.class.php';

set_time_limit(900); // Set high timeout, just in case. 900 seconds, 15 minutes

LogHandler::Log( 'Search', 'DEBUG', 'Cron Optimizer started' );

$searchEngine = new SolrSearchEngine;
$searchEngine->optimize( );

LogHandler::Log( 'Search', 'DEBUG', 'Cron Optimizer ended' );

// Print some output to see results in browser or terminal:
print('Optimize completed');
