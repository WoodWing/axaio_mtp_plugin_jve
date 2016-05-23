<?php
/**
 * @package 	Enterprise
 * @subpackage 	AdminPages
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * An admin page which allows user to start/stop indexing/unindexing Enterprise objects
 * at the installed search engine/server (such as Solr).
 * A progress bar shows how many objects are indexed in percentage of the total objects stored 
 * at the Enterprise DB.
 * It loads an HTML template, which works like a stand-alone client. It talks through Ajax
 * to this server page, which works like a service. The service returns home brewed XML packages
 * to the client app, which then updates the progress bar with arrived progress info.
 */
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
set_time_limit(3600);

checkSecure('admin');

// Dispatch command
$command = isset($_REQUEST['command']) ? $_REQUEST['command'] : 'LoadPage';
switch( $command ) {
	case 'LoadPage': // Request to return the html page (which then will fire InitPage command).
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$tpl = HtmlDocument::loadTemplate( 'searchindexing.htm' );
		//$template = true, $body = null, $flexwidth=false, $fromlicensedir = false, $wwtest=false
		print HtmlDocument::buildDocument( $tpl );
		break;

	case 'InitPage': // Request to initially draw progress bar
		require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
		
		$max = DBObject::countObjects() + DBDeletedObject::countDeletedObjects();
		$todoActObjects = DBObject::countObjectsToIndex( true );
		$todoDelObjects = DBDeletedObject::countDeletedObjectsToIndex( true );
		$todo = $todoActObjects + $todoDelObjects;
		$optLast = BizSearch::getLastOptimized();
		$optPro = 0;// Number optimized always zero except after finishing the optimize step
		
		$plugins = BizSearch::installedSearchServerPlugins();
		$errMsg = '';
		if( !count($plugins) > 0 ) {
			$errMsg = 'There is no Search Server installed.';
		}

		header( 'Content-Type: text/xml' );
		print SearchServerUtils::getProgressAsXml( $max, $max - $todo, $todo == 0, 0/*$lastObjId*/, 0/*$lastDeletedObjId*/, $todoDelObjects, $optPro, $optLast, $plugins, $errMsg );
	break;

	case 'IndexStep': // Request to index one step and update progress bar with results
	case 'UnindexStep':
		require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
		
		$lastObjId = $_REQUEST['lastObjId'];
		$lastDeletedObjId = $_REQUEST['lastDeletedObjId'];
		$progress = $_REQUEST['progress'];
		$todoDelObjects = $_REQUEST['todoDelObjects'];
		$max = DBObject::countObjects() + DBDeletedObject::countDeletedObjects(); // total Enterprise object AND deleteobject count
		$errMsg = '';
		$stepSize = 0;
		$prevTodo = 0;
		try {
			if( $command == 'IndexStep' ) {
				$prevTodo = $max - $progress; // Done equals total minus progress until now
				$areas = array('Workflow'); // Index the Workflow (smart-objects), first step
				$oneProcent = ceil($max / 100);
				if ( $oneProcent > 1000) {
					$stepSize = 1000;
				} elseif ( $oneProcent <= 50 ) {
					$stepSize = 50;
				} else {
					$stepSize = $oneProcent;
				}
				if(($prevTodo <= $todoDelObjects) && ($todoDelObjects > 0)){ //meaning Workflow area is done and switch to Trash area (smart_deletedobjects)
					//now check for Trash area
					$todoDelObjects = $todoDelObjects - $stepSize > 0 ? $todoDelObjects - $stepSize : 0;
					$areas = array('Trash');
				}
				BizSearch::indexObjectsFromDB( $lastObjId, $lastDeletedObjId, $stepSize, $areas );
			} else {
				$areas = array('Workflow','Trash');
				BizSearch::unIndexObjects( 
					null,    // all objects at once
					$areas, //both world: Workflow and Trash
					false ); // throw exceptions on errors
				$stepSize = 0; // Not applicable in case of optimization
				$prevTodo = $max; // All in one step
				$todoDelObjects = DBDeletedObject::countDeletedObjectsToIndex( true );
				// >>> Commented out: The below does not work when objects are moved to trash between index/unindex operations
				// $stepSize = 250; // unindexing goes faster, so take larger steps
				// BizSearch::unindexObjectsFromDB( $lastObjId, $stepSize );
				// <<<
			}
		} catch( BizException $e ) {
			$errMsg = $e->getMessage();
		}
		$todo = ($prevTodo - $stepSize) >= 0 ? ($prevTodo - $stepSize) : ($todoDelObjects > 0 ? $todoDelObjects : 0) ; // Todo = Todo of previous step minus Done in this step
		$done = (($todo <= 0 && $errMsg == '') || $command == 'UnindexStep'); // nothing (un)indexed and no error implies we're done
		$optLast = BizSearch::getLastOptimized();
		$optPro = 0; // Number optimized always zero expect after finishing the optimize step

		$response = SearchServerUtils::getProgressAsXml( $max, $max - $todo, $done, $lastObjId, $lastDeletedObjId, $todoDelObjects, $optPro, $optLast, null, $errMsg);
		//LogHandler::logSOAP( 'SearchIndexing', $response, false ); // heavy debugging only

		header( 'Content-Type: text/xml' );
		print $response;
	break;

	case 'OptimizeIndexes':
		require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
		$errMsg = '';
		try {
			BizSearch::optimizeIndexes();
		} catch( BizException $e ) {
			$errMsg = $e->getMessage();
		}
		$max = DBObject::countObjects() + DBDeletedObject::countDeletedObjects(); // total Enterprise object AND deleteobject count
		$todo = DBObject::countObjectsToIndex( true ) + DBDeletedObject::countDeletedObjectsToIndex( true ); // count of objects and deletedObjects that are marked as unindexed
		$done = (true && $errMsg == '');
		$lastObjId = 0;
		$lastDeletedObjId=0;
		$todoDelObjects = DBDeletedObject::countDeletedObjectsToIndex( true );
		$optLast = BizSearch::getLastOptimized();
		
		$optPro = $done ? $max : 0; // After optimze the number of optimized oobjects equals the total

		$response = SearchServerUtils::getProgressAsXml( $max, $max - $todo, $done, $lastObjId, $lastDeletedObjId, $todoDelObjects, $optPro, $optLast, null, $errMsg );
		//LogHandler::logSOAP( 'SearchIndexing', $response, false ); // heavy debugging only
		
		header( 'Content-Type: text/xml' );
		print $response;
	break;
}

class SearchServerUtils
{
	/**
	 * Returns an XML document (as string) that contains progress data to be sent to client app.
	 * This is not SOAP but SSBMULCP; super simple home brewed ultra light communication protocal... ;-)
	 *
	 * @param integer $max Maximum; Total number of objects in DB.
	 * @param integer $idxPro Progress; Number of indexed objects.
	 * @param boolean $idxRunCompleted; Tells if all iteration steps are taken.
	 * @param string $errMsg; Error message (if any).
	 * @return string XML response
	 */
	static public function getProgressAsXml( $max, $idxPro, $idxRunCompleted, $idxLastObjId, $idxLastDeletedObjId, $idxTodoDelObjects, $optPro, $optLast, $plugins, $errMsg )
	{
		// Create XML output stream to return caller
		$xmlDoc = new DOMDocument();
		$xmlReport = $xmlDoc->createElement( 'ProgressResponse' );
		$xmlDoc->appendChild( $xmlReport );

		$xmlBar = $xmlDoc->createElement( 'IndexProgressBar' );
		$xmlReport->appendChild( $xmlBar );
		self::createTextElem( $xmlDoc, $xmlBar, 'Maximum', $max );
		self::createTextElem( $xmlDoc, $xmlBar, 'Progress', $idxPro );
		self::createTextElem( $xmlDoc, $xmlBar, 'RunCompleted', $idxRunCompleted ? 'true' : 'false' );
		self::createTextElem( $xmlDoc, $xmlBar, 'LastObjId', $idxLastObjId );
		self::createTextElem( $xmlDoc, $xmlBar, 'LastDeletedObjId', $idxLastDeletedObjId );
		self::createTextElem( $xmlDoc, $xmlBar, 'TodoDelObjects', $idxTodoDelObjects );

		$xmlBar = $xmlDoc->createElement( 'OptimizeProgressBar' );
		$xmlReport->appendChild( $xmlBar );
		self::createTextElem( $xmlDoc, $xmlBar, 'Maximum', $max );
		self::createTextElem( $xmlDoc, $xmlBar, 'Progress', $optPro );
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		self::createTextElem( $xmlDoc, $xmlBar, 'LastOptimized', DateTimeFunctions::iso2date($optLast) );

		if( $plugins ) {
			$xmlPlugins = $xmlDoc->createElement( 'InstalledSearchServerPlugins' );
			$xmlReport->appendChild( $xmlPlugins );
			foreach( $plugins as $plugin ) {
				$xmlPlugin = $xmlDoc->createElement( 'Plugin' );
				$xmlPlugins->appendChild( $xmlPlugin );
				self::createTextElem( $xmlDoc, $xmlPlugin, 'DisplayName', $plugin->DisplayName );
			}
		}
		
		if( $errMsg ) {
			$xmlError = $xmlDoc->createElement( 'Error' );
			$xmlReport->appendChild( $xmlError );
			self::createTextElem( $xmlDoc, $xmlError, 'Message', $errMsg );
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