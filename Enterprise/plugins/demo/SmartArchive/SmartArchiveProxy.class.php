<?php

/**
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * This SmartArchive proxy acts as a SOAP client talking to the remote archive server.
 * It wraps GetObjects and QueryObjects services hiding all SOAP details and challenges 
 * from the calling PHP class.
 * When called once, it does implicit logon taking one seat (license) at the archive server.
 */

require_once dirname(__FILE__).'/config.php';
require_once BASEDIR.'/server/smartevent.php';

class SmartArchiveProxy
{
	private static $wflClient = null;    // SOAP client connected to archive server
	private static $logOnRequest = null; // LogOn request to fire against archive server
	private static $ticket = null;       // Session ticket as retrieved from archive server

	/**
	 * Creates a client SOAP proxy that connects to the archive server.
	 */
	static public function createClient()
	{
		if( !is_null(self::$wflClient) ) return; // bail out when nothing to do
		
		LogHandler::Log('SmartArchive', 'DEBUG', __METHOD__.': creating SOAP client proxy...');
		try {
			require_once BASEDIR.'/server/protocols/soap/Client.php';
			$options = array( 
				'location' => SMARTARCHIVE_SERVERURL,
				'uri' => 'urn:SmartConnection', 
				'use' => SOAP_LITERAL,
				'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
				'classmap' => array(),
				'soap_version' => SOAP_1_1 );

			// add our classmaps
			$options['classmap']['ActionProperty'] = 'ActionProperty';
			$options['classmap']['AppFeature'] = 'AppFeature';
			$options['classmap']['Attachment'] = 'Attachment';
			$options['classmap']['BasicMetaData'] = 'BasicMetaData';
			$options['classmap']['Category'] = 'Category';
			$options['classmap']['CategoryInfo'] = 'CategoryInfo';
			$options['classmap']['ChildRow'] = 'ChildRow';
			$options['classmap']['ContentMetaData'] = 'ContentMetaData';
			$options['classmap']['Dialog'] = 'Dialog';
			$options['classmap']['DialogTab'] = 'DialogTab';
			$options['classmap']['DialogWidget'] = 'DialogWidget';
			$options['classmap']['Edition'] = 'Edition';
			$options['classmap']['EditionPages'] = 'EditionPages';
			$options['classmap']['Element'] = 'Element';
			$options['classmap']['ExtraMetaData'] = 'ExtraMetaData';
			$options['classmap']['Facet'] = 'Facet';
			$options['classmap']['FacetItem'] = 'FacetItem';
			$options['classmap']['Feature'] = 'Feature';
			$options['classmap']['FeatureAccess'] = 'FeatureAccess';
			$options['classmap']['FeatureProfile'] = 'FeatureProfile';
			$options['classmap']['Issue'] = 'Issue';
			$options['classmap']['IssueInfo'] = 'IssueInfo';
			$options['classmap']['LayoutObject'] = 'LayoutObject';
			$options['classmap']['Message'] = 'Message';
			$options['classmap']['MetaData'] = 'MetaData';
			$options['classmap']['MetaDataValue'] = 'MetaDataValue';
			$options['classmap']['NamedQueryType'] = 'NamedQueryType';
			$options['classmap']['Object'] = 'Object';
			$options['classmap']['ObjectInfo'] = 'ObjectInfo';
			$options['classmap']['ObjectPageInfo'] = 'ObjectPageInfo';
			$options['classmap']['ObjectTargetsInfo'] = 'ObjectTargetsInfo';
			$options['classmap']['ObjectTypeProperty'] = 'ObjectTypeProperty';
			$options['classmap']['ObjectVersion'] = 'ObjectVersion';
			$options['classmap']['Page'] = 'Page';
			$options['classmap']['PageObject'] = 'PageObject';
			$options['classmap']['PlacedObject'] = 'PlacedObject';
			$options['classmap']['Placement'] = 'Placement';
			$options['classmap']['PlacementInfo'] = 'PlacementInfo';
			$options['classmap']['Property'] = 'Property';
			$options['classmap']['PropertyInfo'] = 'PropertyInfo';
			$options['classmap']['PropertyUsage'] = 'PropertyUsage';
			$options['classmap']['PubChannel'] = 'PubChannel';
			$options['classmap']['PubChannelInfo'] = 'PubChannelInfo';
			$options['classmap']['Publication'] = 'Publication';
			$options['classmap']['PublicationInfo'] = 'PublicationInfo';
			$options['classmap']['QueryOrder'] = 'QueryOrder';
			$options['classmap']['QueryParam'] = 'QueryParam';
			$options['classmap']['Relation'] = 'Relation';
			$options['classmap']['Rendition'] = 'Rendition';
			$options['classmap']['RightsMetaData'] = 'RightsMetaData';
			$options['classmap']['Section'] = 'Section';
			$options['classmap']['SectionInfo'] = 'SectionInfo';
			$options['classmap']['ServerInfo'] = 'ServerInfo';
			$options['classmap']['Setting'] = 'Setting';
			$options['classmap']['SourceMetaData'] = 'SourceMetaData';
			$options['classmap']['State'] = 'State';
			$options['classmap']['StickyInfo'] = 'StickyInfo';
			$options['classmap']['Target'] = 'Target';
			$options['classmap']['Term'] = 'Term';
			$options['classmap']['User'] = 'User';
			$options['classmap']['UserGroup'] = 'UserGroup';
			$options['classmap']['VersionInfo'] = 'VersionInfo';
			$options['classmap']['WorkflowMetaData'] = 'WorkflowMetaData';

			self::$wflClient = new WW_SOAP_Client( SMARTARCHIVE_SERVERURL.'?wsdl', $options );
		} catch( SoapFault $e ) {
			LogHandler::Log( 'SmartArchive', 'ERROR', __METHOD__.': '.$e->faultstring );
			self::$wflClient = null;
		}
	}
	
	/**
	 * Calls the LogOn workflow service at the archive server.
	 * Returned data, such as ticket and publications are cached in the PHP session.
	 */
	static public function logOn()
	{
		if( self::$ticket ) {
			$ticket = self::$ticket;
		} else {
			$ticket = self::getSessionData( 'SMARTARCHIVE_TICKET' );
		}
		if( !empty($ticket) ) {
			LogHandler::Log('SmartArchive','DEBUG', __METHOD__.': Skipped logon; Still using archive ticket: ' . $ticket);
			return null; // bail out when nothing to do
		}

		LogHandler::Log('SmartArchive', 'DEBUG', __METHOD__.': Calling LogOn service at archive server...');
		try {
			$response = null;
			if( self::$logOnRequest ) {
				$logon = self::$logOnRequest;
			} else {
				$logon = unserialize( self::getSessionData( 'SMARTARCHIVE_LOGONREQUEST' ) );
			}
			$response = self::$wflClient->LogOn( $logon );
			self::$ticket = $response->Ticket; // too early to save in session since that is not started yet!

			LogHandler::Log('SmartArchive','DEBUG', __METHOD__.': Retrieved ticket from archive server: ' . $response->Ticket); 
		} catch( SoapFault $e ) {
			self::setSessionData( 'SMARTARCHIVE_TICKET', '' ); // clear
			self::$logOnRequest = null;
			LogHandler::Log( 'SmartArchive', 'WARN', __METHOD__.': Failed logging into Archive Server::'.$e->faultstring );
		}
		return $response;
	}

	/**
	 * Calls the LogOff workflow service at the archive server.
	 */
	static public function logOff()
	{
		LogHandler::Log('SmartArchive', 'DEBUG', __METHOD__.': Calling LogOff service at archive server...');
		try {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffRequest.class.php';
			$request = new WflLogOffRequest();
			$request->Ticket = self::getSessionData( 'SMARTARCHIVE_TICKET' );
			$response = self::$wflClient->LogOff( $request );
		} catch( SoapFault $e ) {
			LogHandler::Log( 'SmartArchive', 'ERROR', __METHOD__.': '.$e->faultstring );
		}
		self::setSessionData( 'SMARTARCHIVE_TICKET', '' ); // clear
	}
	
	/**
	 * Calls the QueryObjects workflow service at archive server.
	 *
	 * @param array $params List of QueryParam objects used to filter requested workflow objects.
	 * @return QueryObjectsResponse or null when failed
	 */
	static public function queryObjects( $params, $firstEntry, $maxEntries, $order )
	{
		LogHandler::Log('SmartArchive', 'DEBUG', __METHOD__.': Calling QueryObjects service at archive server...');
		try{
			require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
			$request = new WflQueryObjectsRequest();
			$request->Ticket = self::getSessionData( 'SMARTARCHIVE_TICKET' );
			$request->Params = $params;
			$request->FirstEntry = $firstEntry;		//for pagination. 
			$request->MaxEntries = $maxEntries;
			$request->Hierarchical = false;
			$request->Order = $order;
			$request->MinimalProps = array( 'ID', 'Type', 'Name', 'PublicationId', 'IssueId', 'CategoryId', 
											'StateId', 'State', 'StateColor', 'Format', 'LockForOffline' );
			$response = self::$wflClient->QueryObjects( $request );
		} catch( SoapFault $e ){
			LogHandler::Log( 'SmartArchive', 'ERROR', 'QueryObjects: '.$e->faultstring );
			$response = null;
		}
		return $response;
	}

	/**
	 * Calls the GetObjects workflow service at archive server.
	 *
	 * @param array $ids IDs of objects to retrieve from archive.
	 * @param string $rendition The object file rendition to retrieve from archive.
	 * @return GetObjectsResponse or null when failed
	 */
	static public function getObjects( array $ids, $rendition )
	{
		LogHandler::Log('SmartArchive', 'DEBUG', __METHOD__.': Calling GetObjects service at archive server...');
		try {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsRequest.class.php';
			$request = new WflGetObjectsRequest();
			$request->Ticket = self::getSessionData( 'SMARTARCHIVE_TICKET' );
			$request->IDs = $ids;
			$request->Lock = false;
			$request->Rendition = $rendition;
			$request->RequestInfo = array( 'MetaData' );
			$response = self::$wflClient->GetObjects( $request );
	
			if( $response->Objects ) foreach( $response->Objects as $object ) {
				if( $object->Files ) {
					$files = array();
					foreach( $object->Files as $file ) {
						require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
						$attachment = new Attachment($file->Rendition, $file->Type);					
						$transferServer = new BizTransferServer();
						$transferServer->copyToFileTransferServer($file->FilePath, $attachment);					
						$files[] = $attachment; 
					}
					$object->Files = $files;
				}
			}	
		} catch( SoapFault $e ){
			LogHandler::Log('SmartArchive','ERROR', 'ERROR:' . $e->faultstring);
			$response = null;
		}
		return $response;
	}

	/**
	 * Keeps track of the LogOn request that was fired against the production server. This is done
	 * to fire it against the archive server too. We assume that the very same user is known at both
	 * servers. Working at the production server, without touching the archive server for a while, it 
	 * could happen that the ticket expires at the archive server, while the production server is still
	 * fine with its ticket. In that case, we need to get the request from the session data and fire
	 * it against the archive server again.
	 * During logon, the session has not started because that requires the ticket which is about
	 * to retrieved from server. In that time, we can not store data in the session, and so the 
	 * setLogOnRequest function stores it in the static members of this class. Once the session has
	 * been created, the saveSessionData function must be called (after logon) which stores the
	 * data (from static members) into the session.
	 */
	static public function setLogOnRequest( $req )
	{
		self::$logOnRequest = $req; // too early to save in session since that is not started yet!
	}
	
	static public function saveSessionData()
	{
		self::setSessionData( 'SMARTARCHIVE_LOGONREQUEST', serialize(self::$logOnRequest) );
		self::setSessionData( 'SMARTARCHIVE_TICKET', self::$ticket );
	}
	
	/**
	 * Retrieve data stored at the PHP session.
	 *
	 * @param $key string Data identifier, which should be unique within Enterprise.
	 */
	static public function getSessionData( $key )
	{
		$vars = BizSession::getSessionVariables();
		return isset($vars[$key]) ? $vars[$key] : null;
	}

	/**
	 * Stores data into the PHP session.
	 *
	 * @param $key string Data identifier, which should be unique within Enterprise.
	 * @param $data string The data to store. Classes and arrays should be serialized.
	 */
	static public function setSessionData( $key, $data )
	{
		$vars = array( $key => $data );
		BizSession::setSessionVariables( $vars );
	}
	
}
