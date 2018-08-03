<?php

/**
 * @since v3.x
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * Notes: 
 * - Since v6.0 this class was renamed from smartplanserver into PlanningServices
 * - Since v7.0 this class uses PHP SOAP (instead of PEAR SOAP)
 * - Since v7.0 this class uses Enterprise DIME handler (instead of PEAR DIME)
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the server/buildtools/genservices/interfaces/pln/SoapServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/interfaces/services/pln/DataClasses.php';
require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php'; // some workflow interface classes are shared with planning interface 

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class WW_SOAP_PlnServices extends WW_SOAP_Service
{
	public static function getClassMap( $soapAction )
	{
		require_once BASEDIR . '/server/services/pln/Pln' . $soapAction . 'Service.class.php';
		return array( $soapAction => 'Pln' . $soapAction . 'Request' );
	}

/*BODY*/
}