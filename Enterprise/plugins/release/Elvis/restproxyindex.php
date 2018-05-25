<?php
/**
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Proxy server that accepts an Elvis request from an Enterprise client and pass it on to Elvis Server.
 *
 * The original client request must be authorized for Enterprise. The Elvis REST service request must be provided
 * as a separate HTTP parameter. Then this module proxies the request to the Elvis Server for which it applies
 * the Elvis authorization. The response body and headers are streamed back to the waiting Enterprise client application.
 *
 * The proxy is introduced to support image cropping in Content Station with help of the Elvis REST API.
 *
 * The restproxyindex.php supports the following URL parameters:
 * - ticket:    A valid session ticket that was obtained through a LogOn service call (e.g. see SCEnterprise.wsdl).
 * - ww-app:    The client application name that was provided in the LogOn service request. This parameter can be used
 *              instead of the 'ticket' parameter to have stable URLs and take full advantage of the web browser's cache.
 *              Note that when the client does not run in a web browser it should round-trip web cookies by itself.
 * - objectid:  The ID of the workflow object in Enterprise. The object may reside in workflow, history or trash can.
 * - rendition: The file rendition to download. Supported values: 'native', 'preview' or 'thumb'.
 * - preview-args: The preview- or cropping dimensions. Optional. See Elvis REST API for details.
 *
 * Example request:
 *    http://localhost/Enterprise/config/plugins/Elvis/restproxyindex.php?ww-app=Content%20Station&objectid=123&rendition=preview
 *
 * The following HTTP codes may be returned:
 * - HTTP 200: The file is found and is streamed back to caller.
 * - HTTP 400: Bad HTTP parameters provided by caller. See above for required parameters.
 * - HTTP 401: When ticket is no longer valid. This should be detected by the client to do a re-login.
 * - HTTP 403: The user has no Read access to the invoked object in Enterprise or Elvis.
 * - HTTP 404: The object could not be found in Enterprise or Elvis.
 * - HTTP 405: Bad HTTP method requested by caller. Only GET, POST and OPTIONS are supported.
 * - HTTP 500: Unexpected server error.*
 */

$beforeInclude = microtime( true );
if( file_exists( __DIR__.'/../../config.php' ) ) {
	require_once '../../config.php';
} else { // fall back at symbolic link to VCS source location of server plug-in
	require_once '../../../Enterprise/config/config.php';
}
$footprint = sprintf( '%03d', round( ( microtime( true ) - $beforeInclude ) * 1000 ) );
LogHandler::Log( 'ElvisRestProxyIndex', 'CONTEXT', 'Enterprise Server footprint: '.$footprint.'ms (= startup time).' );

$index = new Elvis_BizClasses_ProxyServer();
$index->handle();

