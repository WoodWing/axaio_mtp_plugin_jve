<?php

/**
 * Methods to calculate time date differences, add/deduct time/dates etc.
 *
 * @package 	Enterprise
 * @subpackage 	utils
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class DateTimeFunctions
{
	/**
	 * Calculates the timestamp based on the passed timestamp and the time difference to be added/deducted.
	 *
	 * @param string   $operator  '-' in case of previous, else empty
	 * @param string   $entity    date/time entity (D/M/H)
	 * @param integer  $number    number of months/days/hours/minutes to add or deduct
	 * @param bool     $dateIndicator entity is date related (true) or time related (false)
	 * @param integer  $timestamp current time (now)
	 * @return integer Calculated timestamp
	*/
	public static function calculateDateTime(
								$operator,
								$entity,
								$number,
								$dateIndicator,
								$timestamp)
	{
		if ($dateIndicator) {
			if ($entity == 'D') {
				$calcTimestamp = strtotime("$operator$number day", $timestamp);
			}
			else {
				$calcTimestamp = strtotime("$operator$number month", $timestamp);
			}
		}
		else {
			if ($entity == 'M'){
				$calcTimestamp = strtotime("$operator$number minute", $timestamp);
			}
			else {
				$calcTimestamp = strtotime("$operator$number hour", $timestamp);
			}
		}

		return $calcTimestamp;
	}

	/**
	 * Returns the number of days from the current day to the first day of the 'Last Week'.
	 * The 'Last Week' is the last whole week starting at the first day of the week (FIRST_DAY_OF_WEEK).
	 *
	 * @param integer $firstDay First day of the week (range [0...6])
	 * @param integer $currentDay Current day number (range [0...6])
	 * @return integer Number of days to previous first day of the last week
	*/
	public static function getDaysToPrevFirstDay( $firstDay, $currentDay )
	{
		$prevFirstDay = ((-7 + ($firstDay - $currentDay) %7 ));
		if ($prevFirstDay > -7) {
			$prevFirstDay = $prevFirstDay -7;
		}
		return abs($prevFirstDay);
	}

	/**
	 * Returns the number of days from the current day to the first day of the 'Next Week'.
	 * The 'Next Week' is the week starting at the first day of the week (FIRST_DAY_OF_WEEK).
	 * 
	 * @param int $firstDay First day of the week (range [0...6])
	 * @param int $currentDay Current day number (range [0...6])
	 * @return Number of days to next first day
	*/
	public static function getDaysToNextFirstDay( $firstDay, $currentDay )
	{
		$nextFirstDay = ((7 + ($firstDay - $currentDay)) %7);
		if ($nextFirstDay == 0) {
		 	$nextFirstDay = 7;
		}
		return $nextFirstDay;
	}

	/**
	 *	Converts a timedifference in seconds to a formatted timedifference-string as LANGPATTIMEDIFF
	 *	@param $sec int timedifference in total seconds
	 *	@return string LANGPATTIMEDIFF-formatted string
	**/
	static public function relativeDate( $sec )
	{
		if (!$sec) return '';
		
		$day = floor($sec/3600/24);
		$hour = floor(($sec-$day*3600*24)/3600);
		$min = floor(($sec - ($day*24+$hour)*3600)/60);
		
		$patrel = LANGPATTIMEDIFF;
		$strday = substr($patrel,0,1);
		$strhour = substr($patrel,1,1);
		$strmin = substr($patrel,2,1);
		
		$ret = '';
		if ($day) $ret .= $day.$strday;
		if ($hour) $ret .= $hour.$strhour;
		if ($min) $ret .= $min.$strmin;
		
		return $ret;
	}
	
	/**
	 *	Converts an inputstring describing a timedifference in the format
	 *	LANGPATTIMEDIFF (days/hours/minutes), to a difference in seconds.
	 *	@param $rel string inputstring (for example 8D12H30M)
	 *	@return int timedifference in total seconds (in the example 8*(60*60*24) + 12*(60*60) + 30*(60) = 736200 seconds.
	 *	returns false if the inputstring was not valid.
	**/
	static public function validRelativeTime( $rel )
	{
		$patrel = LANGPATTIMEDIFF;
		$strday = substr($patrel,0,1);
		$strhour = substr($patrel,1,1);
		$strmin = substr($patrel,2,1);
		
		if (!$rel) return $rel;
		
		$pat = "/^(([0-9]{1,4})$strday){0,1}(([0-9]{1,4})$strhour){0,1}(([0-9]{1,4})$strmin){0,1}$/i";
		$r = array();
		if (preg_match($pat, trim($rel), $r) == 0) {
			return false;
		}
		return (($r[2]*24+$r[4])*60+$r[6])*60;
	}

	/**
	 * Converts a iso-datetime-string to Unix-seconds
	 *
	 * @param string $t iso-formatted datetime-string
	 * @return int Number of unix-seconds
	 */
	static public function iso2time( $t ) 
	{
		return mktime(
				(int)substr($t, 11, 2),
				(int)substr($t, 14, 2),
				(int)substr($t, 17, 2),
				(int)substr($t, 5, 2),
				(int)substr($t, 8, 2),
				(int)substr($t, 0, 4)
				);
	}

	/**
	 * Converts a iso-datetime-string to a LANGPATDATE formatted string.
	 * Also takes into account the LANGPATAMPM setting.
	 *
	 * @param string $iso Iso-formatted datetime string.
	 * @return string Formatted string in LANGPATDATE/LANGPATAMPM-format. Empty when bad value given.
	 */
	static public function iso2date( $iso )
	{
		if (!$iso) {
			return ''; // bad input
		}
		$r = array();
		if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(.[0-9]{1,3}){0,1}$/", trim($iso), $r) == 0) {
			return ''; // bad input
		}
		$ret = LANGPATDATE;
		
		$ret = str_replace("d", $r[3], $ret);
		$ret = str_replace("m", $r[2], $ret);
		$ret = str_replace("y", $r[1], $ret);
	
		$ampm = '';
		if (LANGPATAMPM) {
			if ($r[4] > 12) {
				$r[4] -= 12;
				$ampm = "pm";
			} else {
				$r[4] += 0;		// skip leading zero if any
				$ampm = "am";
			}
		}
		
		$timeAdded = false;
		if ($r[6] != "00" || isset($r[7])) {
			$ret .= " $r[4]:$r[5]:$r[6]";
			$timeAdded = true;
		} else if ($r[4] != "00" || $r[5] != "00") {
			$ret .= " $r[4]:$r[5]";
			$timeAdded = true;
		}
		
		if( $timeAdded ) {
			// Optionally, add miliseconds.
			if( isset($r[7]) ) {
				$ret .= $r[7].str_repeat( '0', 4 - strlen( $r[7] ) );
			}
			// Add am/pm notation.		
			$ret .= $ampm;
		}
			
		return $ret;
	}

	/**
	 * Converts a given iso-datetime string to an associative array containing values for various 
	 * components for the given iso-datetime. For keys in the array see the standard php-function getdate().
	 *
	 * @param string $iso Iso-formatted datetime-string.
	 * @return array Associative array with values for the different datetime-components, see also getdate(). Emtpy when bad value given.
	 */
	static public function iso2dateArray( $iso )
	{
		if (!$iso) {
			return array(); // bad input
		}
		$r = array();
		if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})$/", trim($iso), $r) == 0) {
			return array(); // bad input
		}
		$ret = array();
	
		$ret['seconds'] = $r[6];
		$ret['minutes'] = $r[5];
		$ret['hours'] = $r[4];
		$ret['mday'] = $r[3];
		$ret['mon'] = $r[2];
		$ret['year'] = $r[1];
		
		return $ret;
	}

	/**
	 *	function addzeros formats a string to a string padded with zeros, for example with time
	 *	a '9' is converted to '09', 19 stays 19
	 *	@param $str inputstring
	 *	@param $sz length to pad the numbers: default = 2; 
	**/
	static public function addzeros( $str, $sz = 2 )
	{
		$len = strlen($str);
		if ($len < $sz) {
			$str = substr("0000000000", 0,$sz-$len).$str;
		}
		return $str;
	}

	/**
	 * Checks if given dateTime is in valid SOAP notation (Format: Y-m-dTH:i:s).
	 *
	 * @param string $dt dateTime to validate
	 * @return boolean Whether or not the date is valid
	 */
	static public function validSoapDateTime( $dt )
	{
		$arr = array();
		if( preg_match( '/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})$/i', trim($dt), $arr ) > 0 ) {
			return 
				is_numeric($arr[1]) && $arr[1] >= 0 &&                  // years
				is_numeric($arr[2]) && $arr[2] >= 0 && $arr[2] <= 12 && // months
				is_numeric($arr[3]) && $arr[3] >= 0 && $arr[3] <= 31 && // days (roughly checked)
				is_numeric($arr[4]) && $arr[4] >= 0 && $arr[4] <= 24 && // hours
				is_numeric($arr[5]) && $arr[5] >= 0 && $arr[5] <= 59 && // minutes
				is_numeric($arr[6]) && $arr[6] >= 0 && $arr[6] <= 59;   // seconds
		}
		return false;
	}
	
	/**
	 * Converts a date-inputstring (formatted as LANGPATDATE) to an $iso-formatted datetime-string
	 * @param $dt string date-time formatted string as a LANGPATDATE (for example D-M-Y or Y-M-D etc...)
	 * typically used for handling input from an string-edit-field. Also takes account of times entered
	 * with AM/PM. So 09:00 PM converts to 21:00.
	 * @param $incltime boolean if the time-part of the inputstring should be written to the iso-time
	 * @return string iso-formatted time string
	 * returns false if the inputstring was not valid
	**/
	static public function validDate($dt, $incltime = true)
	{
		$langpat = LANGPATDATE;
		$dt = trim($dt);
		if (!$dt) {
			return $dt;
		}
		$arr = array();
		$nr = 1;
		for ($i=0;$i<strlen($langpat);$i++) {
			switch ($langpat[$i]) {
				case 'd':
				case 'm':
				case 'y':
					$arr[$langpat[$i]] = $nr++;
					break;
			}
		}
		$pat = $langpat;
		$pat = str_replace("d", "([0-9]{1,2})", $pat);
		$pat = str_replace("m", "([0-9]{1,2})", $pat);
		$pat = str_replace("y", "([0-9]{2,4})", $pat);

		$r = array();
		if ( preg_match("/^$pat(.*)$/", $dt, $r) == 0) {
			return false;
		}
		if ($incltime) {
			if ($r[4]) {
				$rr = array();
				if (preg_match("/^([0-9]{1,2}):([0-9]{2})(:([0-9]{2})){0,1}[ ]*(am|pm){0,1}$/i", trim($r[4]), $rr) == 0) {
					return false;
				}
				// When validating time "00:00" preg_match will return an array with 3 elements: '00:00', '00' and '00'.
				// However, eregi() (which is depricated in PHP 5.3.0) would return 6 elements: '00:00', '00', '00', '', '' and ''. 
				// Check the number of items of the array before obtaining an element.
				// When validating the "00:00pm" preg_match will return the same result as eregi().
				$h = $rr[1];
				$i = $rr[2];
				if ( isset($rr[4]) ) {
					$s = $rr[4];		// beware NOT 3
				} else {
					$s = 0;
				}
				if ( isset($rr[5]) ) {
					$ampm = $rr[5];
					if ($ampm == "pm")	$h += 12;
				}
				if ($h >= 24 || $i >= 60 || $s >= 60) {
					return false;
				}
			}
		} else {
			if (trim($r[4])) {
				return false;
			}
		}
		$d = $r[$arr['d']];
		$m = $r[$arr['m']];
		$y = $r[$arr['y']];
		
		if ($y <100) {
			$y += 2000;
		}
		if (checkdate($m, $d, $y) === false) {
			return false;	
		}
					
		// to iso time
		$date = "y-m-d";
		$date = str_replace("d", self::addzeros($d), $date);
		$date = str_replace("m", self::addzeros($m), $date);
		$date = str_replace("y", self::addzeros($y,4), $date);
		
		$time = "00:00:00";
		if ($r[4]) {
			$time = self::addzeros($h).":".self::addzeros($i).":".self::addzeros($s);
		}
		return $date."T".$time;
	}

	/**
	 * Determine whether if the given unix timestamp ($time) passed in is a non working day.
	 *
	 * Function returns true if the date of the given Unix-time ($time) is either a holiday (in the HOLIDAYS-array)
	 * or the day of the week is in the NONWORKDAYS-array, typically a sunday (0) or saturday (6), otherwise returns false.
	 *
	 * @param int $time time in Unix-seconds
	 * @return bool
	 */
	static public function nonWorkDay( $time )
	{
		$info = getdate($time);
		if (in_array($info["wday"], unserialize(NONWORKDAYS))) {
			return true;
		}
		$date = date("Y-m-d", $time);
		if (in_array($date, unserialize(HOLIDAYS))) {
			return true;
		}
		$date = date("m-d", $time);
		if (in_array($date, unserialize(HOLIDAYS))) {
			return true;
		}
		return false;
	}

	/**
	 *	Calculates the difference in seconds between 2 times.
	 *	When calculating vacations/weekends are taken into account.
	 *	The function can work with negative as well as positive timedifferences
	 *	@param $time1 int time in Unix-seconds
	 *	@param $time2 int time in Unix-seconds
	 *	@return Timedifference in seconds between $time1 and $time2, but non-working days are not counted.
	**/
	static public function diffTime( $time1, $time2 )
	{
		if ($time1 < $time2) {
			return -1;
		}
		if ($time1 == $time2) {
			return 0;
		}
		$oneday = 24*3600;
		$tmp = floor(($time1-$time2) / $oneday);
		$diff = ($time1-$time2) - $tmp * $oneday;
		$time1 -= $diff;
		while (self::nonWorkDay($time1)) {
			$time1 -= $oneday;
		}
		while ($time1 > $time2) {
			$time1 -= $oneday;
			$diff += $oneday;
			while (self::nonWorkDay($time1)) $time1 -= $oneday;
		}
		
		return $diff;
	}
	
	static public function diffIsoTimes( $isotime1,  $isotime2 )
	{
		return self::diffTime( self::iso2time( $isotime1 ), self::iso2time( $isotime2 ) );		
	}

	/**
	 *	Calculates a new time on basis of an inputtime and a timedifference in seconds.
	 *	When calculating vacations/weekends are taken into account.
	 *	The function can work with negative as well as positive timedifferences (is this used?!?!?)
	 *	@param $iso	string iso-formatted time string
	 *	@param $diffsec int difference in seconds between $iso and the time to be calculated.
	 *	@return string iso-formatted calculated time.
	**/
	static public function calcTime($iso, $diffsec)
	{
		if (!$diffsec) {
			return $iso;
		}
		if (!trim($iso)) {
			return '';
		}
		$oneday = 24*3600;
		if ($diffsec < 0) {
			$diffsec = -$diffsec;
			$sign = -1;
		} else {
			$sign = +1;
		}	
		$time = self::iso2time( $iso );
		
		$nrdays = floor($diffsec/(24*3600));
		$secsleft = $diffsec-$nrdays*24*3600;
		
		$time += $sign * $secsleft;
		while (self::nonWorkDay($time)) {
			$time += $sign * $oneday;
		}
		while ($nrdays-- > 0) {
			$time += $sign * $oneday;
			while (self::nonWorkDay($time)) {
				$time += $sign * $oneday;
			}
		}
		return date('Y-m-d\TH:i:s', $time);
	}

	/**
     * Parse the Json Date format to PHP date format
     *
     * @param string $date Json date format, such as '1336197600000-0600'
     * @param string $type Convert type
     * @return string $retDate
     */
	static public function parseJsonDate( $date, $type = 'date' )
    {
    	$matches = null;
    	$retDate = null;

		// Match the time stamp (microtime) and the timezone offset (may be + or -) and also negative Timestamps
		if( preg_match('/\/Date\((-?\d+)([+-]\d{4})\)/', $date, $matches) == 0 ) {
			return $retDate;
		}
		
		$seconds  = $matches[1]/1000;                // microseconds to seconds
    	$UTCSec   = isset($matches[2]) ? $matches[2]/100*60*60 : 0;  // utc timezone difference in seconds
		$seconds  = $seconds + $UTCSec; // add or divide the utc timezone difference

	    $date     = date( 'Y-m-d', $seconds );      	// only date
    	$dateTime = date( 'Y-m-d\TH:i:s', $seconds );	// date and time
	    $time     = date( 'H:i:s', $seconds );       	// only time

		switch($type)
		{
			case 'date':
				$retDate = $date; // returns 'YYYY-MM-DD'
				break;
			case 'datetime':
				$retDate = $dateTime; // returns 'YYYY-MM-DD HH:ii:ss'
				break;
			case 'time':
				$retDate = $time; // returns 'HH:ii:ss'
				break;
			case 'array':
				$dateArray = str_replace(" ", "-", $dateTime);
				$dateArray = str_replace(":", "-", $dateArray);
				$retDate = explode('-', $dateArray); // return array('YYYY', 'MM', 'DD', 'HH', 'ii', 'SS')
				break;
			case 'string':
				$retDate = $matches[1] . $matches[2]; // returns 1336197600000-0600
				break;
    	}
    	return $retDate;
	}
}