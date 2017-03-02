<?php
/**
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Unwraps incoming SOAP requests and dispatches them to DataSource Services.
 * Wraps returned service results into outgoing SOAP responses. Also handles exceptions.
 * This way the SOAP message protocol is entirely hidden from the core Enterprise Server.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the server/buildtools/genservices/interfaces/dat/SoapServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/interfaces/services/dat/DataClasses.php';

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class WW_SOAP_DatServices extends WW_SOAP_Service
{
	public static function getClassMap( $soapAction )
	{
		$soapActionBase = substr( $soapAction, 0, -strlen('Request') );
		require_once BASEDIR . '/server/services/dat/Dat' . $soapActionBase . 'Service.class.php';
		return array( $soapAction => 'Dat' . $soapAction );
	}

	public function QueryDatasources( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatQueryDatasourcesService.class.php';

		try {
			$service = new DatQueryDatasourcesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetDatasource( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatGetDatasourceService.class.php';

		try {
			$service = new DatGetDatasourceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetRecords( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatGetRecordsService.class.php';

		try {
			$service = new DatGetRecordsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SetRecords( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatSetRecordsService.class.php';

		try {
			$service = new DatSetRecordsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function HasUpdates( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatHasUpdatesService.class.php';

		try {
			$service = new DatHasUpdatesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function OnSave( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatOnSaveService.class.php';

		try {
			$service = new DatOnSaveService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetUpdates( $req )
	{
		require_once BASEDIR.'/server/services/dat/DatGetUpdatesService.class.php';

		try {
			$service = new DatGetUpdatesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}


}