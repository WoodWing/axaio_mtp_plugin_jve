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

/**
 * Class for publishing content to Tripolis Dialogue Webservices.
 * This plug-in provided as a demo plug-in to show that it's possible to publish
 * to Tripolis Dialogue Webservices. This plug-in is provided "as is" without
 * warranty of WoodWing Software.
 **/

require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';

class Tripolis_PubPublishing extends PubPublishing_EnterpriseConnector
{
	const LABELFORMAT = 'WWENT_%1_%2_%3_%4';
	/**
	 * Soap Client
	 *
	 * @var DialogueService
	 */
	private $soapClient;
	/**
	 * Authentication information for the Dialogue service
	 *
	 * @var AuthInfo
	 */
	private $authInfo;

	final public function getPrio ()
	{
		return self::PRIO_DEFAULT;
	}

	final public function getRunMode ()
	{
		return self::RUNMODE_SYNCHRON;
	}

	/**
	 * Publishes a dossier with contained objects (articles. images, etc.) to Tripolis.
	 * The plugin is supposed to publish the dossier and it's articles and fill in some fields for reference.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from Tripolis
	 **/
	public function publishDossier (&$dossier, &$objectsindossier, $publishtarget)
	{
		$this->init();
		
		$info = $this->getTargetInfo( $publishtarget );
		
		// should we send direct email or a newsletter
		switch ($info['EmailType']) {
			case 'DIRECTEMAIL':
				// direct email
				LogHandler::Log( 'Tripolis', 'DEBUG', 'Create direct email' );
				$directEmail = $this->upsertDirectMail( $objectsindossier, $publishtarget, $dossier );
				
				if ($info['DirectSent'] === true) {
					LogHandler::Log( 'Tripolis', 'DEBUG', 
						'Send direct email "' . $directEmail->name . '" directly to ' . $info['ContactGroupName'] );
					$jobId = $this->scheduleMailing( $info['ContactGroupName'], $directEmail->name, '' );
					$dossier->ExternalId = $jobId;
				}
				break;
			case 'NEWSLETTER':
				// newsletter
				LogHandler::Log( 'Tripolis', 'DEBUG', 'Create newsletter' );
				$newsletter = $this->upsertNewsletter( $objectsindossier, $info['NewsletterType'], $publishtarget, 
					$dossier );
				
				if ($info['DirectSent'] === true) {
					LogHandler::Log( 'Tripolis', 'DEBUG', 
						'Send newsletter "' . $newsletter->name . '" directly to ' . $info['ContactGroupName'] );
					$jobId = $this->scheduleMailing( $info['ContactGroupName'], '', $newsletter->name );
					$dossier->ExternalId = $jobId;
				}
				break;
		}
		//TODO should we use directEmailId or jobId
		// with job id we can get statistics
		//$dossier->ExternalId =  $directEmail->directEmailId;
		

		$result = array();
		if (class_exists('PubField')){ // PubField only exists in v7.0+
			$result[] = new PubField('URL', 'string',  array(''));
		} else {
			$result[] = new Field('URL', 'string',  array(''));
		}
		return $result;
	}

	/**
	 * Updates/republishes a published dossier with contained objects (articles. images, etc.) to Tripolis,
	 * using the $dossier->ExternalId to identify the dosier to Tripolis.
	 * The plugin is supposed to update/republish the dossier and it's articles and fill in some fields for reference.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from Tripolis
	 **/
	public function updateDossier (&$dossier, &$objectsindossier, $publishtarget)
	{
		throw new BizException( 'ERR_PUBLISH', 'Server', '', null, array('Tripolis' , 'Cannot update to Tripolis Dialogue') );
	}

	/**
	 * Removes/unpublishes a published dossier from Tripolis
	 * using the $dossier->ExternalId to identify the dosier to Tripolis.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from Tripolis
	 */
	public function unpublishDossier ($dossier, $objectsindossier, $publishtarget)
	{
		throw new BizException( 'ERR_PUBLISH', 'Server', '', null, array('Tripolis' , 'Cannot unpublish to Tripolis Dialogue') );
	}

	/**
	 * Requests fieldvalues from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return array of Fields containing information from Tripolis
	 **/
	public function requestPublishFields ($dossier, $objectsindossier, $publishtarget)
	{
		$this->init();
		$result = array();
		
		$getSummaryReport = new getSummaryReport( );
		$getSummaryReport->authInfo = $this->authInfo;
		$getSummaryReport->mailingId = $dossier->ExternalId;
		try {
			$resp = $this->soapClient->getSummaryReport( $getSummaryReport );
			$fields = get_object_vars( $resp->summaryReport );
			foreach ($fields as $key => $value) {
				$result[] = new Field( $key, 'string', array($value) );
			}
		} catch (Exception $e) {
			throw new BizException( 'ERR_PUBLISH', 'Server', '', null, array('Tripolis' , $e->getMessage()) );
		}
		
		return $result;
	}

	/**
	 * Requests dossier URL from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsindossier
	 * @param PublishTarget $publishtarget
	 *
	 * @return string
	 */
	public function getDossierURL ($dossier, $objectsindossier, $publishtarget)
	{
		//TODO?
		return $dossier->ExternalId;
	}

	/**
	 * Previews a dossier with contained objects (articles. images, etc.) to an external publishing system.
	 * The plugin is supposed to send the dossier and it's articles to the publishing system and fill in the URL field for reference.
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
		$this->init();
		
		$result = array();
		$url = '';
		// check where we are called from
		if (strpos( $_SERVER['REQUEST_URI'], 'webservice.php' ) !== FALSE) {
			// called from content station to show preview
			LogHandler::Log( 'Tripolis', 'DEBUG', 'Show preview' );
			$info = $this->getTargetInfo( $publishtarget );
			switch ($info['EmailType']) {
				case 'DIRECTEMAIL':
					$directEmail = $this->upsertDirectMail( $objectsindossier, $publishtarget, $dossier );
					
					if (! is_null( $directEmail )) {
						// get preview direct email
						$previewDirectEmail = $this->getPreviewDirectEmail( 
							$directEmail->directEmailId, '' );
						print $previewDirectEmail->html;
					}
					break;
				case 'NEWSLETTER':
					$newsletter = $this->upsertNewsletter( $objectsindossier, $info['NewsletterType'], 
						$publishtarget, $dossier );
					if (! is_null( $newsletter )) {
						// get preview newsletter
						$previewNewsletter = $this->getPreviewNewsletter( 
							$newsletter->newsletterId, '' );
						print $previewNewsletter->htmlContent;
					}
					break;
			}
		} else {
			// called from content station to return url
			LogHandler::Log( 'Tripolis', 'DEBUG', 'Return preview url' );
			$url = SERVERURL_ROOT . INETROOT . '/config/plugins/Tripolis/webservice.php?ticket=' .
				 BizSession::getTicket() . '&dossierId=' . $dossier->MetaData->BasicMetaData->ID . '&pubChannelId=' . $publishtarget->PubChannelID .
				 '&issuelId=' . $publishtarget->IssueID;
			
			LogHandler::Log( 'Tripolis', 'DEBUG', 'Preview URL = ' . $url );
			$result[] = new Field( 'URL', 'string', array($url) );
		}
		return $result;
	}

	/**
	 * Test configuration called by wwtest.
	 *
	 * @return array with keys "Errors" and "Warnings"
	 */
	public function testConfig ()
	{
		
		$result = array('Errors' => array() , 'Warnings' => array());
		
		return $result;
	}

	/**
	 * Gets dossier components. Currently it only supports Article
	 *
	 * @param array $children
	 * @param array $texts
	 */
	private function getDossierComponents ($children, &$texts)
	{
		foreach ($children as $child) {
			switch ($child->MetaData->BasicMetaData->Type) {
				case 'Article':
					$elements = $this->getArticleElements( $child );
					$texts = array_merge( $texts, $elements );
					break;
			}
		}
	}

	private function getArticleElements ($article)
	{
		$elements = array();
		$content = $this->getFileContent( $article, 'native' );
		$format = $article->MetaData->ContentMetaData->Format;
		switch ($format) {
			case 'application/incopy':
				// Convert article into XHTML frames (tinyMCE compatible)
				require_once BASEDIR . '/server/appservices/textconverters/TextConverter.class.php';
				$fc = TextConverter::createTextImporter( $format );
				$xFrames = array();
				$stylesCSS = '';
				$stylesMap = '';
				$artDoc = new DOMDocument( );
				$artDoc->loadXML( $content );
				$fc->importBuf( $artDoc, $xFrames, $stylesCSS, $stylesMap );
				// handle multi element article
				foreach ($xFrames as $xFrame) {
					// content is in body element
					$doc = new SimpleXMLElement( $xFrame->Content );
					$content = '';
					foreach ($doc->body->children() as $bodyChild) {
						$content .= $bodyChild->asXML();
					}
					$element = new stdClass( );
					// standard label is body (InCopy articles without Smart Layout have the name of the article)
					if ($xFrame->Label != 'head' && $xFrame->Label != 'intro') {
						$element->Label = 'body';
					} else {
						$element->Label = $xFrame->Label;
					}
					$element->Content = $content;
					$element->ContentLength = strlen( $content );
					$element->Article = $article;
					$elements[] = $element;
				}
				break;
			case 'text/html':
				$element = new stdClass( );
				$element->Label = 'body';
				// only get body content from html
				$artDoc = new DOMDocument( );
				$content = mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' );
				
				@$artDoc->loadHTML( $content );
				$xpath = new DOMXPath( $artDoc );
				$bodies = $xpath->query( '/html/body' );
				$body = '';
				if ($bodies->length > 0) {
					$childs = $bodies->item( 0 )->childNodes;
					foreach ($childs as $child) {
						$body .= $artDoc->saveXML( $child );
					}
				} else {
					$body = $content;
				}
				$element->Content = $body;
				$element->ContentLength = strlen( $body );
				$element->Article = $article;
				// find label
				if (! empty( $article->Elements )) {
					// TO DO - handle multi-element articles
					if ($article->Elements[0]->Name != '') {
						// only filled labels
						$element->Label = $article->Elements[0]->Name;
					}
				}
				
				$elements[] = $element;
				break;
			default:
				$element = new stdClass( );
				$element->Label = 'body';
				$element->Content = $content;
				$element->ContentLength = strlen( $content );
				$element->Article = $article;
				// find label
				if (! empty( $article->Elements )) {
					// TO DO - handle multi-element articles
					if ($article->Elements[0]->Name != '') {
						// only filled labels
						$element->Label = $article->Elements[0]->Name;
					}
				}
				
				$elements[] = $element;
		}
		
		return $elements;
	}

	private function init ()
	{
		require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'config.php';
		require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'TripolisClasses.php';
		
		$this->soapClient = new DialogueService( TRIPOLIS_DIALOGSERVICE_URI, array('trace' => 1) );
		$this->authInfo = new AuthInfo( );
		$this->authInfo->client = TRIPOLIS_AUTHINFO_CLIENT;
		$this->authInfo->username = TRIPOLIS_AUTHINFO_USERNAME;
		$this->authInfo->password = TRIPOLIS_AUTHINFO_PASSWORD;
	}

	/**
	 * Gets a direct email object from Enterprise objects.
	 *
	 * @param array $texts
	 * @param PublishTarget $publishtarget
	 * @param Object $dossier
	 * @return DirectEmail
	 */
	private function getDirectEmail ($texts, $publishtarget, Object $dossier)
	{
		$html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">' .
			 '<html><head><title></title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . '</head><body>';
		foreach ($texts as $text) {
			$html .= $text->Content;
		}
		$html .= '</body></html>';
		
		$emailDoc = new DOMDocument( );
		@$emailDoc->loadHTML( $html );
		$xpath = new DOMXPath( $emailDoc );
		$imgNodes = $xpath->query( '//img[@id]' );
		foreach ($imgNodes as $imgNode) {
			$id = $imgNode->getAttribute( 'id' );
			if (strpos( $id, 'ent_' ) === 0) {
				$objectId = intval( substr( $id, 4 ) );
				$shorUserName = BizSession::getShortUserName();
				$object = BizObject::getObject( $objectId, $shorUserName, false, 'native', array('Targets') ); // requestInfo empty array isn't supported yet
				// make sure image object is targetted too
				// check if target already exists because BizRelation::updateObjectRelations deletes the current one
				// and then externalid is removed
				$externalId = $this->getExternalId( 
					$dossier->MetaData->BasicMetaData->ID, $objectId, $publishtarget );
				if (strlen( $externalId ) > 0) {
					// create object relation with the same target as the dossier
					$relation = new Relation( $dossier->MetaData->BasicMetaData->ID, 
						$objectId, 'Contained', null, null, null, null, null, $texts[0]->Article->Targets );
					BizRelation::updateObjectRelations( $shorUserName, array($relation) );
				}
				$imageInfo = $this->upsertImage( $object, $publishtarget, $dossier, $externalId );
				//TODO check other then jpg image formats
				$imgNode->setAttribute( 'src', 
					'http://public.tripolis.com/image_' . $imageInfo->imageId . '.jpg' );
			}
		}
		$html = $emailDoc->saveXML();
		
		$directEmail = new DirectEmail( );
		$directEmail->label = $this->getLabel( $dossier->MetaData->BasicMetaData->ID, $texts[0]->Article );
		$directEmail->name = strtolower( ereg_replace( '[^[:alnum:]]', '', $directEmail->label ) );
		$directEmail->subject = $texts[0]->Article->MetaData->BasicMetaData->Name;
		$directEmail->fromName = BizSession::getUserInfo( 'fullname' );
		$directEmail->fromAddress = BizSession::getUserInfo( 'email' );
		$directEmail->html = $html;
		
		return $directEmail;
	}

	/**
	 * Gets a direct email preview from the Tripolis Dialogue system.
	 *
	 * @param string $directEmailId
	 * @param string $directEmailName
	 * @return DirectEmail
	 */
	private function getPreviewDirectEmail ($directEmailId, $directEmailName)
	{
		$preview = null;
		// get preview direct email
		$previewDirectEmail = new previewDirectEmail( );
		$previewDirectEmail->authInfo = $this->authInfo;
		$previewDirectEmail->contactId = ''; //TODO
		$previewDirectEmail->directEmailId = $directEmailId;
		$previewDirectEmail->directEmailName = $directEmailName;
		$previewDirectEmail->workspaceName = TRIPOLIS_WORKSPACE;
		try {
			$previewDirectEmailResponse = $this->soapClient->previewDirectEmail( $previewDirectEmail );
			$preview = $previewDirectEmailResponse->directEmail;
		
		} catch (Exception $e) {
			LogHandler::Log( 'Tripolis', 'ERROR', 'previewDirectEmail error: ' . $e->getMessage() );
		}
		return $preview;
	}

	/**
	 * Inserts or updates a direct email in the Tripolis Dialogue system.
	 *
	 * @param array $objectsindossier
	 * @param PublishTarget $publishtarget
	 * @param Object $dossier
	 * @return DirectEmail
	 */
	private function upsertDirectMail ($objectsindossier, $publishtarget, $dossier)
	{
		$texts = array();
		$this->getDossierComponents( $objectsindossier, $texts );
		
		$directEmail = $this->getDirectEmail( $texts, $publishtarget, $dossier );
		
		// check if direct email already exists
		$preview = $this->getPreviewDirectEmail( '', $directEmail->name );
		if ($preview) {
			// set directEmailId to update instead of insert
			$directEmail->directEmailId = $preview->directEmailId;
		}
		
		// insert or update new direct email
		$upsertDirectMail = new upsertDirectMail( );
		$upsertDirectMail->authInfo = $this->authInfo;
		$upsertDirectMail->directEmail = $directEmail;
		$upsertDirectMail->directEmailTypeName = TRIPOLIS_DIRECTEMAIL_TYPE;
		$upsertDirectMail->workspaceName = TRIPOLIS_WORKSPACE;
		
		try {
			$upsertDirectMailResponse = $this->soapClient->upsertDirectMail( $upsertDirectMail );
			$directEmail->directEmailId = $upsertDirectMailResponse->directEmailId;
		} catch (Exception $e) {
			LogHandler::Log( 'Tripolis', 'ERROR', 'upsertDirectMail error: ' . $e->getMessage() );
			$directEmail = null;
		}
		
		return $directEmail;
	}

	/**
	 * Inserts or updates a newsletter in the Tripolis Dialogue system.
	 *
	 * @param array $objectsindossier
	 * @param string $newsletterType
	 * @param PublishTarget $publishtarget
	 * @param Object $dossier
	 * @return NewsletterInfo
	 */
	private function upsertNewsletter ($objectsindossier, $newsletterType, $publishtarget, $dossier)
	{
		$texts = array();
		$this->getDossierComponents( $objectsindossier, $texts );
		
		// insert or update articles
		$articles = $this->upsertArticles( $texts, $publishtarget, $dossier );
		
		// insert or update new newsletter
		$newsletterInfo = new NewsletterInfo( );
		$newsletterInfo->newsletterId = '';
		$newsletterInfo->label = $this->getLabel( $dossier->MetaData->BasicMetaData->ID, $dossier );
		$newsletterInfo->name = strtolower( ereg_replace( '[^[:alnum:]]', '', $newsletterInfo->label ) );
		$newsletterInfo->subject = $dossier->MetaData->BasicMetaData->Name;
		$newsletterInfo->fromAddress = '';
		$newsletterInfo->fromName = '';
		$newsletterInfo->newsletterTypeName = $newsletterType;
		
		// check if newsletter already exists
		$previewNewsletter = $this->getPreviewNewsletter( '', $newsletterInfo->name );
		if ($previewNewsletter) {
			// newletter exists so set the newsletter id
			$newsletterInfo->newsletterId = $previewNewsletter->newsletterId;
		}
		
		$upsertNewsletter = new upsertNewsletter( );
		$upsertNewsletter->authInfo = $this->authInfo;
		$upsertNewsletter->newsletterInfo = $newsletterInfo;
		$upsertNewsletter->workspaceName = TRIPOLIS_WORKSPACE;
		
		try {
			$upsertNewsletterResponse = $this->soapClient->upsertNewsletter( $upsertNewsletter );
			$newsletterInfo->newsletterId = $upsertNewsletterResponse->newsletterId;
		} catch (Exception $e) {
			throw new BizException( 'ERR_PUBLISH', 'Server', '', null, array('Tripolis' , $e->getMessage()) );
		}
		
		// assign articles to newsletter
		if ($newsletterInfo) {
			foreach ($articles as $article) {
				LogHandler::Log( 'Tripolis', 'DEBUG', 
					'Assign article ' . $article['TripolisArticle']->name . ' to newsletter' );
				$assignArticleToNewsletter = new assignArticleToNewsletter( );
				$assignArticleToNewsletter->articleName = $article['TripolisArticle']->name;
				$assignArticleToNewsletter->authInfo = $this->authInfo;
				$assignArticleToNewsletter->newsletterName = $newsletterInfo->name;
				$assignArticleToNewsletter->sectionName = strtolower( 
					ereg_replace( '[^[:alnum:]]', '', 
						$article['EntArticle']->Article->MetaData->BasicMetaData->Category->Name ) );
				$assignArticleToNewsletter->workspaceName = TRIPOLIS_WORKSPACE;
				try {
					$this->soapClient->assignArticleToNewsletter( $assignArticleToNewsletter );
				} catch (SoapFault $e) {
					if (is_object( $e->detail ) && isset( $e->detail->InvalidInputException ) && strpos( 
						$e->getMessage(), 'is already assigned' ) >= 0) {
						// article can already be assigned that's no problem, ignore error
						LogHandler::Log('Tripolis', 'DEBUG', 'Ignore assign article error '. $e->getMessage());
					} else {
						throw new BizException( 'ERR_PUBLISH', 'Server', '', null, 
							array('Tripolis' , $e->getMessage()) );
					}
				}
			}
		}
		
		return $newsletterInfo;
	}

	/**
	 * Inserts or updates articles in the Tripolis Dialogue system
	 *
	 * @param array $texts
	 * @param PublishTarget $publishtarget
	 * @param Object $dossier
	 * @return array
	 */
	private function upsertArticles ($texts, $publishtarget, $dossier)
	{
		$articles = array();
		foreach ($texts as $text) {
			$titleField = new KeyValuePair( );
			$titleField->key = 'title'; //TODO make variable?
			$titleField->value = $text->Article->MetaData->BasicMetaData->Name;
			$bodyField = new KeyValuePair( );
			$bodyField->key = 'body'; //TODO make variable?
			$bodyField->value = $text->Content;
			$bodyTextField = new KeyValuePair( );
			$bodyTextField->key = 'bodytext'; //TODO make variable?
			$bodyTextField->value = strip_tags($text->Content);
			
			$articleInfo = new ArticleInfo( );
			$articleInfo->articleFields = array($titleField , $bodyField, $bodyTextField);
			$articleInfo->articleId = $this->getExternalId( $dossier->MetaData->BasicMetaData->ID, 
				$text->Article->MetaData->BasicMetaData->ID, $publishtarget );
			$articleInfo->articleTypeDefinitionName = 'news_article'; //TODO make variable?
			$articleInfo->label = $this->getLabel( $dossier->MetaData->BasicMetaData->ID, $text->Article );
			$articleInfo->modifiedBy = '';
			$articleInfo->modifiedDate = '';
			$articleInfo->name = strtolower( ereg_replace( '[^[:alnum:]]', '', $articleInfo->label ) );
			
			$upsertArticle = new upsertArticle( );
			$upsertArticle->articleInfo = $articleInfo;
			$upsertArticle->authInfo = $this->authInfo;
			$upsertArticle->workspaceName = TRIPOLIS_WORKSPACE;
			
			try {
				$upsertArticleResponse = $this->soapClient->upsertArticle( $upsertArticle );
				$articleInfo->articleId = $upsertArticleResponse->articleId;
				//TODO either Tripolis needs to create a function to retreive article id by name
				// or WW has to save external id on preview
				// for now, save external id ourselfs
				// find object relation id
				$row = DBBase::getRow( 
					'objectrelations', 
					'`parent` = ' . intval( $dossier->MetaData->BasicMetaData->ID ) . ' AND `child` = ' . intval( 
						$text->Article->MetaData->BasicMetaData->ID ) . ' AND `type` =\'Contained\'' );
				if ($row) {
					DBTarget::updateRow( 'targets', array('externalid' => $upsertArticleResponse->articleId), 
						'`objectrelationid` = ' . intval( $row['id'] ) . ' AND `channelid` = ' . intval( 
							$publishtarget->PubChannelID ) . ' AND `issueid` = ' . intval( 
							$publishtarget->IssueID ) );
				}
				$articles[] = array('TripolisArticle' => $articleInfo , 'EntArticle' => $text);
			} catch (Exception $e) {
				throw new BizException( 'ERR_PUBLISH', 'Server', '', null, array('Tripolis' , $e->getMessage()) );
			}
		}
		
		return $articles;
	}

	/**
	 * Get info from the channel description and issue name
	 *
	 * @param PublishTarget $publishtarget
	 * @return array
	 */
	private function getTargetInfo ($publishtarget)
	{
		$channel = DBChannel::getChannel( $publishtarget->PubChannelID );
		$issue = DBIssue::getIssue( $publishtarget->IssueID );
		
		$result = array('ContactGroupName' => '' , 'EmailType' => '' , 'NewsletterType' => '', 'DirectSent' => false);
		$result['ContactGroupName'] = $issue['name'];
		if (strpos( $channel['description'], 'DIRECTSENT' ) !== FALSE) {
			$result['DirectSent'] = true;
		}
		if (strpos( $channel['description'], 'DIRECTEMAIL' ) !== FALSE) {
			$result['EmailType'] = 'DIRECTEMAIL';
		} else {
			$regs = array();
			if (ereg( 'NEWSLETTER=([a-z]+)', $channel['description'], $regs ) !== FALSE) {
				$result['EmailType'] = 'NEWSLETTER';
				$result['NewsletterType'] = $regs[1];
			} else {
				throw new BizException( 'ERR_PUBLISH', 'Server', '', null, 
					array('Tripolis' , 'Channel set-up error') );
			}
		}
		LogHandler::Log( 'Tripolis', 'DEBUG', 
			'EmailType = ' . $result['EmailType'] . '; NewsletterType = ' . $result['NewsletterType'] . '; DirectSent = ' .
				 ($result['DirectSent'] ? 'true' : 'false') );
		
		return $result;
	}

	/**
	 * Gets a newletter preview from the Tripolis Dialogue system.
	 *
	 * @param string $newsletterId
	 * @param string $newsletterName
	 * @return previewNewsletter
	 */
	private function getPreviewNewsletter ($newsletterId, $newsletterName)
	{
		$preview = null;
		$previewNewsletter = new previewNewsletter( );
		$previewNewsletter->authInfo = $this->authInfo;
		$previewNewsletter->contactId = ''; //TODO
		$previewNewsletter->newsletterId = $newsletterId;
		$previewNewsletter->newsletterName = $newsletterName;
		$previewNewsletter->workspaceName = TRIPOLIS_WORKSPACE;
		try {
			$previewNewsletterResponse = $this->soapClient->previewNewsletter( $previewNewsletter );
			$preview = $previewNewsletterResponse->newsletterPreview;
		} catch (Exception $e) {
			LogHandler::Log( 'Tripolis', 'DEBUG', 'previewNewsletter error: ' . $e->getMessage() );
			$previewNewsletter = null;
		}
		
		return $preview;
	}

	/**
	 * Send mailing to a contact group
	 *
	 * @param string $contactGroupName
	 * @param string $directEmailName
	 * @param string $newsletterName
	 * @return string job id
	 */
	private function scheduleMailing ($contactGroupName, $directEmailName, $newsletterName)
	{
		$result = '';
		
		$scheduleMailing = new scheduleMailing( );
		$scheduleMailing->authInfo = $this->authInfo;
		$scheduleMailing->contactGroupName = $contactGroupName;
		$scheduleMailing->databaseName = TRIPOLIS_DATABASE;
		$scheduleMailing->directEmailName = $directEmailName;
		$scheduleMailing->embargoDate = date( 'Y-m-d\TH:i:s', time() + 10 ); // add 10 seconds to prevent time in past error
		$scheduleMailing->mailsPerHour = 10000;
		$scheduleMailing->newsletterName = $newsletterName;
		$scheduleMailing->sampleRate = 0;
		$scheduleMailing->smartGroupName = '';
		$scheduleMailing->testContactGroupName = '';
		$scheduleMailing->testMailing = false;
		$scheduleMailing->workspaceName = TRIPOLIS_WORKSPACE;
		
		try {
			$scheduleMailingReponse = $this->soapClient->scheduleMailing( $scheduleMailing );
			$result = $scheduleMailingReponse->jobId;
		} catch (Exception $e) {
			throw new BizException( 'ERR_PUBLISH', 'Server', '', null, array('Tripolis' , $e->getMessage()) );
		}
		
		return $result;
	}

	/**
	 * Generated a Tripolis Dialogue label from an object.
	 *
	 * @param int $dossierId
	 * @param Object $object
	 * @return string
	 */
	private function getLabel ($dossierId, Object $object)
	{
		$params = array($dossierId, $object->MetaData->BasicMetaData->ID , $object->MetaData->WorkflowMetaData->Version , 
			substr( $object->MetaData->BasicMetaData->Name, 0, 20 ));
		$result = self::LABELFORMAT;
		for ($i = 0; $i < count( $params ); $i ++) {
			$result = str_replace( '%' . ($i + 1), $params[$i], $result );
		}
		// max is 40
		$result = substr( $result, 0, 40 );
		
		return $result;
	}

	/**
	 * Insert or updates an image in the Tripolis Dialogue system
	 *
	 * @param Object $object
	 * @param PublishTarget $publishtarget
	 * @param Object $dossier
	 * @return ImageInfo
	 */
	private function upsertImage (Object $object, $publishtarget, Object $dossier, $externalId)
	{
		$format = $object->MetaData->ContentMetaData->Format;
		$extension = MimeTypeHandler::mimeType2FileExt( $format );
		
		$attachment = new Tripolis_Attachment( );
		$attachment->data = file_get_contents($object->Files[0]->FilePath);
		$attachment->fileName = $object->MetaData->BasicMetaData->Name . $extension;
		
		$imageInfo = new ImageInfo( );
		$imageInfo->attachment = $attachment;
		$imageInfo->description = '';
		if (! is_null( $object->MetaData->ContentMetaData->Description )) {
			$imageInfo->description = $object->MetaData->ContentMetaData->Description;
		}
		$imageInfo->imageId = $externalId;
		$imageInfo->label = $this->getLabel( $dossier->MetaData->BasicMetaData->ID, $object );
		$imageInfo->name = strtolower( ereg_replace( '[^[:alnum:]]', '', $imageInfo->label ) );
		$imageInfo->tags = array(''); // tags must be filled else we get an null pointer exception from Tripolis
		

		$upsertImage = new upsertImage( );
		$upsertImage->authInfo = $this->authInfo;
		$upsertImage->image = $imageInfo;
		$upsertImage->workspaceName = TRIPOLIS_WORKSPACE;
		
		try {
			$upsertImageResponse = $this->soapClient->upsertImage( $upsertImage );
			$imageInfo->imageId = $upsertImageResponse->imageId;
			//TODO either Tripolis needs to create a function to retreive image id by name
			// or WW has to save external id on preview
			// for now, save external id ourselfs
			// find object relation id
			$row = DBBase::getRow( 'objectrelations', 
				'`parent` = ' . intval( $dossier->MetaData->BasicMetaData->ID ) . ' AND `child` = ' . intval( 
					$object->MetaData->BasicMetaData->ID ) . ' AND `type` =\'Contained\'' );
			if ($row) {
				DBTarget::updateRow( 'targets', array('externalid' => $imageInfo->imageId), 
					'`objectrelationid` = ' . intval( $row['id'] ) . ' AND `channelid` = ' . intval( 
						$publishtarget->PubChannelID ) . ' AND `issueid` = ' . intval( $publishtarget->IssueID ) );
			}
		} catch (Exception $e) {
			throw new BizException( 'ERR_PUBLISH', 'Server', '', null, array('Tripolis' , $e->getMessage()) );
		}
		return $imageInfo;
	}

	private function getExternalId ($dossierId, $childId, $publishTarget)
	{
		$result = '';
		$row = DBBase::getRow( 'objectrelations', 
			'`parent` = ' . intval( $dossierId ) . ' AND `child` = ' . intval( $childId ) . ' AND `type` = \'Contained\'' );
		if ($row) {
			$row = DBBase::getRow( 'targets', 
				'`objectrelationid` = ' . intval( $row['id'] ) . ' AND `channelid` = ' . intval( 
					$publishTarget->PubChannelID ) . ' AND `issueid` = ' . intval( $publishTarget->IssueID ) );
			$result = $row['externalid'];
		}
		
		return $result;
	}
}
