<?php
/**
 * Publishes dossiers with contained content.<br>
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the server/buildtools/genservices/interfaces/pub/SoapServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/interfaces/services/pub/DataClasses.php';

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class WW_SOAP_PubServices extends WW_SOAP_Service
{
	public static function getClassMap( $soapAction )
	{
		$soapActionBase = substr( $soapAction, 0, -strlen('Request') );
		require_once BASEDIR . '/server/services/pub/Pub' . $soapActionBase . 'Service.class.php';
		return array( $soapAction => 'Pub' . $soapAction );
	}

	public function PublishDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubPublishDossiersService.class.php';

		try {
			$service = new PubPublishDossiersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function UpdateDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubUpdateDossiersService.class.php';

		try {
			$service = new PubUpdateDossiersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function UnPublishDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubUnPublishDossiersService.class.php';

		try {
			$service = new PubUnPublishDossiersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetDossierURL( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubGetDossierURLService.class.php';

		try {
			$service = new PubGetDossierURLService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetPublishInfo( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubGetPublishInfoService.class.php';

		try {
			$service = new PubGetPublishInfoService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SetPublishInfo( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubSetPublishInfoService.class.php';

		try {
			$service = new PubSetPublishInfoService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function PreviewDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubPreviewDossiersService.class.php';

		try {
			$service = new PubPreviewDossiersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetDossierOrder( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubGetDossierOrderService.class.php';

		try {
			$service = new PubGetDossierOrderService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function UpdateDossierOrder( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubUpdateDossierOrderService.class.php';

		try {
			$service = new PubUpdateDossierOrderService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function AbortOperation( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubAbortOperationService.class.php';

		try {
			$service = new PubAbortOperationService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function OperationProgress( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubOperationProgressService.class.php';

		try {
			$service = new PubOperationProgressService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}


}
