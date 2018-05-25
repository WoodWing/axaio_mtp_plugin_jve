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
 * Lucene Search integration - Connector to the CreateObjects workflow service
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjects_EnterpriseConnector.class.php';

class Lucene_WflCreateObjects extends WflCreateObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	// Not called. See getRunMode.
	final public function runBefore( WflCreateObjectsRequest &$req )
	{
	}
	
	final public function runAfter( WflCreateObjectsRequest $req, WflCreateObjectsResponse &$resp ) 
	{
		require_once dirname(__FILE__) . '/Lucene.class.php';
		if( LUCENE_SYNCHRONOUS_INDEX ) {
			$lucene = new Lucene;
			$lucene->indexObjects( $resp->Objects );
		}
	}
	
	// Not called. See getRunMode.
	final public function runOverruled( WflCreateObjectsRequest $req ) 
	{
	}
}
