<?php
/**
 * Utils class that helps to determine whether or not a given IP network address matches
 * with given ranges of network IP addresses. Supports IPv4 and IPv6 addresses and ranges.
 *
 * @package 	Enterprise
 * @subpackage 	Utils
 * @since 		v9.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_IpAddressRange
{
	const IPV_UNKNOWN = -1;
	const IPV4 = 0;
	const IPV4_V6_DUAL = 1;
	const IPV6 = 2;
	
	/**
	 * Tells whether or not a given IP address ($ip) does NOT match with a given
	 * list of excluded IP ranges ($excludeRanges) and/or DOES match with a given
	 * list of included IP ranges ($includeRanges). When a match is found in the
	 * excluded IP ranges, false is returned (without checking the included IP ranges).
	 * Else, if a match is found in the included IP ranges, true is returned.
	 * When the IP does not match with both IP ranges, false is returned.
	 * See {@link:isIpInRange()} for supported IPs and notations of IP ranges.
	 *
	 * @param string $ip IP network address
	 * @param string[] $excludeRanges List of ranges of network IP addresses
	 * @param string[] $includeRanges List of ranges of network IP addresses
	 * @return boolean When $ip matches, true is returned, else false.
	 */
	public static function isIpIncluded( $ip, $excludeRanges, $includeRanges )
	{
		if( $excludeRanges ) foreach( $excludeRanges as $range ) {
			if( self::isIpInRange( $ip, $range ) ) {
				return false;
			}
		}
		if( $includeRanges ) foreach( $includeRanges as $range ) {
			if( self::isIpInRange( $ip, $range ) ) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Determines whether or not a given IP address ($ip) matches within a IP range ($range).
	 *
	 * When $ip is a IPv4 or dual-stack IPv4-IPv6, then $range should be IPv4, else false 
	 * is returned. When $ip is a IPv6, then $range also should be IPv6, else false is returned.
	 *
	 * For the given IPv4 range, the following notations are supported:
	 * '192.0.0.1'    => matches with '192.0.0.1' only
	 * '192.0.0.*'    => matches with ['192.0.0.0'...'192.0.0.255']
	 * '192.0.*.*'    => matches with ['192.0.0.0'...'192.0.255.255']
	 * '192.0.0.0/16' => matches with ['192.0.0.0'...'192.0.255.255']
	 * '192.0.0.0/24' => matches with ['192.0.0.0'...'192.0.0.255']
	 * '192.0.0.0/255.255.0.0'   => matches with ['192.0.0.0'...'192.0.255.255']
	 * '192.0.0.0/255.255.255.0' => matches with ['192.0.0.0'...'192.0.0.255']
	 * '192.0.0.0-192.0.0.255'   => matches with ['192.0.0.0'...'192.0.0.255']
	 * '192.0.0.0-192.0.255.255' => matches with ['192.0.0.0'...'192.0.255.255']
	 *
	 * For the given IPv6 range, the following notations are supported:
	 * '::1'                     => matches with '0:0:0:0:0:0:0:1' only
	 * '2001:db8::1'             => matches with '2001:db8:0:0:0:0:0:1' only
	 * '2001:db8::/32'           => matches with ['2001:db8:0:0:0:0:0:0'-'2001:db8:ffff:ffff:ffff:ffff:ffff:ffff']
	 * '2001:db8::/64'           => matches with ['2001:db8:0:0:0:0:0:0'-'2001:db8:0:0:ffff:ffff:ffff:ffff']
	 * '2001:db8::-2001:db9::'   => matches with ['2001:db8:0:0:0:0:0:0'-'2001:db9:0:0:0:0:0:0']
	 * '2001:db8::-2001:db8::ffff' => matches with ['2001:db8:0:0:0:0:0:0'-'2001:db8:0:0:0:0:0:ffff']
	 *
	 * @param string $ip IP network address (IPv4, IPv6 or dual-stack IPv4-IPv6). Also accepts 'localhost'.
	 * @param string $range Range of network IP addresses (IPv4 or IPv6)
	 * @return boolean When $ip matches, true is returned, else false.
	 */
	public static function isIpInRange( $ip, $range )
	{
		$inRange = false;
		$rangeVersion = self::detectIpVersion( $range );
		if( $ip == 'localhost' ) {
			switch( $rangeVersion ) {
				case self::IPV4:
					$ip = '127.0.0.1';
					break;
				case self::IPV6:
					$ip = '::1';
					break;
			}
		}
		switch( self::detectIpVersion( $ip ) ) {
			case self::IPV6:
				if( $rangeVersion == self::IPV6 ) {
					$inRange = self::isIpv6InRange( $ip, $range );
				}
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::IPV4_V6_DUAL:
				$ip = self::convertIpv6v4DualToIpv4( $ip );
				// NO break!
			case self::IPV4:
				if( $rangeVersion == self::IPV4 ) {
					$inRange = self::isIpv4InRange( $ip, $range );
				}
				break;
		}
		return $inRange;
	}
	
	/**
	 * Same as {@link: isIpInRange()} but for IPv4 addresses only.
	 *
	 * @param string $ip IPv4 network address
	 * @param string $range Range of network IPv4 addresses
	 * @return boolean When $ip matches, true is returned, else false.
	 */
	private static function isIpv4InRange( $ip, $range )
	{
		$ipInt = self::ip2float( $ip );
		if( strpos( $range, '/' ) !== false ) { // format: ip/netmask
			list( $range, $netmask ) = explode( '/', $range, 2 );
			$rangeInt = self::ip2float( $range );
			if( strpos( $netmask, '.' ) !== false ) { // format: ip/a.b.c.d
				$netmaskInt = self::ip2float( $netmask );
				$inRange = ( ($ipInt & $netmaskInt) == ($rangeInt & $netmaskInt) );
			} else { // format: ip/cidr
				$cidrMask = ~0 << (32 - $netmask); // note that 0xFFFFFFFF does not work on 64 bits, so use ~0
				$inRange = ( ($ipInt & $cidrMask) == ($rangeInt & $cidrMask) );
			}
		} elseif( strpos( $range, '-' ) !== false ) { // format: ipLower-ipUpper
			list( $lower, $upper ) = explode( '-', $range, 2 );
			$lowerInt = self::ip2float( $lower );
			$upperInt = self::ip2float( $upper );
			$inRange = ( ($ipInt >= $lowerInt) && ($ipInt <= $upperInt) );
		} else {
			if( strpos( $range, '*' ) !== false ) { // format: a.b.c.*
				$lowerInt = self::ip2float( str_replace( '*', '0', $range ) );
				$upperInt = self::ip2float( str_replace( '*', '255', $range ) );
				$inRange = ( ($ipInt >= $lowerInt) && ($ipInt <= $upperInt) );
			} else { // format: a.b.c.d
				$rangeInt = self::ip2float( $range );
				$inRange = ( $ipInt == $rangeInt );
			}
		}
		return $inRange;
	}
	
	/**
	 * Same as {@link: isIpInRange()} but for IPv6 addresses only.
	 *
	 * @param string $ip IPv6 network address
	 * @param string $range Range of network IPv6 addresses
	 * @return boolean When $ip matches, true is returned, else false.
	 */
	private static function isIpv6InRange( $ip, $range )
	{
		$ipBits = self::ipv6BinToBitStr( inet_pton( $ip ) );
		if( strpos( $range, '/' ) !== false ) { // format: ip/cidr
			list( $netmask, $cidr ) = explode( '/', $range, 2 );
			$netmaskBits = self::ipv6BinToBitStr( inet_pton( $netmask ) );
			$inRange = substr( $ipBits, 0, $cidr ) == substr( $netmaskBits, 0, $cidr );
		} elseif( strpos( $range, '-' ) !== false ) { // format: ipLower-ipUpper
			list( $lower, $upper ) = explode( '-', $range, 2 );
			$lowerBits = self::ipv6BinToBitStr( inet_pton( $lower ) );
			$upperBits = self::ipv6BinToBitStr( inet_pton( $upper ) );
			$sorted = array( $ipBits, $lowerBits, $upperBits );
			sort( $sorted ); // when $ip is in range, it should end up between lower and upper
			$inRange = ($sorted[1] == $ipBits);
		} else { // format: ip
			$rangeBits = self::ipv6BinToBitStr( inet_pton( $range ) );
			$inRange = ( $ipBits == $rangeBits );
		}
		return $inRange;
	}
	
	/**
	 * Converts a IPv6 binary (16 bytes) into a string of '0' and '1' chars (128 bits).
	 *
	 * @param string $ipv6bin IPv6 in 16 bytes. Compatible with return value of inet_pton().
	 * @return string IPv6 in 128 bits, formatted in '0' and '1' chars.
	 */
	private static function ipv6BinToBitStr( $ipv6bin )
	{
		$bitStr = '';
		foreach( str_split($ipv6bin) as $char ) {
			$bitStr .= str_pad( decbin( ord($char) ), 8, '0', STR_PAD_LEFT );
		}
		return $bitStr;
	}	
	
	/**
	 * Converts a hybrid dual-stack IPv4-IPv6 network address into a pure IPv4 address.
	 * In these addresses, the first 80 bits are zero, the next 16 bits are one, and the 
	 * remaining 32 bits are the IPv4 address. Note that leading zeros and consecutive
	 * colons can be omitted.
	 *
	 * However, it does NOT support pure IPv6 addresses.
	 *
	 * Note that an IPv6 address can have two formats:
	 * - Normal - Pure IPv6 format:
	 *      1111:2222:3333:4444:5555:6666:7777:8888
	 * - Dual - IPv6 plus IPv4 formats:
	 *      0000:0000:0000:0000:0000:00FF:1.2.3.4
	 *      ::0000:00FF:1.2.3.4
	 *      ::FF:1.2.3.4
	 *      ::1.2.3.4 (deprecated format)
	 *
	 * @param string $ip Dual IPv4-IPv6 network address
	 * @return string Pure IPv4 network address
	 */
	private static function convertIpv6v4DualToIpv4( $ip )
	{
		return substr( $ip, strrpos( $ip, ':' ) + 1 );
	}
	
	/**
	 * Converts a given human readalbe IP address into a 32 bit unsigned integer, 
	 * carried out in a float container.
	 *
	 * Note that PHP does not support unsigned(!) integers. And for Windows, 64 bits is 
	 * not supported. Because an IP address consist 4 digits, each one byte (0...255),
	 * a converted address takes the full range of an integer. Since it can not be
	 * unsigned, on a 32 bits platform, PHP's ip2long() function returns values in
	 * the range of [-2147483648...2147483647], which is unwanted. Nevertheless, when
	 * PHP runs in 64 bits mode, PHP's ip2long() function returns [0...4294967295],
	 * which is wanted, but inconsistent (with 32 bits). 
	 
	 * The ip2float() function solves the inconsistency of PHP's ip2long() function by
	 * returning a float that holds the 32 bits unsigned integer value. So the returned 
	 * value is in the range [0...4294967296], regardless of PHP running in 32 or 64 bits mode.
	 *
	 * @param string $ip Network address in 'a.c.b.d' notation.
	 * @return float Network address as 32 bits unsigned integer
	 */
	private static function ip2float( $ip )
	{
		// Under Windows running PHP 5.5 (nts, x86) the ip2long() function returns zero
		// when passing e.g. '127.000.000.001'. (Note that '127.0.0.1' works properly.)
		// For PHP 5.4 this is no problem, nor it is for both PHP versions on MacOSX.
		// To be robust, we cast all digits to integer to get rid of the leading zeros.
		$ip = implode( '.', array_map( 'intval', explode( '.', $ip ) ) );
		
		// Now convert the IP to an unsigned integer and then to a float value.
		// See function header for more info why this is done this way.
		return floatval( sprintf( "%u", ip2long( $ip ) ) );
	}
	
	/**
	 * Validates a given network IP range. Both IPv4 and IPv6 addresses are supported.
	 * See {@link:isIpInRange()} for supported notations.
	 *
	 * @param string $range Range of network IP addresses
	 * @return boolean When $range is valid, true is returned, else false.
	 */
	public static function isValidRange( $range )
	{
		$isValid = false;
		switch( self::detectIpVersion( $range ) ) {
			case self::IPV4:
				$isValid = self::isValidIpv4Range( $range );
			break;
			case self::IPV6:
				$isValid = self::isValidIpv6Range( $range );
			break;
		}
		return $isValid;
	}
	
	/**
	 * Validates a given network IPv4 range.
	 * See {@link:isIpInRange()} for supported notations.
	 *
	 * @param string $range Range of network IPv4 addresses
	 * @return boolean When $range is valid, true is returned, else false.
	 */
	public static function isValidIpv4Range( $range )
	{
		$isValid = false;
		if( strpos( $range, '/' ) !== false ) { // format: ip/netmask
			if( substr_count( $range, '/' ) == 1 ) {
				list( $range, $netmask ) = explode( '/', $range, 2 );
				if( self::isValidIpv4( $range ) ) {
					if( strpos( $netmask, '.' ) !== false ) { // format: ip/a.b.c.d
						$isValid = self::isValidIpv4( $netmask );
					} else { // format: ip/cidr
						if( ctype_digit( $netmask ) || is_int( $netmask ) ) {
							$isValid = ($netmask >= 0 && $netmask <= 32);
						}
					}
				}
			}
		} elseif( strpos( $range, '-' ) !== false ) { // format: ipLower-ipUpper
			if( substr_count( $range, '-' ) == 1 ) {
				list( $lower, $upper ) = explode( '-', $range, 2 );
				if( self::isValidIpv4( $lower ) && self::isValidIpv4( $upper ) ) {
					$isValid = $upper > $lower;
				}
			}
		} elseif( substr_count( $range, '*' ) <= 4 ) { // more than 4 does not make sense
			if( strpos( $range, '*' ) !== false ) { // format: a.b.c.*
				$range = str_replace( '*', '0', $range );
			}
			// format: a.b.c.d
			$isValid = self::isValidIpv4( $range );
		}
		return $isValid;
	}
	
	/**
	 * Validates a given network IPv6 range.
	 * See {@link:isIpInRange()} for supported notations.
	 *
	 * @param string $range Range of network IPv6 addresses
	 * @return boolean When $range is valid, true is returned, else false.
	 */
	public static function isValidIpv6Range( $range )
	{
		$isValid = false;
		if( strpos( $range, '/' ) !== false ) { // format: ip/cidr
			if( substr_count( $range, '/' ) == 1 ) {
				list( $range, $cidr ) = explode( '/', $range, 2 );
				if( self::isValidIpv6( $range ) ) {
					if( ctype_digit( $cidr ) || is_int( $cidr ) ) {
						$isValid = ($cidr >= 0 && $cidr <= 128);
					}
				}
			}
		} elseif( strpos( $range, '-' ) !== false ) { // format: ipLower-ipUpper
			if( substr_count( $range, '-' ) == 1 ) {
				list( $lower, $upper ) = explode( '-', $range, 2 );
				if( self::isValidIpv6( $lower ) && self::isValidIpv6( $upper ) ) {
					$lowerBits = self::ipv6BinToBitStr( inet_pton( $lower ) );
					$upperBits = self::ipv6BinToBitStr( inet_pton( $upper ) );
					$sorted = array( $upperBits, $lowerBits ); // initially put in wrong order
					sort( $sorted );
					$isValid = ( $sorted[0] == $lowerBits && $sorted[1] == $upperBits );
				}
			}
		} else { // format: ip
			$isValid = self::isValidIpv6( $range );
		}
		return $isValid;
	}
		
	/**
	 * Validates whether or not a given network address is in IPv4 notation.
	 *
	 * @param string $ip Network address to validate
	 * @return boolean Returns true when given IP is a IPv4 address, else false.
	 */
	private static function isValidIpv4( $ip )
	{
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false;
	}
	
	/**
	 * Validates whether or not a given network address is in IPv6 notation.
	 *
	 * @param string $ip Network address to validate
	 * @return boolean Returns true when given IP is a IPv6 address, else false.
	 */
	private static function isValidIpv6( $ip )
	{
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;
	}
	
	/**
	 * Detects the version of a given network address.
	 *
	 * Note that it does NOT validate the address. Use {@link:isValidIpv4()} for that.
	 *
	 * It returns one of the following values:
	 * - self::IPV4 => for a human readable 32 bits IPv4 address in 'a.b.c.d' notation
	 * - self::IPV4_V6_DUAL => for a combined IPv4-IPv6 address in '...:a.b.c.d' notation
	 * - self::IPV6 => for a human readable 128 bits IPv6 address in 'a:b:c:d:e:f:g:h' notation
	 * - self::IPV_UNKNOWN => when none of the above notations is recognized
	 *
	 * @param string $ip Network address to inspect
	 * @return integer See header for details.
	 */
	private static function detectIpVersion( $ip )
	{
		$hasColon = strpos( $ip, ':' ) !== false;
		$hasDot = strpos( $ip, '.' ) !== false;
		if( $hasColon ) {
			$ipVersion = $hasDot ? self::IPV4_V6_DUAL : self::IPV6;
		} else {
			$ipVersion = $hasDot ? self::IPV4 : self::IPV_UNKNOWN;
		}
		return $ipVersion;
	}
}
