<?php
/**
 * @package 	EnterpriseProxy
 * @subpackage 	BizClasses
 * @since 		v9.6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * Helper class that shares handy functions for SOAP requests and server responses.
 */
class BizHttpData
{
	/**
	 * Finds the name of the service to call
	 *
	 * @param string $soapRequestOrResponse
	 * @return string
	 */
	static public function getSoapServiceName( $soapRequestOrResponse )
	{
		// Find the requested SOAP action on top of envelope (assuming it's the next element after <Body>)
		$serviceName = '';
		$soapActs = array();
		$bodyPos = stripos( $soapRequestOrResponse, 'Body>' ); // Preparation to work-around bug in PHP: eregi only checks first x number of characters
		if( $bodyPos >= 0 ) {
			$searchBuf = substr( $soapRequestOrResponse, $bodyPos, 255 );
			preg_match( '@Body>[^<]*<([A-Z0-9_-]*:)?([A-Z0-9_-]*)[/> ]@i', $searchBuf, $soapActs );
			// Sample data: <SOAP-ENV:Body><tns:QueryObjects>
		}
		if( sizeof( $soapActs ) > 2 ) {
			$serviceName = $soapActs[2];
		}

		return $serviceName;
	}
}