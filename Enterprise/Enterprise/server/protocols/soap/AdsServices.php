<?php
/**
 * Dispatches incomming SOAP requests to Datasource Admin Services.<br>
 * It unpacks/packs the SOAP operations while doing so.
 *
 * @package Enterprise
 * @subpackage Core
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the AdmDatSrcServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class WW_SOAP_AdsServices extends WW_SOAP_Service
{
	public static function getClassMap( $soapAction )
	{
		$soapActionBase = substr( $soapAction, 0, -strlen('Request') );
		require_once BASEDIR . '/server/services/ads/Ads' . $soapActionBase . 'Service.class.php';
		return array( $soapAction => 'Ads' . $soapAction );
	}

	public function GetPublications( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetPublicationsService.class.php';

		try {
			$service = new AdsGetPublicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetDatasourceInfo( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceInfoService.class.php';

		try {
			$service = new AdsGetDatasourceInfoService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceService.class.php';

		try {
			$service = new AdsGetDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetQueryService.class.php';

		try {
			$service = new AdsGetQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetQueries( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetQueriesService.class.php';

		try {
			$service = new AdsGetQueriesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetQueryFields( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetQueryFieldsService.class.php';

		try {
			$service = new AdsGetQueryFieldsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetDatasourceTypes( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceTypesService.class.php';

		try {
			$service = new AdsGetDatasourceTypesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetDatasourceType( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceTypeService.class.php';

		try {
			$service = new AdsGetDatasourceTypeService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetSettingsDetails( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetSettingsDetailsService.class.php';

		try {
			$service = new AdsGetSettingsDetailsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetSettings( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetSettingsService.class.php';

		try {
			$service = new AdsGetSettingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function QueryDatasources( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsQueryDatasourcesService.class.php';

		try {
			$service = new AdsQueryDatasourcesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function NewQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsNewQueryService.class.php';

		try {
			$service = new AdsNewQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function NewDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsNewDatasourceService.class.php';

		try {
			$service = new AdsNewDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SavePublication( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSavePublicationService.class.php';

		try {
			$service = new AdsSavePublicationService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SaveQueryField( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveQueryFieldService.class.php';

		try {
			$service = new AdsSaveQueryFieldService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SaveQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveQueryService.class.php';

		try {
			$service = new AdsSaveQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SaveDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveDatasourceService.class.php';

		try {
			$service = new AdsSaveDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SaveSetting( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveSettingService.class.php';

		try {
			$service = new AdsSaveSettingService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeletePublication( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeletePublicationService.class.php';

		try {
			$service = new AdsDeletePublicationService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteQueryField( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeleteQueryFieldService.class.php';

		try {
			$service = new AdsDeleteQueryFieldService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeleteQueryService.class.php';

		try {
			$service = new AdsDeleteQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeleteDatasourceService.class.php';

		try {
			$service = new AdsDeleteDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CopyDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsCopyDatasourceService.class.php';

		try {
			$service = new AdsCopyDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CopyQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsCopyQueryService.class.php';

		try {
			$service = new AdsCopyQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}


}