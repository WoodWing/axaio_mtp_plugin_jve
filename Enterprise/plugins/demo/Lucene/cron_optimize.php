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
 * @since 		v6.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Lucene Search integration - The index optimizer
 *
 * Call this script from crontab to optimize the index.
 *
 */

require_once '../../../config/config.php';
require_once dirname(__FILE__) . '/Lucene.class.php';

set_time_limit(900); // Set high timeout, just in case. 900 seconds, 15 minutes

LogHandler::Log( 'Lucene', 'DEBUG', 'Cron Optimizer started' );

$lucene = new Lucene;
$lucene->optimize( );

LogHandler::Log( 'Lucene', 'DEBUG', 'Cron Optimizer ended' );

// Print some output to see results in browser or terminal:
print('Optimize completed');
