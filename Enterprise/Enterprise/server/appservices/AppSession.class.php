<?php
/**
 * Application Services - Session management.
 *
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
 
 require_once BASEDIR . '/server/protocols/soap/Server.php';

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class AppSession extends WW_SOAP_Service
{	
	// - - - - - - - - - - - - public services - - - - - - - - - - - - 

	public function LogOn( $params )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		try{
			$req = new WflLogOnRequest( $params->User, $params->Password, $params->Ticket, $params->Server,
				$params->ClientName, $params->Domain, $params->ClientAppName, $params->ClientAppVersion, 
				$params->ClientAppSerial, $params->ClientAppProductKey, true );
			$service = new WflLogOnService();
			$resp = $service->execute( $req );
			$ret = new stdClass();
			$ret->Ticket = $resp->Ticket;
		}
		catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($ret);
	}

	public function LogOff( $params )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';

		try{
			$req = new WflLogOffRequest( $params->Ticket, $params->SaveSettings, $params->Settings, $params->ReadMessageIDs );
			$service = new WflLogOffService();
			$ret = $service->execute( $req );
		}
		catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($ret);
	}

	public function GetObjectIcons( $params )
	{
		require_once BASEDIR."/server/appservices/GetObjectIcons.class.php";
		try{
			$icons = GetObjectIcons::execute( $params->Ticket, $params->IconMetrics );
			$ret = new stdClass();
			$ret->Icons = $icons;
		}
		catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		
		// Service layer expects an URL. FilePath is only used internally.
		if ( $ret->Icons ) {
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			foreach ( $ret->Icons as $icon ) {
				if ( $icon->Attachments ) {
					foreach ( $icon->Attachments as $attachment ) {
						$transferServer->filePathToURL( $attachment );
					}
				}
			}
		}
			
		return self::returnResponse($ret);
	}

	public function GetPubChannelIcons( $params )
	{
		require_once BASEDIR."/server/appservices/GetPubChannelIcons.class.php";
		try{
			$icons = GetPubChannelIcons::execute( $params->Ticket, $params->IconMetrics );
			$ret = new stdClass();
			$ret->Icons = $icons;
		}
		catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		
		// Service layer expects an URL. FilePath is only used internally.
		if ( $ret->Icons ) {
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			foreach ( $ret->Icons as $icon ) {
				if ( $icon->Attachments ) {
					foreach ( $icon->Attachments as $attachment ) {
						$transferServer->filePathToURL( $attachment );
					}
				}
			}
		}
				
		return self::returnResponse($ret);
	}

	public function InstantiateWidget( $params )
	{
		require_once BASEDIR."/server/appservices/InstantiateWidget.class.php";
		try{
			$attachment = InstantiateWidget::execute( $params->Ticket, $params->DossierId, $params->WidgetId, $params->LayoutId, 
							$params->EditionId,$params->Artboard, $params->Location, $params->Manifest, $params->PageSequence );
			$ret = new stdClass();
			$ret->Attachment = $attachment;
		}
		catch( BizException $e ) {
			LogHandler::Log( 'AppServices', 'ERROR', 'AppSession->InstantiateWidget: '.$e->__toString() );
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($ret);
	}

	public function SendDiagnostics ( $params )
	{
		require_once BASEDIR."/server/appservices/SendDiagnostics.class.php";
		try {
			if ( $params->Files) {
				require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
				$transferServer = new BizTransferServer();
				foreach( $params->Files as $attachment ) {
					$transferServer->urlToFilePath($attachment);
				}
			}
			
			$result = SendDiagnostics::execute($params->Ticket, $params->Category, $params->Synopsis, $params->Description, $params->Files );
			$ret = new stdClass();
			$ret->Status = $result['status'];
			$ret->Detail = $result['detail'];
		}
 		catch ( BizException $e ) {
			LogHandler::Log( 'AppServices', 'ERROR', 'AppSession->InstantiateWidget: '.$e->__toString() );
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		
		return self::returnResponse($ret);
	}	
}
