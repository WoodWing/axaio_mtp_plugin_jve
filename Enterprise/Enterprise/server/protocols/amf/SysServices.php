<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

//* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * Use the AmfServices.template.php file instead.
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -


require_once BASEDIR.'/server/protocols/amf/Services.php';
require_once BASEDIR.'/server/interfaces/services/sys/DataClasses.php';
require_once(BASEDIR.'/server/interfaces/services/sys/SysGetSubApplicationsRequest.class.php');

require_once BASEDIR.'/server/secure.php';

class WW_AMF_SysServices extends WW_AMF_Services
{
	public function GetSubApplications( $req )
	{
		require_once BASEDIR.'/server/services/sys/SysGetSubApplicationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'SysGetSubApplicationsRequest' );
			$service = new SysGetSubApplicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}


}
