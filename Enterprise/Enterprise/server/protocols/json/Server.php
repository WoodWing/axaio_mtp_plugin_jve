<?php
/**
 * Generic JSON Server for Enterprise.
 * It extends Zend's JSON server to log services and to return service map (SMD).
 * 
 * @package 	Enterprise
 * @subpackage 	JSON
 * @since 		v8.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require 'Zend/Json/Server.php';

use Zend\Json\Server\Server,
	Zend\Json\Server\Smd,
	Zend\Json\Json;

class WW_JSON_Server extends Server
{
	public function handle( $request = false )
	{
		// First add Cross Origin headers needed by Javascript applications
		require_once BASEDIR.'/server/utils/CrossOriginHeaderUtil.class.php';
		WW_Utils_CrossOriginHeaderUtil::addCrossOriginHeaders();

		$this->getRequest()->setVersion( Server::VERSION_2 );

		if( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			// Grab the web service definition and set the RPC version to use.
			$smd = $this->getServiceMap();
			$smd->setEnvelope( Smd::ENV_JSONRPC_2 );

			// Return the web service definition to the client.
			header('Content-Type: application/json');
			echo $smd;
			return;
		} elseif( 'OPTIONS' == $_SERVER['REQUEST_METHOD'] ) {
			// The Access-Control-Allow-Methods response header is already set
			// in the addCrossOriginHeaders() call above. So nothing left to do here
			// other that returning a HTTP 200.
			return;
		}

		//ob_start(); // Catch any unexpected output to make sure it does not mixed through JSON output!
		parent::setReturnResponse( true );
		$this->logJsonRequest();
		$resp = parent::handle();

		// Replace BizException with just its detail property to act same as SOAP faults (EN-88154).
		if( $resp ) {
			$error = $resp->getError();
			if( $error instanceof Zend\Json\Server\Error ) {
				$e = $error->getData();
				if( $e instanceof BizException ) {
					$data = new stdClass();
					$data->detail = $e->getDetail();
					$error->setData( $data );
				}
			}
		}

		$this->logJsonResponse();

		// Report if there is data sent through output. See above for details.
		/*$garbage = ob_get_contents();
		ob_end_clean();
		if( strlen($garbage) > 0 ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'Catched garbage data in output: ['.$garbage.']' );
		}*/
		
		// Return JSON response
		//LogHandler::Log( __CLASS__, 'INFO', 'returns: ['.$output.']' );
		echo $resp;
	}

	/**
	 * Logs a JSON request at Enterprise Server log folder.
	 */
	private function logJsonRequest()
	{
		$req = $this->getRequest();
		if( $req->isMethodError() ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'JSON method error occurred for: '.$req->getMethod() );
		}
		LogHandler::logService( $req->getMethod(), Json::prettyPrint($req->__toString()), true, 'JSON' );
	}

	/**
	 * Logs a JSON response at Enterprise Server log folder.
	 */
	private function logJsonResponse()
	{
		$req = $this->getRequest();
		$resp = $this->getResponse();

		LogHandler::logService( $req->getMethod(), Json::prettyPrint($resp->__toString()), false, 'JSON' );
	}
}
