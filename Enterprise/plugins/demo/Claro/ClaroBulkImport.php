<?php
/****************************************************************************
   Copyright 2009 WoodWing Software BV

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

require_once '../../../config/config.php';
require_once dirname(__FILE__) . '/config.php';  	
require_once dirname(__FILE__) . '/Claro.class.php';
require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';

$session = new SoapClient( 
			LOCALURL_ROOT.INETROOT.'/server/interfaces/SmartConnectionAdmin.wsdl', 
			array(
	  			'location' => LOCALURL_ROOT.INETROOT.'/adminindex.php',
	  			'uri' => 'urn:SmartConnectionAdmin',
	  			'soap_version' => SOAP_1_2, 'trace' => 1 )
);

// LogOn
try
{
	$logon 					= new stdClass();
	$logon->AdminUser 		= CLARO_WW_USERNAME;
	$logon->Password 		= CLARO_WW_USERPWD;
	$logon->ClientName 		= 'Claro Importer';
	$logon->ClientAppName 	= 'Claro Bulk Import'; 
	$logon->ClientAppVersion = 'v'.SERVERVERSION;

	$return = $session->LogOn( $logon );
	
} catch( SoapFault $e ){
	$msg = $e->faultstring;
	throw new BizException( '', 'Server', $msg, $msg );
}

$ticket = $return->Ticket;

$claro_config 	= unserialize(CLARO_CONFIG);
$imageTypes	 	= unserialize(CLARO_IMAGE_TYPE);

// Get the files in the import path
foreach ($claro_config as $p => $config) {
	$dir = $config['IMPORT_PATH'];
	
	$files = array();
	if ( ($dh = opendir($dir)) !== false ) {
		while (($file = readdir($dh)) !== false) {
			$mime = MimeTypeHandler::filePath2MimeType($file);
			$ext  = MimeTypeHandler::mimeType2FileExt($mime); 
			if( in_array($ext, $imageTypes) ) {
				$files[] = $file;
			}
		}
		closedir($dh);
	}
	
	$doneDir = $dir."/done/";
	if (!file_exists($doneDir)) mkdir($doneDir);
	
	// process files in import path
	foreach ($files as $file) {
		// Get Image Enterprise Id
		$mimeType	= MimeTypeHandler::filePath2MimeType($file);
		$extension 	= MimeTypeHandler::mimeType2FileExt($mimeType);
		$id 		= MimeTypeHandler::native2DBname($file, $mimeType);

		print "Processing: $id.$extension\n";
			
		// get image content
		require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
		$attachment = new Attachment('native', $mimeType);					
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer($dir."/$file" , $attachment);					
		$files = array(	$attachment );

		require_once BASEDIR.'/server/authorizationmodule.php';
		global $globAuth;
		$globAuth = new authorizationmodule();
		
		// Get current Metadata
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$req = new WflGetObjectsRequest( $ticket, array($id), true, 'native', null );
		$service = new WflGetObjectsService();
		$resp = $service->execute( $req );
		if( $resp ) {
			$object = $resp->Objects[0];
		}

		try {
			$metaData 	= $object->MetaData;
			$pub 		= $metaData->BasicMetaData->Publication;
			$type		= $metaData->BasicMetaData->Type;
			$state		= $metaData->WorkflowMetaData->State;
			$claro 		= new Claro($ticket, null, $pub, $type, $state);
			$status		= $claro->getStateId(CLARO_POST_STATUS);

			$metaData->WorkflowMetaData->State->Id = $status;

			// Create Object
			$newObject = new Object( $metaData, array(), null, $files, null, null, null );
			$newObjects = array ($newObject);

			require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
			$service = new WflSaveObjectsService();
			$service->execute( new WflSaveObjectsRequest( $ticket, true, true, true, $newObjects, null, null ) );
			$succeed = true;
		} catch( BizException $e ) {
			$message = $e->getMessage();
			$succeed = false;
		}
		// move file to donedir
		rename($dir.$file, $doneDir.$file);
	}
}

print "Done";