<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * Use the JsonServices.template.php file instead.
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR.'/server/protocols/json/Services.php';
require_once BASEDIR.'/server/interfaces/services/sys/DataClasses.php';
require_once BASEDIR.'/server/secure.php';

class WW_JSON_SysServices extends WW_JSON_Services
{
	public function GetSubApplications( $req )
	{
		require_once BASEDIR.'/server/services/sys/SysGetSubApplicationsService.class.php';
		$req['__classname__'] = 'SysGetSubApplicationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new SysGetSubApplicationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}


}
