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
require_once BASEDIR.'/server/interfaces/services/dat/DataClasses.php';
require_once BASEDIR.'/server/secure.php';

class WW_JSON_DatServices extends WW_JSON_Services
{
	public function QueryDatasources( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatQueryDatasourcesService.class.php';
		$req['__classname__'] = 'DatQueryDatasourcesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new DatQueryDatasourcesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetDatasource( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatGetDatasourceService.class.php';
		$req['__classname__'] = 'DatGetDatasourceRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new DatGetDatasourceService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetRecords( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatGetRecordsService.class.php';
		$req['__classname__'] = 'DatGetRecordsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new DatGetRecordsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function SetRecords( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatSetRecordsService.class.php';
		$req['__classname__'] = 'DatSetRecordsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new DatSetRecordsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function HasUpdates( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatHasUpdatesService.class.php';
		$req['__classname__'] = 'DatHasUpdatesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new DatHasUpdatesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function OnSave( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatOnSaveService.class.php';
		$req['__classname__'] = 'DatOnSaveRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new DatOnSaveService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetUpdates( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatGetUpdatesService.class.php';
		$req['__classname__'] = 'DatGetUpdatesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new DatGetUpdatesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}


}
