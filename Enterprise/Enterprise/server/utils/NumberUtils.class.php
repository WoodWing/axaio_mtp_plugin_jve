<?php
/**
 * Generic number related functions
 * 
 * @package Enterprise
 * @subpackage Utils
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class NumberUtils
{
	/**
	 * Returns a raw number as a byte display string, e.g. 3 KB, 10 MB etc,
	 *
	 * @param integer $size
	 * @param integer $precision
	 * @return string
	 */
	public static function getByteString( $size, $precision=0 ) 
	{
	  $sizes = Array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
	  $ext = $sizes[0];
	  for ($i=1; (($i < count($sizes)) && ($size >= 1024)); $i++) {
	   $size = $size / 1024;
	   $ext  = $sizes[$i];
	  }
	  return sprintf('%0.'.$precision.'f',round($size, $precision)) . " " . $ext;
	}

	/**
	 * Creates a number range string from a list of numbers. Examples:
	 * 1 2 3 4 5 => 1-5
	 * 1 3 4 5 => 1,3-5
	 * When the string length exceeds $maxLen the string will be shortened to '1 ~ 123'
	 * 
	 * The input is sorted and cleaned from duplicates
	 * 
	 * @param array of numbers $pagenumbers
	 * @param integer $maxLen
	 * @return string
	 */
	public static function createNumberRange( $pagenumbers, $maxLen=40 )
	{
		$pagerange = '';
		// Remove duplicate page numbers and sort
		$pagenumbers = array_unique( $pagenumbers );
		sort( $pagenumbers );
		$pageNumberCount = count( $pagenumbers );
		$lastPageNumber="";
		if( $pageNumberCount >= 1 ) {
			$pagerange = $pagenumbers[0];
			for( $pgn=1 ; $pgn < $pageNumberCount; ++$pgn ) {
				if( $pagenumbers[$pgn] == $pagenumbers[$pgn-1]+1 ) {
					// consecutive page number, remember last number, don't add to string yet
					$lastPageNumber = $pagenumbers[$pgn];
				}
				else {
					// non-consecutive, see if we need to close previous range and add this number:
					if( $lastPageNumber != "" ) {
						$pagerange .= "-".$lastPageNumber;
						$lastPageNumber = "";
					}
					$pagerange .= ",".$pagenumbers[$pgn];
				}
			}
			// See if we have a open range we need to close:
			if( $lastPageNumber != "" ) {
				$pagerange = $pagerange . "-".$lastPageNumber;
				$lastPageNumber = "";
			}
		}
		
		// Too long, change to <start>~<end>
		if( strlen($pagerange) > $maxLen ) {
			$pagerange = $pagenumbers[0] . ' ~ ' . $pagenumbers[count($pagenumbers)-1];
		}
		return $pagerange;
	}

	/**
	 * Converts an arabic number to a roman number. 
	 * For example: 1,2,3,...,38,39,40 is converted in: I,II,III,...,XXXVIII,XXXIX,XL
	 *
	 * @param $num integer  Must be between 0 and 9,999 
	 * @return string representing roman number. Returns empty on error.
	 */ 
	public static function toRomanNumber( $num ) 
	{ 
		if ($num < 0 || $num > 9999) { return ''; } // out of range 
		
		$r_ones = array(1=> "I", 2=>"II", 3=>"III", 4=>"IV", 5=>"V", 6=>"VI", 7=>"VII", 8=>"VIII", 
		9=>"IX"); 
		$r_tens = array(1=> "X", 2=>"XX", 3=>"XXX", 4=>"XL", 5=>"L", 6=>"LX", 7=>"LXX", 
		8=>"LXXX", 9=>"XC"); 
		$r_hund = array(1=> "C", 2=>"CC", 3=>"CCC", 4=>"CD", 5=>"D", 6=>"DC", 7=>"DCC", 
		8=>"DCCC", 9=>"CM"); 
		$r_thou = array(1=> "M", 2=>"MM", 3=>"MMM", 4=>"MMMM", 5=>"MMMMM", 6=>"MMMMMM", 
		7=>"MMMMMMM", 8=>"MMMMMMMM", 9=>"MMMMMMMMM"); 
		
		$ones = $num % 10; 
		$tens = ($num - $ones) % 100; 
		$hundreds = ($num - $tens - $ones) % 1000; 
		$thou = ($num - $hundreds - $tens - $ones) % 10000; 
		
		$tens = intval( $tens / 10 );
		$hundreds = intval( $hundreds / 100 );
		$thou = intval( $thou / 1000 );
		
		$rnum = '';
		if ($thou) { $rnum .= $r_thou[$thou]; } 
		if ($hundreds) { $rnum .= $r_hund[$hundreds]; } 
		if ($tens) { $rnum .= $r_tens[$tens]; } 
		if ($ones) { $rnum .= $r_ones[$ones]; } 
		
		return $rnum; 
	}
	
	/**
	 * Converts an arabic number to an alpha number, especially used for pages or columns.
	 * For example: 1,2,3,...,26,27,28 is converted in: A,B,C,...,Z,AA,AB
	 *
	 * @param $num integer  Must be > 0
	 * @return string representing an alpha number. Returns empty on error.
	 */ 
	public static function toAlphaNumber( $num )
	{
		$anum = '';
		while( $num >= 1 ) {
			$num = $num - 1;
			$anum = chr(($num % 26)+65).$anum;
			$num = $num / 26;
		}
		return $anum;
	}
	
	/*
	 * Generates a new GUID (in Adobe-compatible format: 8-4-4-4-12).
	 * GUID stands for Global Unique IDentifier.
	 *
	 * @return string   GUID
	 */
	static public function createGUID()
	{
        // Create a md5 sum of a random number - this is a 32 character hex string
        $raw_GUID = md5( uniqid( getmypid() . rand( ) . (double)microtime()*1000000, TRUE ) );

        // Format the string into 8-4-4-4-12 (numbers are the number of characters in each block)
        return  substr($raw_GUID,0,8) . "-" . substr($raw_GUID,8,4) . "-" . substr($raw_GUID,12,4) . "-" . substr($raw_GUID,16,4) . "-" . substr($raw_GUID,20,12);
	}

	/**
	 * Validates a GUID (expected to be in 8-4-4-4-12 format).
	 * This can be used to anti-hijack incoming HTTP request parameters.
	 *
	 * @param string $guid Value to validate.
	 * @return bool TRUE when ok, FALSE when in bad format.
	 */
	static public function validateGUID( $guid )
	{
		if( !is_string( $guid ) ) {
			return false;
		}
		$lens = array( 8, 4, 4, 4, 12 ); // expected lenghts of each individual parts
		$parts = explode( '-', $guid );
		if( count($parts) != count($lens) ) {
			return false;
		}
		foreach( $parts as $key => $part ) {
			if( strlen( $part ) != $lens[$key] ) {
				return false;
			}
			if( !ctype_xdigit( $part ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Determines the PHP version number.
	 *
	 * Excludes the EXTRA information that is sometimes added based on distributions.
	 * Additionally the version string gets padded with minor / release version if those
	 * are not available.
	 *
	 * @return string The release version string.
	 */
	static public function getPhpVersionNumber()
	{
		// Since PHP 5.2.7 there are constants defined for the PHP version digits.
		if (defined('PHP_MAJOR_VERSION')){
			return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
		}

		// Pre 5.2.7, calculate the version numbers manually.
		$versions = explode('.',PHP_VERSION,  3);

		// Cleanup versions, remove any extra information.
		foreach ($versions as $k => $v){
			if (!is_numeric($v)){
				$versionArray = str_split($v);
				$incomplete = true;
				$number = '';
				foreach ($versionArray as $ver){
					if (is_numeric($ver) && $incomplete){
						$number .= $ver;
					} else {
						$incomplete = false;
					}
				}
				$versions[$k] = ($number == '') ? '0' : $number;
			}
		}
		// Assign versions.
		$majorVersion = isset($versions[0]) ? $versions[0] : '0';
		$minorVersion = isset($versions[1]) ? $versions[1] : '0';
		$releaseVersion = isset($versions[2]) ? $versions[2]: '0';

		return $majorVersion . '.' . $minorVersion . '.' . $releaseVersion;
	}
	
	/**
	 * Returns a list of php versions that are configured by Enterprise Server.
	 *
	 * See SCENT_PHPVERSIONS option in server/serverinfo.php file for more info.
	 *
	 * @return string[] List of configured versions.
	 */
	static private function getConfiguredPhpVersions()
	{
		static $phpVersions = null;
		
		// Collect php versions supported by core server.
		if( is_null($phpVersions) ) {
			$phpVersions = unserialize(SCENT_PHPVERSIONS);
		}
		
		return $phpVersions;
	}

	/**
	 * Returns a list of php versions that are supported by Enterprise Server.
	 *
	 * See SCENT_PHPVERSIONS option in server/serverinfo.php file for more info.
	 *
	 * @return string[] List of supported versions.
	 */
	static public function getSupportedPhpVersions()
	{
		// Collect php versions configured for core server.
		$configPhpVersions = self::getConfiguredPhpVersions();
		
		// Include all versions, except the excluded versions (prefixed by "-" char).
		$supportedPhpVersions = array();
		if( $configPhpVersions ) foreach( $configPhpVersions as $configPhpVersion ) {
			if( substr( $configPhpVersion, 0, 1 ) != '-' ) {
				$supportedPhpVersions[] = $configPhpVersion;
			}
		}
	
		return $supportedPhpVersions;
	}

	/**
	 * Returns a list of php versions that are NOT supported by Enterprise Server.
	 *
	 * See SCENT_PHPVERSIONS option in server/serverinfo.php file for more info.
	 *
	 * @return string[] List of unsupported versions.
	 */
	static public function getUnsupportedPhpVersions()
	{
		// Collect php versions configured for core server.
		$configPhpVersions = self::getConfiguredPhpVersions();
		
		// Include all versions, except the excluded versions (prefixed by "-" char).
		$unsupportedPhpVersions = array();
		if( $configPhpVersions ) foreach( $configPhpVersions as $configPhpVersion ) {
			if( substr( $configPhpVersion, 0, 1 ) == '-' ) {
				$unsupportedPhpVersions[] = substr( $configPhpVersion, 1 );
			}
		}
	
		return $unsupportedPhpVersions;
	}

	/**
	 * Tells whether or not a given php version is supported by Enterprise Server.
	 *
	 * See SCENT_PHPVERSIONS option in server/serverinfo.php file for more info.
	 *
	 * @param string $phpVersion PHP version to check. Format: "major.minor.patch"
	 * @return boolean TRUE when supported, else FALSE.
	 */
	static public function isPhpVersionSupported( $phpVersion )
	{
		// Take the php versions that are supported by Enterprise Server.
		$supportedPhpVersions = self::getConfiguredPhpVersions();
		
		// Is the given php version literally listed?
		if( in_array( $phpVersion, $supportedPhpVersions ) ) {
			return true; // supported
		}
	
		// Is the given php version excluded?
		foreach( $supportedPhpVersions as $supportedPhpVersion ) {
			if( '-'.$phpVersion == $supportedPhpVersion ) {
				return false; // not supported
			}
		}
	
		// Is the given php version matching a range?
		foreach( $supportedPhpVersions as $supportedPhpVersion ) {
			// For example, $supportedPhpVersion = "5.5.16+"
			if( substr( $supportedPhpVersion, -1 ) == '+' ) {
				// Remove the "+" postfix. E.g. $supportedPhpVersion = "5.5.16"
				$supportedPhpVersion = substr( $supportedPhpVersion, 0, strlen($supportedPhpVersion)-1 );
				// Split on "." char. E.g. $parts[0]=5, $parts[1]=5, $parts[2]=16
				$parts = explode( '.', $supportedPhpVersion );
				// Compose major.minor, but take next minor. E.g. $majorNextMinor = "5.6"
				$majorNextMinor = $parts[0].'.'.($parts[1]+1);
				// Check range. E.g. $phpVersion >= 5.5.16 && $phpVersion < 5.6
				if( version_compare( $phpVersion, $supportedPhpVersion, '>=' ) &&
					version_compare( $phpVersion, $majorNextMinor, '<' ) ) {
					return true; // supported
				}
			}
		}
	
		// No matching anything above, so not supported.
		return false;
	}
}
