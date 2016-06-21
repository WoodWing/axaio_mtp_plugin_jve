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

/**
 * @package 	Demo Server Plugins
 * @subpackage 	Fotoware Content Source plugin
 * @since 		v6.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';
require_once BASEDIR.'/server/interfaces/services/BizDataClasses.php';
require_once BASEDIR.'/config/plugins/Fotoware/Fotoware.class.php';
require_once BASEDIR.'/config/plugins/Fotoware/config.php';

// specify a Unique ID & prefix for this contentSource
define ('FOTOWARE_CONTENTSOURCEID'     , 'FW');
define ('FOTOWARE_CONTENTSOURCEPREFIX' , '_FW_');

class Fotoware_ContentSource extends ContentSource_EnterpriseConnector
{
	final public function getContentSourceId( )
	{
		return FOTOWARE_CONTENTSOURCEID;
	}
	
	public function isInstalled()
	{
		try {
			$archives = Fotoware::getArchives();
		} catch( BizException $e ) {
			// We catch any errors to let Server Plug-ins admin page show our uninstalled plugin.
			$archives = null;
		}
		return !empty( $archives );
	}
	
	public function runInstallation()
	{
		if( !$this->isInstalled() ) {
			throw new BizException( '', 'Server', 'Unable to Initialize the Fotoware plugin', 'Unable to Initialize the Fotoware plugin' );
		}
	}
	
	/**
	 * getQueries
	 *
	 * Returns queries offered, we have one called Fotoware that has combo to select archive and editbox for keyword to search on.
	 */
	final public function getQueries()
	{
		$queries = array();
		// When not connected to Internet getArchives will raise an exception which would make logon fail.
		// This is unwanted, so we catch it. It's already written to log by raiser, so no need to log here.
        Try {
			// Define queries to be returned 
			$archives = Fotoware::getArchives();
        }  
        catch (BizException $e) {
        	return $queries;
        }

		$queryParamArchives = new PropertyInfo( 
		'Archive', 'Archive', // Name, Display Name
		null  ,			    // Category, not used
		'list',				// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
		$archives[0],		// Default value first of array
		$archives,		    // value list
		null, null, null,	// min value, max value,max length
		null, null			// parent value (not used), dependent property (not used)
		);		

	 	$queryParamKeyword = new PropertyInfo( 
			'keyword', 'keyword',    // Name, Display Name
			null,				// Category, not used
			'string',				// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
			'',		// Default value
			null,		// value list
			null, null, null,	// min value, max value,max length
			null, null			// parent value (not used), dependent property (not used)
			);
			
		$queries[] = new NamedQueryType( 'Fotoware', array( $queryParamArchives, $queryParamKeyword ) );                                                                  
       
		return $queries;
	}
	
	/**
	 * doNamedQuery
	 *
	 * Do search in fotoware, first param is archive, second the keyword being searched on
	 * We have just one namedQuery, so we ignore the query parameter.
	 */
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		// keep code analyzer happy for unused params:
		$query=$query; $firstEntry=$firstEntry; $maxEntries=$maxEntries; $order=$order;
		
		// Create array with column definitions and run query
		$cols = Fotoware::getQueryColumns();
		$rows = Fotoware::runQuery ( $params[0]->Value, $params[1]->Value );
		
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		return new WflNamedQueryResponse( $cols, $rows,	null, null, null, null, 1, count($rows), count($rows), null );
	}
		
	// -------------------------
	// - getAliebObject-
	// Get the FW_-File
	// -------------------------
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		$lock=$lock ; // we don't use this argument, keep analyzer happy
		LogHandler::Log('Fotoware', 'DEBUG', "Fotoware::getAlienObject called for $alienID - $rendition" );
		
		$fwID = substr( $alienID, strlen(FOTOWARE_CONTENTSOURCEPREFIX) );
	
		$content = '';
        $metaData = Fotoware::getFile( $fwID, $rendition, $content );
        
		if( empty($content) ) {
			$files = array();
		} else {
			require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
			$attachment = new Attachment($rendition, 'image/jpeg');
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($content, $attachment);
			$files = array(	$attachment );
		}

		// 20100210: !!Dirty Hack!! This hack prevents BZ #17769 !
		$alienID = '_FW_5004+113';
		// End Dirty Hack!

		$meta = new MetaData();
		$this->fillMetaData( $meta, $alienID, $metaData );
		
		$object = new Object( 	$meta,				// meta data
							 	array(), null,		// relations, pages
			 					$files, 			// Files array of attachment
 								null, null, null	// messages, elements, targets
							);
		return $object;
	}
	
	/**
	 * createShadowObject
	 * 
	 * Create object record in Enterprise with thumb and preview, native to stay in file-system.
	 * For simplicity of this example, we assume that we only deal with jpg images
	 *
	 * @param string	$id 		Alien object id, so include the _<ContentSourceID>_ prefix
	 * 
	 * @return Object
	 */
	final public function createShadowObject( $alienID, $destObject )
	{
		LogHandler::Log('SimpleFileSystem', 'DEBUG', "Fotoware::createShadowObject called for $alienID" );
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/utils/ImageUtils.class.php'; // ResizeJPEG
		
		$fwID = substr( $alienID, strlen(FOTOWARE_CONTENTSOURCEPREFIX) );
	
		$nativeContent = '';
        $metaData = Fotoware::getFile( $fwID, 'native', $nativeContent );
		$previewContent = '';
        Fotoware::getFile( $fwID, 'preview', $previewContent );
		$thumbContent = '';
        Fotoware::getFile( $fwID, 'thumb', $thumbContent );

		require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
		$native = new Attachment('native', $metaData['Format']);
		$transferServer = new BizTransferServer();
		$transferServer->writeContentToFileTransferServer($nativeContent, $native);
		$files = array($native);
		if( !empty($previewContent) ) {
			require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
			$preview = new Attachment('preview', 'image/jpg');
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($previewContent, $preview);
			$files[] = $preview;
		}
		if( !empty($thumbContent) ) {
			require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
			$thumb = new Attachment('thumb', 'image/jpg');
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($thumbContent, $thumb);
			$files[] = $thumb;
		}
		
		// In case of a copy the user already filled in an object, we use that
		// For a real-life content source this would be further filled with metadata from the content source
		if( $destObject ) {
			$meta = $destObject->MetaData;
		} else {
			$meta = new MetaData();
			$destObject = new Object( $meta, array(), null, null, null, null, null );
		}
		$this->fillMetaData( $meta, $alienID, $metaData );
		$destObject->Files = $files;
		$destObject->Targets = array();
		return $destObject;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - 
	// Below private implementation methods:
	// - - - - - - - - - - - - - - - - - - - - - - 
		
	private function getEnterpriseContext( &$publication, &$category, &$status )
	{
		// Get list of publications from Enterpise. If available we use WW News
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		
		$username = BizSession::getShortUserName();
		$pubs     = BizPublication::getPublicationInfos($username);
		$pubFound = $pubs[0]; // default to first pub
		
		// Can we find the defined FOTOWARE_SHADOW_BRAND
		foreach( $pubs as $pub ) {
			if( $pub->Name == FOTOWARE_SHADOW_BRAND ) {
				$pubFound = $pub;
				break;
			}
		}
		$publication 	= new Publication($pubFound->Id);
		
		$category 		= new Category($pubFound->Sections[0]->Id);
		foreach ( $pubFound->Sections as $section ) {
			if ( $section->Name == FOTOWARE_SHADOW_CATEGORY ) {
			   $category = new Category( $section->Id );
			   break;
			}   
		}
		foreach( $pubFound->States as $state ) {
			if( $state->Type == ('Image' ) ) {
				$status	= new State($state->Id);
				break;
			}
		}
	}
	
	private function getPublication( )
	{
		// do we have pub id already cached?
		if( !$this->pubId) {
			$dum1=''; $dum2='';
			$this->getEnterpriseContext( $this->pubId, $dum1, $dum2 );
		}

		return $this->pubId->Id;
	}
	
	private function fillMetaData( &$meta, $alienID, $metaData )
	{	
		// Get defult Pub, Category and Status
		$publication = ''; $category = ''; $status = '';
		$this->getEnterpriseContext( $publication, $category, $status );
		
		if( !$meta->BasicMetaData ) $meta->BasicMetaData = new BasicMetaData();
		$meta->BasicMetaData->ID    		= $alienID;
		$meta->BasicMetaData->DocumentID    = $metaData['Id'];
		$meta->BasicMetaData->Type			= $metaData['Type'];
		$meta->BasicMetaData->ContentSource	= FOTOWARE_CONTENTSOURCEID;
		if( empty($meta->BasicMetaData->Name) ) {
			$meta->BasicMetaData->Name 			= $metaData['Name'];
		}
		if( !$meta->BasicMetaData->Publication ) {
			$meta->BasicMetaData->Publication = $publication;
		}
		if( !$meta->BasicMetaData->Category ) {
			$meta->BasicMetaData->Category = $category;
		}
		if( !$meta->ContentMetaData ) {
			$meta->ContentMetaData =  new ContentMetaData();
		}
		// ensure the following content fields are set, also when contentmeta data already available:
		$meta->ContentMetaData->Format		= $metaData['Format'] ;
		$meta->ContentMetaData->Width		= $metaData['Width'] ;
		$meta->ContentMetaData->Height		= $metaData['Height'] ;
		$meta->ContentMetaData->FileSize	= $metaData['FileSize'];
		$meta->ContentMetaData->Dpi			= array_key_exists('Dpi', $metaData) ? $metaData['Dpi'] : '72'; // default to 72 dpi if not known
		$meta->ContentMetaData->ColorSpace	= array_key_exists('ColorSpace', $metaData) ? strtoupper($metaData['ColorSpace']) : '';
		// And some more fields that we will in if not already there and available:
		if( !$meta->ContentMetaData->Description && array_key_exists('Caption', $metaData)) {
			$meta->ContentMetaData->Description 		= $metaData['Caption'];
		}
		if( !$meta->ContentMetaData->DescriptionAuthor && array_key_exists('Caption Writer',$metaData)) {
			$meta->ContentMetaData->DescriptionAuthor	= $metaData['Caption Writer'];
		}
		if( !$meta->ContentMetaData->Keywords  && array_key_exists('Keywords',$metaData)) {
			$meta->ContentMetaData->Keywords = $metaData['Keywords'];
		}
		if( !$meta->ContentMetaData->Slugline  && array_key_exists('Headline',$metaData)) {
			$meta->ContentMetaData->Slugline = $metaData['Headline'];
		}
		
		if( !$meta->RightsMetaData ) $meta->RightsMetaData = new RightsMetaData();
		$meta->RightsMetaData->Copyright		= array_key_exists('Copyright', $metaData) ? $metaData['Copyright'] : '';
		$meta->RightsMetaData->CopyrightMarked	= array_key_exists('CopyrightMarked', $metaData) ? $metaData['CopyrightMarked'] : 'false';
		$meta->RightsMetaData->CopyrightURL		= array_key_exists('CopyrightURL', $metaData) ? $metaData['CopyrightURL'] : '';
		
		if( !$meta->SourceMetaData ) $meta->SourceMetaData = new SourceMetaData();
		$meta->SourceMetaData->Credit		= array_key_exists('Credit', $metaData) ? $metaData['Credit'] : '';
		$meta->SourceMetaData->Source		= array_key_exists('Source', $metaData) ? $metaData['Source'] : '';
		$meta->SourceMetaData->Author		= array_key_exists('Author', $metaData) ? $metaData['Byline'] : '';

		if( !$meta->WorkflowMetaData ) $meta->WorkflowMetaData = new WorkflowMetaData();
		$meta->SourceMetaData->Urgency		= array_key_exists('Priority', $metaData) ? $metaData['Priority'] : '';
		
		$meta->WorkflowMetaData->Modified	= $metaData['Modified'];
		$meta->WorkflowMetaData->Created	= $metaData['Created'];
		$meta->WorkflowMetaData->State		= $status;
	}
}
