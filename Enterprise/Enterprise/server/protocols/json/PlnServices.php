<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * Use the JsonServices.template.php file instead.
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR.'/server/protocols/json/Services.php';
require_once BASEDIR.'/server/interfaces/services/pln/DataClasses.php';
require_once BASEDIR.'/server/secure.php';

class WW_JSON_PlnServices extends WW_JSON_Services
{
	public function LogOn( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnLogOnService.class.php';
		$req['__classname__'] = 'PlnLogOnRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PlnLogOnService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function LogOff( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnLogOffService.class.php';
		$req['__classname__'] = 'PlnLogOffRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PlnLogOffService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateLayouts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnCreateLayoutsService.class.php';
		$req['__classname__'] = 'PlnCreateLayoutsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PlnCreateLayoutsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyLayouts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnModifyLayoutsService.class.php';
		$req['__classname__'] = 'PlnModifyLayoutsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PlnModifyLayoutsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteLayouts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnDeleteLayoutsService.class.php';
		$req['__classname__'] = 'PlnDeleteLayoutsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PlnDeleteLayoutsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateAdverts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnCreateAdvertsService.class.php';
		$req['__classname__'] = 'PlnCreateAdvertsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Adverts ) foreach( $req->Adverts as $advert ) {
				if( $advert->File ) {
					$transferServer->urlToFilePath( $advert->File );
				}
				if( !is_null( $advert->Page ) && isset( $advert->Page->Files ) ) {
					foreach( $advert->Page->Files as $file ) {
						$transferServer->urlToFilePath( $file );
					}
				}
			}
		$service = new PlnCreateAdvertsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyAdverts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnModifyAdvertsService.class.php';
		$req['__classname__'] = 'PlnModifyAdvertsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Adverts ) foreach( $req->Adverts as $advert ) {
				if( $advert->File ) {
					$transferServer->urlToFilePath( $advert->File );
				}
				if( !is_null( $advert->Page ) && isset( $advert->Page->Files ) ) {
					foreach( $advert->Page->Files as $file ) {
						$transferServer->urlToFilePath( $file );
					}
				}
			}
		$service = new PlnModifyAdvertsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteAdverts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnDeleteAdvertsService.class.php';
		$req['__classname__'] = 'PlnDeleteAdvertsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PlnDeleteAdvertsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}


}
