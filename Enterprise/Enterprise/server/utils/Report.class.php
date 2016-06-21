<?php

/**
 * Utils build a report, e.g. collecting operation results of biz logics.
 * It builds a list of report items in memory whereby severities can be tracked
 * and optionally all reported information can be logged.
 * MVC tip: This could help separating biz logics (Model/Control) from UI (View).
 *
 * @package 	Enterprise
 * @subpackage 	utils
 * @since 		v9.0.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
 
class WW_Utils_ReportItem
{
	/**
	 * @var string Contextual or caller information, such as a simplified class name.
	 */
	public $context    = null;

	/**
	 * @var string FATAL, ERROR, WARN or INFO
	 */
	public $severity   = null;

	/**
	 * @var string Problem description.
	 */
	public $message    = null;

	/**
	 * @var string Background information of the reported problem.
	 */
	public $detail     = null;

	/**
	 * @var string Tip how to resolve the problem.
	 */
	public $help       = null;

	/**
	 * @var array Custom properties that optionally could carry more info.
	 */
	public $attributes = null;
}

class WW_Utils_Report
{
	/**
	 * @var bool $hasFatal Whether or not the report contains FATAL severity item(s).
	 */
	private $hasFatal;

	/**
	 * @var bool $hasError Whether or not the report contains ERROR severity item(s).
	 */
	private $hasError;

	/**
	 * @var bool $hasWarning Whether or not the report contains WARN severity item(s).
	 */
	private $hasWarning;

	/**
	 * @var WW_Utils_ReportItem[] $report List of report items (the report itself).
	 */
	private $report;
	
	/**
	 * Initializes a new report.
	 *
	 * @param boolean $log Whether or not all report items should be logged.
	 */
	public function __construct( $log = true )
	{
		$this->log = $log;
		$this->clearReport();
	}
	
	/**
	 * Whether or not to log items reported through the {@link add()} function.
	 *
	 * @param boolean $log Whether or not all report items should be logged.
	 */
	public function enableLogging( $log = true )
	{
		$this->log = $log;
	}
	
	/**
	 * Adds an item to the report.
	 *
	 * @param string $context   Contextual or caller information, such as a simplified class name.
	 * @param string $severity  Severity at report: FATAL, ERROR, WARN or INFO
	 * @param string $logSeverity Severity at server log: ERROR, WARN, INFO or DEBUG. Pass empty to skip logging.
	 * @param string $message   Problem description.
	 * @param string $detail    Background information of the reported problem.
	 * @param string $help      Tip how to resolve the problem.
	 * @param array $attributes Custom properties that optionally could carry more info.
	 */
	public function add( $context, $severity, $logSeverity, $message, $detail, $help, array $attributes = null )
	{
		// Log the report item.
		if( $logSeverity ) {
			$logMsg = $message;
			if( $detail ) {
				$logMsg .= PHP_EOL.'Detail: '.$detail;
			}
			if( $help ) {
				$logMsg .= PHP_EOL.'Tip: '.$help;
			}
			LogHandler::Log( $context, $logSeverity, nl2br($logMsg) );
		}
		
		// Build report item.
		$item = new WW_Utils_ReportItem();
		$item->context    = $context;
		$item->severity   = $severity;
		$item->message    = $message;
		$item->detail     = $detail;
		$item->help       = $help;
		$item->attributes = $attributes;

		// Add the item to the report.
		$this->report[] = $item;
		
		// Keep track if there were fatals/errors/warnings in the report.
		switch( $severity ) {
			case 'FATAL' : $this->hasFatal   = true;  break;
			case 'ERROR' : $this->hasError   = true;  break;
			case 'WARN'  : $this->hasWarning = true;  break;
			case 'INFO'  : break; // nothing extra to do here
			default: // log error when bad option given
				LogHandler::Log( __CLASS__, 'ERROR', 'Known severity: '.$severity );
			break;
		}
	}
	
	/**
	 * Returns the reported items.
	 *
	 * @param array|null $severities Filter items with a specific severity. NULL for all items (no filter).
	 * @return WW_Utils_ReportItem[]
	 */
	public function get( array $severities = null )
	{
		$report = array();
		if( is_null( $severities ) ) {
			$report = $this->report;
		} else {
			$severities = array_flip( $severities );
			foreach( $this->report as $reportItem ) {
				if( array_key_exists( $reportItem->severity, $severities ) ) {
					$report[] = $reportItem;
				}
			}
		}
		return $report;		
	}
	
	/**
	 * Whether or not the report contains FATAL severity item(s).
	 *
	 * @return boolean
	 */
	public function hasFatal()
	{
		return $this->hasFatal;
	}

	/**
	 * Whether or not the report contains ERROR severity item(s).
	 *
	 * @param boolean $errorOrWorse Whether or not to check for ERROR too.
	 * @return boolean
	 */
	public function hasError( $errorOrWorse = false )
	{
		if( $errorOrWorse ) {
			$retVal = $this->hasError || $this->hasFatal;
		} else {
			$retVal = $this->hasError;
		}
		return $retVal;
	}

	/**
	 * Whether or not the report contains WARN severity item(s).
	 *
	 * @param boolean $warningOrWorse Whether or not to check for ERROR and FATAL too.
	 * @return boolean
	 */
	public function hasWarning( $warningOrWorse = false ) 
	{
		if( $warningOrWorse ) {
			$retVal = $this->hasWarning || $this->hasError || $this->hasFatal;
		} else {
			$retVal = $this->hasWarning;
		}
		return $retVal;
	}
	
	/**
	 * Clears the current report in memory. All reported items are erased.
	 */
	public function clearReport()
	{
		$this->hasFatal = false;
		$this->hasError = false;
		$this->hasWarning = false;
		$this->report = array();
	}
}
