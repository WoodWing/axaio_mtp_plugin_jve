<?php
/**
 * Generic AMF Server for Enterprise.
 * 
 * @package 	Enterprise
 * @subpackage 	AMF
 * @since 		v8.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require 'Zend/Amf/Server.php';

class WW_AMF_Server extends Zend_Amf_Server
{
	public function __construct()
	{
		parent::__construct();
		$this->setProduction(false); // Some comments about this: http://framework.zend.com/issues/browse/ZF-5118
	}

	public function handle($request = null)
	{
		// Catch any unexpected output to make sure it does not mixed through AMF output!
		// This prevents fatal errors in CS when accidental data is output at PHP code.
		// Such errors look like this: "Channel disconnected before an acknowledgement was received"
		ob_start();

		$this->logAmf( $this->getRequest() );
		$output = parent::handle( $request );
		$this->logAmf( $this->getResponse() );
		
		// Report if there is data sent through output. See above for details.
		$garbage = ob_get_contents();
		ob_end_clean();
		if( strlen($garbage) > 0 ) {
			LogHandler::Log( 'WW_AMF_Server', 'ERROR', 'Catched garbage data in output: ['.$garbage.']' );
		}
		
		// Return AMF response
		echo $output;
	}

	/**
	 * Does AMF logging of a request/response at Enterprise Server log folder.
	 *
	 * @param object $obj AMF request or response data object to be logged.
	 */
	private function logAmf( $obj )
	{
		$bodies = $obj->getAmfBodies();
		$debug = LogHandler::debugMode();
		if( $bodies ) foreach( $bodies as $body ) {
			$data = $body->getData();
			
			// remember request details for response logging
			static $interfaceName = null;
			static $serviceName = null;
			if( isset($data->source) && isset($data->operation) ) {
				if( is_null($interfaceName)) $interfaceName = $data->source;
				if( is_null($serviceName)) $serviceName = $data->operation;
			}

			// log request or reponse
			if( $data instanceof Zend_Amf_Value_Messaging_RemotingMessage ) { // request
				if( $debug ) {
					// We do NOT classmap the request, since that would require in include ALL request
					// definitions! The side effect of this is that the request is a stdClass. So here
					// we replace the stdClass with the request name, for debugging convenience.
					if( count($data->body) == 1 ) { // for Enterprise, there should be just one param, which is the request
						$reqStream = print_r($data->body[0],true);
						if( substr( $reqStream, 0, strlen('stdClass Object') ) == 'stdClass Object' ) {
							$reqName = $data->operation;
							$reqName .= (stripos( $reqName, 'Request' ) === false) ? 'Request' : ''; // add Request postfix (only for Wfl)
							$reqName = substr($data->source,0,3).$reqName; // add prefix, such as 'Wfl'
							$reqStream = $reqName.' Object' . substr( $reqStream, strlen('stdClass Object') );
						}
					} else { // fall back, but typically won't happen
						$reqStream = print_r($data->body,true);
					}
					LogHandler::logService( $data->operation, $reqStream, true, 'AMF' );
				}
				// Commented out since it causes logging even for feather-light services which is unwanted.
				// Alternatively we could call BizSession::setServiceName( $data->operation ) but this would be ugly.
				//LogHandler::Log( 'WW_AMF_Server', 'INFO', 'AMF service called: '.$data->source.'::'.$data->operation );
			} else if( $data instanceof Zend_Amf_Value_Messaging_ErrorMessage ) { // error
				if( $debug ) {
					LogHandler::logService( $serviceName, print_r($data,true), null, 'AMF' );
				}
				LogHandler::Log( 'WW_AMF_Server', 'ERROR', 'Error occurred: '.$data->faultString.' Detail: '.$data->faultDetail );
			} else if( $data instanceof Zend_Amf_Value_Messaging_AcknowledgeMessage ) { // response
				if( is_null($interfaceName) || is_null($serviceName) ) {
					LogHandler::Log( 'WW_AMF_Server', 'DEBUG', 'AMF hand-shake completed.' ); // happens before first request
				} else {
					if( $debug ) {
						LogHandler::logService( $serviceName, print_r($data->body,true), false, 'AMF' );
					}
					LogHandler::Log( 'WW_AMF_Server', 'INFO', 'AMF service completed: '.$interfaceName.'::'.$serviceName );
				}
			} else if( $data instanceof Zend_Amf_Value_Messaging_CommandMessage ) {
				LogHandler::Log( 'WW_AMF_Server', 'DEBUG', 'AMF hand-shake called.' ); // happens before first request
			}
		}
	}
}