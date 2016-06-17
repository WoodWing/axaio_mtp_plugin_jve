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

// Plug-in config file
require_once dirname(__FILE__) . '/config.php';

class Claro
{
	private $id = null;
	private $publication = null;
	private $ticket = null;
	private $blackwhite = null;
	
	public function addObject()
	{
		$id = $this->id;
		$ticket = $this->ticket;
		// Get current Metadata
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$req = new WflGetObjectsRequest( $ticket, array($id), false, 'native', null );
		$service = new WflGetObjectsService();
		$resp = $service->execute( $req );
		if( $resp ) {
			$object = $resp->Objects[0];
		}

		$comment = $object->MetaData->WorkflowMetaData->Comment;
		$filePath = $object->Files[0]->FilePath;
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		$ext = MimeTypeHandler::mimeType2FileExt($object->MetaData->ContentMetaData->Format);
		
		$imageTypes	 	= unserialize(CLARO_IMAGE_TYPE);
		if( !in_array($ext, $imageTypes) ) {
			return;
		}

		$image = $this->getImageData($comment);
		if(!$image){ return; }

		// write image
		$exportPath = $this->getConfig('EXPORT_IMAGE_PATH');
		$imagePath = "$exportPath$id$ext";
		file_put_contents($imagePath, file_get_contents($filePath));
		
		// adjust path if server runs on different filesystem
		if(defined('SERVER_EXPORT_IMG_PATH')) {
			$image_directory_from_server = $this->getConfig('SERVER_EXPORT_IMG_PATH');
			if ($image_directory_from_server) {
				$imagePath = "$image_directory_from_server$id.$ext";
			}
		}

		$image['imagepath'] = $imagePath;
		$xml = $this->generateXML($image);
	
		$to_directory = $this->getConfig('EXPORT_PATH');
		$path = "$to_directory$id.xml";
		file_put_contents($path, $xml);
				
		// update status
		$st = $this->getStateId(CLARO_PROCESS_STATUS);
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename("objects");
		$sql = "update $db set `state` = $st where `id` = $id";
		$dbdriver->query($sql);
	}

	private function getImageData($comment)
	{
		$id = $this->id;
		$comment = str_replace('<', '', $comment);
		$comment = str_replace('>', '', $comment);
		$comment = str_replace('"', '', $comment);	

		$img = array();
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename("claro");
		$sql = "SELECT * from $db  where `oid` = $id";
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		if(!$row) return null;
		
		$img['cropx'] 	= $row['cropx'];
		$img['cropy'] 	= $row['cropy'];
		$img['rotate']	= $row['rotate'];
		$img['width']	= $row['width'];
		$img['height']	= $row['height'];
		$img['comment'] = $comment;
		
		if (!$this->getConfig('DOCROP') ) {
			$img['cropx'] 	= 0;
			$img['cropy'] 	= 0;
			$img['width']	= 100;
			$img['height']	= 100;
		}
		if (!$this->getConfig('DOROTATE') ){
			$img['rotate'] = 0;
		}
		return $img;
	}

	private function generateXML($image)
	{
		$path = BASEDIR."/config/plugins/claro/claro.xml";
		$xml = file_get_contents($path);

		$xml = str_replace('%CROPX%',		$image['cropx'], 	$xml);
		$xml = str_replace('%CROPY%',		$image['cropy'], 	$xml);
		$xml = str_replace('%ROTATE%',		$image['rotate'], 	$xml);
		$xml = str_replace('%WIDTH%',		$image['width'], 	$xml);
		$xml = str_replace('%HEIGHT%',		$image['height'], 	$xml);
		$xml = str_replace('%IMAGEPATH%', 	$image['imagepath'],$xml);
		$xml = str_replace('%COMMENT%', 	$image['comment'], 	$xml);
		
		// which script (color or BW)
		if ($this->blackwhite)
			$xml = str_replace('%BW%', '1', $xml);
		else
			$xml = str_replace('%BW%', '0', $xml);
		return $xml;
	}

	private function validateState($state)
	{
		if (defined('CLARO_PRE_STATUS') && $state == $this->getStateId(CLARO_PRE_STATUS)) { 
			return true;
		}
		if (defined('CLARO_PRE_BW_STATUS') && $state == $this->getStateId(CLARO_PRE_BW_STATUS)) {
			$this->blackwhite = true;
			return true;
		}
		if (defined('CLARO_PROCESS_STATUS') && $state == $this->getStateId(CLARO_PROCESS_STATUS)) {
			return true;
		}
		if (defined('CLARO_POST_STATUS') && $state == $this->getStateId(CLARO_POST_STATUS)) {
			return true;
		}
		
		return false;
	}
	
	public function getStateId($status)
	{
		$id = $this->publication->Id;
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename("states");
		$sql = "SELECT id from $db" . " where `publication` = $id and `type` = 'Image' and `state` = '$status'";
		$sth = $dbDriver->query($sql);
		$result = $dbDriver->fetch($sth);	
		return $result['id'];
	}

	private function getConfig($config)
	{
		$configs = unserialize(CLARO_CONFIG);

		return $configs[$this->publication->Name][$config];
	}

	public function __construct($ticket, $id, $publication, $type, $state)
	{
		$this->publication 	= $publication;
		$this->ticket 		= $ticket;
		$this->id = $id;
		if($type != 'Image' || !$this->validateState($state->Id)) {
			throw new Exception('Type is not image or state is invalid');
		}
	}
}
