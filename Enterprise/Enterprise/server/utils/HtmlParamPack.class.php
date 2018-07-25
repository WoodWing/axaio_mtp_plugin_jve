<?php
/**
 * Html parameter package utility class.
 *
 * A packs/unpacks Html params into a special format.
 * The format allows to inject objects into Html pages.
 * This can be done for any element attribute using double quotes.
 * JavaScript can collect packed objects from the page and post it back to PHP.
 * This can be extreemly usefull if you can to let user select some objects.
 * Once the selection is posted, PHP does NOT have to request objects from db again.
 * Instead, it unpacks them from the form post!
 * Tip: Have a look a importgroups.php for implementation example.
 *
 * For example, the package can be store into a checkbox:
 *   <input id="myid" value="PACKAGE" type="checkbox">
 *
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
class HtmlParamPack
{
	/*
	 * Pack any object (or array). <br>
	 * See {@link unpackObjects} how to unpack objects. <br>
	 *
	 * @param object $obj
	 * @return string Packed object
	 */
	public static function packObject( $obj )
	{
		// pack group object
		$pack = serialize( $obj );
		$pack = mb_strlen( $pack ).','.$pack;
		return htmlentities( $pack, ENT_COMPAT, 'UTF-8' );
	}

	/*
	 * Unpack to array of objects (or arrays). <br>
	 * See {@link packObject} how to pack objects. <br>
	 *
	 * @param string List of concatenated packages
	 * @return string Packed object
	 */
	public static function unpackObjects( $packList )
	{
		$retObjs = array();
		$packList = html_entity_decode( $packList, ENT_COMPAT, 'UTF-8' );
		$packList = str_replace( '\"', '"', $packList );
		$pos = 0;
		do {
			$comma = mb_strpos( $packList, ',', $pos ); // take next record
			if( $comma > 0 ) {
				// Read data length indicator
				$dataLen = 0 + intval( mb_substr( $packList, $pos, $comma ) );
				$pos = $comma + 1;
				// Read data pack
				$pack = mb_substr( $packList, $pos, $dataLen );
				$pos += $dataLen;

				// Unpack object
				$retObjs[] = unserialize( $pack );
			}
		} while( $comma > 0 );
		return $retObjs;
	}
}