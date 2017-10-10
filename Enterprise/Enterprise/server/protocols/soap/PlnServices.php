<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @since v3.x
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * Notes: 
 * - Since v6.0 this class was renamed from smartplanserver into PlanningServices
 * - Since v7.0 this class uses PHP SOAP (instead of PEAR SOAP)
 * - Since v7.0 this class uses Enterprise DIME handler (instead of PEAR DIME)
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the server/buildtools/genservices/interfaces/pln/SoapServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/interfaces/services/pln/DataClasses.php';
require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php'; // some workflow interface classes are shared with planning interface 

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class WW_SOAP_PlnServices extends WW_SOAP_Service
{
	public static function getClassMap( $soapAction )
	{
		require_once BASEDIR . '/server/services/pln/Pln' . $soapAction . 'Service.class.php';
		return array( $soapAction => 'Pln' . $soapAction . 'Request' );
	}

	public function LogOn( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnLogOnService.class.php';

		try {
			$service = new PlnLogOnService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function LogOff( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnLogOffService.class.php';

		try {
			$service = new PlnLogOffService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateLayouts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnCreateLayoutsService.class.php';

		try {
			$service = new PlnCreateLayoutsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyLayouts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnModifyLayoutsService.class.php';

		try {
			$service = new PlnModifyLayoutsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteLayouts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnDeleteLayoutsService.class.php';

		try {
			$service = new PlnDeleteLayoutsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateAdverts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnCreateAdvertsService.class.php';

		try {
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
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyAdverts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnModifyAdvertsService.class.php';

		try {
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
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteAdverts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnDeleteAdvertsService.class.php';

		try {
			$service = new PlnDeleteAdvertsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}


}