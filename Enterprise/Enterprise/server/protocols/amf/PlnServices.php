<?php

/**
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

//* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * Use the AmfServices.template.php file instead.
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -


require_once BASEDIR.'/server/protocols/amf/Services.php';
require_once BASEDIR.'/server/interfaces/services/pln/DataClasses.php';
require_once(BASEDIR.'/server/interfaces/services/pln/PlnLogOnRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pln/PlnLogOffRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pln/PlnCreateLayoutsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pln/PlnModifyLayoutsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pln/PlnDeleteLayoutsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pln/PlnCreateAdvertsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pln/PlnModifyAdvertsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/pln/PlnDeleteAdvertsRequest.class.php');

require_once BASEDIR.'/server/secure.php';

class WW_AMF_PlnServices extends WW_AMF_Services
{
	public function LogOn( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnLogOnService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PlnLogOnRequest' );
			$service = new PlnLogOnService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function LogOff( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnLogOffService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PlnLogOffRequest' );
			$service = new PlnLogOffService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateLayouts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnCreateLayoutsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PlnCreateLayoutsRequest' );
			$service = new PlnCreateLayoutsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyLayouts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnModifyLayoutsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PlnModifyLayoutsRequest' );
			$service = new PlnModifyLayoutsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteLayouts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnDeleteLayoutsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PlnDeleteLayoutsRequest' );
			$service = new PlnDeleteLayoutsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateAdverts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnCreateAdvertsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PlnCreateAdvertsRequest' );
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
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyAdverts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnModifyAdvertsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PlnModifyAdvertsRequest' );
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
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteAdverts( $req )
	{
		require_once BASEDIR.'/server/services/pln/PlnDeleteAdvertsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'PlnDeleteAdvertsRequest' );
			$service = new PlnDeleteAdvertsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}


}
