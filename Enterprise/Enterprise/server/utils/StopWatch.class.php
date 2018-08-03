<?php
/**
 * Stop Watch utility class.
 *
 * A timer with Start and Pause buttons.<br>
 * It tells how long it runs since start, excluding pause times.
 * Usefull tool for profiling and logging purposes.
 * 
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class StopWatch 
{
	/**
	 * @var float Moment of time the Start button is pressed.
	 */
	private $mStart;
	
	/**
	 * @var float Moment of time the Pause button is pressed.
	 */
	private $mPause;

	/**
	 * @var boolean Wether or not the stopwatch is currently watching (=> started but not paused).
	 */
	private $isWatching;

	/**
	 * Construct the StopWatch.<br>
	 *
	 * This does <b>not</b> start the timer. Call {@link Start()} to start the timer.<br>
	 */
	public function __constructor()
	{
		$this->mStart = 0;
		$this->mPause = 0;
		$this->isWatching = false;
	}
	
	/**
	 * Start the timer.<br>
	 *
	 * Restarts the timer when started before.<br>
	 *
	 * @return float  Unix timestamp with microseconds when timer has started.
	 */
	public function Start() 
	{
		$this->mStart = StopWatch::GetMicrotime();
		$this->isWatching = true;
		return $this->mStart;
	}

	/**
	 * Pause the timer.<br>
	 *
	 * Use {@link Unpause()} to let timer continue again.<br>
	 */
	public function Pause() 
	{
		$this->mPause = StopWatch::GetMicrotime();
		$this->isWatching = false;
	}

	/**
	 * Unpause the timer.<br>
	 *
	 * Use {@link Pause()} to halt the timer again.<br>
	 *
	 */
	public function Unpause() 
	{
		$this->mStart += (StopWatch::GetMicrotime() - $this->mPause);
		$this->mPause = 0;
		$this->isWatching = true;
	}

	/**
	 * Fetch the watched time.<br>
	 *
	 * This is the time since last start, excluding pauses.<br>
	 * This does <b>not</b> stop the timer. Call {@link Pause()} to stop timer.<br>
	 *
	 * @param integer $decimalPlaces
	 * @return float
	 */
	public function Fetch($decimalPlaces = 3) 
	{
		if( !$this->isWatching ) {
			$ret = round( ($this->mPause - $this->mStart), $decimalPlaces );
		} else {
			$ret = round( (StopWatch::GetMicrotime() - $this->mStart), $decimalPlaces );
		}

		return $ret;
	}

	/**
	 * Get Micro Time.<br>
	 *
	 * Return current Unix timestamp with microseconds.<br>
	 *
	 * @static
	 * @return float
	 */
	public static function GetMicrotime() 
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	/**
	 * Indicates if the timer is currently running. That is started, but not paused.<br>
	 *
	 */
	public function IsWatching() 
	{
		return $this->isWatching;
	}
}