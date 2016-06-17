<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the AmfServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR.'/server/protocols/amf/Services.php';
require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
require_once(BASEDIR.'/server/interfaces/services/pub/PubPublishDossiersRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pub/PubUpdateDossiersRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pub/PubUnPublishDossiersRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pub/PubGetDossierURLRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pub/PubGetPublishInfoRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pub/PubSetPublishInfoRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pub/PubPreviewDossiersRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pub/PubGetDossierOrderRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pub/PubUpdateDossierOrderRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pub/PubAbortOperationRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pub/PubOperationProgressRequest.class.php');

require_once BASEDIR.'/server/secure.php';

class WW_AMF_PubServices extends WW_AMF_Services
{
	public function PublishDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubPublishDossiersService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PubPublishDossiersRequest' );
			$service = new PubPublishDossiersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function UpdateDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubUpdateDossiersService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PubUpdateDossiersRequest' );
			$service = new PubUpdateDossiersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function UnPublishDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubUnPublishDossiersService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PubUnPublishDossiersRequest' );
			$service = new PubUnPublishDossiersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDossierURL( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubGetDossierURLService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PubGetDossierURLRequest' );
			$service = new PubGetDossierURLService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetPublishInfo( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubGetPublishInfoService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PubGetPublishInfoRequest' );
			$service = new PubGetPublishInfoService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SetPublishInfo( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubSetPublishInfoService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PubSetPublishInfoRequest' );
			$service = new PubSetPublishInfoService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function PreviewDossiers( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubPreviewDossiersService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PubPreviewDossiersRequest' );
			$service = new PubPreviewDossiersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDossierOrder( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubGetDossierOrderService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PubGetDossierOrderRequest' );
			$service = new PubGetDossierOrderService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function UpdateDossierOrder( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubUpdateDossierOrderService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PubUpdateDossierOrderRequest' );
			$service = new PubUpdateDossierOrderService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function AbortOperation( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubAbortOperationService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PubAbortOperationRequest' );
			$service = new PubAbortOperationService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function OperationProgress( $req )
	{
		require_once BASEDIR.'/server/services/pub/PubOperationProgressService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PubOperationProgressRequest' );
			$service = new PubOperationProgressService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}


}
