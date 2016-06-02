<?php
/****************************************************************************
   Copyright 2009 WoodWing Software BV

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
 * @since 		v7.0
 *
 * Search integration - Library class implenting Imprezzeo search
 */

require_once BASEDIR.'/server/ZendFramework/library/Zend/Http/Client.php'; 
require_once BASEDIR .'/server/bizclasses/BizQuery.class.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once dirname(__FILE__) . '/config.php';


class ImprezzeoSearchEngine extends BizQuery
{
	protected $search = ''; //Query sent to engine
	protected $firstEntry = 0; //Serail number of the first returned object
	protected $maxEntries = 0; //Maximuum number to be returned
	protected $searchParams = array(); //Passed search parameters
	protected $resultFirstEntry	= 0; //Offset
	protected $resultListedEntries	= 0; //Number of results returned
	protected $resultTotalEntries = 0; //Total number found (can be more than the returned number)
	protected $columns = array(); //Columns to return
	protected $results = array(); //Result of search action
	protected $handledObjectIds = array(); //Objecs Ids indexed/unindexed
	protected $minimalProps = null; //Minimal properties requested
	protected $requestProps = null; //Extra properties requested
	protected $mode = null; //client
	
	protected $childRows = array(); //Child rows array for the queryObjectRows function
	protected $componentColumns = array(); //Component columns array for the queryObjectRows function
	protected $components = array(); //Components array for the queryObjectRows function
	protected $facets = null; //Contains the facets of the result set
	protected $shortUserName = '';
	protected $ticket = '';	

	/**
	 * 'More like this' search for one or more specified images.
	 * See Optimize for performance notes.
	 * 
	 * @param array $areas Workflow (workflow Objects) or Trash(Deleted objects)
	 * @return array of Imprezzeo hits
	*/
	public function mltSearch( $areas = array('Workflow') )
	{
		PerformanceProfiler::startProfile( 'Imprezzeo', 3 );
		$this->buildQuery();
		$objectIds = array();
		$scores = array();
		$xml = $this->httpRequestXML($this->search);
		if( isset($xml) ) {
			$result = $xml->xpath('//return-code');
			if( empty($result) || $result[0] != 0 ) {
				$msg = $xml->xpath('//error-message');
				$msg = 'Imprezzeo: '.(string)$msg[0];
				LogHandler::Log('Imprezzeo', 'ERROR',  $msg );
				throw new BizException( '', 'Server', $msg, $msg );
			}
			$images = $xml->xpath('//image');
			foreach( $images as $image ) {
				$xmlAttr1 = $image->xpath('@image-id');
				$xmlAttr2 = $image->xpath('@similarity');
				$imageID = (string)$xmlAttr1[0];
				$scores[$imageID] = (string)$xmlAttr2[0]; 
				$objectIds[$imageID] = $imageID;
			}
		}		
		PerformanceProfiler::stopProfile( 'Imprezzeo', 3 );
		
		$this->results = $this->buildRows($objectIds, $scores, $areas );

		if (!empty($this->results)) {
			$this->columns = self::getColumns($this->results);
		}
		else {
			$requestedpropnames = self::getPropNames('contentstation', null, null, $areas );
			$this->columns = self::getColumns(array(array_flip($requestedpropnames)));
		}
		
		$this->results = self::getRows($this->results);		
	}


		
	public function getResultFirstEntry()
	{
		$this->resultFirstEntry	= $this->firstEntry + 1;
		return $this->resultFirstEntry;
	}

	public function getResultListedEntries()
	{
		return $this->resultListedEntries;
	}
	
	public function getResultTotalEntries()
	{
		return $this->resultTotalEntries;	
	}
	
	public function getResultColumns()
	{
		return $this->columns;
	}
	
	public function getResults()
	{
		return $this->results;	
	}
	
	public function getHandledObjectIds()
	{
		return $this->handledObjectIds;
	}
		
	public function getFacets()
	{
		return $this->facets;
	}
		
	/**
	 * Based on the Search Params a query is build. Query is based on object ids.
	 * Multiple object ids can passed to get MLT results based on common characteristics.
	 */
	private function buildQuery()
	{
		$imageElements = '';
		$paramsRestrictions = array();
		foreach ($this->searchParams as $param) {
			if ($param->Property == 'ID') {
			 	$imageElements .= '<image id="'.$param->Value.'"></image>';
			} 	
			else {
				$paramsRestrictions[] = $param;
			}
		}
		// for Imprezzeo we are only interested in Images.
		$param = new QueryParam('Type', '=', 'Image', false);
		$paramsRestrictions[] = $param;
		
		// Restrict the images set to the entered selection criteria.
		// queryObjects also handles access rights.
		$restrictions = self::queryObjects($this->ticket, $this->shortUserName, $paramsRestrictions, null, 0, 0, null, false, null, null, null);
		
		//Find the index that points to ID
		$index = 0;
		foreach($restrictions->Columns as $property) {
			if ($property->Name == 'ID') {
				break; 
			}
			$index += 1;
		}
		
		// Add the found image IDs to restrict 
		$idRestricted = '';
		foreach($restrictions->Rows as $row) {
			$idRestricted .= '<restriction id="'.$row[$index].'"></restriction>';
		}
		
		LogHandler::Log( 'Imprezzeo', 'DEBUG', "Finding More Like This images in Imprezzeo" );

		//Get all relevant images from imprezzeo. Later on during buildRows we take the right slice to return.
		$this->search = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cbir="http://imprezzeo.com/Cbir/">
   					 	<soapenv:Header/><soapenv:Body><cbir:Query>'.
					    $imageElements.$idRestricted.
      					'</cbir:Query></soapenv:Body></soapenv:Envelope>';			    
	}
	
	/**
	 * Based on the search result object rows are composed.
	 * If needed Keywords are merged into a string. Scores are added to the result
	 * set. Based on the actual result set of imprezzeo the facets are composed.
	 *
	 * @param array $objectIds object ids 
	 * @param array $scores
	 * @param array $areas Workflow or Trash
	 * @return array of object rows
	 */
	private function buildRows ($objectIds, $scores, array $areas)
	{
		if (empty($objectIds)) {
			return array(); 
		}
		
		$sortedRows = self::validateSearchResultsAgainsDB($objectIds, 'Imprezzeo', $areas);
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$displayNameScore = BizResources::localize('SEARCH_SCORE');

		$objectRows = self::queryObjectRows($sortedRows, 'contentstation', $this->minimalProps, null, false, $this->childRows, $this->componentColumns, $this->components, $areas);
		
		//Restore original sorting
		$queryParams = array();
		foreach ($objectRows as &$objectRow) {
			$id = $objectRow['ID'];
			$queryParams[] = new QueryParam('ID', '=', "$id", false); 
			if (isset($scores[$id])){
				$objectRow[$displayNameScore] = sprintf("%01.3f", round($scores[$id], 3));
				//Round to max 3 decimals and show all scores with 3 decimals 		
			}
			$sortedRows[$id] = $objectRow;
		}
		
		//Compose facets
		$queryResult = self::queryObjects($this->ticket, $this->shortUserName, $queryParams, null, 0, 0, null, false, null, null, null);
		if (property_exists($queryResult, 'Facets')) {
			$this->facets = $queryResult->Facets;
		}	
		$this->resultTotalEntries = count($sortedRows);
		// Get the right slice to return for paging.
		$sortedRows = array_slice($sortedRows, $this->firstEntry, $this->maxEntries);
		$this->resultListedEntries = count($sortedRows);
		
		return $sortedRows;
	}
	
	/**
	 * Adds Enterprise objects to the index.
	 * 
	 * @param array of Object	$object		Enterprise objects to index
	 * @param array $areas
	 * @return void
	*/
	public function indexObjects( $objects, $areas = array('Workflow') )
	{
		PerformanceProfiler::startProfile( 'Imprezzeo', 3 );
		foreach( $objects as $object ) {
			$this->indexObject($object, $areas);
		}
		    
		PerformanceProfiler::stopProfile( 'Imprezzeo', 3 );
	}	
	
	/**
	 * Adds image object to Imprezzeo Image index
	 * Adds object to the index, its individual props are passed.
	 * 
	 * @param object Enterprise object
	 * @param array $areas
	 * @return void
	*/
	public function indexObject( $object, array $areas)
	{
		LogHandler::Log( 'Imprezzeo', 'DEBUG', 'Adding image to Imprezzeo' );
		PerformanceProfiler::startProfile( 'Imprezzeo', 3 );
		
		//In case the object is not an Image it cannot be indexed by Imprezzeo.
		//The id of the object is added to the list of indexed objects (because
		//there is no failure).
		$objectID = $object->MetaData->BasicMetaData->ID;
		if (!$this->canHandleObject($object)) {
			$this->handledObjectIds[] = $objectID;
			return;
		}
		
		// We need to pass Imprezzeo a URL to get the image. A preview rendition is sufficient.
		// We use image.php with ticket, object id and rendition as params.
		// When called from Mover and passing Mover ticket image.php hangs.... Didn't investigate this yet, as work-around doing a re-logon for that case
		// Ticket can also be empty (when called by cron_index		
		$ticket = BizSession::getTicket(); //session_id();
		if( !empty($ticket) ) {
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			$appName = DBTicket::DBappticket( $ticket );
			if( substr_compare( $appName, 'Mover',0, 5 ) == 0 ) {
				$ticket = '';
			}
		}
		
		$tempTicket = false;
		//If no ticket use the imprezzeo user to get a temporary ticket
		if( empty( $ticket) ) {
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			$dum1=''; $dum2='';
			$ticket = DBTicket::genTicket( IMPREZZEO_WWUSER, IMPREZZEO_WWUSER, 'This Server', '' /*clientname*/, 'Imprezzeo'.rand(), // duplicate name would logoff other for same user
											'1.0' /*appversion*/, '' /*$appserial*/, ''/*$appproductcode*/, $dum1, $dum2 ); 
			$tempTicket = true;
		}
		
		$postData = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cbir="http://imprezzeo.com/Cbir/">
   					 	<soapenv:Header/><soapenv:Body><cbir:FeatureImport>
					        	<image id="'.$objectID.'" url="' . SERVERURL_ROOT . INETROOT . '/server/apps/image.php?ticket='.$ticket.'&amp;id='.$objectID.'&amp;rendition=preview&amp;areas='.$areas['0'].'"/>
      					</cbir:FeatureImport></soapenv:Body></soapenv:Envelope>';

		$xml = $this->httpRequestXML( $postData );

		//Remove temporary ticket
		if ($tempTicket) {
			DBTicket::DBendticket( $ticket );
		}	
		
		if( $xml ) { // $xml empty means it's Ok
			$this->handleError($xml);
		}
		else {
			$this->handledObjectIds[] = $objectID;
		}
		
		PerformanceProfiler::stopProfile( 'Imprezzeo', 3 );
		LogHandler::Log( 'Imprezzeo', 'DEBUG', 'Image ('.$objectID.') added to Imprezzeo' );
	}
	
	/**
	 * Removes image objects from Imprezzeo Image index
	 * 
	 * @param array Enterprise object ids
	 * @return void
	*/	
	public function unindexObjects($objectIds, $deletedObject) 
	{
		PerformanceProfiler::startProfile( 'Imprezzeo', 3 );
		LogHandler::Log( 'Imprezzeo', 'DEBUG', "Remove index from Imprezzeo" );
		if( count( $objectIds )  > 0 ) { 
			foreach( $objectIds as $id ) {
				$this->unIndexObject($id, $deletedObject);
			}
		} else {
			/**
			 * TODO: should by done by a simple query. Imprezzeo doesn't support such a query, so send
			 * all the image ids.
			 */
			$dbDriver = DBDriverFactory::gen();
			$dbo = $dbDriver->tablename('objects');
			
			// First get the count of all the image ids
			$result = $dbDriver->query("SELECT COUNT(o.`id`) AS `count` FROM $dbo o WHERE type = 'Image'");
			$count = 0;
			while($row = $dbDriver->fetch($result)) {
				$count = $row['count'];
			}
			
			// Set the current row for the limit functionality
			$currentRow = 0;
			// Set the row count per step. 
			$rowsStep = 2500;
			
			// Send the ids to Imprezzeo per rowsStep
			while($currentRow < $count) {
				$sql = "SELECT o.`id` FROM $dbo o WHERE type = 'Image'";
				$sql = $dbDriver->limitquery( $sql, $currentRow, $rowsStep );
				$result = $dbDriver->query($sql);
				
				$postData = 
					'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cbir="http://imprezzeo.com/Cbir/">
			   			<soapenv:Header/>
			   				<soapenv:Body>
			      				<cbir:FeatureRemove>
			         				<!--1 or more repetitions:-->\n';
				while ($row = $dbDriver->fetch($result)) {
					$postData .= '<image id="'.$row['id'].'"/>\n';
				}
			      				
			    $postData .= '	</cbir:FeatureRemove>
			   				</soapenv:Body>
						</soapenv:Envelope>';
			
				$this->httpRequestXML( $postData );
				
				$currentRow += $rowsStep;
			}
		}
		PerformanceProfiler::stopProfile( 'Imprezzeo', 3 );
	}
	
	/**
	 * Removes image object from Imprezzeo Image index
	 * 
	 * @param Enterprise object id
	 * @return void
	*/	
	private function unIndexObject($objectId, $deletedObject)
	{
		//In case the object is not an Image it cannot be indexed by Imprezzeo.
		//The id of the object is added to the list of indexed objects (because
		//there is no failure).
		if (!$this->canHandleUnindexObject($objectId, $deletedObject)) {
			$this->handledObjectIds[] = $objectId;
			return;
		}
		
		$postData = 
		'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cbir="http://imprezzeo.com/Cbir/">
		   <soapenv:Header/>
		   <soapenv:Body>
		      <cbir:FeatureRemove>
		         <!--1 or more repetitions:-->'.
		         '<image id="'.$objectId.'"/>'.
		      '</cbir:FeatureRemove>
		   </soapenv:Body>
		</soapenv:Envelope>';
		
		$xml = $this->httpRequestXML( $postData );
		
		if( $xml ) { // $xml empty means it's Ok
			$this->handleError($xml);
		}
		else {
			$this->handledObjectIds[] = $objectId;
		}

		LogHandler::Log( 'Search', 'DEBUG', 'Image removed from Imprezzeo' );	
	}
	
	/**
	 * Constructor 
	 *
	 * Establish and test Imprezzeo connection
	 */
	public function __construct ($searchParams = array(), $firstEntry = 0, $maxEntries = 0, $mode = null,  $order = array(),  $minimalProps = null, $requestProps = null)
	
	{
		$this->searchParams = $searchParams;
		$this->firstEntry = $firstEntry;
		$this->maxEntries = $maxEntries;
		$this->order = $order;
		$this->mode = $mode;
		$this->minimalProps = $minimalProps;
		$this->requestProps = $requestProps;
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$this->shortUserName = BizSession::getShortUserName();
		$this->ticket = BizSession::getTicket();			
	}	
	
	/**
	 * @throws BizException on error
	 */
    private function httpRequestXML( $postData )
    {
    	$xmlString = $this->httpRequest( $postData );

        Try {
            $xml  = @simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
        }  
        catch (Exception $e) {
            LogHandler::Log('Imprezzeo', 'ERROR','Caught exception: ',  $e->getMessage());
        }
        
        // Note: empty response means it's Ok.
        return $xml;
	}	
	
	/**
	 * @throws BizException on error
	 */
	 private function httpRequest( $postData )
	{
    	$retVal = '';
    	$url = 'http://'.IMPREZZEO_SERVER.':'.IMPREZZEO_PORT.IMPREZZEO_PATH;
    	
		try {
			$http = new Zend_Http_Client();
			$http->setUri( $url );

			if( IMPREZZEO_PROXY != '' ) {
				$config = array( 'http' => array( 'proxy' => IMPREZZEO_PROXY ) );
				$http->setConfig( $config );
			}

			$response  = $http->setRawData($postData, 'text/xml')->request('POST');

			if( $response->isSuccessful() ) {
				$retVal = $response->getBody();
			} else {
				$this->handleHttpError( $response );
			}
		} catch (Zend_Http_Client_Exception $e) {
            LogHandler::Log('Imprezzeo', 'ERROR','Caught exception: ', $e->getMessage());
            throw new BizException( '', 'Server', '', 'Could not connect to Imprezzeo' );
		}
		return $retVal;
	}	
	
	/**
	 * Checks status and throws exception on communication errors.
	 * Assumed is that response is an error.
	 * 
	 * @param Zend_Http_Response $response
	 * @throws BizException on error
	 */
	private function handleHttpError( $response )
	{
		$responseHeaders = $response->getHeaders();
		$contentType = $responseHeaders['Content-type'];
		$respBody = $response->getBody();
		$respStatusCode = $response->getStatus();
		$respStatusText = $response->responseCodeAsText( $respStatusCode );
		
		if( $contentType == 'text/html' && 
   			$respStatusCode == 500 && 
  			($msg = self::getErrorFromHtmlPage($respBody)) ) {
			$msg = 'Imprezzeo error: '.$msg;
	   	    LogHandler::Log('Imprezzeo', 'ERROR',  $respBody ); // dump entire HTML page
    	}
		$msg = "Imprezzeo connection problem: $respStatusText (HTTP code: $respStatusCode)";
		LogHandler::Log('Imprezzeo', 'ERROR',  $respBody ); // dump entire HTML page
	}	
	
	/**
	 * Picks error information from Imprezzeo response and writes it to the log file.
	 *
	 * @param xml $xml xml error response.
	 */
	private function handleError($xml)
	{
		$result = $xml->xpath('//return-code');
		if( empty($result) || $result[0] != 0 ) {
			$msg = $xml->xpath('//error-message');
			$msg = 'Imprezzeo: '.(string)$msg[0];
			LogHandler::Log('Imprezzeo', 'ERROR',  $msg );
		}	
	}
	
	/**
	 * Checks if object type is supported by search engine.
	 *
	 * @param object $object Enterprise object.
	 * @return type is supported (true/false).
	 */
	private function canHandleObject($object)
	{
		$objectType = $object->MetaData->BasicMetaData->Type;
		
		require_once dirname(__FILE__) .'/Imprezzeo_Search.class.php';
		$result = Imprezzeo_Search::supportedObjectTypes($objectType);
		
		return $result;
	}
	
	/**
	 * Checks if an object can be unindexed. 
	 *
	 * @param integer $objectId
	 * @param boolean $deletedObject object is in smart_deletedobjects or smart_objects.
	 * @return type is supported (true/false).
	 */
	private function canHandleUnindexObject($objectId, $deletedObject)
	{
		if ($deletedObject) {
			require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
			$objectType = DBDeletedObject::getObjectType($objectId);
		}
		else {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$objectType = DBObject::getObjectType($objectId);
		}
		
		require_once dirname(__FILE__) .'/Imprezzeo_Search.class.php';
		$result = Imprezzeo_Search::supportedObjectTypes($objectType);
		
		return $result;
	}
}
