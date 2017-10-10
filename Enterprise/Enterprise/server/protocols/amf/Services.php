<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_AMF_Services
{
	public function __construct()
	{
		// init authorization
		global $globAuth;
		if (! isset( $globAuth )) {
			require_once BASEDIR . '/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}
	}
	
	/**
	 * Casts an object to a request object.
	 *
	 * This is typical for AMF; On arrival, it is unknown to which service to dispatch
	 * since the request is binary. After letting Zend parse the request, we know it
	 * but we are too late to do classmapping. And, we do not want to include all requests
	 * classes for the sake of one request (for performance reasons).
	 *
	 * PHP does not support class casting, so we'll simply copy the object members.
	 *
	 * @param mixed $req Request to cast.
	 * @param string $requestClass Name of request class.
	 * @return mixed The cast request.
	 */
	public function objectToRequest( $req, $requestClass )
	{
		// Clean Number fields in the Request, NAN values have to become NULL.
		// Clean Boolean fields in the Request, NULL needs to remain NULL, 'true' / 'false' should be Boolean.
		$req->sanitizeProperties4Php();

		$retReq = new $requestClass();
		foreach( array_keys(get_class_vars($requestClass)) as $prop ) { // *
			if( isset($req->$prop) ) {
				$retReq->$prop = $req->$prop;
			}
		}
		// * Do not use get_object_vars($req) for security reasons.
		//   Avoid props that are not specified in the interface.
		return $retReq;
	}
}
