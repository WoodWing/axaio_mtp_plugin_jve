<?php
/**
 * Dispatches incomming SOAP requests to Datasource Admin Services.<br>
 * It unpacks/packs the SOAP operations while doing so.
 *
 * @package Enterprise
 * @subpackage Core
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the AdmDatSrcServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class WW_SOAP_AdsServices extends WW_SOAP_Service
{
	public static function getClassMap( $soapAction )
	{
		$soapActionBase = substr( $soapAction, 0, -strlen('Request') );
		require_once BASEDIR . '/server/services/ads/Ads' . $soapActionBase . 'Service.class.php';
		return array( $soapAction => 'Ads' . $soapAction );
	}

/*BODY*/
}