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
 *
 * Configuration file for the Tika server plug-in
 */

// TIKA_SERVER_URL
//    HTTP root location (URL) of the Tika Server. 
//    The URL should end with a slash '/'.
//    By default, each Enterprise Server uses its own Tika Server:
//       define ('TIKA_SERVER_URL', LOCALURL_ROOT.'/config/plugins/Tika/TikaServer/' );
//    When there is a need to share one Tika Server, configure its URL instead:
//       define ('TIKA_SERVER_URL', 'http://<Tika_IP>/TikaServer/' );
//
if( !defined('TIKA_SERVER_URL') ) {
	define( 'TIKA_SERVER_URL', LOCALURL_ROOT.INETROOT.'/config/plugins/Tika/TikaServer/' );
}

// TIKA_CONNECTION_TIMEOUT
//    Tika Server HTTP connection timeout in seconds. 
//    Increase the timeout to support large files (upload) or when server logging shows this error:
//       "Unable to read response, or response is empty"
//
if( !defined('TIKA_CONNECTION_TIMEOUT') ) {
	define( 'TIKA_CONNECTION_TIMEOUT', 600 );
}

// TIKA_FORMATS
//    File formats (content types) to process by the Tika server (when uploaded or saved).
//    WoodWing has tested Adobe PDF, Microsoft Word, Excel and Powerpoint formats, which are
//    enabled by default. Other formats supported by Tika are not tested and are therefore commented out.
//    Nevertheless, any format supported by both Enterprise and Tika can be enabled, but at your own risk.
//	  Note: Tika supports more formats then listed below. Only those formats are listed that are also supported
//	  by Enterprise. Make sure that formats added below are also enabled in the 'EXTENSIONMAP' in configserver.php.
//
if( !defined('TIKA_FORMATS') ) {
	define ('TIKA_FORMATS', serialize(array(
			'application/pdf',
			'application/msword',
			'application/vnd.ms-excel',
			'application/vnd.ms-powerpoint',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'application/x-apple-numbers',
			'application/x-apple-pages',
			'application/x-apple-keynote',
			/*
			'application/vnd.oasis.opendocument.text',
			'xml',
			'application/x-gzip',
			'application/zip',
			'text/richtext',
			'audio/x-aiff',
			'audio/basic',
			'audio/midi',
			'audio/mpeg',
			'audio/x-wav',
			'image/gif',
			'image/jpeg',
			'image/png',
			'image/tiff',
			*/
	)));
}