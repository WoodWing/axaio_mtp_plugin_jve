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
	/** @var string $ticket  */
	private $ticket = null;

	/** @var PublicationInfo $publication  */
	private $publication = null;

	/** @var Target $target  */
	private $target = null;

	/** @var PubChannelInfo $publicationChannel  */
	private $publicationChannel = null;

	/** @var CategoryInfo $category  */
	private $category = null;

	/** @var BizTransferServer $transferServer  */
	private $transferServer = null;

	/** @var WW_TestSuite_BuildTest_WebServices_WflServices_Utils $wflServicesUtils */
	private $wflServicesUtils = null;

	const TRANSFERIMAGE = '/testdata/WflWcml2Xhtml_Image.png';
	const TRANSFERARTICLE = '/testdata/WflWcml2Xhtml_Article.wcml';
	const ELEMENTID = '30DE1381-8A7A-41E6-8F6C-5D80D8D9BAF8';

	/** @var Object $dossier */
	private $dossier = null;

	/** @var Object $image */
	private $image = null;

	/** @var Object $article */
	private $article = null;

	/** @var State[] $statuses */
	private $statuses = array();

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
	 * Runs the test case.
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
			// Note:
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
	 * Determines and sets up all the data for our test case.
	 *
	 * @return bool Whether or not the setup was successful.
	 */
	private function setup()
	{
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/WebServices/WflServices/Utils.class.php';
		$this->wflServicesUtils = new WW_TestSuite_BuildTest_WebServices_WflServices_Utils();
		if( !$this->wflServicesUtils->initTest( $this ) ) {
			return false;
		}

		// Get all the session related variables.;
		$this->readSessionVariables();

		// Get the transferServer
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		// Create a dossier to test with.
		$this->createDossier();
		if (is_null($this->dossier)) {
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
	 */
	private function readSessionVariables()
	{
		$vars = $this->getSessionVariables();
		$this->ticket              = $vars['BuildTest_WebServices_WflServices']['ticket'];
		$this->publication         = $vars['BuildTest_WebServices_WflServices']['publication'];
		$this->publicationChannel  = $vars['BuildTest_WebServices_WflServices']['printPubChannel'];
		$this->target              = $vars['BuildTest_WebServices_WflServices']['printTarget'];
		$this->category            = $vars['BuildTest_WebServices_WflServices']['category'];
		$this->statuses['Image']   = $vars['BuildTest_WebServices_WflServices']['imageStatus'];
		$this->statuses['Article'] = $vars['BuildTest_WebServices_WflServices']['articleStatus'];
		$this->statuses['Dossier'] = $vars['BuildTest_WebServices_WflServices']['dossierStatus'];
	}

	/**
	 * Deconstructs the data assembled for the test case.
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
	 * Creates an image to be used as test data in an inline image resize action.
	 */
	private function createImage()
	{
		require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';

		// Get the current User.
		$user = BizSession::checkTicket( $this->ticket );

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

		$image = new Object();
		$image->MetaData = new MetaData();
		$image->MetaData->BasicMetaData = new BasicMetaData();
		$image->MetaData->BasicMetaData->ID = null;
		$image->MetaData->BasicMetaData->DocumentID = null;
		$image->MetaData->BasicMetaData->Name = 'Screen Shot 2012-12-13 at 12.48.12 PM';
		$image->MetaData->BasicMetaData->Type = 'Image';
		$image->MetaData->BasicMetaData->Publication = new Publication();
		$image->MetaData->BasicMetaData->Publication->Id = $this->publication->Id;
		$image->MetaData->BasicMetaData->Publication->Name = $this->publication->Name;
		$image->MetaData->BasicMetaData->Category = new Category();
		$image->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$image->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$image->MetaData->BasicMetaData->ContentSource = null;
		$image->MetaData->RightsMetaData = new RightsMetaData();
		$image->MetaData->RightsMetaData->CopyrightMarked = false;
		$image->MetaData->RightsMetaData->Copyright = null;
		$image->MetaData->RightsMetaData->CopyrightURL = null;
		$image->MetaData->SourceMetaData = new SourceMetaData();
		$image->MetaData->SourceMetaData->Credit = null;
		$image->MetaData->SourceMetaData->Source = null;
		$image->MetaData->SourceMetaData->Author = null;
		$image->MetaData->ContentMetaData = new ContentMetaData();
		$image->MetaData->ContentMetaData->Description = null;
		$image->MetaData->ContentMetaData->DescriptionAuthor = null;
		$image->MetaData->ContentMetaData->Keywords = array();
		$image->MetaData->ContentMetaData->Slugline = null;
		$image->MetaData->ContentMetaData->Format = 'image/png';
		$image->MetaData->ContentMetaData->Columns = null;
		$image->MetaData->ContentMetaData->Width = null;
		$image->MetaData->ContentMetaData->Height = null;
		$image->MetaData->ContentMetaData->Dpi = null;
		$image->MetaData->ContentMetaData->LengthWords = null;
		$image->MetaData->ContentMetaData->LengthChars = null;
		$image->MetaData->ContentMetaData->LengthParas = null;
		$image->MetaData->ContentMetaData->LengthLines = null;
		$image->MetaData->ContentMetaData->PlainContent = null;
		$image->MetaData->ContentMetaData->FileSize = 111109;
		$image->MetaData->ContentMetaData->ColorSpace = null;
		$image->MetaData->ContentMetaData->HighResFile = null;
		$image->MetaData->ContentMetaData->Encoding = null;
		$image->MetaData->ContentMetaData->Compression = null;
		$image->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$image->MetaData->ContentMetaData->Channels = null;
		$image->MetaData->ContentMetaData->AspectRatio = null;
		$image->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$image->MetaData->WorkflowMetaData->Deadline = null;
		$image->MetaData->WorkflowMetaData->Urgency = null;
		$image->MetaData->WorkflowMetaData->Modifier = null;
		$image->MetaData->WorkflowMetaData->Modified = null;
		$image->MetaData->WorkflowMetaData->Creator = null;
		$image->MetaData->WorkflowMetaData->Created = null;
		$image->MetaData->WorkflowMetaData->Comment = null;
		$image->MetaData->WorkflowMetaData->State = $this->statuses['Image'];
		$image->MetaData->WorkflowMetaData->RouteTo = null;
		$image->MetaData->WorkflowMetaData->LockedBy = null;
		$image->MetaData->WorkflowMetaData->Version = null;
		$image->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$image->MetaData->WorkflowMetaData->Rating = null;
		$image->MetaData->WorkflowMetaData->Deletor = null;
		$image->MetaData->WorkflowMetaData->Deleted = null;
		$image->MetaData->ExtraMetaData = array();
		$image->MetaData->ExtraMetaData[0] = new ExtraMetaData();
		$image->MetaData->ExtraMetaData[0]->Property = 'RelatedTargets';
		$image->MetaData->ExtraMetaData[0]->Values = array();
		$image->MetaData->ExtraMetaData[0]->Values[0] = '';
		$image->Relations = $relations;
		$image->Pages = null;
		$image->Files = array( $attachment );
		$image->Messages = null;
		$image->Elements = array();
		$image->Targets = array();
		$image->Renditions = null;
		$image->MessageList = null;

		$this->image = $this->createObject( $image );
	}

	/**
	 * Creates a test article with a resize'd inline image for testing purposes.
	 */
	private function createArticle()
	{
		// Get the current User.
		$user = BizSession::checkTicket( $this->ticket );

		// Update the link object id with the image object id.
		$find = '{objectid}';
		$replaceWith = $this->image->MetaData->BasicMetaData->ID;

		$inputPath = dirname( __FILE__ ).self::TRANSFERARTICLE;
		$content = file_get_contents( $inputPath );
		$content = str_replace( $find, $replaceWith, $content );

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

		$article = new Object();
		$article->MetaData = new MetaData();
		$article->MetaData->BasicMetaData = new BasicMetaData();
		$article->MetaData->BasicMetaData->ID = null;
		$article->MetaData->BasicMetaData->DocumentID = null;
		$article->MetaData->BasicMetaData->Name = 'buildtest_inline_img';
		$article->MetaData->BasicMetaData->Type = 'Article';
		$article->MetaData->BasicMetaData->Publication = new Publication();
		$article->MetaData->BasicMetaData->Publication->Id = $this->publication->Id;
		$article->MetaData->BasicMetaData->Publication->Name = $this->publication->Name;
		$article->MetaData->BasicMetaData->Category = new Category();
		$article->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$article->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$article->MetaData->BasicMetaData->ContentSource = null;
		$article->MetaData->RightsMetaData = new RightsMetaData();
		$article->MetaData->RightsMetaData->CopyrightMarked = null;
		$article->MetaData->RightsMetaData->Copyright = null;
		$article->MetaData->RightsMetaData->CopyrightURL = null;
		$article->MetaData->SourceMetaData = new SourceMetaData();
		$article->MetaData->SourceMetaData->Credit = null;
		$article->MetaData->SourceMetaData->Source = null;
		$article->MetaData->SourceMetaData->Author = null;
		$article->MetaData->ContentMetaData = new ContentMetaData();
		$article->MetaData->ContentMetaData->Description = null;
		$article->MetaData->ContentMetaData->DescriptionAuthor = null;
		$article->MetaData->ContentMetaData->Keywords = null;
		$article->MetaData->ContentMetaData->Slugline = 'A test article with

'.chr( 0xef ).chr( 0xbf ).chr( 0xbc ).'

An inline image';
		$article->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$article->MetaData->ContentMetaData->Columns = 1;
		$article->MetaData->ContentMetaData->Width = 440.787402;
		$article->MetaData->ContentMetaData->Height = 566.929134;
		$article->MetaData->ContentMetaData->Dpi = 0;
		$article->MetaData->ContentMetaData->LengthWords = 7;
		$article->MetaData->ContentMetaData->LengthChars = 35;
		$article->MetaData->ContentMetaData->LengthParas = 5;
		$article->MetaData->ContentMetaData->LengthLines = 5;
		$article->MetaData->ContentMetaData->PlainContent = 'A test article with

'.chr( 0xef ).chr( 0xbf ).chr( 0xbc ).'

An inline image';
		$article->MetaData->ContentMetaData->FileSize = 322249;
		$article->MetaData->ContentMetaData->ColorSpace = null;
		$article->MetaData->ContentMetaData->HighResFile = null;
		$article->MetaData->ContentMetaData->Encoding = null;
		$article->MetaData->ContentMetaData->Compression = null;
		$article->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$article->MetaData->ContentMetaData->Channels = null;
		$article->MetaData->ContentMetaData->AspectRatio = null;
		$article->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$article->MetaData->WorkflowMetaData->Deadline = null;
		$article->MetaData->WorkflowMetaData->Urgency = null;
		$article->MetaData->WorkflowMetaData->Modifier = null;
		$article->MetaData->WorkflowMetaData->Modified = '2013-03-19T12:09:54';
		$article->MetaData->WorkflowMetaData->Creator = null;
		$article->MetaData->WorkflowMetaData->Created = '2013-03-19T12:09:54';
		$article->MetaData->WorkflowMetaData->Comment = '';
		$article->MetaData->WorkflowMetaData->State = $this->statuses['Article'];
		$article->MetaData->WorkflowMetaData->RouteTo = null;
		$article->MetaData->WorkflowMetaData->LockedBy = null;
		$article->MetaData->WorkflowMetaData->Version = null;
		$article->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$article->MetaData->WorkflowMetaData->Rating = null;
		$article->MetaData->WorkflowMetaData->Deletor = null;
		$article->MetaData->WorkflowMetaData->Deleted = null;
		$article->MetaData->ExtraMetaData = null;
		$article->Relations = array();
		$article->Relations[0] = new Relation();
		$article->Relations[0]->Parent = $this->dossier->MetaData->BasicMetaData->ID;
		$article->Relations[0]->Child = '';
		$article->Relations[0]->Type = 'Contained';
		$article->Relations[0]->Placements = null;
		$article->Relations[0]->ParentVersion = null;
		$article->Relations[0]->ChildVersion = null;
		$article->Relations[0]->Geometry = null;
		$article->Relations[0]->Rating = null;
		$article->Relations[0]->Targets = null;
		$article->Relations[0]->ParentInfo = null;
		$article->Relations[0]->ChildInfo = null;
		$article->Pages = null;
		$article->Files = array( $attachment );
		$article->Messages = null;
		$article->Elements = array();
		$article->Elements[0] = new Element();
		$article->Elements[0]->ID = '30DE1381-8A7A-41E6-8F6C-5D80D8D9BAF8';
		$article->Elements[0]->Name = 'body';
		$article->Elements[0]->LengthWords = 7;
		$article->Elements[0]->LengthChars = 35;
		$article->Elements[0]->LengthParas = 5;
		$article->Elements[0]->LengthLines = 5;
		$article->Elements[0]->Snippet = 'A test article with

'.chr( 0xef ).chr( 0xbf ).chr( 0xbc ).'

An inline image';
		$article->Elements[0]->Version = 'D08BF04F-1986-4D07-94FC-AAA5EF1927A1';
		$article->Elements[0]->Content = null;
		$article->Targets = null;
		$article->Renditions = null;
		$article->MessageList = new MessageList();
		$article->MessageList->Messages = null;
		$article->MessageList->ReadMessageIDs = null;
		$article->MessageList->DeleteMessageIDs = null;

		$this->article = $this->createObject( $article );
	}

	/**
	 * Creates an Object in DB.
	 *
	 * @param Object $object
	 * @return null|Object The created Object or null.
	 */
	private function createObject( $object )
	{
		$response = null;
		try {
			require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
			$request = new WflCreateObjectsRequest();
			$request->Ticket = $this->ticket;
			$request->Lock = false;
			$request->Objects = array( $object );
			$service = new WflCreateObjectsService();
			$response = $service->execute( $request );
		} catch( BizException $e ) {
			self::setResult( 'ERROR', 'Creating object failed.'.$e->getMessage() );
		}
		return $response ? $response->Objects[0] : null;
	}

	/**
	 * Creates a Dossier for testing.
	 */
	private function createDossier()
	{
		$user = BizSession::checkTicket( $this->ticket );

		// Compose new Dossier in memory.
		$dossier = new Object();
		$dossier->MetaData = new MetaData();
		$dossier->MetaData->BasicMetaData = new BasicMetaData();
		$dossier->MetaData->BasicMetaData->Name = 'WflArticleInlineImageTest';
		$dossier->MetaData->BasicMetaData->Type = 'Dossier';
		$dossier->MetaData->BasicMetaData->Publication = new Publication();
		$dossier->MetaData->BasicMetaData->Publication->Id = $this->publication->Id;
		$dossier->MetaData->BasicMetaData->Publication->Name = $this->publication->Name;
		$dossier->MetaData->BasicMetaData->Category = new Category();
		$dossier->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$dossier->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$dossier->MetaData->RightsMetaData = new RightsMetaData();
		$dossier->MetaData->SourceMetaData = new SourceMetaData();
		$dossier->MetaData->ContentMetaData = new ContentMetaData();
		$dossier->MetaData->ContentMetaData->Description = 'Temporary dossier for testing inline image conversion.';
		$dossier->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$dossier->MetaData->WorkflowMetaData->State = $this->statuses['Dossier'];
		$dossier->MetaData->ExtraMetaData = array();
		$dossier->Targets = array( $this->target );

		// Create the Dossier in DB.
		$this->dossier = self::createObject( $dossier );
	}

	/**
	 * Deletes a given Object permanently from DB.
	 *
	 * @param object $object The object to be deleted.
	 * @return bool Whether or not the object was deleted.
	 */
	private function deleteObject( $object )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		try {
			$request = new WflDeleteObjectsRequest();
			$request->Ticket = $this->ticket;
			$request->IDs = array( $object->MetaData->BasicMetaData->ID );
			$request->Permanent = true;
			$request->Areas = array( 'Workflow' );
			$service = new WflDeleteObjectsService();
			$service->execute( $request );
		} catch( BizException $e ) {
			$message = $e->getDetail();
			self::setResult( 'ERROR', 'Failed to delete Object: '.$message );
			return false;
		}
		return true;
	}
}