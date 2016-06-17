<?php
/**
 * Enterprise Proxy Server
 *
 * @package EnterpriseProxy
 * @subpackage Core
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * The proxy server is used to speed up large file uploads/downloads between Smart Connection for InDesign
 * clients located at a remote editorial department and a remote Enterprise Server. Both worlds are
 * connected through WAN. Instead of sending SOAP/DIME over a HTTP connection, the proxy server uses
 * the help of Aspera integration (3rd party). Aspera uses UDP for file transfers, which is much faster than HTTP.
 * IMPORTANT: This solution can NOT be used for other clients, such as Content Station.
 *
 * The Enterprise Proxy solution is a two-fold; There is a Proxy Server and a Proxy Stub. The Proxy Server
 * runs stand-alone (outside Enterprise Server) at a remote location to serve an editorial department.
 * It acts like a real Enterprise Server and forwards InDesign client SOAP/DIME requests to the Proxy Stub
 * that runs inside the real Enterprise Server. Forwarding is implemented  by saving/reading the entire
 * SOAP/DIME stream to local disk and letting Aspera do a file copy from one to the other server machine
 * (one running the Proxy Server and the other running the Stub).

 * Even though the stub runs 'inside' an Enterprise Server installation, it connects to Enterprise Server
 * through a HTTP connection. And therefor it takes an extra PHP process (at server side) and is isolated
 * from the Enterprise Server process.
 *
 * This PHP module (proxyindex.php) implements both the Enterprise Proxy Server and the Enterprise Proxy Stub
 * and so it runs on both locations bridging all HTTP traffic. It talks a home brewed XML over HTTP passing
 * the original HTTP header information, and the temporary location of the SOAP/DIME request/response files.
 *
 * Overview:
 *        -------------------                      -------------------
 *       |  InDesign client  |                    | Enterprise Server |
 *        -------------------                      -------------------
 *                 |                                       ^
 *                 | accept HTTP request [1]               | fire HTTP request [6]
 *                 | (SOAP/DIME over LAN)                  | (SOAP/DIME over LAN)
 *                 V                                       |
 *        -------------------   HTTP headers [4]   -------------------
 *       |   Proxy Server    |    ----------->    |    Proxy Stub     |
 *        -------------------        (WAN)         -------------------
 *                 |                                       ^
 *                 | write HTTP body [2]                   | load HTTP body [5]
 *                 | (local save SOAP/DIME)                | (local read SOAP/DIME)
 *                 V                                       |
 *        -------------------     file copy [3]    -------------------
 *       |   request file    |    ----------->    |   request file    |
 *        -------------------        (WAN)         -------------------
 *         (local tmp folder)                       (local tmp folder)
 *
 * The above shows the flow of client request data. The server response data, is sent back through
 * the same flow/connections (HTTP responses), but then instead of request files, there are
 * response files created and copied back to the proxy server.
 *
 * Instead of the index.php at the Enterprise Server, all InDesign clients at the remote editorial
 * department connect to the proxyindex.php at the Proxy Server. This needs to be configured at the
 * WWSettings.xml file.
 */

// ----------------------------------------------------------------------------
// To deal with large upload, avoid script abortion due to low max_execution_time setting
@ignore_user_abort(1); // Disallow clients to stop server (PHP script) execution.
@set_time_limit(0);    // Run server (PHP script) forever.

require_once dirname(__FILE__).'/config/config.php';

// Dispatch and handle incoming request
if( isset($_GET['ProxyStubTrigger']) ) { // proxy is calling the stub
	require_once BASEDIR . '/server/bizclasses/BizProxyStub.class.php';
    $entStub = new BizProxyStub();
    $entStub->handle();
} else { // client application is calling the proxy
	require_once BASEDIR . '/server/bizclasses/BizProxyServer.class.php';
    $entProxy = new BizProxyServer();
    $entProxy->handle();
}

