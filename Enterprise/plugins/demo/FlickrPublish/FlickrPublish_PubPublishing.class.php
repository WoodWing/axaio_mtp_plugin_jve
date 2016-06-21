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

// Plug-in config file
require_once dirname(__FILE__) . '/config.php';

class FlickrPublish_PubPublishing extends PubPublishing_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }

	public function isInstalled()
	{
		$installed = false;
		require_once dirname(__FILE__) . '/config.php';
		// check if values have been defined
		if (defined('FLICKRPUBLISH_API_KEY') && defined('FLICKRPUBLISH_API_SECRET') && defined('FLICKRPUBLISH_TOKEN'))
		{
			$installed = true;
		}
		return $installed;
	}
	
	public function runInstallation()
	{
		if (!$this->isInstalled()){
			$msg = 'Flickr API_Key, Secret and Token must be define in "' . dirname(__FILE__) . '/config.php' . '"';
			throw new BizException('' , 'Server', null, $msg);
		}
	}

	/**
	 * Publishes a dossier which contained image objects to Flickr.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array Empty array
	 **/
	public function publishDossier (&$dossier, &$objectsindossier, $publishtarget)
	{
		require_once dirname(__FILE__) . '/Flickr.class.php';
		$Flickr = new FlickrPublish;
		foreach ($objectsindossier as $object) {
			switch ($object->MetaData->BasicMetaData->Type ){
			case 'Image':
				$tags 		= array();
				$imgPath 	= $this->saveLocal( $object->MetaData->BasicMetaData->ID );
				$imgName 	= $object->MetaData->BasicMetaData->Name;
				$imgDesc 	= $object->MetaData->ContentMetaData->Description;
				$imgTags 	= $object->MetaData->ContentMetaData->Keywords;
				if( file_exists( $imgPath ) ) {
					$object->ExternalId = $Flickr->publishImage( $imgPath, $imgName, $imgDesc, $imgTags);
					if( $object->ExternalId ) {
						unlink($imgPath);
					}
				}
				break;
			}
		}
		return array();
	}

	/**
	 * Updates/republishes a published dossier with contained image type objects to Flickr
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array empty array
	 **/
	public function updateDossier (&$dossier, &$objectsindossier, $publishtarget)
	{
		$images = array();
		foreach ($objectsindossier as $object){
			switch ($object->MetaData->BasicMetaData->Type ){
				case 'Image':
					$images[] = $object;
					break;
			}
		}
		require_once dirname(__FILE__) . '/Flickr.class.php';
		$Flickr = new FlickrPublish;
		foreach( $images as $image ) {
			$imgId 	 = $object->ExternalId;
  			$imgPath = $this->saveLocal( $object->MetaData->BasicMetaData->ID );
			$imgName = $image->MetaData->BasicMetaData->Name;
  			$imgDesc = $image->MetaData->ContentMetaData->Description;
			$Flickr->updateImage( $imgId, $imgPath, $imgName, $imgDesc );
			unlink($imgPath);
		}
		return array();
	}

	/**
	 * Removes/unpublishes a published dossier from Flickr.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array Empty array
	 */
	public function unpublishDossier ($dossier, $objectsindossier, $publishtarget)
	{
		$imageIds = array();
		foreach ($objectsindossier as $object){
			switch ($object->MetaData->BasicMetaData->Type ){
				case 'Image':
					$imageIds[] = $object->ExternalId;
					break;
			}
		}
		require_once dirname(__FILE__) . '/Flickr.class.php';
		$Flickr = new FlickrPublish;
		foreach( $imageIds as $imageId ) {
			$Flickr->unpublishImage( $imageId );
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
		// keep analyzer happy
		$dossier = $dossier;
		$objectsindossier = $objectsindossier;
		$publishtarget = $publishtarget;
		$msg = 'Cannot preview Flickr photo';
		throw new BizException( 'ERR_ERROR', 'Server', $msg, $msg );
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
		// keep analyzer happy
		$objectsindossier = $objectsindossier;
		
		$result = array();

		$imageIds = array();
		foreach ($objectsindossier as $object){
			switch ($object->MetaData->BasicMetaData->Type ){
				case 'Image':
					$field = 'url';
					if( !empty($result) ) {
						$field = $field.' '.(count($result)+1).'';
					}
					$result[] = self::getNewPubField($field, 'string', array(self::getFlickrAccountURL().'/'.$object->ExternalId) );
					break;
			}
		}
		
		return $result;
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
		// keep analyzer happy
		$objectsindossier = $objectsindossier;
		$publishtarget = $publishtarget;

		// We can publish multiple images, so we just show the account page
		return self::getFlickrAccountURL();
	}
	
	public function saveLocal ($imageId)
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$userName = BizSession::getShortUserName();
		$object = BizObject::getObject( $imageId, $userName, false, 'native', array('Targets') ); 
	
		$Name	= $object->MetaData->BasicMetaData->Name;
		$format	= $object->MetaData->ContentMetaData->Format;
		// Change Format into extension
		$extension = MimeTypeHandler::mimeType2FileExt($format);
	
		// Retreive file data
		$filePath = $object->Files[0]->FilePath;
	

		if (defined( 'FLICKR_DIRECTORY' )) {
			if( !is_dir(FLICKR_DIRECTORY) ) {
				mkdir(FLICKR_DIRECTORY);
			}
			$exportname = FLICKR_DIRECTORY . '/' . $Name . $extension;
			$fp = fopen($exportname, "w+");
			if ($fp) {
				fputs($fp, file_get_contents($filePath));
				fclose($fp);
			} else {
				$msg = 'Cannot write image file' . $exportname;
				throw new BizException( 'ERR_ERROR', 'Server', $msg, $msg );
			}
		} else {
			$msg = 'FLICKR_DIRECTORY is not defined';
			throw new BizException( 'ERR_ERROR', 'Server', $msg, $msg );
		}
		return $exportname;
	}
	/**
	 * Return new PubField or Field object depending on the server version.
	 *
	 * @param string $Key
	 * @param PropertyType $Type
	 * @param array $Values
	 * @return PubField|Field
	 */
	private static function getNewPubField( $Key=null, $Type=null, $Values=null )
	{
		// PubField only exists in 7.0
		if (class_exists('PubField')){
			return new PubField($Key, $Type, $Values);
		}
		return new Field($Key, $Type, $Values);
	}
	
	private static function getFlickrAccountURL()
	{
		return 'http://www.flickr.com/photos/'.FLICKRPUBLISH_ACCOUNT;
	}
}
