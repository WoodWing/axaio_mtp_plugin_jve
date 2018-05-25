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
 * Lucene Search integration - Windows helper class to call scheduled tasks
 *
 * Call this script from from Windows command line scheduler task to run the cron_index.php, 
 * cron_optimize with curl library support.
 */

$url = $argv[2]; // Get the url parameter that pass in through command line

$ch = curl_init($url);

curl_exec($ch);

curl_close($ch);
?>