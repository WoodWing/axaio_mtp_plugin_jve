<?php

/**
 * Splitting a string by a delimiter.
 *
 * @package     Enterprise
 * @subpackage  Utils
 * @since       v10.0.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * This utils class provides the possibility to split a string given a delimiter.
 * Nevertheless, when the value happens to be the same as the delimiter, the value
 * will be escaped. And the splitting should not happen on the escaped value ( the "delimiter").
 */

/********
For Debugging purposes: Can uncomment the fragment below and directly run the script

$mvList = array( '/test1\\test2/', 'test3/test4', 'test5\\\\test6//test7', '. -!@#$%^&*()[]{}' );
print'List: '.print_r( $mvList, true );

$encoder = new WW_Utils_ListEncoder( '\\', '/' );
$dbData = $encoder->encodeList( $mvList );
print'Encoded: '. $dbData.PHP_EOL;

$mvList = $encoder->decodeList( $dbData );
print'Decoded: '.print_r( $mvList, true );

*********/

class WW_Utils_ListEncoder
{
	/**
	 * The delimiter / glue that will be used to join a list of values.
	 *
	 * @var string
	 */
	private $delimiter;

	/**
	 * The character that will be / needs to be escaped by this $escapeChar.
	 *
	 * @var string
	 */
	private $escapeChar;

	/**
	 * WW_Utils_ListEncoder constructor.
	 *
	 * @param string $escapeChar
	 * @param string $delimiter
	 */
	public function __construct( $escapeChar, $delimiter )
	{
		$this->delimiter = $delimiter;
		$this->escapeChar = $escapeChar;
	}

	/**
	 * The function takes a list of values and "join" the values as one string.
	 *
	 * The original values are preserved, this is important especially
	 * when the original value(s) happens to be the same as the delimiter.
	 * In this case, it should not be seen as a delimiter and the original
	 * value should be preserved as it is.
	 *
	 * @param array $list A list of values to be "encoded" and join as one string.
	 * @return string
	 */
	public function encodeList( array $list )
	{
		$encodedItems = array_map( array( $this, 'encodeListItem' ), $list );
		return implode( $this->delimiter, $encodedItems );
	}

	/**
	 * Function takes a string and split them into a list of values.
	 *
	 * The function takes a string "encoded" by encodeList() and
	 * revert it back to a list of its original values.
	 *
	 * The function makes sure that any value that happens to be the
	 * same as the delimiter will retain its original value and not
	 * be seen as the delimiter.
	 *
	 * The solution below is taken from
	 * http://stackoverflow.com/questions/6243778/split-string-by-delimiter-but-not-if-it-is-escaped
	 *
	 * @param string $text A string that has been "encoded" by encodeList(), to be "decoded".
	 * @return array
	 */
	public function decodeList( $text )
	{
		$d = preg_quote( $this->delimiter, '~' );
		$e = preg_quote( $this->escapeChar, '~' );
		$tokens = preg_split(
			'~' . $e . '(' . $e . '|' . $d . ')(*SKIP)(*FAIL)|' . $d . '~',
			$text
		);
		return preg_replace(
			array( '~' . $e . $e . '~', '~' . $e . $d . '~' ),
			array( $this->escapeChar, $this->delimiter ),
			$tokens
		);
	}

	/**
	 * Function adds delimiter to the list of values $listItem, passed in.
	 *
	 * This function takes care of adding the delimiter into the list so that
	 * the caller can just use this list of values and join them into a string
	 * using a delimiter. Caller doesn't need to worry if the delimiter is also
	 * one of the values in this list.
	 *
	 * @param array $listItem List of values to be added with delimiter.
	 * @return array
	 */
	private function encodeListItem( $listItem ) 
	{
		$listItem = str_replace( $this->escapeChar, $this->escapeChar . $this->escapeChar, $listItem );
		$listItem = str_replace( $this->delimiter, $this->escapeChar . $this->delimiter, $listItem );
		return $listItem;
	}
}