<?php
/**
 * @package Enterprise
 * @subpackage Services
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Unwraps incoming SOAP requests and dispatches them to Workflow Administration Services.
 * Wraps returned service results into outgoing SOAP responses. Also handles exceptions.
 * This way the SOAP message protocol is entirely hidden from the core Enterprise Server.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the server/buildtools/genservices/interfaces/adm/SoapServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class WW_SOAP_AdmServices extends WW_SOAP_Service
{
	public static function getClassMap( $soapAction )
	{
		$soapActionBase = substr( $soapAction, 0, -strlen('Request') );
		require_once BASEDIR . '/server/services/adm/Adm' . $soapActionBase . 'Service.class.php';
		return array( $soapAction => 'Adm' . $soapAction );
	}

/*BODY*/
}