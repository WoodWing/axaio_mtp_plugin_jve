<?php
/**
 * @package 	SCEnterprise
 * @subpackage 	MadeToPrint
 * @since 		v4.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Interface to Made To Print  (Axaio / Callas)
 */

class MadeToPrintDispatcher
{
	// Singleton object, so block create/delete/copy instances
	private function __construct() {}
	private function __destruct() {}
	private function __copy() {}
	
	private static function domAddElement($dom, $parentNode, $name, $value)
	{
		$node = $dom->createElement($name);
		$node->appendChild(new DOMText($value));
		$parentNode->appendChild($node);
	}

	/**
	 * Called when MtP has processed a job (requested by us).
	 * Sends layout and its placements to the configured 'After' status.
	 * Also, it adds the processing message to the comment field of te layout.
	 * This message/comment is shown when user reopens the layout.
	 *
	 * @param string $ticket 	 Session ticket
	 * @param int  $layoutId     Id of the layout
	 * @param int  $layStatusId  Status id of the layout
	 * @param int  $layEditionId Edition id of the layout
	 * @param int  $success      Whether or not the process was successful (=1)
	 * @param string $message    Message about the process
	 */
	public static function postProcess( $ticket, $layoutId, $layStatusId, $layEditionId, $success, $message )
	{
		/* At MtP you can see process, handled jobs, job status, etc etc  No more reason to do this at SCE
		// Update print status
		$dbDriver = DBDriverFactory::gen();
		$dbmtpsent = $dbDriver->tablename("mtpsentobjects");
		$sql = 'update '.$dbmtpsent.' set `printstate`='.$success.' '
				.'where `objid`='.$layoutId.' and `laytriggerstate`='.$layStatusId;
		$dbDriver->query($sql);
		*/

		// Quit when MtP job has failed		
		if( $success != 1 ) {
			LogHandler::Log( 'mtp', 'ERROR', 'postProcess: MtP failed with message: '.$message );
		}

		try {
			require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
			$curLayoutObject = BizObject::getObject( $layoutId, MTP_USER, false, 'none', array('MetaData'), null, false );
		} catch ( BizException $e ) {
			LogHandler::Log( 'mtp', 'ERROR', 'postProcess: Could not find layout. Id='.$layoutId );
			return;
		}

		$comment = self::composeComment( $curLayoutObject, $layEditionId, $message );

		// Get MtP configuration record for the layout trigger status
		$mtpConfig = self::getMtpConfig( $layStatusId );
		if( !$mtpConfig ) {
			LogHandler::Log( 'mtp', 'ERROR', 'postProcess: Could not find MtP configuration for layout status '.$layStatusId );
			return; // should never happen
		}
		$refstatelayout  = $mtpConfig['layprogstate'];
		$refstatearticle = $mtpConfig['artprogstate'];
		$refstateimage   = $mtpConfig['imgprogstate'];

		require_once BASEDIR . '/server/bizclasses/BizUser.class.php';
		$userFullName = BizUser::resolveFullUserName(MTP_USER);
		$updatedObjects = array();
		// Update article/image child object status, broadcast after update
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		require_once BASEDIR. '/server/smartevent.php';
		if($success == 1){
			require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';
			$childIds = self::getPlacedChilds( $layoutId );
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			foreach( $childIds as $childId ) {
				$objType = DBObject::getObjectType( $childId );
				
				$refstate = 0;				
				if( $objType == 'Image' ) {
					$refstate = $refstateimage;				
				} elseif( $objType == 'Article') {
					$refstate = $refstatearticle;
				}
				
				if( $refstate != 0 ) {
					$curObject = BizObject::getObject( $childId, MTP_USER, false, 'none', array('MetaData'), null, false );
					$oldRouteTo = $curObject->MetaData->WorkflowMetaData->RouteTo;
					$oldRouteToFullName = !empty($oldRouteTo) ? BizUser::resolveFullUserName( $oldRouteTo ) : '';
					$newRouteTo = BizWorkflow::doGetDefaultRouting( $mtpConfig['publid'], $mtpConfig['issueid'], null, $refstate );
					if( $newRouteTo ){ 
						// BZ##4729: Adding routeTo into update as well.
						DBBase::updateRow( 'objects', array( 'routeto' => $newRouteTo, 'state' => $refstate ), "`id` = $childId");
					}else {
						DBBase::updateRow( 'objects', array( 'state' => $refstate ), "`id` = $childId" );
					}
					// Add to search index
					$updatedObject = BizObject::getObject($childId, MTP_USER, false, 'none', array('Targets','MetaData', 'Relations'), null, false);
					$updatedObjects[] = $updatedObject;
					// Broadcasting for updated object
					new smartevent_setobjectpropertiesEx( $ticket, $userFullName, $updatedObject, $oldRouteToFullName );
				}
			}
		}

		// Get old RouteTo field
		$oldRouteTo = $curLayoutObject->MetaData->WorkflowMetaData->RouteTo;
		$oldRouteToFullName = !empty($oldRouteTo) ? BizUser::resolveFullUserName( $oldRouteTo ) : '';

		// Update layout status and comment
		if($refstatelayout != 0 && $success == 1){
			$newRouteTo = BizWorkflow::doGetDefaultRouting( $mtpConfig['publid'], $mtpConfig['issueid'], null, $refstatelayout );
			if( $newRouteTo ){
				DBBase::updateRow( 'objects', array( 'state' => $refstatelayout, 'routeto' => $newRouteTo, 'comment' => $comment ),
										"`id` = $layoutId");
			}else{
				DBBase::updateRow( 'objects', array( 'state' => $refstatelayout, 'comment' => $comment ), "`id` = $layoutId");
			}
		}else{
			DBBase::updateRow( 'objects', array( 'comment' => $comment ), "`id` = $layoutId");
		}

		// Add to search index:
		$updatedObject = BizObject::getObject($layoutId, MTP_USER, false, 'none', array('Targets','MetaData', 'Relations'), null, false);
		$updatedObjects[] = $updatedObject;
		// Broadcasting for updated object
		new smartevent_setobjectpropertiesEx( $ticket, $userFullName, $updatedObject, $oldRouteToFullName );
		
		require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
		BizSearch::indexObjects( $updatedObjects );

		LogHandler::Log('mtp', 'DEBUG', 'postProcess: layout status='.$refstatelayout.' success='.$success);
	}

	/**
	 * Composes the comment to be stored on the object.
	 *
	 * @param Object $object
	 * @param integer $editionId
	 * @param string $message Message returned by the MTP-process.
	 * @return string new message
	 */
	private static function composeComment( $object, $editionId, $message )
	{
		$comment = $object->MetaData->WorkflowMetaData->Comment ;
		// Add MtP job notification to the layout's comment
		if( $editionId > 0 ) {
			require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
			$editionObj = DBEdition::getEditionObj( $editionId );
			$editionTxt = '('.BizResources::localize('EDITION').': '.$editionObj->Name.') ';
		} else {
			$editionTxt = '';
		}

		$comment = '[MtP'. date('Y-m-d H:i:s', time()). '] '.$editionTxt.$message."\n".$comment;

		return $comment;
	}	

	/**
	 * Pushes the given layout into the MtP queue by creating processing scripts.
	 *
	 * @param string $ticket     Session ticket.
	 * @param int  $layoutId     Id of the layout
	 * @param int  $layPubId     Publication id of the layout
	 * @param int  $layIssueId   Issue id of the layout
	 * @param int  $layStatusId  Status id of the layout
	 * @param array $layEditions List of Edition objects of layout
	 */
	private static function queueLayoutObject( $ticket, $layoutId, $layPubId, $layIssueId, $layStatusId, $layEditions )
	{
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		/*$user =*/ DBTicket::checkTicket( $ticket );

		/* At MtP you can see process, handled jobs, job status, etc etc  No more reason to do this at SCE
		// Create job record at smart_mtpsentobjects table
		if( !self::saveLayoutIntoQueue( $layoutId, $layPubId, $layIssueId, $layStatusId ) ) {
			return; // error already reported at saveLayoutIntoQueue
		}*/

		// Retrieve object props from smart_objects table
		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		$fullrow = BizQuery::queryObjectRow($layoutId );
		
		// We risk getting no issue when no current is set at channel; so we overrule here
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$fullrow['IssueId'] = $layIssueId; 
		$fullrow['Issue'] = DBIssue::getIssueName( $layIssueId );

		// Optional feature: Collect special custom MTP fields too (for outputting later on)
		// Those fields have C_MTP_ prefixes at smart_objects table.
		$mtparr = array();
		foreach($fullrow as $propName => $propValue){
			if( strncasecmp( $propName, 'C_MTP_', 6 ) == 0 ) { // could upper (new) or lower (old)
				$mtparr[substr($propName, 6, strlen($propName)-6)] = $propValue;
			}
		}
		
		// Determine the MtP job name
		$mtpConfig = self::getMtpConfig( $layStatusId );
		if( !$mtpConfig ) {
			LogHandler::Log( 'mtp', 'ERROR', 'queueLayoutObject: Could not find MtP configuration for layout status '.$layStatusId );
			return;
		}
		$jobname = $mtpConfig['mtptext'];
		$jobname = (trim($jobname) == '') ? trim(MTP_JOB_NAME) : trim($jobname);
		
		if( count($layEditions) > 0 ) {
			foreach( $layEditions as $layEdition ) {
				self::outputProcessingFiles( $layoutId, $layStatusId, $layEdition, $jobname, $fullrow, $mtparr );
			}
		} else { // no edition, so output layout once
			self::outputProcessingFiles( $layoutId, $layStatusId, null, $jobname, $fullrow, $mtparr );
		}
	}

	/**
	 * Creates processing scripts for MtP to output given layout for certain edition.
	 *
	 * @param int $layoutId      Id of the layout
	 * @param int $layStatusId   Status id of the layout
	 * @param object $layEdition Edition object of layout. Null for no edition.
	 * @param string $jobname    MtP operation to request
	 * @param array  $fullrow    List of all layout object properties
	 * @param array  $mtparr     List of MtP specific custom properties to send to MtP process
	 */
	private static function outputProcessingFiles( $layoutId, $layStatusId, $layEdition, $jobname, $fullrow, $mtparr )
	{
		// Calculate page range for printing
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		$sth = DBPage::getPages( $layoutId, 'Production', null, $layEdition ? $layEdition->Id : null, true );
		$firstPage = 1000000;
		$lastPage = 0;
		$dbDriver = DBDriverFactory::gen();
		while( ($pageRow = $dbDriver->fetch($sth)) ) {
			if( $firstPage > $pageRow['pageorder'] ) $firstPage = $pageRow['pageorder'];
			if( $lastPage  < $pageRow['pageorder'] ) $lastPage  = $pageRow['pageorder'];
		}
		if( $firstPage == 1000000 ) $firstPage = 1;
		if( $lastPage == 0 ) $lastPage = 1;

		// Concat object id + status id + edition id => to make up unique file name
		// Note: we should not use names here since (accented) unicode chars have problems on cross OS mounted disks
		$layEditionId = $layEdition ? $layEdition->Id : 0;
		$name = $layoutId.'_'.$layStatusId.'_'.$layEditionId;

		// Build processing files and write them to folder (MTP_CALLAS_FOLDER_IN)
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$rootNode = $dom->appendChild(new DOMElement('mtp-data'));
		self::domAddElement($dom, $rootNode, 'preprocess-javascript', MTP_CALLAS_FOLDER_IN.$name.'_pre.js');
		self::domAddElement($dom, $rootNode, 'postprocess-javascript', MTP_CALLAS_FOLDER_IN.$name.'_post.js');
		self::domAddElement($dom, $rootNode, 'print-set', $jobname);

		// If page range is NOT a custom prop, let's request for all pages
		// Note: Since v6.0 this is renamed from "page-range" to "PAGE_RANGE" to meet name validation
		if(array_key_exists('PAGE_RANGE', $mtparr) === false){
			self::domAddElement($dom, $rootNode, 'allpages', '1');

			// If start page is NOT a custom prop, let's take the actual start page
			// Note: Since v6.0 this is renamed from "start-page" to "START_PAGE" to meet name validation
			if(array_key_exists('START_PAGE', $mtparr) === false){
				self::domAddElement($dom, $rootNode, 'start-page', $firstPage);
			} else {
				if( $mtparr['START_PAGE'] < $firstPage ) $mtparr['START_PAGE'] = $firstPage; // repair out-of-scope values
				// Note: the custom value is outputted below!
			}
		} else {
			// page range always overrules the start page, so remove to avoid errors at MtP
			unset($mtparr['START_PAGE']); 
			// repair out-of-scope values
			if( $mtparr['PAGE_RANGE'] > $lastPage ) $mtparr['PAGE_RANGE'] = $lastPage; 
			// Note: the custom value is outputted below!
		}
		
		// Add custom MtP meta data props to the job
		foreach($mtparr as $field => $value){
			// Convert SCE custom prop name convention to MtP, e.g.
			//    PAGE_RANGE to page-range
			//    START_PAGE to start-page
			$value = $mtparr[$field];
			unset($mtparr[$field]);
			$field = strtolower($field);
			$field = str_replace( '_', '-', $field );
			$mtparr[$field] = $value;
			// Output prop to MtP
			self::domAddElement($dom, $rootNode, $field, $value);
		}
		
		// Add standard set of meta data props to the job
		// This allows output (PDF) file name configuration at MtP!
		$list = array( 'ID', 'Name', 'State', 'StateId', 'RouteTo', 'LockedBy', 
						'Modifier', 'Modified', 'Creator', 'Created', 
						'Publication', 'PublicationId', 'Issue', 'IssueId', 'Section', 'SectionId', 
						'Deadline', 'Edition', 'EditionId' );
						// Note: PageRange property can NOT be used, since it could differ per edition!
						//       and also it does not take care of duplicate pages e.g: 7 and VII

		// Optionally fill the layout edition info
		if( $layEdition && $layEdition->Id > 0 ) {
			$fullrow['EditionId'] = $layEdition->Id;
			$fullrow['Edition'] = $layEdition->Name;
		} else {
			$fullrow['EditionId'] = 0;
			$fullrow['Edition'] = '';
		}
		$extendedNode = $rootNode->appendChild(new DOMElement('extended'));
		foreach($list as $field) {
			$xmetadataEl = $dom->createElement('xmetadata');
			$xmetadataEl->setAttribute('name', $field);
			$xmetadataEl->setAttribute('value', $fullrow[$field]);
			$extendedNode->appendChild($xmetadataEl);
		}
		$mtpjob = $dom->saveXML();
		
		$preprocessjs = 
				'try {'."\n".
				'	app.scriptPreferences.userInteractionLevel = UserInteractionLevels.neverInteract;'."\n".
				'} catch ( e ) {} // fail silently; expected in InDesign Server'."\n".
				'try {'."\n".
				'	app.entSession.logout();'."\n".
				'} catch ( e ) {}' . "\n" . // fail silently; expected
				'try {' ."\n".
				'	report = File("'.addslashes(MTP_CALLAS_FOLDER_OUT.$name).'.xml"); ' ."\n".
				'	report.remove();'."\n". // remove pending results (errors) from previous jobs
				'} catch(err) { }' ."\n". // fail silently; expected
				"\n".
				'try {' ."\n".
				'	app.entSession.login( "'.addslashes(MTP_USER).'", "'.addslashes(MTP_PASSWORD).'", "'.addslashes(MTP_SERVER_DEF_ID).'" );'."\n".
				'} catch(err) { ' ."\n".
				'	app.performSimpleRequest("'.MTP_POSTPROCESS_LOC.'?id='.$layoutId.'&state='.$layStatusId.'&edition='.$layEditionId.'&success=4&message=cannot%20logon%20MtP%20user"); ' ."\n".
				'	exit(0);' ."\n".
				'} ' ."\n".
				"\n".
				'var doc = null;' . "\n".
				'try {' ."\n".
				'	doc = app.openObject("'.$layoutId.'", false);'."\n".
				'} catch(err) { ' ."\n".
				'	app.performSimpleRequest("'.MTP_POSTPROCESS_LOC.'?id='.$layoutId.'&state='.$layStatusId.'&edition='.$layEditionId.'&success=5&message=cannot%20open%20layout%20'.$layoutId.'"); ' ."\n".
				'	exit(0);' ."\n".
				'} ' ."\n";
		// Pre-select the requested edition before MtP starts processing
		if( $layEditionId > 0 ) {
			$preprocessjs .= 	
				'try {' ."\n".
				'	doc.activeEdition = "'.addslashes($layEdition->Name).'";'."\n".
				'} catch(err) { ' ."\n".
				'	app.performSimpleRequest("'.MTP_POSTPROCESS_LOC.'?id='.$layoutId.'&state='.$layStatusId.'&edition='.$layEditionId.'&success=6&message=cannot%20activate%20edition%20'.$layEditionId.'"); ' ."\n".
				'	exit(0);' ."\n".
				'} ' ."\n";
		}
							
		$postprocessjs = 	
				'try {'."\n".
				'	app.scriptPreferences.userInteractionLevel = UserInteractionLevels.neverInteract;'."\n".
				'} catch ( e ) {} // fail silently; expected in InDesign Server'."\n".
				'try {' ."\n".
				'	report = File("'.addslashes(MTP_CALLAS_FOLDER_OUT.$name).'.xml"); ' ."\n".
				'	report.open("r"); ' ."\n".
				'	content = report.read(); ' ."\n".
				'	if( content.length == 0 ) throw new Error( "error" ); ' ."\n".
				'} catch(err) { ' ."\n".
				'	app.performSimpleRequest("'.MTP_POSTPROCESS_LOC.'?id='.$layoutId.'&state='.$layStatusId.'&edition='.$layEditionId.'&success=3&message=cannot%20read%20status%20report"); ' ."\n".
				'} ' ."\n".
				'if( content.length == 0 ) { ' ."\n". // error already posted above, but avoid more posts
				'} else if(content.indexOf("<type>ok") > 0) { ' ."\n".
				'	app.performSimpleRequest("'.MTP_POSTPROCESS_LOC.'?id='.$layoutId.'&state='.$layStatusId.'&edition='.$layEditionId.'&success=1&message=ok"); ' ."\n".
				'	report.remove();'."\n". // we only clean on success! or else there is no way to find back fatal errors!
				'} else { ' ."\n".
				'	i = content.indexOf("<status>")+8; j = content.indexOf("</status>");'."\n".
				'	msg = content.substring(i,j);'."\n".
				'	msg = encodeURIComponent(msg);'."\n".
				'	app.performSimpleRequest("'.MTP_POSTPROCESS_LOC.'?id='.$layoutId.'&state='.$layStatusId.'&edition='.$layEditionId.'&success=2&message="+msg); ' ."\n".
				'}' ."\n";
		// MtP can not start with documents open, so here we safely close *all* documents;
		// They must be all ours and this is just to make sure documents don't get stacked in fatal situations,
		// for example documents still left open from previous sessions that ended unexpectedly.
		$postprocessjs .= 	
				'var documents = app.documents; ' ."\n".
				'for(i=documents.length-1;i>=0;i--){ ' ."\n".
				'	documents.item(i).close(SaveOptions.no); ' ."\n".
				'}' . "\n" .
				'try {'."\n".
				'	app.entSession.logout();'."\n".
				'} catch ( e ) {}' . "\n"; // fail silently; not expected
		
		$fileName = MTP_SERVER_FOLDER_IN.$name.'_pre.js';					
       	$fp = fopen( $fileName, "w+" );
       	if( $fp ) {
			fwrite( $fp, $preprocessjs );
			fclose( $fp );
			LogHandler::Log( 'mtp', 'INFO', 'Wrote into: '.$fileName );
		} else {
			LogHandler::Log( 'mtp', 'ERROR', 'No write access for: '.$fileName );
		}

		$fileName = MTP_SERVER_FOLDER_IN.$name.'_post.js';					
		$fp = fopen( $fileName, "w+" );
		if( $fp ) {
			fwrite( $fp, $postprocessjs );
			fclose( $fp );
			LogHandler::Log( 'mtp', 'INFO', 'Wrote into: '.$fileName );
		} else {
			LogHandler::Log( 'mtp', 'ERROR', 'No write access for: '.$fileName );
		}
		
		$fileName = MTP_SERVER_FOLDER_IN.$name.'.xml';					
		$fp = fopen( $fileName, "w+" );
		if( $fp ) {
			fwrite( $fp, $mtpjob );
			fclose( $fp );
			LogHandler::Log( 'mtp', 'INFO', 'Wrote into: '.$fileName );
		} else {
			LogHandler::Log( 'mtp', 'ERROR', 'No write access for: '.$fileName );
		}
	}

	/**
	 * Method is used to add extra functionality to the MTP process. The method is not used by the core.
	 * It is used by partners/customers.
	 * DO NOT REMOVE THIS METHOD AS THIS WILL BREAK EXISTING INTEGRATIONS.
	 * 
	 * @param integer $objectId The Id of the involved layout.
	 * @param integer $newPubId	The Id of the publication. 
	 * @param integer $newStatusId The Id of the new status.
	 * @param integer $oldStatusId The Id of the previous status.
	 */
	public static function clearSentObject( $objectId, $newPubId, $newStatusId, $oldStatusId )
	{
	}

	/**
	 * Pushes layouts into the MadeToPrint queue when configured trigger statuses are reached.
	 * When a layouts pushed into the queue, it will get processed by MadeToPrint later on.
	 * The passed object can be a layout or a placed article/image.
	 * When passing article/image, it will push the layouts on which they are placed.
	 * A Made to Print job should not be created by during the processing of an InDesign Server Automation job.
	 * The reason is this job is doing some post processing after an user has save/changed a layout. The Axaio MTP is
	 * already called during that initial action. See EN-87051.
	 *
	 * @param int $objectId The object to push into the queue
	 * @param string ticket The session ticket
	 */
	public static function doPrint( $objectId, $ticket )
	{
		require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
		if ( BizInDesignServerJobs::calledByIDSAutomation( $ticket )) {
			return;
		}

		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$objType = DBObject::getObjectType( $objectId );
		if( $objType == 'Layout' ) {
			$layoutIds = array( $objectId );
		} elseif( $objType == 'Article' || $objType == 'Image' ) {
			$layoutIds = self::getParentLayouts( $objectId );
		} else { // ignore unsupported object types
			$layoutIds = array();
		}

		foreach( $layoutIds as $layoutId ){
			$layPubId = $layIssueId = $layStatusId = 0;
			$layEditions = array();
			if( self::getLayoutDetails( $layoutId, $layPubId, $layIssueId, $layStatusId, $layEditions ) ) {
				if( self::checkTriggerStatuses( $layoutId, $layStatusId ) ) {
					self::queueLayoutObject( $ticket, $layoutId, $layPubId, $layIssueId, $layStatusId, $layEditions );
				}
			}
		}
	}

	/*
	 * Checks if the layout and its children all match the configured 'trigger' statuses.
	 *
	 * @param int $layoutId     Id of the layout
	 * @param int $layStatusId  Status id of the layout
	 * @return boolean Whether or not all triggers are matching
	 */
	private static function checkTriggerStatuses( $layoutId, $layStatusId )
	{
		$mtpConfig = self::getMtpConfig( $layStatusId  );
		if( !$mtpConfig ) {
			return false;
		}
		$childIds = self::getPlacedChilds( $layoutId );
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		foreach( $childIds as $childId ){
			$objType = DBObject::getObjectType( $childId );
			if( $objType == 'Article' ) {
				if($mtpConfig['arttriggerstate'] != 0 ) {
					$childStatusId = DBObject::getObjectStatusId( $childId );
					if( $mtpConfig['arttriggerstate'] != $childStatusId ) {
						return false;
					}
				}
			} elseif( $objType == 'Image' ) {
				if($mtpConfig['imgtriggerstate'] != 0) {
					$childStatusId = DBObject::getObjectStatusId( $childId );
					if( $mtpConfig['imgtriggerstate'] != $childStatusId ) {
						return false;
					}
				}
			}
		}
		return true; 
	}

	/**
	 * Returns all objects that are placed on the given layout.
	 *
	 * @param int $layoutId
	 * @return array List of placed object ids
	 */ 
	private static function getPlacedChilds( $layoutId )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbobjectrel = $dbDriver->tablename("objectrelations");
		$children = array();
		$sql = 'SELECT `child` FROM '.$dbobjectrel.' WHERE `parent` = ? AND `type` = ? ';
		$params = array( $layoutId, 'Placed' );
		$sth = $dbDriver->query($sql, $params );
		while(($res = $dbDriver->fetch($sth))){
			array_push($children, $res['child']);
		}
		return $children;
	}

	/**
	 * Returns all layouts on which the given object is placed.
	 *
	 * @param int $objectId
	 * @return array List of layout ids
	 */ 
	private static function getParentLayouts( $objectId )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbobjectrel = $dbDriver->tablename("objectrelations");
		$parents = array();
		$sql = 'SELECT `parent` FROM '.$dbobjectrel.' WHERE `child`= ? AND `type` = ? ';
		$params = array( $objectId, 'Placed' );
		$sth = $dbDriver->query($sql, $params );
		while(($res = $dbDriver->fetch($sth))){
			array_push($parents, $res['parent']);
		}
		return $parents;
	}

	/**
	 * Get the configured MadeToPrint configuration for the given layout trigger status
	 *
	 * @param int $layStatusId  Status id of the layout
	 * @return array
	 */
	private static function getMtpConfig( $layStatusId )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbmtp = $dbDriver->tablename("mtp");
		$sql = 'SELECT * FROM '.$dbmtp.' WHERE `laytriggerstate`= ? ';
		$params = array( $layStatusId );
		$sth = $dbDriver->query( $sql, $params );
		$row = $dbDriver->fetch($sth);
		if( !$row ) return null;

		// TODO: Move this to admin page (setup) -> validation/repair
		if(trim($row['arttriggerstate'])=='') $row['arttriggerstate'] = 0;
		if(trim($row['imgtriggerstate'])=='') $row['imgtriggerstate'] = 0;

		if(trim($row['layprogstate'])=='') $row['layprogstate'] = 0;
		if(trim($row['artprogstate'])=='') $row['artprogstate'] = 0;
		if(trim($row['imgprogstate'])=='') $row['imgprogstate'] = 0;
		
		return $row;
	}

	/**
	 * Determines the current layout's publication, issue and status.
	 * Layouts have only one pub+issue !
	 *
	 * @param int  $layoutId     Layout id
	 * @param int  $layPubId     Returned: Publication id of layout
	 * @param int  $layIssueId   Returned: Issue id of layout
	 * @param int  $layStatusId  Returned: Status id of layout
	 * @param array $layEditions Returned: List of Edition objects of layout
	 * @return boolean Whether or not successful.
	 */
	private static function getLayoutDetails( $layoutId, &$layPubId, &$layIssueId, &$layStatusId, &$layEditions )
	{
		// Get layout's issue and editions; we assume layouts have exactly 1 issue (=business rule!) and so it has 1 target
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		$targets = BizTarget::getTargets( null, $layoutId );
		if( count($targets) != 1 ) {
			LogHandler::Log('mtp', 'ERROR', 'Layout '.$layoutId.' is NOT bound to ONE issue. Target count = '.count($targets) );
			return false; // quit; we don't know what issue to take
		}
		if( !isset($targets[0]->Issue->Id) || !$targets[0]->Issue->Id ) {
			LogHandler::Log('mtp', 'ERROR', 'Layout '.$layoutId.' has unknown issue. Target count = '.count($targets) );
			return false; // quit; issue is corrupt/unset
		}
		$layIssueId = $targets[0]->Issue->Id;
		$layEditions = $targets[0]->Editions;
		
		// Get layout's publication and status
		$dbDriver = DBDriverFactory::gen();
		$dbobjects = $dbDriver->tablename("objects");
		$sql = 'select `publication`, `state` from '.$dbobjects.' where `id`='.$layoutId;
		$sth = $dbDriver->query($sql);
		$res = $dbDriver->fetch($sth);
		if( !$res ) {
			LogHandler::Log('mtp', 'ERROR', 'Layout not found. Id='.$layoutId );
			return false;
		}
		$layPubId = $res['publication'];
		if( !$layPubId ) {
			LogHandler::Log('mtp', 'ERROR', 'Layout '.$layoutId.' has unknown publication.' );
			return false;
		}
		$layStatusId = $res['state'];
		if( !$layStatusId ) {
			LogHandler::Log('mtp', 'ERROR', 'Layout '.$layoutId.' has unknown status.' );
			return false;
		}
		return true;
	}

	/**
	 * Stores the given layout at the MtP queue by saving it into smart_mtpsentobjects.
	 *
	 * @param int $layoutId     Id of the layout
	 * @param int $layPubId     Publication id of the layout
	 * @param int $layIssueId   Issue id of the layout
	 * @param int $layStatusId  Status id of the layout
	 * @return boolean Whether or not the object is saved.
	 */
	/* At MtP you can see process, handled jobs, job status, etc etc  No more reason to do this at SCE
	private static function saveLayoutIntoQueue( $layoutId, $layPubId, $layIssueId, $layStatusId )
	{
		if( !$layoutId || !$layPubId || !$layIssueId || !$layStatusId ) {
			LogHandler::Log('mtp', 'ERROR', 'Attempt to create bad record in MtP queue. '
							.'None of the following values should be empty or zero: '
							.'id=['.$layoutId.'] pub=['.$layPubId.'] iss=['.$layIssueId.'] status=['.$layStatusId.']' );
			return false; // avoid bad records that could lead into troubles
		}
		$dbDriver = DBDriverFactory::gen();
		$dbmtpsent = $dbDriver->tablename("mtpsentobjects");
		$sql = 'select count(*) from '.$dbmtpsent.' where `objid`='.$layoutId. ' '
				.'and `publid`='.$layPubId.' and `issueid`='.$layIssueId.' and `laytriggerstate`='.$layStatusId;
		$sth = $dbDriver->query($sql);
		$row = $dbDriver->fetch($sth);
		if( $row && $row['count'] > 0 ) {
			return false; // job is already present at the MtP queue
		}
		$sql = 'insert into '.$dbmtpsent.' values('.$layoutId.', '.$layPubId.', '.$layIssueId.', '.$layStatusId.', 0)';
		$sth = $dbDriver->query($sql);
		return true;
	}*/
}
