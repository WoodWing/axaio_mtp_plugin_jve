<?php
/**
 * Utility class tracking progress of preview/publish operations for a magazine (issue).
 *
 * It supports multiple phases per operation, each having its own progress bar.
 *
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
class WW_Utils_PublishingProgressBar
{
	private $bars;           // Multi-dim array which tracks progress of process phases. Each key 
	                         // holds a process phase id, for which each there are two property keys:
	                         //   'progressMax': Total amount of (expensive) steps to take during export.
	                         //   'progressCur': Current step (steps completed) during export process.
	private $lastUpdate;     // Last time the progress is written to the progress file.
	                         // L> Note: We want to do this once per second for performance reasons.
	private $operationId;    // System wide GUID in 8-4-4-4-12 format that identifies the operation.

	/**
	 * Constructs the progress bar(s).
	 *
	 * @param string $operationId System wide GUID in 8-4-4-4-12 format that identifies the operation.
	 */
	public function __construct( $operationId )
	{
		$this->bars = array();
		$this->lastUpdate = null;
		$this->operationId = $operationId;
	}
	
	/**
	 * Initializes a progress bar for an operation phase.
	 *
	 * @param string $phaseId Internal name/id of the current processing phase.
	 * @param string $mode Indicates how the progress is caluclated. Default 'steps' means 'step by step' so progress
	 * is raised by steps of one. In case $mode is set to 'increments' the progress can be raised with any number.
	 */
	public function initPhase( $phaseId, $mode = 'steps' )
	{
		$this->bars[$phaseId] = array();
		$this->bars[$phaseId]['progressCur'] = 0;
		$this->bars[$phaseId]['progressMax'] = 0;
		$this->bars[$phaseId]['progressMode'] = $mode;
	}
	
	/**
	 * Tells how many steps to go before the export is completed.
	 *
	 * @param string $phaseId Internal name/id of the current processing phase.
	 * @param integer $progressMax Number of steps.
	 */
	public function setMaxProgress( $phaseId, $progressMax )
	{
		$this->bars[$phaseId]['progressCur'] = 0;
		$this->bars[$phaseId]['progressMax'] = $progressMax;
	}

	/**
	 * Some additional data can be stored for the progress bar. This could be useful to recognize the
	 * context for which the progress bar is created when called by another process later again.
	 * The stored data can be retrieved through getBarData().
	 *
	 * @param string $data Serialized data.
	 */
	public function setBarData( $data )
	{
		$barDataFile = $this->getBarDataFile();
		file_put_contents( $barDataFile, $data );
	}

	/**
	 * Some additional data can be stored for the progress bar. This could be useful to recognize the
	 * context for which the progress bar is created when called by another process later again.
	 * The stored data can be retrieved through getBarData().
	 *
	 * @return string $data Serialized data.
	 */
	public function getBarData()
	{
		$barDataFile = $this->getBarDataFile();
		$barData = file_exists( $barDataFile ) ? file_get_contents( $barDataFile ) : null;
		return $barData;
	}

	/**
	 * To flatten potential counting mismatches (predicted count <> real count),
	 * there the progress is told to be complete by setting current to max.
	 *
	 * @param string $phaseId Internal name/id of the current processing phase.
	 */
	public function setProgressDone( $phaseId )
	{
		$this->bars[$phaseId]['progressCur'] = $this->bars[$phaseId]['progressMax'];
		$progressFile = $this->getProgressFile( $phaseId );
		if( file_exists( $progressFile ) ) {
			unlink( $progressFile );
			clearstatcache(); // Make sure unlink call above is reflected
		}
	}
	
	/**
	 * Set the progress to the next iteration step. 
	 * The progress is only saved to the file system once per second (for performance reasons)
	 * so it can be retrieved by the getProgress() call.
	 *
	 * @param string $phaseId Internal name/id of the current processing phase.
	 * @param integer $incr Indicates with how much the progress has grown. Is not applicable in case the progress mode
	 * is set to 'steps'. In that case progress is raised just with 1.
	 */
	public function setProgressNext( $phaseId, $incr = 0 )
	{
		$currentTime = time(); // in seconds
		$bar = &$this->bars[$phaseId];
		if ( $bar['progressMode'] == 'steps' ) {
			$bar['progressCur'] += 1;
		} elseif ( $bar['progressMode'] == 'increments' ){
			$bar['progressCur'] += $incr;
		}
		if( empty($this->lastUpdate) || // first time calling?
			($currentTime > $this->lastUpdate) || // waited more than one second?
			$bar['progressCur'] == $bar['progressMax'] ) { // operation completed?
				$progressData = array( 
					'progress' => min($bar['progressCur'], $bar['progressMax']), 
					'maximum'  => $bar['progressMax']
				);
				file_put_contents( $this->getProgressFile( $phaseId ), serialize($progressData) );
				$this->lastUpdate = $currentTime;
		}
	}
	
	/**
	 * Returnes the progress. If the progress isn't set yet a progres and maximum of 0 is returned
	 * otherwise the progress from the progress file is returned
	 *
	 * @param string $phaseId Internal name/id of the current processing phase.
	 * @return array with the progress and maximum
	 */
	public function getProgress( $phaseId )
	{
		$progressFile = $this->getProgressFile( $phaseId );
		if( file_exists($progressFile) ) {
			$progressData = unserialize( file_get_contents( $progressFile ) );
		} else {
			$progressData = array( 'progress' => 0, 'maximum' => 0 );
		}
		return $progressData;
	}
	
	/**
	 * While one PHP process takes care for a publishing opertion of the current issue (which might
	 * take a while), another PHP process can be launched that allows the end user to abort that publishing
	 * operation. This function checks whether the 'abort flag' was raised by another PHP process.
	 * The 'abort flag' is a global (system wide) file that can be read/written by any PHP process.
	 * The raiseAbort() function raises the flag (writes the file) and isAborted() function
	 * checks the flag (file exists) and will return TRUE when the file (the flag) exists; 
	 * the current PHP process can call this function many times, for which it will return 
	 * TRUE as long as the file ( the flag ) exists.
	 * The isAborted() function is called many times during export to detect the flag, while
	 * the raiseAbort() function is called once when the user decides to abort the export.
	 *
	 * @return boolean
	 */
	public function isAborted()
	{
		$abortFile = $this->getAbortFile();
		return file_exists( $abortFile );
	}
	
	/**
	 * Clears the abort flag. Should be called before starting a new operation since
	 * the abort flag could have been raised by another process through raiseAbort(),
	 * AFTER the publishing operation has been ended.
	 */
	public function clearAbort()
	{
		$abortFile = $this->getAbortFile();
		if( file_exists( $abortFile ) ) {
			unlink( $abortFile );
		}
	}
	
	/**
	 * Raises the 'abort flag' for the current issue being exported. 
	 * See isAborted() function for details.
	 */
	public function raiseAbort()
	{
		file_put_contents( $this->getAbortFile(), 'true' );
	}
	
	/**
	 * Get the file location of the temporary bar data file for this operation.
	 *
	 * @return string
	 */
	private function getBarDataFile()
	{
		return BizSession::getSessionWorkspace().$this->operationId.'_bardata';
	}

	/**
	 * Get the file location of the temporary progress file for this operation.
	 *
	 * @param string $phaseId Internal name/id of the current processing phase.
	 * @return string
	 */
	private function getProgressFile( $phaseId )
	{
		return BizSession::getSessionWorkspace().$this->operationId.'_'.$phaseId.'_progress';
	}
	
	/**
	 * Get the file location of the temporary abort file for this operation.
	 *
	 * @return string
	 */
	private function getAbortFile()
	{
		// Unlike getProgressFile(), the $phaseId is not included in the file path
		// because the whole operation needs to get aborted at once.
		return BizSession::getSessionWorkspace().$this->operationId.'_abort';
	}
}

class WW_Utils_PublishingProgressBarStore
{
	private static $progress;
	
	/**
	 * Stores the progress information of the running, publishing, process.
	 * 
	 * @param WW_Utils_PublishingProgressBar $progress 
	 */
	public static function setProgressIndicator( WW_Utils_PublishingProgressBar $progress )
	{
		if ( !isset( self::$progress )) {	
			self::$progress = $progress;
		}	
	}		
	
	/**
	 * Return the progress information of the running, publishing process.
	 *  
	 * @return WW_Utils_PublishingProgressBar progress information. 
	 */
	public static function getProgressIndicator( )
	{	
		return isset( self::$progress) ? self::$progress : null;
	}	
}
