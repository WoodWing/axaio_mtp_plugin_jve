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
 * Lucene Search integration - Connector to the DeleteObjects workflow service
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjects_EnterpriseConnector.class.php';

class Lucene_WflDeleteObjects extends WflDeleteObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	// Not called. See getRunMode.
	final public function runBefore( WflDeleteObjectsRequest &$req )
	{
		$req = $req; // make code analyzer happy
	}
	
	final public function runAfter( WflDeleteObjectsRequest $req, WflDeleteObjectsResponse &$resp ) 
	{
		$resp = $resp; // make code analyzer happy
		require_once dirname(__FILE__) . '/Lucene.class.php';
		if( LUCENE_SYNCHRONOUS_INDEX ) {
			$lucene = new Lucene;
			$lucene->unindexObjects( $req->IDs, true ); // use smart_deletedobjects table!
		}
	}
	
	// Not called. See getRunMode.
	final public function runOverruled( WflDeleteObjectsRequest $req ) 
	{
		$req = $req; // make code analyzer happy
	}
}
