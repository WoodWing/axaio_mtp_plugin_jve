<?php

/** 
 * @package Enterprise
 * @subpackage BizServices
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
class BizErrorReport
{
	private static $systems = array(); // Error reporting systems; One per service call.

	private static $roles	= array( 'DoesNotExist' => true,
									 'Receiving'	=> true,
									 'Sending'		=> true );
	
	// - - - - - - - - - Called by EnterpriseService class - - - - - - - - -

	/**
	 * When a service is started, this is called by EnterpriseService class.
	 * It pushes a reporting system onto an internal stack. Doing so, there can
	 * be service-in-service calls whereby one does support error reporting and
	 * another does not (or both do, or both don't). Errors thrown in the context
	 * of a certain service are collected in one reporting system, while errors of
	 * other (inner/outer) services are collected on their own reporting system.
	 */
	static public function startService()
	{
		$system = array( 
			'reports' => array(),
			'throwExceptions' => true,
		);
		array_push( self::$systems, $system );
		return true;
	}

	/**
	 * When a service ends, this is called by EnterpriseService class.
	 * It pops a reporting sustem from an internal stack. See also startService().
	 */
	static public function stopService()
	{
		array_pop( self::$systems );
	}

	// - - - - - - - - - - - Called by SERVICE layer - - - - - - - - - - -

	/**
	 * Called by a service class, to indicate that the service supports error reporting.
	 * Doing so, there are no BizExceptions thrown, but errors are written in a report instead.
	 * See also reportError().
	 */
	static public function enableReporting()
	{
		// Get top most reporting system from stack.
		$system = &self::$systems[count(self::$systems)-1];

		// Switch into error writing mode (instead of throwing).
		$system['throwExceptions'] = false;
	}

	/**
	 * Returns the error reports that were collected during a service call.
	 * Called by service class to pass back to clients through response data.
	 *
	 * @return array of ErrorReport
	 */
	static public function getReports()
	{
		// Return top most reporting system from stack.
		if( count(self::$systems) > 0 ) {
			$system = end( self::$systems );
			$reports = $system['reports'];
		} else {
			$reports = array();
		}
		return $reports;
	}

	// - - - - - - - - - - - Called by BUSINESS layer - - - - - - - - - - -
	
	/**
	 * Called by business classes BEFORE each iteration step of top most entities handled
	 * for a service operation. For example, for CreateObjects, this function is called
	 * for each Object (entity). This sets the context for error reporting. See reportError().
	 *
	 * @return ErrorReportEntity
	 */
	static public function startReport()
	{
		if( count(self::$systems) > 0 ) {
			// Get top most reporting system from stack.
			$system = &self::$systems[count(self::$systems)-1];
		} else { // Happens when a function is not called via service.
			$system = null;
		}
		
		// Push new ErrorReport to stack.
		$report = new ErrorReport();
		$report->BelongsTo = new ErrorReportEntity();
		$report->Entries = array();
		if( $system ) {
			array_push( $system['reports'], $report );
		}
		return $report->BelongsTo;
	}

	/**
	 * Same as startReport() but called AFTER each iteration step of top most entities.
	 * It ends the context for error reporting. When there was nothing reported, the
	 * error report is simply popped of an internal stack.
	 */
	static public function stopReport()
	{
		if( count(self::$systems) > 0 ) {
			// Get top most reporting system from stack.
			$system = &self::$systems[count(self::$systems)-1];
			
			// Get last report from error system.
			$report = end( $system['reports'] );
			
			// When nothing reported (reportError not called), pop it from stack.
			if( isset( $report->Entries ) && count( $report->Entries ) == 0 ) {
				array_pop( $system['reports'] );
			}
		}
	}

	// - - - - - - - - - Called by ANY function to raise errors - - - - - - - - -

	/**
	 * Adds an entry to the error report.
	 * This function should always be called in try{} block.
	 *
	 * @throws BizException When service has no support for error reports.
	 * @param ErrorReportEntry $entry
	 */
	static public function reportError( ErrorReportEntry $entry )
	{
		// Get top most reporting system from stack.
		if( count(self::$systems) > 0 ) {
			$system = &self::$systems[count(self::$systems)-1];
		} else { // Happens when a function is not called via service.
			$system = null;
		}

		$severity = self::messageLevelToSeverity( $entry->MessageLevel );
		// Throw BizException or write to ErrorReport (depending on mode).
		if( is_null($system) || 
			$system['throwExceptions'] ) { // throwing mode?
			// This function is called in the try{} block, 
			// so here DON'T DO stopReport() as the caller should be responsible
			// to do the stopReport() if needed.
			// self::stopReport();  // Not needed!!
			throw new BizException( null, 'Server', $entry->Details, $entry->Message, null, $severity );
		} else {
			self::validateEntityRole( $entry );

			// Determine S-code, but only when not provided (fallback).
			if( is_null( $entry->ErrorCode ) ) {
				$errorCode = self::getErrorCode( $entry->Message );
				if( $errorCode ) { // leave null when message has no code
					$entry->ErrorCode = $errorCode;
				} else {
					$entry->ErrorCode = ''; // Respect WSDL
				}
			}
			if( is_null( $entry->Entities ) ) {
				$entry->Entities = array();  // Respect WSDL
			}
			
			// Push entry to the report (on top of stack).
			$report = &$system['reports'][count($system['reports'])-1];
			array_push( $report->Entries, $entry );

			// Ask BizException to log the error, since it has support for mapping
			// severities. Note that we do NOT throw the exception here!
			new BizException( null, 'Server', $entry->Details, $entry->Message, null, $severity );
		}
	}
	
	/**
	 * Adds an entry to the error report, based on a given BizException.
	 * This function should always be called in catch{} block.
	 *
	 * @throws BizException When service has no support for error reports.
	 * @param BizException $e
	 */
	static public function reportException( BizException $e )
	{
		// Get top most reporting system from stack.
		if( count(self::$systems) > 0 ) {
			$system = &self::$systems[count(self::$systems)-1];
		} else { // Happens when a function is not called via service.
			$system = null;
		}
		
		// Throw BizException or write to ErrorReport (depending on mode).
		if( is_null($system) || // empty array or is not an array
			$system['throwExceptions'] ) { // throwing mode?
			// This function is called in the catch{} block, 
			// so here do a stopReport() so that the catch{} block don't have to
			// do a try and catch again just to do stopReport().
			self::stopReport(); 
			throw $e;
		} else {
			$entry = new ErrorReportEntry();
			//$entry->Entities  TODO:Grep pattern "object:123" from $e->getDetail().
			$entry->Message = $e->getMessage();
			$entry->Details = $e->getDetail();
			$entry->ErrorCode = $e->getErrorCode();
			$entry->MessageLevel = self::severityToMessageLevel( $e->getSeverity() );
			
			if( is_null( $entry->Entities ) ) {
				$entry->Entities = array();  // Respect WSDL
			}
			
			if( is_null( $entry->Details ) ) {
				$entry->Details = ''; // Respect WSDL
			}

			// Push entry to the report (on top of stack).
			$report = &$system['reports'][count($system['reports'])-1];
			array_push( $report->Entries, $entry );
		}
	}

	// - - - - - - - - - - - - - - - PRIVATE - - - - - - - - - - - - - - -

	/**
	 * Extracts error code (S-code) from a given message. The S-code is in format S(XXXX).
	 * When there's no S-code found, an empty string is returned.
	 *
	 * @param string $message
	 * @return string S-code. E.g: S1014
	 */
	private static function getErrorCode( $message )
	{
		$sCodes = array();
		preg_match_all( '/\((S[0-9]+)\)/', $message, $sCodes); //grab S(xxxx) error code (S-code) from Exception::getMessage()
		// There should be only one S-code, but when many, take last one since those codes are at the end of message (=rule). 
		$sCode = count($sCodes[1]) > 0 ? $sCodes[1][count($sCodes[1])-1] : ''; 
		return $sCode;
	}

	/**
	 * Transform a given severity type (as used in LogHandler) to a MessageLevel.
	 *
	 * @param string $severity 
	 * @return string MessageLevel
	 */
	private static function severityToMessageLevel( $severity )
	{
		switch( $severity ) {
			case 'INFO': $messageLevel = 'Info';  break;
			case 'WARN': $messageLevel = 'Warning';  break;
			default:     $messageLevel = 'Error'; break;
		}
		return $messageLevel;
	}

	/**
	 * Transform a given MessageLevel into a severity type (as used in LogHandler)
	 *
	 * @param string $messageLevel
	 * @return string severity
	 */	
	private static function messageLevelToSeverity( $messageLevel )
	{
		switch( $messageLevel ) {
			case 'Info':    $severity = 'INFO';  break;
			case 'Warning': $severity = 'WARN';  break;
			default:        $severity = 'ERROR'; break;
		}
		return $severity;
	}

	/**
	 * Validate the ErrorReportEntity role value, log when unknown role
	 *
	 * @param ErrorReportEntry $entry
	 */
	private static function validateEntityRole( ErrorReportEntry $entry )
	{
		if( isset($entry->Entities) ) {
			foreach( $entry->Entities as $entity ) {
				if( isset($entity->Role) && !array_key_exists( $entity->Role, self::$roles ) ) {
					LogHandler::Log( __CLASS__, 'INFO', 'Unknown ErrorReportEntity role' );
				}
			}
		}
	}
}
