<?php
/**
 * InDesign Server SOAP client.
 *
 * Wraps the SoapClient class to set IDS SOAP options and patch the IDSP.wsdl file.
 * In debug mode, it also logs SOAP requests and responses at Enterprise server log folder.
 *
 * IMPORTANT: Unlike other SoapClient wrapper classes at Enterprise, this IDS client runs
 *            at the core server and services are implemented by IDS (instead of Enterprise).
 *
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
*/
class WW_SOAP_IdsSoapClient extends SoapClient 
{
	/**
	 * Overload SoapClient constructor to set IDS SOAP options and to pass patched WSDL.
	 *
	 * @inheritdoc For parameters, see SoapClient at PHP manual
	 */
	public function __construct( $wsdl, $options = array() )
	{
   		if( LogHandler::debugMode() ) {
			$options['trace'] = 1;
		}
		$options['uri'] = 'http://ns.adobe.com/InDesign/soap/';
		$options['use'] = SOAP_LITERAL;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$options['soap_version'] = SOAP_1_1;
		
		if( is_null($wsdl) ) {
			$wsdl = $options['location'].'/service?wsdl';
		}
		parent::__construct( $wsdl, $options );
	}

	/**
	 * Overload requests to log all SOAP requests and responses in the Enterprise log folder.
	 *
	 * @inheritdoc For parameters, see SoapClient at PHP manual
	 */
	function __doRequest( $request, $location, $action, $version, $one_way = 0 ) 
	{
		if( empty($action) ) {
			$logAction = 'RunScript'; // Fix: somehow action is not set.. seems to be a PHP bug?
		} else {
			$logAction = str_replace( 'http://ns.adobe.com/InDesign/soap/#', '', $action );
		}
		LogHandler::logService( $logAction, $request, true, 'SOAP' );
		$response = parent::__doRequest( $request, $location, $action, $version );

		// Avoid 'fetching HTTP Headers' error on method time-out, we throw the fault our self.
		if ( is_null( $response )) {
			$error = BizResources::localize( 'ERR_CONNECT', true, array( $location ) );
			LogHandler::logService( $logAction, $error, null, 'SOAP' );
			throw new BizException( '', 'SERVER', '', $error );
		}
		LogHandler::logService( $logAction, $response, false, 'SOAP' );
		return $response;
	}
}
