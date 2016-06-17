<?php
/****************************************************************************
   Copyright 2008-2013 WoodWing Software BV

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
 * This is the callback module that plays role in the Twitter authorization procedure.
 * See index.php for more information.
 */

// Get Enterprise ticket and start session.
if( file_exists('../../../config/config.php') ) {
	require_once '../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
	require_once '../../../Enterprise/config/config.php';
}
include_once dirname(__FILE__) . '/EnterpriseTwitterConnector.class.php';
require_once BASEDIR . '/server/secure.php';

$ticket = checkSecure('admin');
BizSession::startSession( $ticket );

// Get Access Token from Twitter.
$twitConn = new EnterpriseTwitterConnector();
$twitConn->retrieveAccessTokenFromRedirection( $_GET );

// Go to index.php.
header('Location: ' . SERVERURL_ROOT.INETROOT . '/server/admin/webappindex.php?webappid=TwitterConfig&plugintype=config&pluginname=Twitter');
