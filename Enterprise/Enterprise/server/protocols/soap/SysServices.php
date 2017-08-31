<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @since v8.2.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * Unwraps incoming SOAP requests and dispatches them to System Administration Services.
 * Wraps returned service results into outgoing SOAP responses. Also handles exceptions.
 * This way the SOAP message protocol is entirely hidden from the core Enterprise Server.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the server/buildtools/genservices/interfaces/sys/SoapServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/interfaces/services/sys/DataClasses.php';

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class WW_SOAP_SysServices extends WW_SOAP_Service
{
	public static function getClassMap( $soapAction )
	{
		$soapActionBase = substr( $soapAction, 0, -strlen('Request') );
		require_once BASEDIR . '/server/services/sys/Sys' . $soapActionBase . 'Service.class.php';
		return array( $soapAction => 'Sys' . $soapAction );
	}

	public function GetSubApplications( $req )
	{
		require_once BASEDIR.'/server/services/sys/SysGetSubApplicationsService.class.php';

		try {
			$service = new SysGetSubApplicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}


}
