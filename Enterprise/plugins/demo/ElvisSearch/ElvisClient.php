<?php
/****************************************************************************
   Copyright 2008-2010 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

/**
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

class ElvisClient extends SoapClient
{
	private $userName = '';
	private $password = '';
	
	public function __doRequest ($request, $location, $action, $version, $one_way = null)
	{
		$soapAction = 'Elvis_' . $this->getSoapAction($request);
		
		LogHandler::logSOAP($soapAction . '_Org', $request, true);
		// PHP doesn't support Web Services Security, we have to remove the security elements from request
		$request = str_replace( '<ns1:Security><ns1:UsernameToken/></ns1:Security>', '', $request );
		$request = preg_replace( '|<SecurityHeader>.*</SecurityHeader>|s', '', $request );
		
		LogHandler::logSOAP($soapAction, $request, true);
		$response = parent::__doRequest( $request, $location, $action, $version, $one_way );
		LogHandler::logSOAP($soapAction, $response, false);
		
		return $response;
	}
	
	private function getSoapAction ($soapRequest)
	{
		// Find the requested SOAP action on top of envelope (assuming it's the next element after <Body>)
		$soapActs = array();
		$bodyPos = stripos( $soapRequest, 'Body>' ); // Preparation to work-around bug in PHP: eregi only checks first x number of characters
		if ($bodyPos >= 0) {
			$searchBuf = substr( $soapRequest, $bodyPos, 255 );
			preg_match( '@Body>[^<]*<([A-Z0-9_-]*:)?([A-Z0-9_-]*)[/> ]@i', $searchBuf, $soapActs );
			// Sample data: <SOAP-ENV:Body><tns:QueryObjects>
		}
		if (sizeof( $soapActs ) <= 2)
			throw new BizException( 'ERR_ERROR', 'Client', 'The SOAP action was not found in envelope. Request = ' . $soapRequest );
		$soapAction = $soapActs[2];
		
		return $soapAction;
	}
	
	public function setUser($username, $password)
	{
		$this->userName = $username;
		$this->password = $password;
	}
	
	protected function getAuthSoapHeader()
	{
		// Assemble the authentication header
		$authheader = sprintf( '<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">' . "\n"
        	. '<wsse:UsernameToken>' . "\n"
            . '<wsse:Username>%s</wsse:Username>' . "\n"
            . '<wsse:Password>%s</wsse:Password>' . "\n"
        	. '</wsse:UsernameToken>' . "\n"
    		. '</wsse:Security>', 
    		htmlspecialchars( $this->userName ), htmlspecialchars( $this->password ) );
		
		// Create the SoapHeader using a Soapvar. there's some magic here.
		$authvars = new SoapVar( $authheader, XSD_ANYXML );
		$header = new SoapHeader( "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd", 
			"Security", $authvars );
		
		return $header;
	}
	
	protected function callSoapFunction($function, $arguments)
	{
		// We have to add a security argument because PHP treats it as such
		$SecurityHeaderType = new stdClass( );
		$SecurityHeaderType->UsernameToken = null;
		
		array_unshift($arguments, $SecurityHeaderType);
		
		$header = $this->getAuthSoapHeader();
		
		return $this->__call($function, $arguments, null, $header);
	}

	/**
	 * Search for the given query string and return the hits.
	 *
	 * @param string $queryString
	 * @param int $firstResult
	 * @param int $maxHits
	 */
	public function search ($queryString, $firstResult = 0, $maxHits = 20)
	{
		// PHP dosn't support Web Services Security, we have to add the header ourself
		return $this->callSoapFunction( 'search', 
			array( array('query' => array('queryStringQuery' => $queryString) , 'firstResult' => $firstResult, 'maxResultHits' => $maxHits)) );
	}
	
	/**
	 * Search for the Elvis id and return the hits.
	 *
	 * @param string $queryString
	 * @param int $firstResult
	 * @param int $maxHits
	 */
	public function searchById ($elvisId, $firstResult = 0, $maxHits = 20)
	{
		$queryString = 'id:' . $elvisId;
		
		return $this->search($queryString, $firstResult, $maxHits);
	}
}
