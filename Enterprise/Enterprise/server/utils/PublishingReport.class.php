<?php

/**
 * Publishing Report class
 *
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Helper class tracking errors and building an error report while exporting digital magazine (issue).
 *
 */
class WW_Utils_PublishingReport
{
	private $logs = null;
	private $dossier = null;
	private $layout = null;
	private $page = null;
	
	public function __construct()
	{
		$this->logs = array();
	}
	
	/**
	 * Add logging to the report
	 *
	 * @param string $method Class method calling
	 * @param string $severity Error, Warning, Notice, Info, Debug
	 * @param string $message Explains what happened to end user
	 * @param string $reason Tells how to solve the problem
	 * @param Object|null $object The object being handled (null for none)
	 * @return string $log Return the log( most recent one ) with the variable(s) name translated with the real value.
	 */
	public function log( $method, $severity, $message, $reason, $object=null )
	{
		$log = new WW_Utils_PublishingReportRecord( $method, $severity, $message, $reason,
			$this->dossier, $this->layout, $object, $this->page );
		$this->logs[] = $log;	
		
		return $log->getLogMessage() . ' ' . $log->getLogReason();
	}

	/**
	 * Set contextual objects to automatically add to logging details when calling the log() function.
	 */
	public function setCurrentDossier( $dossier ) { $this->dossier = $dossier; }
	public function setCurrentLayout( $layout )   { $this->layout = $layout; }
	public function setCurrentPage( $page )       { $this->page = $page; }

	/**
	 * Build XML document from error log
	 *
	 * @return string XML string of document
	 */
	public function toXML()
	{
		$xDoc = new DOMDocument('1.0');
		$xDoc->formatOutput = true;
		$xReport = $xDoc->createElement( 'Report' );
		$xDoc->appendChild( $xReport );

		foreach( $this->logs as $log ) {
			$log->toXML( $xDoc, $xReport );
		}
		return $xDoc->saveXML();
	}

	/**
	 * Build structured error log.
	 *
	 * @return array of PubReportMessage
	 */
	public function toPubReportMessages()
	{
		$report = array();
		foreach( $this->logs as $log ) {
			$report[] = $log->toPubReportMessage();
		}
		return $report;
	}
	
	public function logCount()
	{
		return count($this->logs);
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/**
 * Publishing Report Record class
 *
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Helper class holding error info from which an error report can be built while exporting digital magazine (issue).
 */
class WW_Utils_PublishingReportRecord
{
	private $method;
	private $severity;
	private $message;
	private $reason;

	private $dossier;
	private $layout;
	private $object;
	private $page;
	
	/**
	 * Creates a log record
	 *
	 * @param string $method Class method calling
	 * @param string $severity Error, Warning, Notice, Info, Debug
	 * @param string $message Explains what happened to end user
	 * @param string $reason Tells how to solve the problem. Pass in empty string '' when there is no reason needed.
	 * @param Object|null $dossier The dossier (Object) being handled (null for none)
	 * @param Object|null $layout The layout(Object) being handled (null for none)
	 * @param Object|null $object The object (Object) being handled (null for none)
	 * @param Object|null $page The page (Page) being handled (null for none)
	 */
	public function __construct( $method, $severity, $message, $reason, $dossier, $layout, $object, $page )
	{
		// Validate params
		if( !$method || !$severity ) {
			LogHandler::Log( 'PublishingReport', 'ERROR',
							"Bad params: method=[$method] severity=[$severity]." );
		}
		$severities = array(		
			// Below, all severities are flattened to INFO because the sys admin is the only 
			// one looking at the log file. He/she will panic on any error. Therefore is it not 
			// a good idea to show 'normal' production errors that are shown to end-user
			// through this Report feature. End-user should solve production errors while
			// admins should solve system errors.
			'Error'     => 'INFO', 
			'Warning'   => 'INFO',
			'Notice'    => 'INFO',
			'Info'      => 'INFO',
			'Debug'     => 'DEBUG'
		);
		if( !isset( $severities[$severity] ) ) {
			LogHandler::Log( 'PublishingReport', 'ERROR', 'Unknown severity: ['.$severity.']' );
		}

		// Log
		$objMap = getObjectTypeMap();
		$ids = '';
		if( $dossier ) {
			$ids .= ' Dossier ID: ' . $dossier->MetaData->BasicMetaData->ID . ' ';
			$message = str_replace( '%DossierName%', '"'.$dossier->MetaData->BasicMetaData->Name.'"', $message );
			$reason  = str_replace( '%DossierName%', '"'.$dossier->MetaData->BasicMetaData->Name.'"', $reason );
		}
		if( $layout ) {
			$ids .= ' Layout ID: ' . $layout->MetaData->BasicMetaData->ID . ' ';
			$message = str_replace( '%LayoutName%', '"'.$layout->MetaData->BasicMetaData->Name.'"', $message );
			$reason  = str_replace( '%LayoutName%', '"'.$layout->MetaData->BasicMetaData->Name.'"', $reason );
		}
		if( $object ) {
			$ids .= ' Object ID: ' . $object->MetaData->BasicMetaData->ID . ' ';
			$message = str_replace( '%ObjectName%', '"'.$object->MetaData->BasicMetaData->Name.'"', $message );
			$message = str_replace( '%ObjectType%', $objMap[$object->MetaData->BasicMetaData->Type], $message );
			$reason  = str_replace( '%ObjectName%', '"'.$object->MetaData->BasicMetaData->Name.'"', $reason );
			$reason  = str_replace( '%ObjectType%', $objMap[$object->MetaData->BasicMetaData->Type], $reason );
		}
		if( $page ) {
			$ids .= ' Page number/sequence/order: ' . $page->PageNumber .
						'/' . $page->PageSequence . '/' . $page->PageOrder;
			$message = str_replace( '%PageSequence%',$page->PageSequence, $message );
			$reason  = str_replace( '%PageSequence%',$page->PageSequence, $reason );
			$message = str_replace( '%PageNumber%',  $page->PageNumber, $message );
			$reason  = str_replace( '%PageNumber%',  $page->PageNumber, $reason );
			$message = str_replace( '%EditionName%', '"'.$page->Edition->Name.'"', $message );
			$reason  = str_replace( '%EditionName%', '"'.$page->Edition->Name.'"', $reason );
		}
		
		$log = $message . ' ' . $reason . ' ' . $ids;
		LogHandler::Log( 'PublishingReport', $severities[$severity], $log );

		// Take over params
		$this->method   = $method;
		$this->severity = $severity;
		$this->message  = $message;
		$this->reason   = $reason;
		
		$this->dossier  = $dossier;
		$this->layout   = $layout;
		$this->object   = $object;
		$this->page     = $page;
	}
	
	/**
	 * Builds a data object structure of the report message.
	 *
	 * @return PubReportMessage
	 */
	public function toPubReportMessage()
	{
		$objInfos = null;
		if( $this->dossier || $this->layout || $this->object ) {
			$objInfos = array();
			if( $this->dossier ) {
				$objInfo = new PubObjectInfo();
				$objInfo->ID   = $this->dossier->MetaData->BasicMetaData->ID;
				$objInfo->Name = $this->dossier->MetaData->BasicMetaData->Name;
				$objInfo->Type = $this->dossier->MetaData->BasicMetaData->Type;
				$objInfos[] = $objInfo;
			}
			if( $this->layout ) {
				$objInfo = new PubObjectInfo();
				$objInfo->ID   = $this->layout->MetaData->BasicMetaData->ID;
				$objInfo->Name = $this->layout->MetaData->BasicMetaData->Name;
				$objInfo->Type = $this->layout->MetaData->BasicMetaData->Type;
				$objInfos[] = $objInfo;
			}
			if( $this->object ) {
				$objInfo = new PubObjectInfo();
				$objInfo->ID   = $this->object->MetaData->BasicMetaData->ID;
				$objInfo->Name = $this->object->MetaData->BasicMetaData->Name;
				$objInfo->Type = $this->object->MetaData->BasicMetaData->Type;
				$objInfos[] = $objInfo;
			}
		}
		$pageInfo = null;
		if( $this->page ) {
			$pageInfo = new PubPageInfo();
			$pageInfo->PageNumber   = $this->page->PageNumber;
			$pageInfo->PageSequence = $this->page->PageSequence;
			$pageInfo->PageOrder    = $this->page->PageOrder;
		}
		$context = new PubMessageContext();
		$context->Objects = $objInfos ? $objInfos : array();
		$context->Page = $pageInfo;
		
		$userMsg = new PubUserMessage();
		$userMsg->Severity  = $this->severity;
		$userMsg->MessageID = intval($this->getErrorCode());
		$userMsg->Message   = $this->message;
		$userMsg->Reason    = $this->reason;
		
		$reportMsg = new PubReportMessage();
		$reportMsg->UserMessage = $userMsg;
		$reportMsg->Context = $context;

		return $reportMsg;
	}

	/**
	 * Returns the S-code taken from the current message.
	 */
	private function getErrorCode()
	{
		$sCodes = array();
		preg_match_all( '/\((S[0-9]+)\)/', $this->message, $sCodes); //grab S(xxxx) error code (S-code) from Exception::getMessage()
		// There should be only one S-code, but when many, take last one since those codes are at the end of message (=rule). 
		$sCode = count($sCodes[1]) > 0 ? $sCodes[1][count($sCodes[1])-1] : ''; 
		return $sCode;
	}
	
	/**
	 * Adds this log object to the given XML document (log report).
	 *
	 * @param DOMDocument $xDoc
	 * @param DOMNode $xReport
	 */
	public function toXML( DOMDocument $xDoc, DOMNode $xReport )
	{
		$xItem = $xDoc->createElement( 'Item' );
		$xReport->appendChild( $xItem );

		$this->createTextElem( $xDoc, $xItem, 'Message', $this->message );
		$this->createTextElem( $xDoc, $xItem, 'Reason', $this->reason );

		$xContext = $xDoc->createElement( 'Context' );
		$xItem->appendChild( $xContext );
		$this->createTextElem( $xDoc, $xContext, 'Method', $this->method );

		if( $this->dossier || $this->layout || $this->object ) {
			$xObjects = $xDoc->createElement( 'Objects' );
			$xContext->appendChild( $xObjects );
			if( $this->dossier ) {
				$xDossier = $xDoc->createElement( 'Object' );
				$xObjects->appendChild( $xDossier );
				$this->createTextElem( $xDoc, $xDossier, 'ID', $this->dossier->MetaData->BasicMetaData->ID );
				$this->createTextElem( $xDoc, $xDossier, 'Name', $this->dossier->MetaData->BasicMetaData->Name );
				$this->createTextElem( $xDoc, $xDossier, 'Type', $this->dossier->MetaData->BasicMetaData->Type );
			}

			if( $this->layout ) {
				$xLayout = $xDoc->createElement( 'Object' );
				$xObjects->appendChild( $xLayout );
				$this->createTextElem( $xDoc, $xLayout, 'ID', $this->layout->MetaData->BasicMetaData->ID );
				$this->createTextElem( $xDoc, $xLayout, 'Name', $this->layout->MetaData->BasicMetaData->Name );
				$this->createTextElem( $xDoc, $xLayout, 'Type', $this->layout->MetaData->BasicMetaData->Type );
			}

			if( $this->object ) {
				$xObject = $xDoc->createElement( 'Object' );
				$xObjects->appendChild( $xObject );
				$this->createTextElem( $xDoc, $xObject, 'ID', $this->object->MetaData->BasicMetaData->ID );
				$this->createTextElem( $xDoc, $xObject, 'Name', $this->object->MetaData->BasicMetaData->Name );
				$this->createTextElem( $xDoc, $xObject, 'Type', $this->object->MetaData->BasicMetaData->Type );
			}
		}
		if( $this->page ) {
			$xPage = $xDoc->createElement( 'Page' );
			$xContext->appendChild( $xPage );
			$this->createTextElem( $xDoc, $xPage, 'PageNumber', $this->page->PageNumber );
			$this->createTextElem( $xDoc, $xPage, 'PageSequence', $this->page->PageSequence );
			$this->createTextElem( $xDoc, $xPage, 'PageOrder', $this->page->PageOrder );
		}
		$xItem->setAttribute( 'Level', $this->severity );
	}

	/**
	 * Create XML node with text node inside.
	 *
	 * @param DOMDocument $xDoc
	 * @param DOMNode $xmlParent
	 * @param string $nodeName Name of node that must contain the text.
	 * @param string $nodeText The text to add to node.
	 * @return DOMNode The created node. (Already added to the document.)
	 */
	private function createTextElem( DOMDocument $xDoc, DOMNode $xmlParent, $nodeName, $nodeText )
	{
		$xmlNode = $xDoc->createElement( $nodeName );
		$xmlParent->appendChild( $xmlNode );
		$xmlText = $xDoc->createTextNode( $nodeText );
		$xmlNode->appendChild( $xmlText );
		return $xmlNode;
	}
	
	/**
	 * Returns the message of the last/recent log added.
	 *
	 */
	public function getLogMessage()
	{
		return $this->message;
	}
	
	/**
	 * Returns the reason of the last/recent log added.
	 * 
	 */
	public function getLogReason()
	{
		return $this->reason;
	}
}
