<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the JsonServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR.'/server/protocols/json/Services.php';
require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
require_once BASEDIR.'/server/secure.php';

class WW_JSON_PubServices extends WW_JSON_Services
{
	public function PublishDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubPublishDossiersService.class.php';
		$req['__classname__'] = 'PubPublishDossiersRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PubPublishDossiersService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function UpdateDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubUpdateDossiersService.class.php';
		$req['__classname__'] = 'PubUpdateDossiersRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PubUpdateDossiersService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function UnPublishDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubUnPublishDossiersService.class.php';
		$req['__classname__'] = 'PubUnPublishDossiersRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PubUnPublishDossiersService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetDossierURL( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubGetDossierURLService.class.php';
		$req['__classname__'] = 'PubGetDossierURLRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PubGetDossierURLService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetPublishInfo( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubGetPublishInfoService.class.php';
		$req['__classname__'] = 'PubGetPublishInfoRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PubGetPublishInfoService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function SetPublishInfo( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubSetPublishInfoService.class.php';
		$req['__classname__'] = 'PubSetPublishInfoRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PubSetPublishInfoService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function PreviewDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubPreviewDossiersService.class.php';
		$req['__classname__'] = 'PubPreviewDossiersRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PubPreviewDossiersService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetDossierOrder( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubGetDossierOrderService.class.php';
		$req['__classname__'] = 'PubGetDossierOrderRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PubGetDossierOrderService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function UpdateDossierOrder( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubUpdateDossierOrderService.class.php';
		$req['__classname__'] = 'PubUpdateDossierOrderRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PubUpdateDossierOrderService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function AbortOperation( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubAbortOperationService.class.php';
		$req['__classname__'] = 'PubAbortOperationRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PubAbortOperationService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function OperationProgress( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubOperationProgressService.class.php';
		$req['__classname__'] = 'PubOperationProgressRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new PubOperationProgressService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}


}
