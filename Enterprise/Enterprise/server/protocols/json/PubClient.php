<?php
/**
 * Publishing JSON client.
 *
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/json/Client.php';

class WW_JSON_PubClient extends WW_JSON_Client
{
	public function __construct( $baseUrl = '', $config = null )
	{
		if( !$baseUrl ) {
			$baseUrl = LOCALURL_ROOT.INETROOT.'/publishindex.php';
		}

		// json handler class
		parent::__construct( $baseUrl, $config );
	}
}
