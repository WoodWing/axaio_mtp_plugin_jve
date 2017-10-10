<?php
/**
 * @package Enterprise
 * @subpackage Services
 * @since v3.x
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Unwraps incoming SOAP requests and dispatches them to Worflow Services.
 * Wraps returned service results into outgoing SOAP responses. Also handles exceptions.
 * This way the SOAP message protocol is entirely hidden from the core Enterprise Server.
 *
 * Notes: 
 * - Since v6.0 this class was renamed from smartserverbase into WorkflowServices
 * - Since v6.1 this class uses PHP SOAP (instead of PEAR SOAP)
 * - Since v7.0 this class uses Enterprise DIME handler (instead of PEAR DIME)
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the server/buildtools/genservices/interfaces/wfl/SoapServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class WW_SOAP_WflServices extends WW_SOAP_Service 
{
	public static function getClassMap( $soapAction )
	{
		require_once BASEDIR . '/server/services/wfl/Wfl' . $soapAction . 'Service.class.php';
		return array( $soapAction => 'Wfl' . $soapAction . 'Request' );
	}
	
/*BODY*/
}