<?php
/****************************************************************************
   Copyright 2008-2010 WoodWing Software BV

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
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * An admin page which allows user to start/stop extraction of plain content for Enterprise objects
 * that were already present in DB before Tika server plug-in got installed.
 * A progress bar shows how many objects are extracted in percentage of the objects stored 
 * at the Enterprise DB that are candidate for extraction (matching object type and format).
 * It loads an HTML template, which works like a stand-alone client. It talks through Ajax
 * to this server page, which works like a service. The service returns home brewed XML packages
 * to the client app, which then updates the progress bar with arrived progress info.
 */

if( file_exists('../../../config/config.php') ) {
	require_once '../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
	require_once '../../../Enterprise/config/config.php';
}

require_once BASEDIR.'/server/secure.php';
set_time_limit(3600);

checkSecure('admin');

// Dispatch command
$command = isset($_REQUEST['command']) ? $_REQUEST['command'] : 'LoadPage';
switch( $command ) {
	case 'LoadPage': // Request to return the html page (which then will fire InitPage command).
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$tpl = HtmlDocument::loadTemplate( dirname(__FILE__).'/contentextraction.htm' );
		//$template = true, $body = null, $flexwidth=false, $fromlicensedir = false, $wwtest=false
		print HtmlDocument::buildDocument( $tpl, true, null, false, true );
	break;

	case 'RetryErrorObjs':
		TikaUtils::clearErrorObjs();
		
	// continue !!! ...
		
	case 'InitPage': // Request to initially draw progress bar
		$max = TikaUtils::countObjectsToExtract( 'all' );
		$todo = TikaUtils::countObjectsToExtract( 'todo' );
		$errObjs = TikaUtils::countObjectsToExtract( 'error' );
		$messages = array();

		header( 'Content-Type: text/xml' );
		print TikaUtils::getProgressAsXml( $max, $max - $todo, $todo == 0, 0, $messages, $errObjs );
	break;

	case 'ExtractionStep': // Request perform one step and update progress bar with results
		$max = TikaUtils::countObjectsToExtract( 'all' );
		$prevTodo = TikaUtils::countObjectsToExtract( 'todo' );

		// Get the plugin object
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$pluginObj = BizServerPlugin::getPluginForConnector( 'Tika_MetaData' );

		$errMsg = '';
		$messages = array();
		$connector = null;
		if ( !$pluginObj || !$pluginObj->IsInstalled ) {
			$errMsg = 'Tika server plug-in not installed! '; // should never happen
		} else if( !$pluginObj->IsActive ) {
			$errMsg = 'The "'.$pluginObj->DisplayName.'" server plug-in not enabled! ';
		} else {
			$connector = BizServerPlugin::searchConnectorByPluginId('MetaData', null, $pluginObj->Id );
			if( is_null($connector) ) {
				$errMsg = 'Tika MetaData connector of server plug-in not installed! '; // should never happen
			}
		}
		if( $errMsg ) {
			$errMsg .= 'Check <a href="'.SERVERURL_ROOT.INETROOT.'/server/admin/serverplugins.php">Server Plug-ins</a> ';
			$messages['ERROR'][] = $errMsg;
			$errObjs = TikaUtils::countObjectsToExtract( 'error' );
			print TikaUtils::getProgressAsXml( $max, $max - $prevTodo, $prevTodo, 0, $messages, $errObjs );
			break;
		}

		try {
			$lastObjId = $_REQUEST['lastObjId'];
			$stepSize = 10;
			TikaUtils::extractContentForDBObjects( $lastObjId, $stepSize ); // also updates $lastObjId
		} catch( BizException $e ) {
			$messages['ERROR'][] = $e->getMessage();
		}

		$todo = TikaUtils::countObjectsToExtract( 'todo' );
		$errObjs = TikaUtils::countObjectsToExtract( 'error' );
		$done = ($todo == $prevTodo);
		$response = TikaUtils::getProgressAsXml( $max, $max - $todo, $done, $lastObjId, $messages, $errObjs );
		//LogHandler::logSOAP( 'ContentExtraction', $response, false ); // heavy debugging only

		header( 'Content-Type: text/xml' );
		print $response;
	break;
}


class TikaUtils
{
	// IMPORATANT: The following set should match type checking done in BizMetaDataPreview::readMetaData() !
	static private $objTypes = array('Article', 'Spreadsheet', 'Other', 'Image', 'Advert', 'Presentation', 'Archive');
	
	/**
	 * Retrieves plain content for rich native object file and updates the object record DB.
	 * Also the slugline is derived from plain content and updated at DB.
	 * On failure, plaincontent and slugline are set to '?' to avoid picking up problematic
	 * files that else would fail over-and-over again. When empty content the '-' char is taken.
	 *
	 * @param string $lastObjId The last updated object id.
	 * @param integer $stepSize Amount of objects to handle in this step.
	 */
	static public function extractContentForDBObjects( &$lastObjId, $stepSize )
	{
		require_once dirname(__FILE__).'/config.php';
		require_once dirname(__FILE__).'/TikaServerProxy.class.php';
		require_once dirname(__FILE__).'/TikaDBObjects.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizStorage.php'; //StorageFactory

		// Determine what objects to process in this step
		$formats = unserialize(TIKA_FORMATS);
		$rows = TikaDBObjects::getObjectsToExtract( $lastObjId, $stepSize, self::$objTypes, $formats, '' );
		if( $rows ) foreach( $rows as $row ) {

			// Get native from DB; do NOT use GetObjects since we are in repair mode (doing things silently)
			$plainContent = '';
			$types = unserialize($row['types']);
			$tp = $types['native'];
			if( $tp ) {
				$attachobj = StorageFactory::gen( $row['storename'], $row['id'], 'native', $tp, $row['version'] );
				if( $attachobj ) {
					if( $attachobj->doesFileExist() ) {

						// Retrieve plain content using Tika Server
						$plainContent = TikaServerProxy::extractPlainContent( $attachobj->getFileContent() );
						if( is_null($plainContent) ) { // error?
							$plainContent = '?'; // mark as error!  (avoiding processing over-and-over again)
						}
					}
				}
			}
			if( empty($plainContent) ) { // no content?
				$plainContent = '-'; // mark as processed!  (avoiding processing over-and-over again)
			}			
			// Update plain content in Enterprise DB
			$modifier = $modified = ''; // just repair; let's leave no trail
			$slugline = mb_strcut( $plainContent, 0, 250, 'UTF-8' );
			$arr = array( 'plaincontent' => $plainContent, 'slugline' => $slugline, 
				'indexed' => '' ); // clear indexed flag to trigger Solr later on!
			DBObject::updateObject( $row['id'], $modifier, $arr, $modified );
		}
	}

	/**
	 * Clears plaincontent and slugline fields when flagged with '?' indicating problematic extractions.
	 * This way, admin users can re-try content extraction. For example, when Tika was down of after
	 * the Tika application was updated with new version that poptentially would fix problems.
	 */
	static public function clearErrorObjs()
	{
		require_once dirname(__FILE__).'/config.php';
		require_once dirname(__FILE__).'/TikaDBObjects.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		$formats = unserialize(TIKA_FORMATS);
		$rows = TikaDBObjects::getObjectsToExtract( 0, 0, self::$objTypes, $formats, '?' );
		if( $rows ) foreach( $rows as $row ) {
			$modifier = $modified = ''; // just repair; let's leave no trail
			$arr = array( 'plaincontent' => '', 'slugline' => '' );
			DBObject::updateObject( $row['id'], $modifier, $arr, $modified );
		}
	}
	
	/**
	 * Determines how many objects are candidate for content extraction.
	 * This is mainly based on configured file formats and fixed objects types.
	 * But, also on the current plaincontent at records and the given request mode.
	 *
	 * @param string $mode What kind of objects to count. Possible values:
	 * - 'error': The ones flagged with '?' for which extraction has failed in the past.
	 * - 'todo' : The ones for which plain content is (still) set empty.
	 * - 'all'  : The objects which fit into the content extraction scope (looking at format/types only).
	 * @return object count.
	 */
	static public function countObjectsToExtract( $mode )
	{
		require_once dirname(__FILE__).'/config.php';
		require_once dirname(__FILE__).'/TikaDBObjects.class.php';

		$formats = unserialize(TIKA_FORMATS);
		if( $mode == 'todo' ) {
			$pcEquals = '';
		} elseif( $mode == 'error' ) {
			$pcEquals = '?';
		} else {
			$pcEquals = null;
		}
		return TikaDBObjects::countObjectsToExtract( self::$objTypes, $formats, $pcEquals );
	}

	/**
	 * Returns an XML document (as string) that contains progress data to be sent to client app.
	 * This is not SOAP but SSBMULCP; super simple home brewed ultra light communication protocal... ;-)
	 *
	 * @param integer $max          Number of objects in DB that are candidate for extraction.
	 * @param integer $progress     Number of extracted objects.
	 * @param boolean $runCompleted Tells if all extraction iteration steps are taken.
	 * @param string  $lastObjId    Last proccessed object id for extraction.
	 * @param array   $message      Categorized list of messages to show user (array in array). Indexes for inner array are 'ERROR', 'WARN' or 'INFO'.
	 * @return string XML response
	 */
	static public function getProgressAsXml( $max, $progress, $runCompleted, $lastObjId, $messages, $errObjs )
	{
		if( $max == 0 || $runCompleted ) {
			$messages['INFO'][] = 'There are no objects to extract. Your database is already up-to-date.';
		
			// Add warning when there are some objects not indexed yet.
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$objCnt = DBObject::countObjectsToIndex( true );
			if( $objCnt > 0 ) {
				$messages['WARN'][] = $objCnt.' objects have not been indexed yet and will therefore not appear in the search results. '.
					'Click <a href="'.SERVERURL_ROOT.INETROOT.'/server/admin/searchindexing.php">here</a> to start indexing from the Search Server Maintenance page.';
			}
		}

		// Create XML output stream to return caller
		$xmlDoc = new DOMDocument();
		$xmlReport = $xmlDoc->createElement( 'ProgressResponse' );
		$xmlDoc->appendChild( $xmlReport );

		$xmlBar = $xmlDoc->createElement( 'ExtractionProgressBar' );
		$xmlReport->appendChild( $xmlBar );
		self::createTextElem( $xmlDoc, $xmlBar, 'Maximum', $max );
		self::createTextElem( $xmlDoc, $xmlBar, 'Progress', $progress );
		self::createTextElem( $xmlDoc, $xmlBar, 'RunCompleted', $runCompleted ? 'true' : 'false' );
		self::createTextElem( $xmlDoc, $xmlBar, 'LastObjId', $lastObjId );
		self::createTextElem( $xmlDoc, $xmlBar, 'ErrorObjs', $errObjs ? 'There are '.$errObjs.' objects in your database for which content extraction has failed. Click Retry to process those objects again.' : '' );

		if( $messages ) {
			$xmlMessages = $xmlDoc->createElement( 'Messages' );
			$xmlReport->appendChild( $xmlMessages );
			foreach( $messages as $severity => $messageBag ) {
				foreach( $messageBag as $message ) {
					$xmlMessage = self::createTextElem( $xmlDoc, $xmlMessages, 'Message', $message );
					$xmlMessage->setAttribute( 'severity', $severity );
				}
			}
		}
		
		$ret = $xmlDoc->saveXML(); // return XML stream to caller
		//LogHandler::Log( 'Search', 'DEBUG', $ret );
		return $ret;
	}

	/**
	 * Creates new wrapper element node with a new text element inside that contains given text string.
	 *
	 * @param DOMDocument $xmlDoc Document to be mutated.
	 * @param DOMNode $xmlParent Node under which the new node must be created.
	 * @param string $nodeName Name of XML node that gets created between parent and text node.
	 * @param string $nodeText The text data to add to text node.
	 * @return DOMNode The wrapper node that contains the text node.
	 */
	static private function createTextElem( $xmlDoc, $xmlParent, $nodeName, $nodeText )
	{
		$xmlNode = $xmlDoc->createElement( $nodeName );
		$xmlParent->appendChild( $xmlNode );
		$xmlText = $xmlDoc->createTextNode( $nodeText );
		$xmlNode->appendChild( $xmlText );
		return $xmlNode;
	}
}