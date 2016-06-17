<?php
/**
 * Performance Profiler utility class.<br>
 *
 * Usefull tool for profiling purposes.<br>
 * 
 * @package EnterpriseProxy
 * @subpackage Utils
 * @since v9.x
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class PerformanceProfiler
{
	private static $contexts = array();

	/**
	 *	Profile info
	 */
	// Input
	private static $mLevels = 5;
	private static $mIterations = 10000;
	// Output
	private static $mOverhead = 0;
	private static $mX = 0;
	private static $mYAvg = 0;
	private static $mYs = array();
	 
	private static $enabled = false;
	private static $profileLevel = 1; 	// [0,5] 0: no profiling, 5: most detailed

	public function __construct()
	{
		if( OUTPUTDIRECTORY != '' ) {
			$debugLevel = LogHandler::getDebugLevel();
			if( $debugLevel == 'INFO' || $debugLevel == 'DEBUG' ) {
				self::$enabled = true;
			}
		}
		
		if( self::$enabled ) {
			if( PROFILELEVEL >= 1) {
				self::$profileLevel = PROFILELEVEL;
			}
			else {
				self::$enabled = false;
			}			
		}

		self::startProfile( strtolower($_SERVER['SCRIPT_NAME']), -1, true );
	}
	public function __destruct()
	{
		if( count( self::$contexts ) > 0 ) {
			self::stopProfile( strtolower($_SERVER['SCRIPT_NAME']), -1, true );
		}
	}
	
	/**
	 * Returns a Byte count as Mega Byte count
	 *
	 * @param integer $size
	 * @param integer $precision
	 * @return string
	 */
	public static function getMBString( $size, $precision=0 ) 
	{
	  $size = $size / 1048576; // B->MB  (1024*1024=1048576)
	  return sprintf('%0.'.$precision.'f',round($size, $precision));
	}

	private static function openFile( $fileName, $opentype = 'a+' )
	{
		// Rename log file when > 1Mb
		$exists = file_exists( OUTPUTDIRECTORY.$fileName );
		
		if( $exists && filesize(OUTPUTDIRECTORY.$fileName) > 1048576 ) { // 1Mb = 1048576 bytes
			list($ms, $sec) = explode(" ", microtime()); // get seconds with ms part
			$msFmt = sprintf( '%03d', round( $ms*1000, 0 ));
			$dt = date('Ymd').'_'.date('His',$sec).'_'.$msFmt;
			$pieces = explode( '.', OUTPUTDIRECTORY.$fileName );
			$fileExt = array_pop( $pieces ); // remove extension
			$dtFile = implode( '.', $pieces ).'_'.$dt.'.'.$fileExt;  
			clearstatcache(); // needed for WinXP, or else rename() could fail!
			if( rename( OUTPUTDIRECTORY.$fileName, $dtFile ) ) {
				$exists = false;
			}
		}

   		// Open or create the log file
		$handle = fopen( OUTPUTDIRECTORY.$fileName, $opentype );
		if( !is_resource($handle) ) {
			return false; // error
		}
   		// Just created this file / for first time use?
		if( !$exists ) {

			require_once BASEDIR.'/server/utils/htmlclasses/HtmlLog.class.php';
			$header = HtmlLog::getHeader( 'Enterprise Server', 'Performance Profile' );

			$header .= '<h2>Profiler Info</h2><table>';
			$header .= '	<tr><th>Setting</th><th>Value</th></tr>';
			$header .= '	<tr><td class="h">Depth</td><td class="d">'.self::$mLevels.'</td></tr>';
			$header .= '	<tr><td class="h">Iterations</td><td class="d">'.self::$mIterations.'</td></tr>';
			$header .= '	<tr><td class="h">Overhead</td><td class="d">'.self::$mOverhead.'</td></tr>';
			$header .= '	<tr><td class="h">X</td><td class="d">'.self::$mX.'</td></tr>';
			$header .= '	<tr><td class="h">Y<sub>avg</sub></td><td class="d">'.self::$mYAvg.'</td></tr>';
			foreach( self::$mYs as $key => $value )
				$header .= '	<tr><td class="h">Y<sub>'.$key.'</sub></td><td class="d">'.$value.'</td></tr>';
			$header .= '</table><br/>';

			// table header in preparation to the actual logging
			$header .= '<h2>Profile Log</h2>';
//			$header .= '<table><tr><th>Context</th><th colspan="4">Real memory (MB)</th><th colspan="4">Virtual memory (MB)</th><th colspan="3">Timing</th><th>Hits</th><th>Participation</th></tr>';
//			$header .= '<tr><th></th><th colspan="2">Use Start/Stop</th><th colspan="2">Peak Start/Stop</th><th colspan="2">Use Start/Stop</th><th colspan="2">Peak Start/Stop</th><th>Start</th><th>Stop</th><th>Duration</th><th></th><th></th></tr>';
			$header .= '<table><tr><th>Context</th><th>Hits</th><th colspan="4">Real memory (MB)</th><th colspan="4">Virtual memory (MB)</th><th colspan="4">Raw Timing</th><th colspan="4">Adjusted Timing</th></tr>';
			$header .= '<tr><th></th><th></th><th colspan="2">Use Start/Stop</th><th colspan="2">Peak Start/Stop</th><th colspan="2">Use Start/Stop</th><th colspan="2">Peak Start/Stop</th><th>Start</th><th>Stop</th><th>Duration</th><th>%</th><th colspan="2">Overhead</th><th>Duration</th><th>%</th></tr>';

			fwrite( $handle, $header );
		}
		return $handle;
	}

	private static function determineOverheadBaseline()
	{
		$overhead = 0;
		
		$filePath = OUTPUTDIRECTORY.'PerformanceProfiler.dat';
		$exists = file_exists( $filePath );
		if( $exists ) {
			$settings = explode( "##", file_get_contents( $filePath ) );
			self::$mLevels = (int)$settings[0];
			self::$mIterations = (int)$settings[1];
			self::$mOverhead = (float)$settings[2];
			self::$mX = (float)$settings[3];
			self::$mYAvg = (float)$settings[4];
			$ys = explode( ";", $settings[5] );
			$cnt = 1;
			foreach( $ys as $y ) {
				self::$mYs[$cnt++] = (float)$y;
			}
		} else {
			// Calculate the overhead of the profiler
			$n = self::$mIterations;		// number of repetitions
			$aMax = self::$mLevels;		// depth of the profile stack
			$start = microtime( true );
			for( $i = 0; $i < $n; $i++ ) {
				for( $j = 1; $j <= $aMax; $j++ ) {
					self::startProfile( 'OverheadCalculator'.$j, -1 );
				}
				for( $j = $aMax; $j > 0; $j-- ) { 
					self::stopProfile( 'OverheadCalculator'.$j, -1 );
				}
			}
			$stop = microtime( true );
			$overhead = $stop - $start;
			self::$mOverhead = $overhead;
			
			$durations = array();
			for( $j = 1; $j <= $aMax; $j++ ) {
				$durations[$j] = 0;
			}
			for( $i = 0; $i < $n; $i++ ) {
				for( $j = 1; $j <= $aMax; $j++ ) {
					$profile = self::$contexts['OverheadCalculator'.$j];
					$durations[$j] += ( $profile->mStops[$i] - $profile->mStarts[$i] );
				}
			}
			
			self::$mX = ($durations[$aMax] * $aMax) / $overhead;
			for( $j = 1; $j < $aMax; $j++ ) {
				self::$mYs[$j] = ($aMax * ($durations[$aMax-$j] - ( (self::$mX * $overhead ) / $aMax))) / ($j * $overhead); 
				self::$mYAvg += self::$mYs[$j];
			}
			self::$mYAvg = self::$mYAvg / ($aMax - 1);
				
			$settings = "";
			$settings .= self::$mLevels.'##';
			$settings .= self::$mIterations.'##';
			$settings .= self::$mOverhead.'##';
			$settings .= self::$mX.'##';
			$settings .= self::$mYAvg.'##';
			$l = count( self::$mYs );
			for( $i = 1; $i <= $l; $i++ ) {
				$settings .= self::$mYs[$i];
				if( $i != $l )
					$settings .= ';';
			}
			file_put_contents( $filePath, $settings );
		}
	}
		
		
	private static function writeOutput( $context, $extraInfo )
	{
		self::determineOverheadBaseline();
		$handle = self::openFile( 'sce_profile.htm' );
		if( $handle ) {
			$totalDuration = 0;
			$correctedTotalDuration = 0;
			$finalStop = 0;
			foreach( self::$contexts as $contextKey => $profile ) {
				// No timings? Skip it.
				if( count( $profile->mStarts ) == 0 )
					continue;
				
				// get a formatted start time
				list($sec, $msec) = explode(".", sprintf("%.2f",$profile->mStarts[0]));  // use sprintf, to prevent errors when float has no decimals.
				$started = date('H:i:s',$sec).'.'.sprintf( '%03d', round( ("0.".$msec)*1000, 0 ));
				
				// Profilers started but not stopped get the end time of the initiator
				$n = count( $profile->mStarts );
				while( $n > count( $profile->mStops ) ) {
					$profile->mStops[] = $finalStop;
				}

				// get a formatted stop time
				list($sec, $msec) = explode(".", sprintf("%.2f", end( $profile->mStops ) )); // use sprintf, to prevent errors when float has no decimals.
				$stopped = date('H:i:s',$sec).'.'.sprintf( '%03d', round( ("0.".$msec)*1000, 0 ));

				$hitCount = $profile->mHitcount;
				// Determine the amount of overhead caused by the calls to startProfile
				// and stopProfile
				$overheadCount = ( (float)$profile->mOverheadX * self::$mX + (float)$profile->mOverheadY * self::$mYAvg );
				$overhead = ( (float)$overheadCount * self::$mOverhead ) / (float)(self::$mLevels * self::$mIterations);
				
				$duration = 0;
				for( $i = 0; $i < $n; $i++ ) {
					$duration += ( $profile->mStops[$i] - $profile->mStarts[$i] );
				}
				
				$correctedDuration = $duration - $overhead;
				

				$vmuStart = $profile->mVMU_start;
				$rmuStart = $profile->mRMU_start;
				$vmuStop  = isset($profile->mVMU_stop) ? $profile->mVMU_stop : '-';
				$rmuStop  = isset($profile->mRMU_stop) ? $profile->mRMU_stop : '-';
				$vmpStart = $profile->mVMP_start;
				$rmpStart = $profile->mRMP_start;
				$vmpStop  = isset($profile->mVMP_stop) ? $profile->mVMP_stop : '-';
				$rmpStop  = isset($profile->mRMP_stop) ? $profile->mRMP_stop : '-';

				$extraInfo  = isset( $profile->mExtraInfo ) ? $profile->mExtraInfo : '';

				if( $contextKey == $context ) { // initiator's context?
					$styleClass = 'h'; // highlighted
					$totalDuration = $duration;
					$correctedTotalDuration = $correctedDuration;
					$finalStop = end( $profile->mStops );
				} else {
					$styleClass = 'd'; // default
				}
				if( $totalDuration == 0 ) { // BZ#36282 - Prevent/avoid Division by zero, divide by zero is simply not allow to happen
					$durationPercent = 0;
				} else {
					$durationPercent = ($duration / $totalDuration) * 100;
				}
				if( $correctedTotalDuration == 0 ) {
					$correctedDurationPercent = 0;
				} else {
					$correctedDurationPercent = ($correctedDuration / $correctedTotalDuration) * 100;
				}
				$fmtPercent = sprintf( '%.1f', $durationPercent );
				$fmtDuration = sprintf( '%.3f', $duration );
				$fmtCorrectedPercent = sprintf( '%.1f', $correctedDurationPercent );
				$fmtOverheadCount = sprintf( '%.1f', $overheadCount );
				$fmtOverhead = sprintf( '%.3f', $overhead );
				$fmtCorrectedDuration = sprintf( '%.3f', $correctedDuration );
				$msg = '<tr class="'.$styleClass.'">'
					.'<td>'.$contextKey.' '.$extraInfo.'</td>'
					.'<td align="right">'.$hitCount.'</td>'
					.'<td align="right">'.$rmuStart.'</td><td align="right">'.$rmuStop.'</td>'
					.'<td align="right">'.$rmpStart.'</td><td align="right">'.$rmpStop.'</td>'
					.'<td align="right">'.$vmuStart.'</td><td align="right">'.$vmuStop.'</td>'
					.'<td align="right">'.$vmpStart.'</td><td align="right">'.$vmpStop.'</td>'
					.'<td align="right">'.$started.'</td><td align="right">'.$stopped.'</td>'
					.'<td align="right">'.$fmtDuration.'</td><td align="right">'.$fmtPercent.'</td>'
					.'<td align="right">'.$fmtOverheadCount.'</td><td align="right">'.$fmtOverhead.'</td>'
					.'<td align="right">'.$fmtCorrectedDuration.'</td><td align="right">'.$fmtCorrectedPercent.'</td>'
					.'</tr>';
			}
		} // else: error handled
		self::$contexts = array(); // clear (used by destructor)	
	}
	
	/**
	 * Start profiling. Must be called before stopProfile() providing the same param values.
	 * Execution times in between the two calls are measured and logged. Overhead time is excluded.
	 *
	 * @param string $context Unique short name for identification of this profile. Also shown in log.
	 * @param integer $level  See comments made for PROFILELEVEL option at configserver.php!
	 * @param bool $initiator For internal use only.
	 */
	public static function startProfile( $context, $level, $initiator=false )
	{
		if( self::$enabled && self::$profileLevel >= $level ) {
			
			// All profiles currently running take a hit of this startProfile and the subsequent 
			// stopProfile.
			foreach( self::$contexts as $profile ) {
				// Running?
				if( $profile->mInstances > 0 )
					$profile->mOverheadY += 1;
			}

			if( isset( self::$contexts[$context] ) ) {
				// context already exists, reuse
				$profile = self::$contexts[$context];
				
				if( $profile->mInstances > 0 ) { 
					// already running, increase the number of instances
					$profile->mInstances += 1;
				} else { 
					// paused, restart now
					$profile->mStarts[] = microtime(true);
					$profile->mInstances = 1;
					$profile->mOverheadX += 1; 
				}
				$profile->mHitcount += 1;

//				echo "start [$context] : ";
//				print_r( $profile );
//				echo '<br>';
			} else { 
				// first time for this context
				$profile = new StdClass();
				$profile->mStarts = array( microtime(true) );
				$profile->mStops = array();

				$profile->mInstances = 1; 
				$profile->mHitcount = 1; 
				$profile->mOverheadX = 1; 
				$profile->mOverheadY = 0; 

				$profile->mVMU_start = self::getMBString(memory_get_usage(),2);
				$profile->mRMU_start = self::getMBString(memory_get_usage(true),2);
				$profile->mVMP_start = self::getMBString(memory_get_peak_usage(),2);
				$profile->mRMP_start = self::getMBString(memory_get_peak_usage(true),2);

//				echo "create [$context] : ";
//				print_r( $profile );
//				echo '<br>';

				self::$contexts[$context] = $profile;
			}
		}
	}

	/**
	 * Stop profiling. Must be called after startProfile() providing the same param values.
	 * Execution times in between the two calls are measured and logged. Overhead time is excluded.
	 *
	 * @param string $context Unique short name for identification of this profile. Also shown in log.
	 * @param integer $level  See comments made for PROFILELEVEL option at configserver.php!
	 * @param bool $initiator For internal use only.
	 * @param string extraInfo
	 */
	public static function stopProfile( $context, $level, $initiator=false, $extraInfo='' )
	{
		if( self::$enabled && self::$profileLevel >= $level ) {

			if( isset( self::$contexts[$context] ) ) {
				// context exists
				$profile = self::$contexts[$context];
				
				// The double if construction prevents the size of mStops to grow longer than 
				// mStarts (you can't stop anything that didn't start) and prevents mInstances 
				// from going below 0
				if( $profile->mInstances > 0 ) {
					$profile->mInstances -= 1;
					
					// Pause the profiling for this context when the instance count reaches 0
					if( $profile->mInstances == 0 ) {
						
						$profile->mStops[] = microtime(true);

						$profile->mVMU_stop = self::getMBString(memory_get_usage(),2);
						$profile->mRMU_stop = self::getMBString(memory_get_usage(true),2);
						$profile->mVMP_stop = self::getMBString(memory_get_peak_usage(),2);
						$profile->mRMP_stop = self::getMBString(memory_get_peak_usage(true),2);
							
						$profile->mExtraInfo = $extraInfo;
					}
				}

				if( $initiator ) { // log when initator ends
					self::writeOutput( $context, $extraInfo );
				}
			} else {
				// error
			}
		}
	}
}

static $profiler; // singleton
if( !isset($profiler) ) {
	$profiler = new PerformanceProfiler();
	// -> $profiler singleton runs out of scope when PHP process dies in which case we stop profiling (see destructor)
}