<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

//* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * Use the AmfServices.template.php file instead.
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -


require_once BASEDIR.'/server/protocols/amf/Services.php';
require_once BASEDIR.'/server/interfaces/services/dat/DataClasses.php';
require_once(BASEDIR.'/server/interfaces/services/dat/DatQueryDatasourcesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/dat/DatGetDatasourceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/dat/DatGetRecordsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/dat/DatSetRecordsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/dat/DatHasUpdatesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/dat/DatOnSaveRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/dat/DatGetUpdatesRequest.class.php');

require_once BASEDIR.'/server/secure.php';

class WW_AMF_DatServices extends WW_AMF_Services
{
	public function QueryDatasources( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatQueryDatasourcesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'DatQueryDatasourcesRequest' );
			$service = new DatQueryDatasourcesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDatasource( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatGetDatasourceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'DatGetDatasourceRequest' );
			$service = new DatGetDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetRecords( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatGetRecordsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'DatGetRecordsRequest' );
			$service = new DatGetRecordsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SetRecords( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatSetRecordsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'DatSetRecordsRequest' );
			$service = new DatSetRecordsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function HasUpdates( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatHasUpdatesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'DatHasUpdatesRequest' );
			$service = new DatHasUpdatesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function OnSave( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatOnSaveService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'DatOnSaveRequest' );
			$service = new DatOnSaveService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetUpdates( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatGetUpdatesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'DatGetUpdatesRequest' );
			$service = new DatGetUpdatesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}


}
