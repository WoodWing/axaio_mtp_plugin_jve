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
require_once BASEDIR.'/server/interfaces/services/ads/DataClasses.php';
require_once BASEDIR.'/server/secure.php';

class WW_JSON_AdsServices extends WW_JSON_Services
{
	public function GetPublications( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetPublicationsService.class.php';

		try {
			$req['__classname__'] = 'AdsGetPublicationsRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsGetPublicationsService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDatasourceInfo( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceInfoService.class.php';

		try {
			$req['__classname__'] = 'AdsGetDatasourceInfoRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsGetDatasourceInfoService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceService.class.php';

		try {
			$req['__classname__'] = 'AdsGetDatasourceRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsGetDatasourceService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function GetQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetQueryService.class.php';

		try {
			$req['__classname__'] = 'AdsGetQueryRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsGetQueryService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function GetQueries( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetQueriesService.class.php';

		try {
			$req['__classname__'] = 'AdsGetQueriesRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsGetQueriesService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function GetQueryFields( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetQueryFieldsService.class.php';

		try {
			$req['__classname__'] = 'AdsGetQueryFieldsRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsGetQueryFieldsService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDatasourceTypes( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceTypesService.class.php';

		try {
			$req['__classname__'] = 'AdsGetDatasourceTypesRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsGetDatasourceTypesService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDatasourceType( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceTypeService.class.php';

		try {
			$req['__classname__'] = 'AdsGetDatasourceTypeRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsGetDatasourceTypeService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function GetSettingsDetails( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetSettingsDetailsService.class.php';

		try {
			$req['__classname__'] = 'AdsGetSettingsDetailsRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsGetSettingsDetailsService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function GetSettings( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetSettingsService.class.php';

		try {
			$req['__classname__'] = 'AdsGetSettingsRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsGetSettingsService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function QueryDatasources( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsQueryDatasourcesService.class.php';

		try {
			$req['__classname__'] = 'AdsQueryDatasourcesRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsQueryDatasourcesService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function NewQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsNewQueryService.class.php';

		try {
			$req['__classname__'] = 'AdsNewQueryRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsNewQueryService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function NewDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsNewDatasourceService.class.php';

		try {
			$req['__classname__'] = 'AdsNewDatasourceRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsNewDatasourceService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function SavePublication( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSavePublicationService.class.php';

		try {
			$req['__classname__'] = 'AdsSavePublicationRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsSavePublicationService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function SaveQueryField( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveQueryFieldService.class.php';

		try {
			$req['__classname__'] = 'AdsSaveQueryFieldRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsSaveQueryFieldService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function SaveQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveQueryService.class.php';

		try {
			$req['__classname__'] = 'AdsSaveQueryRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsSaveQueryService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function SaveDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveDatasourceService.class.php';

		try {
			$req['__classname__'] = 'AdsSaveDatasourceRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsSaveDatasourceService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function SaveSetting( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveSettingService.class.php';

		try {
			$req['__classname__'] = 'AdsSaveSettingRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsSaveSettingService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function DeletePublication( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeletePublicationService.class.php';

		try {
			$req['__classname__'] = 'AdsDeletePublicationRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsDeletePublicationService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteQueryField( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeleteQueryFieldService.class.php';

		try {
			$req['__classname__'] = 'AdsDeleteQueryFieldRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsDeleteQueryFieldService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeleteQueryService.class.php';

		try {
			$req['__classname__'] = 'AdsDeleteQueryRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsDeleteQueryService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeleteDatasourceService.class.php';

		try {
			$req['__classname__'] = 'AdsDeleteDatasourceRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsDeleteDatasourceService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function CopyDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsCopyDatasourceService.class.php';

		try {
			$req['__classname__'] = 'AdsCopyDatasourceRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsCopyDatasourceService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}

	public function CopyQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsCopyQueryService.class.php';

		try {
			$req['__classname__'] = 'AdsCopyQueryRequest';
			$req = $this->arraysToObjects( $req );
			$req = $this->restructureObjects( $req );
			$service = new AdsCopyQueryService();
			$resp = $service->execute( $req );
			$resp = $this->restructureObjects( $resp );
		} catch( BizException $e ) {
			throw new Zend\Json\Server\Exception\ErrorException( $e->getMessage() );
		}
		return $resp;
	}


}
