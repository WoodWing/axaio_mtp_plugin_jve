<?php

/**
 * Helper functions for multi byte Unicode strings.
 *
 * @package Enterprise
 * @subpackage Utils
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
**/  

class UtfString
{
	/*
	 * Calculates the number of (internal) bytes of given multi-byte string. <br/>
	 * Byte count is important when storing strings into limited database fields. <br/>
	 * This function deals also with 3-byte Unicode, such as Japanese. <br/>
	 * Note: Do NOT confuse bytes with human readable (or printable) characters. <br/>
	 * Important: The PHP's mb_strwidth function fails for 3-byte strings!! <br/>
	 *   -> Those are counted as 2-byte chars. Tested with PHP v5.1. <br/>
	 *
	 * @input $string Multi-byte UTF-8 string
	 * @return integer Number of bytes (memory usage).
	 */
	static public function byteCount($string) {
	   return count(preg_split("`.`", $string)) - 1 ;
	}
	
	/*
	 * Returns hexadecimal representation of a multi-byte string. <br/>
	 * For debugging purposes. <br/>
	 *
	 * @input $string Multi-byte UTF-8 string
	 * @return string Space separated hex dump.
	 */
	static public function dumpHex($string) {
		$retVal = '';
		$sl = self::byteCount($string);
		for( $i = 0; $i < $sl; $i++ ) {
			 $retVal .= dechex(ord($string[$i])).' ';
		}
		return $retVal;
	}
	
	/**
	 * Convert string to UTF-8 encoding and remove illegal XML characters
	 *
	 * @param string $instring string to encode
	 * @return string encode UTF-8 string
	 */
	static public function smart_utf8_encode($instring)
	{
		if (!defined('MB_ENCODINGS')) {
			define('MB_ENCODINGS', 'UTF-8, ISO-8859-15, ISO-8859-1');
		}
		
		$instring = mb_convert_encoding($instring, 'UTF-8', MB_ENCODINGS);
		//Remove non-printable characters from begin and end (TODO check if x7F is really illegal)
		$instring = trim($instring, "\x7F\x00..\x1F");
		// BZ#12513 remove illegal XML characters (all before 0x1F except for tab, newline, carriage return)
		$instring = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F]/', '', $instring);
		
		return $instring;
	}
	
	/**
	 * Unescapes a string that was escaped with JavaScript escape() function.
	 * Note that JavaScript strings are in UCS-2 encoding.
	 *
	 * @since 7.5.1
	 * @param string $str Escaped string in UCS-2 (JavaScript) encoding.
	 * @return string Unescaped string in UTF-8
	 */
	static public function unescape( $str )
	{
		// PHP's rawurldecode() function seems not to work well for accented characters and therefore the 
		// line below is commented out. Instead, "%.{2}" is added to the expression in preg_match_all() 
		// to take over the functionality of rawurldecode(), but then better. For example, the u+diaeresis 
		// char is represented as "%fc" at the raw incoming string. This is doubtful(?) since it is a
		// single byte representation. (Better would be %u00fc.) The input originates from IDPreview.js. 
		// JavaScript respects UCS-2 format, which is two-byte. The IDPreview.js calls escape() to safely
		// send multi-byte characters through composedata.xml file, as read by BizWebEditWorkspace, which 
		// then calls this unescape() function to convert it to UTF-8 (as used in Enterprise Server core).
		// The characters from the 'extended ascii set' are having this kind of problem, which are in the 
		// range of 00A0...00FF (see http://www.columbia.edu/kermit/ucs2.html). All those char are badly  
		// escaped by JavaScript. Higher range characters (such as Chinese) are escaped in %udddd format 
		// and are therefore no problem. Related to BZ#27030 / BZ#27285 / BZ#26670.
		//$str = rawurldecode( $str ); 
		
		$matches = array();
		preg_match_all( '/(?:%u.{4})|&#x.{4};|&#\d+;|%.{2}|.+/U', $str, $matches );
		$ar = $matches[0];
		foreach( $ar as $key => $val ) {
			if( substr( $val, 0, 2 ) == '%u' ) { // %uhhhh
				$ar[$key] = iconv( 'UCS-2', 'UTF-8', pack( 'H4', substr( $val, -4 ) ) );
			} elseif( substr( $val, 0, 1 ) == '%' ) { // %dd
				$ar[$key] = iconv( 'UCS-2', 'UTF-8', pack( 'H4', '00'.substr( $val, -2 ) ) ); // BZ#27030 (see above)
			} elseif( substr( $val, 0, 3 ) == '&#x' ) { // &#xhhhh;
				$ar[$key] = iconv( 'UCS-2', 'UTF-8', pack( 'H4', substr( $val, 3, -1 ) ) );
			} elseif( substr( $val, 0, 2 ) == '&#' ) { // &#dddd;
				$ar[$key] = iconv( 'UCS-2', 'UTF-8', pack( 'n', substr( $val, 2, -1 ) ) );
			}
		}
		return join( '', $ar );
	}

	/**
	 * Truncate the field value if it exceeds the passed in length value.
	 * It will truncate the extra characters or bytes, based on DB flavors:
	 *     L> MYSQL: Truncates length characters.
	 *     L> MSSQL,ORACLE, Mysql blob: Truncates length bytes.
	 *
	 * @param string $fieldValue The field value to be checked if it needs to be truncated.
	 * @param integer $length Maximum number of characters to use from $fieldValue.
	 * @param bool $isTextField Is the $fieldValue a text field? For an example, blob is not seen as text field hence it is not multi-byte aware in the case of Mysql.
	 * @return string $fieldValue Value that has been adjusted if the chars/bytes has exceeded length.
	 */
	static public function truncateMultiByteValue( $fieldValue, $length, $isTextField = true )
	{
		if( $length > 0 ) {
			$dbdriver = DBDriverFactory::gen();
			if( $dbdriver->hasMultibyteSupport() && $isTextField ) { // MYSQL
				// mb_substr gets the length of string in number of characters
				$fieldValue = mb_substr( $fieldValue, 0, $length, 'UTF-8' );
			} else { // Oracle & MSSQL Or Mysql with blob field
				// mb_strcut gets the length of string in bytes
				$fieldValue = mb_strcut( $fieldValue, 0, $length, 'UTF-8' );
			}
		}
		return $fieldValue;
	}
}
