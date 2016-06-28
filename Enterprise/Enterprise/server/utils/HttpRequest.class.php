<?php
/**
 * Helper class to handle HTTP request parameters.
 *
 * @package Enterprise
 * @subpackage Utils
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_HttpRequest
{
	/**
	 * Retrieves HTTP parameters, as read from from GET/POST/COOKIE data.
	 *
	 * By default, cookies overwrite POST and GET params while PHP is composing $_REQUEST.
	 * This depends on the request_order (or variables_order) setting in php.ini which
	 * by default is set to 'GPC'. This means the $_GET is loaded into $_REQUEST, which
	 * then is overwritten with all values of $_GET and finally by $_COOKIE. This means
	 * that when a certain parameter (e.g. 'ticket') is put on the URL, and there is also
	 * a cookie with the same name, the cookie overwrites the value of the URL...!
	 *
	 * To make this even more bizar, Content Station (CS) and Safari running from one machine 
	 * are sharing cookies. And so when logging in to the Web Apps, the 'ticket' is stored
	 * in the cookies. Then CS does request for a preview at the Transfer Server, the ticket
	 * is put on the URL. But, this is overruled by the cookie from the Web Apps due to the 
	 * GPC setting. This problem is reported at BZ#30503 and fixed with use of this function.
	 *
	 * When this is not wanted, or when you do not like to rely on the request_order setting
	 * at all, this function can be used instead of $_REQUEST.
	 *
	 * @param string $order In what order to read values. See request_order at php.ini.
	 * @return mixed Value of request HTTP param. NULL when not found.
	 */
	static public function getHttpParams( $order = 'GPC' )
	{
		// Iterate in the given order while combining the super globals.
		$globalMerged = array();
		$orderChars = str_split( $order );
		foreach( $orderChars as $orderChar ) {
			switch( $orderChar ) {
				case 'G':
					$globalArray = $_GET;
					break;
				case 'P':
					$globalArray = $_POST;
					break;
				case 'C':
					$globalArray = $_COOKIE;
					break;
				default:
					$globalArray = array();
					break;
			}
			
			// If the input arrays have the same string keys, then the later value 
			// for that key will overwrite the previous one.
			$globalMerged = array_merge( $globalMerged, $globalArray );
		}
		return $globalMerged;
	}
}