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

class WW_JSON_Server extends Zend_Json_Server
{
	public function handle( $request = false )
	{
		// First add Cross Origin headers needed by Javascript applications
		require_once BASEDIR.'/server/utils/CrossOriginHeaderUtil.class.php';
		WW_Utils_CrossOriginHeaderUtil::addCrossOriginHeaders();

		$request = $request; // keep analyzer happy
		if ('GET' == $_SERVER['REQUEST_METHOD']) {
			// Indicate the URL endpoint, and the JSON-RPC version used:
			$this->setEnvelope(Zend_Json_Server_Smd::ENV_JSONRPC_2);
		
			// Grab the SMD
			$smd = $this->getServiceMap();
		
			// Return the SMD to the client
			header('Content-Type: application/json');
			echo $smd;
			return;
		} elseif ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
			// The Access-Control-Allow-Methods response header is already set
			// in the addCrossOriginHeaders() call above. So nothing left to do here
			// other that returning a HTTP 200.
			return;
		}

		//ob_start(); // Catch any unexpected output to make sure it does not mixed through JSON output!
		$this->logJsonRequest();
		$output = parent::handle();
		$this->logJsonResponse();

		// Report if there is data sent through output. See above for details.
		/*$garbage = ob_get_contents();
		ob_end_clean();
		if( strlen($garbage) > 0 ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'Catched garbage data in output: ['.$garbage.']' );
		}*/
		
		// Return JSON response
		//LogHandler::Log( __CLASS__, 'INFO', 'returns: ['.$output.']' );
		echo $output;
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
		require_once 'Zend/Json.php';
		LogHandler::logService( $req->getMethod(), Zend_Json::prettyPrint($req->__toString()), true, 'JSON' );
	}

	/**
	 * Logs a JSON response at Enterprise Server log folder.
	 */
	private function logJsonResponse()
	{
		$req = $this->getRequest();
		$resp = $this->getResponse();

		require_once 'Zend/Json.php';
		LogHandler::logService( $req->getMethod(), Zend_Json::prettyPrint($resp->__toString()), false, 'JSON' );
	}
}
