<?php
/****************************************************************************
   Copyright 2008-2009 WoodWing Software BV

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

require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';

class YouTubePublish_PubPublishing extends PubPublishing_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }

	public function isInstalled()
	{
		$installed = false;
		require_once dirname(__FILE__) . '/config.php';
		// check if values have been defined
		if (defined('YOUTUBEPUBLISH_USERNAME') && defined('YOUTUBEPUBLISH_USERPWD') && 
			defined('YOUTUBEPUBLISH_CLIENT_ID') && defined('YOUTUBEPUBLISH_DEV_KEY')) {
			$installed = true;
		}
		return $installed;
	}
	
	public function runInstallation()
	{
		if (!$this->isInstalled()){
			$msg = 'YouTube User Account, Client ID and Developer Key must be define in "' . dirname(__FILE__) . '/config.php' . '"';
			throw new BizException('' , 'Server', null, $msg);
		}
	}

	/**
	 * Publishes a dossier which contained video objects to YouTube.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array Empty array
	 **/
	public function publishDossier (&$dossier, &$objectsindossier, $publishtarget)
	{
		require_once dirname(__FILE__) . '/YouTube.class.php';
		$YouTube = new YouTube();
		foreach ($objectsindossier as $object) {
			LogHandler::Log( 'YouTubePublish', 'DEBUG', 'Video Name:' . $object->MetaData->BasicMetaData->Name);
			switch ($object->MetaData->BasicMetaData->Type ){
			case 'Video':
				$tags 		= array();
  				$videoPath 	= $this->saveLocal( $object->MetaData->BasicMetaData->ID );
  				$videoName 	= $object->MetaData->BasicMetaData->Name;
  				$videoDesc 	= $object->MetaData->ContentMetaData->Description;
  				$videoTags 	= $object->MetaData->ContentMetaData->Keywords;
  				$videoFormat= $object->MetaData->ContentMetaData->Format;
  				$videoCat	= $this->getCategory( $publishtarget );
  				if( file_exists( $videoPath ) ) {
  					$videoId = $YouTube->publishVideo( $videoPath, $videoName, $videoDesc, $videoTags, $videoFormat, $videoCat);
  					$object->ExternalId = $videoId;
  					if( $object->ExternalId ) {
  						unlink($videoPath);
  					}
  				}
				break;
			}
		}
		return array();
	}

	/**
	 * Updates/republishes a published dossier with contained video type objects to YouTube
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array empty array
	 **/
	public function updateDossier (&$dossier, &$objectsindossier, $publishtarget)
	{
		$videos = array();
		foreach ($objectsindossier as $object){
			switch ($object->MetaData->BasicMetaData->Type ){
				case 'Video':
					$videos[] = $object;
					break;
			}
		}
		require_once dirname(__FILE__) . '/YouTube.class.php';
		$YouTube = new YouTube();
		foreach( $videos as $video ) {
			$videoId	= $object->ExternalId;
			$videoPath 	= $this->saveLocal( $object->MetaData->BasicMetaData->ID );
			$videoName 	= $video->MetaData->BasicMetaData->Name;
  			$videoDesc 	= $video->MetaData->ContentMetaData->Description;
  			$videoTags 	= $object->MetaData->ContentMetaData->Keywords;
  			$videoFormat= $object->MetaData->ContentMetaData->Format;
  			$videoCat   = $this->getCategory( $publishtarget );
			$updateId 	= $YouTube->updateVideo( $videoId, $videoPath, $videoName, $videoDesc, $videoTags, $videoFormat, $videoCat );
			if($updateId){
				$object->ExternalId = $updateId;
				unlink($videoPath);	
			}
				
		}
		return array();
	}

	/**
	 * Removes/unpublishes a published dossier from YouTube.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array Empty array
	 */
	public function unpublishDossier ($dossier, $objectsindossier, $publishtarget)
	{
		$videoIds = array();
		foreach ($objectsindossier as $object){
			switch ($object->MetaData->BasicMetaData->Type ){
				case 'Video':
					$videoIds[] = $object->ExternalId;
					break;
			}
		}
		require_once dirname(__FILE__) . '/YouTube.class.php';
		$YouTube = new YouTube();
		foreach( $videoIds as $videoId ) {
			$YouTube->unpublishVideo( $videoId );
		}
		return array();
	}
	
	/**
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from Publishing system
	 *
	 **/
	public function previewDossier (&$dossier, &$objectsindossier, $publishtarget)
	{
		$msg = 'Cannot preview YouTube Video';
		throw new BizException( 'ERR_ERROR', 'Server', $msg, $msg );
	}
	
	/**
	 * getCategory
	 * 
	 * The Issue name of YouTube Publication channel will be the the category in YouTube
	 *
	 * @param unknown_type $publishtarget
	 * @return unknown
	 */
	private function getCategory( $publishtarget )
	{
		$issue = DBIssue::getIssue( $publishtarget->IssueID );
		return $issue['name'];
	}
	/**
	 * Requests fieldvalues from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array empty array
	 **/
	public function requestPublishFields ($dossier, $objectsindossier, $publishtarget)
	{
		return array();
	}
	
	/**
	 * Requests dossier URL from an external publishing system
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return string
	 */
	public function getDossierURL ($dossier, $objectsindossier, $publishtarget)
	{
		return '';
	}
	
	public function saveLocal ($videoId)
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$userName = BizSession::getShortUserName();
		$object = BizObject::getObject( $videoId, $userName, false, 'native', array('Targets') ); 
	
		$Name	= $object->MetaData->BasicMetaData->Name;
		$format	= $object->MetaData->ContentMetaData->Format;
		// Change Format into extension
		$extension = MimeTypeHandler::mimeType2FileExt($format);
	
		// Retreive file data
		$filePath = $object->Files[0]->FilePath;
	

		if (defined( 'YOUTUBE_DIRECTORY' )) {
			if( !is_dir(YOUTUBE_DIRECTORY) ) {
				mkdir(YOUTUBE_DIRECTORY);
			}
			$exportname = YOUTUBE_DIRECTORY . '/' . $Name . $extension;
			$fp = fopen($exportname, "w+");
			if ($fp) {
				fputs($fp, file_get_contents($filePath));
				fclose($fp);
			} else {
				$msg = 'Cannot write video file' . $exportname;
				throw new BizException( 'ERR_ERROR', 'Server', $msg, $msg );
			}
		} else {
			$msg = 'YOUTUBE_DIRECTORY is not defined';
			throw new BizException( 'ERR_ERROR', 'Server', $msg, $msg );
		}
		return $exportname;
	}
}
