<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflWcml2Xhtml_TestCase extends TestCase
{
	private $vars = null;
	private $ticket = null;
	private $publication = null;
	private $issue = null;
	private $target = null;
	private $editions = null;
	private $publicationChannel = null;
	private $transferServer = null;

	const TRANSFERIMAGE = '/testdata/WflWcml2Xhtml_Image.png';
	const TRANSFERARTICLE = '/testdata/WflWcml2Xhtml_Article.wcml';
	const ELEMENTID = '30DE1381-8A7A-41E6-8F6C-5D80D8D9BAF8';

	private $dossier = null;
	private $image = null;
	private $article = null;

	public function getDisplayName() { return 'WCML To XHTML conversion.'; }
	public function getTestGoals()   { return 'Checks if the Article can be converted to XHTML and that the image '
		.'dimensions of an inline image are correct.'; }
	public function getTestMethods() { return 'Testing the conversion of a WCML article with inline image to XHTML.'
		. '<ol><li>A Dossier is created for the test using createObject.</li>'
		. '<li>An Image is created for the test using createObject to be used as a resized inline image.</li>'
		. '<li>An Article is created for the test using createObject to be used for conversion to XHTML.</li>'
		. '<li>The Article is converted and the results are verified against a predetermined result.</li></ol>';
	}
	public function getPrio()        { return 113; }

	/**
	 * Runs the testcase.
	 *
	 * @return bool
	 */
	final public function runTest()
	{
		// Manage and set up peripherals.
		$this->setup();

		require_once BASEDIR . '/server/bizclasses/BizPublishForm.class.php';
		try {
			$elements = BizPublishForm::extractArticleObjectElements( $this->article, $this->publicationChannel->Id );
		} catch (BizException $e) {
			$this->setResult( 'ERROR',  'Extraction failed: ' . $e->getMessage(), 'Test failed.' );
			$this->teardown();
			return false;
		}

		if ($elements) foreach ($elements as $elementArray) {
			$el = $elementArray['elements'][0];
			if ($el->ID == self::ELEMENTID) {
				// compare the content
				$orgContent = '<div class="story story_body" id="30DE1381-8A7A-41E6-8F6C-5D80D8D9BAF8"><p class="para '
					.'para_$ID/NormalParagraphStyle"><span class="char char_$ID/[No_character_style]">A test article '
					.'with</span></p><p class="para para_$ID/NormalParagraphStyle"><span class="char char_$ID/[No_char'
					.'acter_style]"/></p><p class="para para_$ID/NormalParagraphStyle"><span class="char char_$ID/[No_'
					.'character_style]"><img id="ent_' . $this->image->MetaData->BasicMetaData->ID . '" '
					.'src="ww_enterprise" width="319" height="291"/></span></p><p class="para para_$ID/NormalParagraph'
					.'Style"><span class="char char_$ID/[No_character_style]"/></p><p class="para para_$ID/Normal'.
					'ParagraphStyle"><span class="char char_$ID/[No_character_style]">An inline image</span></p></div>';
				if ($el->Content != $orgContent) {
					$this->setResult( 'ERROR',  'Extracted content does not match the expected content.', 'Test failed.');
					$this->teardown();
					return false;
				}
			}
		}

		$this->teardown();
		return true;
	}

	/**
	 * Determines and sets up all the data for our testcase.
	 *
	 * @return bool Whether or not the setup was succesful.
	 */
	private function setup()
	{
		// Get all the session related variables.;
		$this->readSessionVariables();

		// Determine target and editions.
		$this->determineTargetAndEditions();

		// Get the transferServer
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		// Create a dossier to test with.
		$this->dossier = $this->createDossier();
		if (is_null($this->dossier))
		{
			$this->setResult( 'ERROR',  'Dossier could not be created.', 'Test failed.' );
			$this->teardown();
			return false;
		}

		// Create an image to test with.
		$this->createImage();
		if (is_null($this->image)) {
			$this->setResult( 'ERROR',  'Image could not be created.', 'Test failed.' );
			$this->teardown();
			return false;
		}

		// Create the article.
		$this->createArticle();
		if (is_null($this->article)) {
			$this->setResult( 'ERROR',  'Article could not be created.', 'Test failed.' );
			$this->teardown();
			return false;
		}
		return true;
	}

	/**
	 * Reads out the session variables and sets them for the test.
	 *
	 * @return bool Whether or not reading out the variables was succesful.
	 */
	private function readSessionVariables()
	{
		// Get Session variables.
		$this->vars = $this->getSessionVariables();

		// Get the ticket.
		$this->ticket = @$this->vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return false;
		}

		// Get the publication.
		$this->publication = @$this->vars['BuildTest_WebServices_WflServices']['publication'];
		if( !$this->publication ) {
			$this->setResult( 'ERROR',  'Could not find publication to test with.', 'Please enable the WflLogon test.' );
			return false;
		}

		// Get the issue.
		$this->issue = @$this->vars['BuildTest_WebServices_WflServices']['issue'];
		if( !$this->publication ) {
			$this->setResult( 'ERROR',  'Could not find an issue to test with.', 'Please enable the WflLogon test.' );
			return false;
		}
		return true;
	}

	/**
	 * Determines the Target and Editions for this testcase.
	 */
	private function determineTargetAndEditions()
	{
		$pubChannel = new PubChannel();
		$issue = new Issue();
		$editions = null;

		foreach( $this->publication->PubChannels as $pubChannelInfo ) {
			if( $pubChannelInfo->Name == 'Print' ) {

				$pubChannel->Id = $pubChannelInfo->Id;
				$pubChannel->Name = $pubChannelInfo->Name;

				foreach( $pubChannelInfo->Issues as $issInfo ) {
					if( $issInfo->Name == $this->issue )	{
						$issue->Id   = $issInfo->Id;
						$issue->Name = $issInfo->Name;
						$issue->OverrulePublication = $issInfo->OverrulePublication;
						break;
					}
				}

				$editions = $pubChannelInfo->Editions;
				break;
			}
		}

		// Set the issue.
		$this->issue = $issue;

		// Set the editions.
		$this->editions = $editions;

		// Generate a target.
		$target = new Target();
		$target->PubChannel = $pubChannel;
		$target->Issue      = $issue;
		$target->Editions   = $editions;
		$this->target = $target;
		$this->publicationChannel = $target->PubChannel;
	}

	/**
	 * Deconstructs the data assembled for the testcase.
	 */
	private function teardown()
	{
		// Delete the article.
		if (!is_null($this->article)) {
			$this->deleteObject($this->article);
		}

		// Delete the image.
		if (!is_null($this->image)) {
			$this->deleteObject($this->image);
		}

		// Delete the dossier.
		if (!is_null($this->dossier)) {
			$this->deleteObject($this->dossier);
		}
	}

	/**
	 * Creates an image to be used as testdata in an inline image resize action,.
	 */
	private function createImage()
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
		require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';

		// Get the current User.
		$user = BizSession::checkTicket( $this->ticket );

		// Determine the Publication.
		$publication = $this->getPublication();

		// Determine the State.
		$state = BizObjectComposer::getFirstState($user, $this->publication->Id, null, null, 'Image');

		// Determine the Category.
		$category = BizObjectComposer::getFirstCategory($user, $publication->Id);

		// Determine parent relation (with the Dossier.)
		$relations = array();
		$relations[] = new Relation();
		$relations[0]->Parent = $this->dossier->MetaData->BasicMetaData->ID;
		$relations[0]->Child = null;
		$relations[0]->Type = 'Contained';
		$relations[0]->Placements = array();
		$relations[0]->ParentVersion = null;
		$relations[0]->ChildVersion = null;
		$relations[0]->Geometry = null;
		$relations[0]->Rating = null;
		$relations[0]->Targets = array();
		$relations[0]->ParentInfo = null;
		$relations[0]->ChildInfo = null;

		// Determine an attachment.
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'image/png';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = null;

		// Copy the test file to the transfer server.
		$inputPath = dirname(__FILE__). self::TRANSFERIMAGE;
		$this->transferServer->copyToFileTransferServer( $inputPath, $attachment );

		$objects = array();
		$objects[0] = new Object();
		$objects[0]->MetaData = new MetaData();
		$objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$objects[0]->MetaData->BasicMetaData->ID = null;
		$objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$objects[0]->MetaData->BasicMetaData->Name = 'Screen Shot 2012-12-13 at 12.48.12 PM';
		$objects[0]->MetaData->BasicMetaData->Type = 'Image';
		$objects[0]->MetaData->BasicMetaData->Publication = $publication;
		$objects[0]->MetaData->BasicMetaData->Category = $category;
		$objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$objects[0]->MetaData->RightsMetaData->Copyright = null;
		$objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$objects[0]->MetaData->SourceMetaData->Credit = null;
		$objects[0]->MetaData->SourceMetaData->Source = null;
		$objects[0]->MetaData->SourceMetaData->Author = null;
		$objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$objects[0]->MetaData->ContentMetaData->Description = null;
		$objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$objects[0]->MetaData->ContentMetaData->Keywords = array();
		$objects[0]->MetaData->ContentMetaData->Slugline = null;
		$objects[0]->MetaData->ContentMetaData->Format = 'image/png';
		$objects[0]->MetaData->ContentMetaData->Columns = null;
		$objects[0]->MetaData->ContentMetaData->Width = null;
		$objects[0]->MetaData->ContentMetaData->Height = null;
		$objects[0]->MetaData->ContentMetaData->Dpi = null;
		$objects[0]->MetaData->ContentMetaData->LengthWords = null;
		$objects[0]->MetaData->ContentMetaData->LengthChars = null;
		$objects[0]->MetaData->ContentMetaData->LengthParas = null;
		$objects[0]->MetaData->ContentMetaData->LengthLines = null;
		$objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$objects[0]->MetaData->ContentMetaData->FileSize = 111109;
		$objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$objects[0]->MetaData->ContentMetaData->Encoding = null;
		$objects[0]->MetaData->ContentMetaData->Compression = null;
		$objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$objects[0]->MetaData->ContentMetaData->Channels = null;
		$objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$objects[0]->MetaData->WorkflowMetaData->Created = null;
		$objects[0]->MetaData->WorkflowMetaData->Comment = null;
		$objects[0]->MetaData->WorkflowMetaData->State = $state;
		$objects[0]->MetaData->WorkflowMetaData->RouteTo = null;
		$objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$objects[0]->MetaData->WorkflowMetaData->Version = null;
		$objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$objects[0]->MetaData->ExtraMetaData = array();
		$objects[0]->MetaData->ExtraMetaData[0] = new ExtraMetaData();
		$objects[0]->MetaData->ExtraMetaData[0]->Property = 'RelatedTargets';
		$objects[0]->MetaData->ExtraMetaData[0]->Values = array();
		$objects[0]->MetaData->ExtraMetaData[0]->Values[0] = '';
		$objects[0]->Relations = $relations;
		$objects[0]->Pages = null;
		$objects[0]->Files = array( $attachment );
		$objects[0]->Messages = null;
		$objects[0]->Elements = array();
		$objects[0]->Targets = array();
		$objects[0]->Renditions = null;
		$objects[0]->MessageList = null;

		$this->image = $this->createObject($objects);
	}

	/**
	 * Creates a test article with a resized inline image for testing purposes.
	 */
	private function createArticle()
	{
		// Get the current User.
		$user = BizSession::checkTicket( $this->ticket );

		// Determine the Publication.
		$publication = $this->getPublication();

		// Determine the State.
		$state = BizObjectComposer::getFirstState($user, $this->publication->Id, null, null, 'Image');

		// Determine the Category.
		$category = BizObjectComposer::getFirstCategory($user, $publication->Id);

		// Update the link object id with the image object id.


		$find = '{objectid}';
		$replaceWith = $this->image->MetaData->BasicMetaData->ID;

		$inputPath = dirname(__FILE__). self::TRANSFERARTICLE;
		$content = file_get_contents($inputPath);
		$content = str_replace($find, $replaceWith, $content);

		// Set the file(s).
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'application/incopyicml';
		$attachment->Content = $content;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = '';

		// Place the image on the file transfer server.
		$this->transferServer->writeContentToFileTransferServer( $content, $attachment );

		$objects = array();
		$objects[0] = new Object();
		$objects[0]->MetaData = new MetaData();
		$objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$objects[0]->MetaData->BasicMetaData->ID = null;
		$objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$objects[0]->MetaData->BasicMetaData->Name = 'buildtest_inline_img';
		$objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$objects[0]->MetaData->BasicMetaData->Publication = $publication;
		$objects[0]->MetaData->BasicMetaData->Category = $category;
		$objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$objects[0]->MetaData->RightsMetaData->CopyrightMarked = null;
		$objects[0]->MetaData->RightsMetaData->Copyright = null;
		$objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$objects[0]->MetaData->SourceMetaData->Credit = null;
		$objects[0]->MetaData->SourceMetaData->Source = null;
		$objects[0]->MetaData->SourceMetaData->Author = null;
		$objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$objects[0]->MetaData->ContentMetaData->Description = null;
		$objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$objects[0]->MetaData->ContentMetaData->Keywords = null;
		$objects[0]->MetaData->ContentMetaData->Slugline = 'A test article with

'.chr(0xef).chr(0xbf).chr(0xbc).'

An inline image';
		$objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$objects[0]->MetaData->ContentMetaData->Columns = 1;
		$objects[0]->MetaData->ContentMetaData->Width = 440.787402;
		$objects[0]->MetaData->ContentMetaData->Height = 566.929134;
		$objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$objects[0]->MetaData->ContentMetaData->LengthWords = 7;
		$objects[0]->MetaData->ContentMetaData->LengthChars = 35;
		$objects[0]->MetaData->ContentMetaData->LengthParas = 5;
		$objects[0]->MetaData->ContentMetaData->LengthLines = 5;
		$objects[0]->MetaData->ContentMetaData->PlainContent = 'A test article with

'.chr(0xef).chr(0xbf).chr(0xbc).'

An inline image';
		$objects[0]->MetaData->ContentMetaData->FileSize = 322249;
		$objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$objects[0]->MetaData->ContentMetaData->Encoding = null;
		$objects[0]->MetaData->ContentMetaData->Compression = null;
		$objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$objects[0]->MetaData->ContentMetaData->Channels = null;
		$objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$objects[0]->MetaData->WorkflowMetaData->Modified = '2013-03-19T12:09:54';
		$objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$objects[0]->MetaData->WorkflowMetaData->Created = '2013-03-19T12:09:54';
		$objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$objects[0]->MetaData->WorkflowMetaData->State = $state;
		$objects[0]->MetaData->WorkflowMetaData->RouteTo = null;
		$objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$objects[0]->MetaData->WorkflowMetaData->Version = null;
		$objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$objects[0]->MetaData->ExtraMetaData = null;
		$objects[0]->Relations = array();
		$objects[0]->Relations[0] = new Relation();
		$objects[0]->Relations[0]->Parent = $this->dossier->MetaData->BasicMetaData->ID;
		$objects[0]->Relations[0]->Child = '';
		$objects[0]->Relations[0]->Type = 'Contained';
		$objects[0]->Relations[0]->Placements = null;
		$objects[0]->Relations[0]->ParentVersion = null;
		$objects[0]->Relations[0]->ChildVersion = null;
		$objects[0]->Relations[0]->Geometry = null;
		$objects[0]->Relations[0]->Rating = null;
		$objects[0]->Relations[0]->Targets = null;
		$objects[0]->Relations[0]->ParentInfo = null;
		$objects[0]->Relations[0]->ChildInfo = null;
		$objects[0]->Pages = null;
		$objects[0]->Files = array( $attachment );
		$objects[0]->Messages = null;
		$objects[0]->Elements = array();
		$objects[0]->Elements[0] = new Element();
		$objects[0]->Elements[0]->ID = '30DE1381-8A7A-41E6-8F6C-5D80D8D9BAF8';
		$objects[0]->Elements[0]->Name = 'body';
		$objects[0]->Elements[0]->LengthWords = 7;
		$objects[0]->Elements[0]->LengthChars = 35;
		$objects[0]->Elements[0]->LengthParas = 5;
		$objects[0]->Elements[0]->LengthLines = 5;
		$objects[0]->Elements[0]->Snippet = 'A test article with

'.chr(0xef).chr(0xbf).chr(0xbc).'

An inline image';
		$objects[0]->Elements[0]->Version = 'D08BF04F-1986-4D07-94FC-AAA5EF1927A1';
		$objects[0]->Elements[0]->Content = null;
		$objects[0]->Targets = null;
		$objects[0]->Renditions = null;
		$objects[0]->MessageList = new MessageList();
		$objects[0]->MessageList->Messages = null;
		$objects[0]->MessageList->ReadMessageIDs = null;
		$objects[0]->MessageList->DeleteMessageIDs = null;

		$this->article = $this->createObject($objects);
	}

	/**
	 * Determines the Publication to use for this testcase.
	 *
	 * @return Publication The Publication to use for this testcase.
	 */
	private function getPublication()
	{
		$publication = new Publication();
		$publication->Id = $this->publication->Id;
		$publication->Name = $this->publication->Name;
		return $publication;
	}

	/**
	 * Creates an Object.
	 *
	 * @param array $objects The objects to be created.
	 * @return null|Object The created Object or null.
	 */
	private function createObject($objects)
	{
		$object = null;

		// Test creating an object.
		try {
			require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
			$service = new WflCreateObjectsService();
			$req = new WflCreateObjectsRequest();
			$req->Ticket = $this->ticket;
			$req->Lock = false;
			$req->Objects = $objects;
			$response = $service->execute( $req );
			$object = $response->Objects[0];
		} catch( BizException $e ) {
			self::setResult( 'ERROR', 'Creating object failed.' . $e->getMessage());
		}
		return $object;
	}


	/**
	 * Creates a Dossier.
	 *
	 * Creates a dossier for testing.
	 *
	 * @return null|Object The created Dossier or null on a failure.
	 */
	private function createDossier()
	{
		$publication = $this->publication;

		// Retrieve the State.
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->ticket );

		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
		$state = BizObjectComposer::getFirstState($user, $publication->Id, null, null, 'Dossier');
		$categoryId = BizObjectComposer::getFirstCategory($user, $publication->Id);

		// The WSDL expects a Publication object, a PublicationInfo object is given, so transform
		$objectPublication = $this->getPublication();

		// MetaData.
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData(null,null,'WflArticleInlineImageTest', 'Dossier'
			, $objectPublication, $categoryId, null);
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData('Temporary dossier for testing inline image conversion.');
		$metaData->WorkflowMetaData = new WorkflowMetaData(null,null,null,null,null,null,null,$state);
		$metaData->ExtraMetaData = array();

		// Set the dossier MetaData and Target(s).
		$dosObject = new Object();
		$dosObject->MetaData = $metaData;
		$dosObject->Targets = array($this->target);

		// Create and return the dossier.
		$object = self::createObject(array($dosObject));
		return (is_null($object)) ? null : $object;
	}

	/**
	 * Deletes an object
	 *
	 * Deletes objects permanently.
	 *
	 * @param object $object The object to be deleted.
	 * @param bool Whether or not the object was deleted.
	 */
	private function deleteObject( $object )
	{
		try {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsResponse.class.php';
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';

			$service = new WflDeleteObjectsService();
			$request = new WflDeleteObjectsRequest();

			$request->Ticket = $this->ticket;
			$request->IDs = array($object->MetaData->BasicMetaData->ID);
			$request->Permanent = true;
			$request->Areas = array('Workflow');
			$service->execute($request);
		} catch( BizException $e ) {
			$message = $e->getDetail();
			self::setResult('ERROR', 'Failed to delete Object: ' . $message );
			return false;
		}
		return true;
	}
}