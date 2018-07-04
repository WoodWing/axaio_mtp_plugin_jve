<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

//* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * Use the AmfServices.template.php file instead.
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -


require_once BASEDIR.'/server/protocols/amf/Services.php';
require_once BASEDIR.'/server/interfaces/services/ads/DataClasses.php';
require_once(BASEDIR.'/server/interfaces/services/ads/AdsGetPublicationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceInfoRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsGetQueryRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsGetQueriesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsGetQueryFieldsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceTypesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceTypeRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsGetSettingsDetailsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsGetSettingsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsQueryDatasourcesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsNewQueryRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsNewDatasourceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsSavePublicationRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsSaveQueryFieldRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsSaveQueryRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsSaveDatasourceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsSaveSettingRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsDeletePublicationRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsDeleteQueryFieldRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsDeleteQueryRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsDeleteDatasourceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsCopyDatasourceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/ads/AdsCopyQueryRequest.class.php');

require_once BASEDIR.'/server/secure.php';

class WW_AMF_AdsServices extends WW_AMF_Services
{
	public function GetPublications( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetPublicationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsGetPublicationsRequest' );
			$service = new AdsGetPublicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDatasourceInfo( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceInfoService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsGetDatasourceInfoRequest' );
			$service = new AdsGetDatasourceInfoService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsGetDatasourceRequest' );
			$service = new AdsGetDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetQueryService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsGetQueryRequest' );
			$service = new AdsGetQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetQueries( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetQueriesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsGetQueriesRequest' );
			$service = new AdsGetQueriesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetQueryFields( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetQueryFieldsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsGetQueryFieldsRequest' );
			$service = new AdsGetQueryFieldsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDatasourceTypes( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceTypesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsGetDatasourceTypesRequest' );
			$service = new AdsGetDatasourceTypesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDatasourceType( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetDatasourceTypeService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsGetDatasourceTypeRequest' );
			$service = new AdsGetDatasourceTypeService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetSettingsDetails( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetSettingsDetailsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsGetSettingsDetailsRequest' );
			$service = new AdsGetSettingsDetailsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetSettings( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsGetSettingsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsGetSettingsRequest' );
			$service = new AdsGetSettingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function QueryDatasources( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsQueryDatasourcesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsQueryDatasourcesRequest' );
			$service = new AdsQueryDatasourcesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function NewQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsNewQueryService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsNewQueryRequest' );
			$service = new AdsNewQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function NewDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsNewDatasourceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsNewDatasourceRequest' );
			$service = new AdsNewDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SavePublication( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSavePublicationService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsSavePublicationRequest' );
			$service = new AdsSavePublicationService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SaveQueryField( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveQueryFieldService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsSaveQueryFieldRequest' );
			$service = new AdsSaveQueryFieldService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SaveQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveQueryService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsSaveQueryRequest' );
			$service = new AdsSaveQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SaveDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveDatasourceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsSaveDatasourceRequest' );
			$service = new AdsSaveDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SaveSetting( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsSaveSettingService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsSaveSettingRequest' );
			$service = new AdsSaveSettingService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeletePublication( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeletePublicationService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsDeletePublicationRequest' );
			$service = new AdsDeletePublicationService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteQueryField( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeleteQueryFieldService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsDeleteQueryFieldRequest' );
			$service = new AdsDeleteQueryFieldService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeleteQueryService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsDeleteQueryRequest' );
			$service = new AdsDeleteQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsDeleteDatasourceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsDeleteDatasourceRequest' );
			$service = new AdsDeleteDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CopyDatasource( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsCopyDatasourceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsCopyDatasourceRequest' );
			$service = new AdsCopyDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CopyQuery( $req )
	{
		require_once BASEDIR.'/server/services/ads/AdsCopyQueryService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdsCopyQueryRequest' );
			$service = new AdsCopyQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}


}
