<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 */

class SmartArchive
{
	/*
	* When SmartArchive needs to intercept the connectors, it needs to detect whether it is an alien Object or not.
	* And if it is Alien Object, is the alien object belongs to Smart Archive Content Source Object?
	*/
	static public function isContentSourceID($id){
		$contentSourceId = '';
		BizContentSource::getExternalID( $id, $contentSourceId );
		require_once dirname(__FILE__) . '/config.php';
		if( $contentSourceId == SMARTARCHIVE_CONTENTSOURCEID ) { // alien object? (=from our archive?)
			return true;
		}else{
			return false;
		}
	}
}


?>