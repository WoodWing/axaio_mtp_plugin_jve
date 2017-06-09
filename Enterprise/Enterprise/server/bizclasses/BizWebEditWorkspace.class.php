<?php

/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since       v7.0.15
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Manages a so called 'workspace' folder for each article being edit.
 * This is started for the CS editor that supports WCML articles.
 *
 * @since 9.4 support for preview of multiple articles in edit mode;
 * When two articles are placed on same layout and opened for editing by same user,
 * a preview can be generated, which shows the recent changes of both articles.
 * The first article is opened with {@link:createArticleWorkspace()} while succeeding
 * articles are opened with {@link:saveArticleInWorkspace()}. When there are two articles
 * opened this way, two records exist in the smart_appsessions table, both having the same
 * session id. All fields are the same, except the article's id, name, format and version.
 * To keep those records 'integer' with each other, always -all- records are read from DB
 * and/or saved back again, no matter how many articles are involved in context of the services.
 *
 * Note: The former format is INCX that can be edit by the Web Editor.
 * That is all implemented by the WebEditWorkflow and BizAppSession classes.
 */

require_once BASEDIR.'/server/dbclasses/DBAppSession.class.php';
class BizWebEditWorkspace
{
	private $workspace = null;
	const SEPARATOR = '/';

	// ------------------------------------------------------------------
	// SERVICES
	// ------------------------------------------------------------------

	/**
	 * To create article workspace in the server.
	 * It first creates and stores the application session into DB.
	 * Followed by creating a directory, the directory name is based on unique GUID generated.
	 * Then it stores the $content into a file and the file is placed into the directory created.
	 * After the above, it returns the workspaceId which is uniquely generated (GUID).
	 *
	 * @param string $id Article Id. Null for new article
	 * @param string $format For file extension. supported one is 'application/incopyicml'
	 * @param string|null $content For new articles, pass content from template. For existing articles, pass null to auto resolve from filestore.
	 * @return int $workspaceId Unique id(GUID) of the workspace
	 */
	public function createArticleWorkspace( $id, $format, $content )
	{
		//$this->validateId( $id, 'Article' ); // article may be null when not created at DB yet (only checked for preview)
		$this->validateArticleFormat( $format );

		$article = $this->createWorkspaceAtDb( $id, $format, $content ); // Note that if no content given, it gets resolved.
		$this->storeArticleAtFileSystem( false, $article, null, $content ); // false = create
		// storeArticleAtFileSystem resolves $this->workspace->DOMVersion so update workspace record at DB
		$this->saveWorkspaceAtDb();
		$this->validateWorkspaceAtDb();
		return $this->workspace->ID;
	}

	/**
	 * Deletes an article at the workspace. Before the workspace can be deleted a semaphore must be set. If this fails
	 * the workspace is still in use by another process (e.g. InDesign Server is creating a preview). After a short wait
	 * a new attempt will be done. The maximum number of attempts is 480 and the wait time after each attempt is 250 ms.
	 *
	 * @param string $workspaceId
	 */
	public function deleteArticleWorkspace( $workspaceId )
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = 'Wew_'.$workspaceId;
		$bizSemaphore->setAttempts( array_fill( 0, 480, 250 ) );
		$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName );

		if( $semaphoreId ) {
			$this->validateWorkspaceId( $workspaceId );
			$this->openWorkspaceAtDb( $workspaceId );
			$this->deleteArticlesAtFileSystem();
			$this->closeWorkspaceAtDb( $workspaceId );
			BizSemaphore::releaseSemaphore( $semaphoreId );
		}
	}

	/**
	 * Save article content into workspace
	 *
	 * @param string $workspaceId
	 * @param integer $id
	 * @param string $format
	 * @param array $elements array of Element object
	 * @param string $content Story contents
	 * @throws BizException
	 */
	public function saveArticleInWorkspace( $workspaceId, $id, $format, $elements, $content )
	{
		$workspaceArticles = $this->getArticlesFromWorkspace( $workspaceId );

		foreach($workspaceArticles as $workspaceArticle){
			if( $workspaceArticle->ID > 0 && !$id ){
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Not allowed to have multiple articles in one workspace when any of them is not stored in the database yet.' );
			}
		}

		$this->validateWorkspaceId( $workspaceId );
		$this->validateArticleFormat( $format );
		$this->validateElementsContent( $elements, $content );

		$this->openWorkspaceAtDb( $workspaceId ); // get workspace data from db

		// Article may be null when not created at DB yet. However, it is not supported
		// having more than one NEW articles in workspace.
		if( count($this->workspace->Articles) ) {
			$this->validateId( $id, 'Article' );
		}

		$fsContent = ''; $updatedVersion = false;
		$article = $this->openArticleAtWorkspace( $id, $format, $fsContent, $updatedVersion );
		$wsContent = $content ? $content : $fsContent; // content to store in workspace
		$this->storeArticleAtFileSystem( true, $article, $elements, $wsContent ); // true = save
		$this->validateWorkspaceAtDb();
		$this->saveWorkspaceAtDb(); // store workspace data at db
	}

	/**
	 * Retrieve an article from the workspace.
	 *
	 * @param string $workspaceId
	 * @param integer|null $articleId Article ID. NULL to get the first article.
	 * @return array List containing 'ID', 'Format' and 'Content'
	 */
	public function getArticleFromWorkspace( $workspaceId, $articleId )
	{
		$this->validateWorkspaceId( $workspaceId );

		$this->openWorkspaceAtDb( $workspaceId ); // get workspace data from db
		$this->validateWorkspaceAtDb();

		$ret = array();
		foreach( $this->workspace->Articles as $article ) {
			if( is_null($articleId) || $article->ID == $articleId ) {
				$ret['ID'] = $article->ID;
				$ret['Format'] = $article->Format;
				$ret['Content'] = $this->getArticleFromFileSystem( $article );
				break;
			}
		}
		return $ret;
	}

	/**
	 * Retrieve an article from the workspace.
	 *
	 * @param string $workspaceId
	 * @return array List of Articles
	 */
	public function getArticlesFromWorkspace( $workspaceId )
	{
		$this->validateWorkspaceId( $workspaceId );

		$this->openWorkspaceAtDb( $workspaceId ); // get workspace data from db
		$this->validateWorkspaceAtDb();

		return $this->workspace->Articles;
	}

	/**
	 * Retrieve the article's name that is being at the workspace.
	 *
	 * @param string $workspaceId
	 * @return string
	 */
	public function getArticleNameFromWorkspace( $workspaceId )
	{
		$this->validateWorkspaceId( $workspaceId );
		$this->openWorkspaceAtDb( $workspaceId ); // get workspace data from db
		// Due to multiple articles implementation in workspace, article object's name should retrieve from the first article object in the list.
		return (isset($this->workspace->Articles) && count($this->workspace->Articles) > 0 ) ? $this->workspace->Articles[0]->Name : '';
	}

	/**
	 * Lists all workspaces that are pending for the current user.
	 *
	 * @return array of workspace ids
	 */
	public function listArticleWorkspaces()
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		$userId = BizSession::getShortUserName();
		$appName = DBTicket::DBappticket( BizSession::getTicket() );

		return $this->getWorkspaceIdsAtDb( $userId, $appName );
	}

	/**
	 * Preview/Compose/PDF article content at workspace
	 *
	 * @deprecated since 9.4
	 * @param string $workspaceId
	 * @param string $id Object ID. NULL when article not created yet.
	 * @param string $format
	 * @param array $elements array of text components (Element objects) to update article at workspace.
	 * @param string $content Article contents containing all text components.
	 * @param string $action 'Compose','Preview' or 'PDF'
	 * @param integer $layoutId
	 * @param integer $editionId
	 * @param string|null $previewType Preview for a 'page' or 'spread'. Pass null when $action is not 'Preview'.
	 * @param string[] $requestInfo [9.7] Pass in 'InDesignArticles' to resolve InDesignArticles and populate Placements with their frames.
	 * @return array 'Elements', 'Placements' and 'Pages'
	 */
	public function previewArticleAtWorkspace( $workspaceId, $id, $format, $elements, $content,
									$action, $layoutId, $editionId, $previewType,
									array $requestInfo = array() )
	{
		$article = new ArticleAtWorkspace();
		$article->ID       = $id;
		$article->Format   = $format;
		$article->Elements = $elements;
		$article->Content  = $content;

		return $this->previewArticlesAtWorkspace( $workspaceId, array($article),
									$action, $layoutId, $editionId, $previewType,
									$requestInfo );
	}

	/**
	 * Wrapper for Preview/Compose/PDF article content at workspace. The process is safeguared by setting a semaphore.
	 * The lifetime of the semaphore is 300 seconds.
	 *
	 * @since 10.0
	 * @param string $workspaceId
	 * @param ArticleAtWorkspace[] $articles List of articles to preview. Properties per article:
	 * - ID: NULL when article not created yet.
	 * - Format
	 * - Elements: Array of text components (Element objects) to update article at workspace.
	 * - Content: Article contents containing all text components.
	 * @param string $action 'Compose','Preview' or 'PDF'
	 * @param integer $layoutId
	 * @param integer $editionId
	 * @param string|null $previewType Preview for a 'page' or 'spread'. Pass null when $action is not 'Preview'.
	 * @param array() $requestInfo [9.7] Pass in 'InDesignArticles' to resolve InDesignArticles and populate Placements with their frames.
	 * @throws BizException
	 * @throws $e
	 * @return array 'Elements', 'Placements' and 'Pages'
	 */
	public function previewArticlesAtWorkspace( $workspaceId, array $articles,
									$action, $layoutId, $editionId, $previewType,
									array $requestInfo = array() )
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = 'Wew_'.$workspaceId;
		$bizSemaphore->setLifeTime( 300 );
		$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName );

		if( !$semaphoreId ) {
			$details = 'Failed to create preview, because there is another preview generation still in progress.';
			throw new BizException( 'ERR_ERROR', 'Server', $details );
		}

		$e = null;
		$retVal = array();
		try {
			$retVal = self::doPreviewArticlesAtWorkspace( $workspaceId, $articles, $action, $layoutId, $editionId, $previewType, $requestInfo );
		} catch( BizException $e ) {
			// Do nothing here
		}

		if( $semaphoreId ) {
			BizSemaphore::releaseSemaphore( $semaphoreId );
		}
		if( $e ) {
			throw $e;
		}

		return $retVal;
	}

	/**
	 * Preview/Compose/PDF article content at workspace
	 *
	 * @since 9.4
	 * @param string $workspaceId
	 * @param ArticleAtWorkspace[] $articles List of articles to preview. Properties per article:
	 * - ID: NULL when article not created yet.
	 * - Format
	 * - Elements: Array of text components (Element objects) to update article at workspace.
	 * - Content: Article contents containing all text components.
	 * @param string $action 'Compose','Preview' or 'PDF'
	 * @param integer $layoutId
	 * @param integer $editionId
	 * @param string|null $previewType Preview for a 'page' or 'spread'. Pass null when $action is not 'Preview'.
	 * @param string[] $requestInfo [9.7] Pass in 'InDesignArticles' to resolve InDesignArticles and populate Placements with their frames.
	 * @throws BizException
	 * @return array 'Elements', 'Placements' and 'Pages'
	 */
	public function doPreviewArticlesAtWorkspace( $workspaceId, array $articles,
	                                            $action, $layoutId, $editionId, $previewType,
	                                            array $requestInfo = array() )
	{
		require_once BASEDIR.'/server/utils/UtfString.class.php';

		// Validate client input parameters
		$this->validateWorkspaceId( $workspaceId );
		foreach( $articles as $article ) {
			$this->validateId( $article->ID, 'Article' );
			$this->validateArticleFormat( $article->Format );
			$this->validateElementsContent( $article->Elements, $article->Content );
		}
		$this->validateActionType( $action );
		$previewType = $this->validatePreviewType( $action, $previewType ); // validate and repair
		$this->validateId( $layoutId, 'Layout' );
		$this->validateId( $editionId, 'Edition' );
		if( $layoutId ) { // having no layout is possible for articles with geometrical info
			$this->validateEditionId( $layoutId, $editionId );
		}

		$ticket = BizSession::getTicket();
		$this->openWorkspaceAtDb( $workspaceId );

		// Read the layout version of previous preview operation (if there was any).
		// For the first previous operation, we get this layout info from the database instead.
		$layoutProps = null; $requestParams = null;
		$this->getLayoutPropsAndRequestParamsFromComposeData( $layoutProps, $requestParams );

		$layoutVersion = '';
		if( $layoutId ) { // having no layout is possible for articles with geometrical info
			if( $layoutProps && $layoutProps->ID == $layoutId ) {
				$layoutVersion = $layoutProps->Version;
			} else {
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$layoutVersion = DBObject::getObjectVersion( $layoutId );
			}
		}

		// When the user selects different layout to preview, the layout id differs.
		// In that case, the delta WCML file should be removed to tell SC to read whole article.
		$sameLayoutId = $layoutProps && $layoutProps->ID == intval($layoutId);

		$guidsOfChangedStories = array();
		foreach( $articles as $articleAtWs ) {
			$fsContent = ''; $updatedVersion = false;
			$article = $this->openArticleAtWorkspace( $articleAtWs->ID, $articleAtWs->Format, $fsContent, $updatedVersion );
			$wsContent = $articleAtWs->Content ? $articleAtWs->Content : $fsContent; // content to store in workspace
			$this->storeArticleAtFileSystem( true, $article, $articleAtWs->Elements, $wsContent ); // true = save
			$this->validateWorkspaceAtDb();
			$this->saveWorkspaceAtDb(); // store workspace data at db (e.g. version nr update)
			if( $articleAtWs->Elements ) foreach( $articleAtWs->Elements as $element ) {
				$guidsOfChangedStories[] = $element->ID;
			}
			if( $updatedVersion || !$sameLayoutId ) {
				$this->deleteDeltaArticleFile( $article );
			} else {
				$this->updateDeltaArticleFile( $article, $articleAtWs->Elements );
			}
		}

		$parExpType = '';
		$outFileIDS = '';
		switch( $action ) {
			case 'Compose':
				$parExpType = 'COMPOSE';
				$outFileIDS = '';
				break;
			case 'Preview':
				$parExpType = 'JPEG';
				$outFileIDS = $this->getPreviewPath( $workspaceId, $layoutId, $editionId, 0, false ); // False for IDS perspective
				break;
			case 'PDF':
				$parExpType = 'PDF';
				$outFileIDS = $this->getPdfPath( $workspaceId, $layoutId, $editionId, false ); // False for IDS perspective
				break;
		}

		// When the user fills in different preview request parameters, such as another
		// edition, the compose data and previews must be regenerated for ALL stories and pages.
		// When request differs, simply reset the $guidsOfChangedStories, to trigger all this.
		$sameRequest =
			$requestParams && // first time preview this is null
			$requestParams->EditionId == intval($editionId) &&
			$requestParams->PreviewType == $previewType &&
			$requestParams->ExportType == $parExpType &&
			$sameLayoutId;
		// L> Note: The layout Version can only be checked after opening layout, in IDPreview.js
		if( !$sameRequest ) {
			$guidsOfChangedStories = array();
		}

		// from Web Editor perspective...
		$composeUpdateXmlFile = $this->workspace->WebEditor.'compose_update.xml';
		$pdfFile = $this->getPdfPath( $workspaceId, $layoutId, $editionId, true ); // True for WebEditor perspective

		// Clean up generated files from previous actions. When file does not exists,
		// it is created with zero bytes. When exists, it is cleared (zero bytes).
		// Since PHP creates the file, it has read/write access, as required. By
		// giving full read/write access, also IDS has write access as required.
		file_put_contents( $composeUpdateXmlFile, '' );
		$oldUmask = umask(0); // Needed for mkdir, see http://www.php.net/umask
		chmod($composeUpdateXmlFile, 0777);
		umask($oldUmask);

		if( $action == 'PDF' && file_exists($pdfFile) ) { // for PDF there is only one file
			unlink($pdfFile);
		}

		// from InDesign Server perspective...
		$composeUpdateXmlFileIDS = $this->workspace->InDesignServer.'compose_update.xml';

		// Determine the internal document version, since that tells us which IDS version
		// to pick to run the job. When the article is placed on a layout, the preparation
		// steps to generate preview may differ per IDS version. For example, for CS6 we do
		// less performance optimization steps than for CC2014. For that reason, the job must
		// be picked up by a matching IDS version, or else the prepared files in the workspace
		// folder won't be understood. The document version of the article may be misleading
		// and therefore should not be used when the article is placed. For example, a CS6
		// article can be placed on a CS6 layout that was migrated to CC2014, whereby the
		// version of the article remains CS6.
		$domVersion = null;
		if( $layoutId ) { // use layout document version when article is placed
			require_once BASEDIR.'/server/bizclasses/BizFileStoreXmpFileInfo.class.php';
			$domVersion = BizFileStoreXmpFileInfo::getInDesignDocumentVersion( $layoutId );
		}
		// When article is not placed, or when layout version could not be resolved,
		// fall back to article document version.
		if( !$domVersion ) {
			$domVersion = $this->workspace->DOMVersion;
		}

		// Generate compose data and preview/pdf pages
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Calling BizWebEditWorkspace::generateOutputPages( '.
				'ticket='.$ticket.
				', export type='.$parExpType.
				', layout id='.$layoutId.
				', WebEdit path='.$this->workspace->WebEditor.
				', IDS path='.$this->workspace->InDesignServer.
				', edition id='.$editionId.
				', IDS compose file='.$composeUpdateXmlFileIDS.
				', IDS output file='.$outFileIDS.
				', DOM version='.$domVersion.
				', previewType='.$previewType.')'
			);
		}

		// Create edition subfolder at workspace to store edition specific preview/pdf files
		if( $editionId ) {
			$this->createWorkspaceDir( dirname($pdfFile) );
		}

		// Compose list of article ids.
		$articleIdsFormats = array();
		foreach( $articles as $article ) {
			$articleIdsFormats[$article->ID] = $article->Format;
		}

		// Ask IDS to generate the previews / PDFs.
		$scriptResult = self::generateOutputPages( $ticket, $parExpType, $layoutId,
			$this->workspace->WebEditor, $this->workspace->InDesignServer, null, null,
			$editionId, $composeUpdateXmlFileIDS, $outFileIDS, null,
			$domVersion, null, $previewType, $articleIdsFormats,
			$layoutVersion, $guidsOfChangedStories, $requestInfo );

		// Store the result returned by the IDS script in the compose update file
		file_put_contents($composeUpdateXmlFile, $scriptResult->composeData);

		// For robustness, when the update XML file is empty, we simply delete it.
		// That avoids (in the succeeding code below) that the base XML gets emptied as well.
		$this->deleteEmptyComposeUpdateXmlFile( $composeUpdateXmlFile );

		// Make sure there is always a base XML file. Only the first time preview there is
		// no base, and so we rename the update XML to the base XML.
		$composeBaseXmlFile = $this->workspace->WebEditor.'compose_base.xml';
		if( !file_exists($composeBaseXmlFile) && file_exists($composeUpdateXmlFile) ) {
			rename( $composeUpdateXmlFile, $composeBaseXmlFile );
			clearstatcache(); // reflect rename() disk operation back into PHP
		}

		// Read base XML.
		if( !file_exists($composeBaseXmlFile) ) { // should never happen...
			throw new BizException( 'IDS_ERROR', 'Server', 'Could not find compose data file "'.$composeBaseXmlFile.'".' );
		}
		$composeBaseXmlStr = file_get_contents($composeBaseXmlFile);
		if( !$composeBaseXmlStr ) { // should never happen...
			throw new BizException( 'IDS_ERROR', 'Server', 'Compose data file is empty "'.$composeBaseXmlFile.'".' );
		}
		$composeBaseXmlDom = new DOMDocument();
		$composeBaseXmlDom->loadXML( $composeBaseXmlStr );

		// Read the update XML and merge into the base XML.
		if( file_exists($composeUpdateXmlFile) ) {

			// Merge update XML into base XML.
			$this->mergeComposeData( $composeBaseXmlDom, $composeUpdateXmlFile );

			// Save the base XML (to be picked up by succeeding preview operations).
			$composeBaseXmlDom->save( $composeBaseXmlFile );
		}

		// Build response data
		return $this->parseComposeData( $composeBaseXmlDom, $ticket, $action,
			$layoutId, $editionId, $requestInfo, $articles );
	}

	/**
	 * Deletes an empty (0 bytes) composeUpdateXmlFile.
	 *
	 * The file containing the updates to compose the preview is often stored on mounted/shared locations. PHP uses
	 * a cache to store information on files. For volatile files located on mounted/shared locations it turns out that
	 * the info of the cache is not reliable. Clearing the cache by calling clearstatcache() is one way to solve the
	 * problem. But that doesn't do the trick always, see EN-89055. For cifs-systems opening and closing a file updates
	 * the cache.
	 *
	 * @param string $composeUpdateXmlFile
	 */
	private function deleteEmptyComposeUpdateXmlFile( $composeUpdateXmlFile )
	{
		if( file_exists( $composeUpdateXmlFile ) ) {
			fclose( fopen( $composeUpdateXmlFile, 'a' ) );
		}

		if( !filesize( $composeUpdateXmlFile ) ) {
			unlink( $composeUpdateXmlFile );
			clearstatcache( true, $composeUpdateXmlFile ); // reflect unlink() disk operation back into PHP
		}
	}

	/**
	 * Generates write-to-fit info, page previews and/or PDFs for an article in the WebEditWorkspace.
	 *
	 * @since 9.7 Moved this function from the utils/InDesignServer class (which got obsoleted).
	 * @param string $ticket  		SCE logon ticket
	 * @param string $exptype  		what kind of file ( COMPOSE / JPEG / PDF ) do we need
	 * @param number $pageLayout  	object id of pagelayout
	 * @param string $workspace_webeditor  	path to web editor workspace from webservers perspective
	 * @param string $workspace_indesign  	path to web editor workspace from InDesign servers perspective
	 * @param string $artname  			Obsoleted
	 * @param string $guids  			Obsoleted
	 * @param string $editionId			edition id
	 * @param string $composeXMLfile  	xml file with compose information to create
	 * @param string $previewfile  		preview file to create
	 * @param integer $artid            Article object id. Obsoleted, better use $articleIdsFormats instead.
	 * @param integer $domVersion		Mininum required internal IDS version to run the job. Typically the version that was used to create the article/layout.
	 * @param string $format			File format. Obsoleted, better use $articleIdsFormats instead.
	 * @param string $previewType  		[v7.6] Preview for a 'page' or 'spread'. Pass empty string when $exptype is not 'JPEG'.
	 * @param array $articleIdsFormats  [v9.4] Map of article ids (keys) and file formats (values). Supersedes $artid and $format params.
	 * @param string $layoutVersion     [v9.5] The expected layout object version.
	 * @param array $guidsOfChangedStories [v9.5] List of stories (GUIDs) that contain text changes which are not saved in filestore yet.
	 * @param string[] $requestInfo      [9.7] Pass in 'InDesignArticles' to resolve InDesignArticles and populate Placements with their frames.
	 * @return string|object             [10.2] Result returned by IDS script.
	 * @throws BizException [9.7] Raises error when IDS script has failed.
	 */
	public static function generateOutputPages( $ticket, $exptype, $pageLayout, $workspace_webeditor,
												$workspace_indesign, $artname, $guids, $editionId,
												$composeXMLfile, $previewfile, $artid, $domVersion,
												$format = 'application/incopy', $previewType = 'page',
												$articleIdsFormats = array(),
												$layoutVersion = '', $guidsOfChangedStories = array(),
												array $requestInfo = array() )
	{
		// Determine the article ids-formats mapping.
		if( count($articleIdsFormats) == 0 ) { // backwards compatible mode
			$articleIdsFormats = array( $artid => $format );
		}
		unset( $artid );  // obsoleted
		unset( $format ); // obsoleted

		// Check if the article files are present in the workspace folder.
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		foreach( $articleIdsFormats as $articleId => $articleFormat ) {
			$fileExt = MimeTypeHandler::mimeType2FileExt( $articleFormat, 'Article' );
			$url = $workspace_webeditor . $articleId . $fileExt;
			if( !is_file($url) ) {
				// should never happen... just for debug purposes
				throw new BizException( 'IDS_ERROR', 'Server', 'Could not find file ['.$url.']' );
			}
		}

		if (file_exists($workspace_webeditor . 'InDesignServer.log')) {
			// remove logfile, only keep latest logfile.
			unlink($workspace_webeditor . 'InDesignServer.log');
		}

		$server_indesignFile = '';
		if( version_compare( $domVersion, '8.9', '<=' ) ) { // SC8.x for CS6 (or before)
			if( !empty($pageLayout) ) {
				require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
				$getObjService = new WflGetObjectsService();

				// Get layout version information  (do not get native file yet)
				$getObjReq = new WflGetObjectsRequest($ticket, array($pageLayout), false, 'none', array());
				$getObjResp = $getObjService->execute( $getObjReq );
				$objects = $getObjResp->Objects;

				$layoutid = $objects[0]->MetaData->BasicMetaData->ID;
				$version = $objects[0]->MetaData->WorkflowMetaData->Version;

				// do not fetch InDesign document each time
				// put layout version in filename to see if latest version is allready available
				$indesignFile = $workspace_webeditor.$layoutid.'-v'.$version.'.indd';
				$server_indesignFile = $workspace_indesign.$layoutid.'-v'.$version.'.indd';

				if ( !file_exists( $indesignFile )) { // get InDesign doc from filestore
					// Get layout information with native file
					$getObjReq = new WflGetObjectsRequest($ticket, array($pageLayout), false, 'native', array());
					$getObjResp = $getObjService->execute( $getObjReq );
					$objects = $getObjResp->Objects;
					$filePath = $objects[0]->Files[0]->FilePath;
					copy( $filePath, $indesignFile );
				}
			}
		}

		// Prefer Web Editor ticket. Fall back at Web Apps ticket.
		$prodVer = PRODUCTMAJORVERSION.PRODUCTMINORVERSION.'0'; // taken from serverinfo.php
		$ticketCookies = array(
			'webedit_ticket_ContentStationPro'.$prodVer, // cookie name for Web Editor ticket (CS login)
			'webapp_ticket_ContentStationPro'.$prodVer, // cookie name for web apps ticket (CS login)
			'ticket' // cookie name for web apps ticket (browser login)
		);

		// Get the name of the Web Editor user by running through cookie information.
		// Cookie info is set when the Web Editor gets lauched. For the CS editor this is
		// not the case. See next step below for more info.
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		$weTicket = '';
		$weUserId = '';
		foreach( $ticketCookies as $ticketCookie ) {
			if( isset($_REQUEST[$ticketCookie]) ) {
				$weTicket = $_REQUEST[$ticketCookie];
				$weUserId = DBTicket::DBuserticket( $weTicket );
				if( $weUserId === false ) {
					// Clear to avoid passing bad tickets! (BZ#11375)
					$weTicket = '';
					$weUserId = '';
				} else {
					break; // found user!
				}
			}
		}

		// When failed reading CS ticket or Web Editor ticket from cookies (above), try the ticket
		// passed in by caller. This fallback is especially needed for the CS editor that is talking
		// through web services. In this case typically no cookies are updated (as used above).
		// (Same happens happens for the Build Test when not logging out from web apps and hitting
		// the Test button the next morning, in which case all tickets at cookies are expired).
		if( !$weTicket && !$weUserId ) {
			$weTicket = $ticket;
			$weUserId = DBTicket::DBuserticket( $weTicket );
			if( $weUserId === false ) { // Clear to avoid passing bad tickets!
				$weTicket = '';
				$weUserId = '';
			}
		}

		// Get the specified edition
		if ($editionId > 0) {
			require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
			$edition = DBEdition::getEdition( $editionId );
			$editionName = !is_null( $edition) && isset( $edition->Name ) ? $edition->Name : '';
		} else {
			$editionName = '';
		}

		// Setup parameters to pass to IDPreview.js script.
		// IMPORTANT: It is far from obvious to communicate UTF-8 chars between PHP and JavaScript...!
		// See related problems: BZ#7341, BZ#13049, BZ#13062
		// Note that the PHP functions "urlencode"/"urldecode" are NOT compatible with the Javascript functions
		// "escape"/"unescape", "encodeURI"/"decodeURI", "encodeURIComponent"/"decodeURIComponent".
		// See: http://www.captain.at/howto-php-urlencode-javascript-decodeURIComponent.php
		// However, we can not use that solution since it is under GPL !
		// Nevertheless, we have even a better alternative; We pack all parameters into an XML DOM
		// and stream it to a string, which we pass on through SOAP to InDesign Server.

		$articleIds = array_keys($articleIdsFormats);
		$articleIdString = implode( ',', $articleIds );

		// create the paths to the .wcml files
		$articlePathsArray = array();
		foreach( $articleIdsFormats as $articleId => $articleFormat ) {
			$fileExt = MimeTypeHandler::mimeType2FileExt( $articleFormat, 'Article' );
			$articlePathsArray[] = $workspace_indesign . $articleId . $fileExt;
		}
		$articlePaths = implode( ',', $articlePathsArray );

		$params = array(
			'editionId'		=> $editionId,
			'editionName'	=> $editionName,
			'previewfile'	=> $previewfile,
			'template'		=> $pageLayout ? '' : reset($articlePathsArray),
			'getRelations'  => in_array( 'InDesignArticles', $requestInfo ) || in_array( 'Relations', $requestInfo ) ? 'true' : 'false',

			'dumpfile'		=> $composeXMLfile,
			'exportType'	=> $exptype,

			'layoutID'		=> $pageLayout ? $pageLayout : '', // BZ#13012 change zero into empty
			'layoutVersion'	=> $layoutVersion,
			'layoutPath'	=> $server_indesignFile,

			'articleIDS'	=> $articleIdString ? $articleIdString : '', // BZ#13012 change zero into empty
			'articlePaths'	=> $articlePaths ? $articlePaths : '',
			'guidsOfChangedStoriesCsv' => implode( ',', $guidsOfChangedStories ),

			'ticketID'		=> $weTicket,
			'userId'		=> $weUserId,
			'appServer'		=> INDESIGNSERV_APPSERVER,
			'previewType'	=> $previewType ); // v7.6 feature: 'page' or 'spread'

		// Convert parameters to XML
		$paramDoc = new DOMDocument();
		$rootElem = $paramDoc->appendChild( new DOMElement('root') );
		foreach( $params as $name => $value ) {
			$rootElem->appendChild( new DOMElement( $name, $value ) );
		}

		// Determine the required IDS version. The code path for SC10 is quite different
		// from SC8. For example, for SC8 we did download the layout as a preparation step.
		// The IDPreview.js also acts quite different in SC8 mode. Therefore we have to
		// stick to a specific IDS version since we are already half-way the SC8 or SC10
		// code path. For SC8 we pick IDS CS6 (8.0) and for SC10 we pick IDS CC2014 (10.0).
		// Currently, there are ID CC2014.1 and IDS/IC CC2014 available only.
		// IDS CC2014 has 10.0 and ID CC2014.1 has 10.1 and so do their layouts.
		// We don't know if there ever will be a IDS CC2014.1 released, but when that happens,
		// we want to support it and so we search for IDS [10.0-10.1] when layout DOM is 10.0 or 10.1.
		// Note that for SC9 we pick IDS CC (9.2), but this is not officially supported.
		// Thereby the DOMVersion is 9.0 but the IDS version is 9.2, which is mapped below.
		// In case of IDS version 8.x or 10.x we expect that that these servers can handle all version 8 or 10
		// layouts.
		require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
		list( $minReqVersion, $maxReqVersion ) = BizInDesignServer::getServerMinMaxVersionForDocumentVersion( $domVersion );

		// Run IDPreview.js at InDesign Server, which handles the Preview/Compose/PDF request
		require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
		$scriptParams = array(
			'XMLParams' => $paramDoc->saveXML(),
			'jsonResult' => true
		);
		if(LogHandler::debugMode()) {
			$scriptParams['logfile'] = $workspace_indesign . 'InDesignServer.log';
		}
		return BizInDesignServerJobs::createAndRunJob(
			'file:{{BASEDIR}}/server/apps/IDPreview.js', $scriptParams,
			'WEB_EDITOR', key($articleIdsFormats), null, // jobtype, object id, ids obj
			$minReqVersion, $maxReqVersion, // min ids version, max ids version
			'Content Station' // context
		);
	}

	/**
	 * Merges the update XML file into the base XML file.
	 *
	 * @param DOMDocument $baseXmlDom Base XML to update.
	 * @param string $updateXmlFile Full file path to the update XML file.
	 * @throws BizException
	 */
	private function mergeComposeData( DOMDocument $baseXmlDom, $updateXmlFile )
	{
		$updateXmlStr = file_get_contents($updateXmlFile);
		if( !$updateXmlStr ) { // should never happen...
			throw new BizException( 'IDS_ERROR', 'Server', 'Compose data file is empty "'.$updateXmlFile.'".' );
		}
		$updateXmlDom = new DOMDocument();
		$updateXmlDom->loadXML( $updateXmlStr );
		$updateXpath = new DOMXPath( $updateXmlDom );
		$baseXpath = new DOMXPath( $baseXmlDom );

		// Detect whether or not the layout id and version are the same as previous request.
		$updateLayouts = $updateXpath->query('/textcompose/context/layout');
		$updateLayout = $updateLayouts->length > 0 ? $updateLayouts->item(0) : null;
		$baseLayouts = $baseXpath->query('/textcompose/context/layout');
		$oldBaseLayout = $baseLayouts->length > 0 ? $baseLayouts->item(0) : null;
		if( $updateLayout && $oldBaseLayout ) {
			$sameLayoutId = $updateLayout->getAttribute('id') == $oldBaseLayout->getAttribute('id');
			if( $sameLayoutId ) {
				$sameLayoutVersion = $updateLayout->getAttribute('version') == $oldBaseLayout->getAttribute('version');
				if( $sameLayoutVersion ) {
					LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Layout version is still the same.' );
				} else {
					LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Layout version is updated.' );
				}
			} else {
				$sameLayoutVersion = false;
				LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'User has picked another layout.' );
			}
		} else {
			$sameLayoutId = true;
			$sameLayoutVersion = true;
		}

		// Detect whether or not the IDS request parameters are the same as previous one.
		$updateContexts = $updateXpath->query('/textcompose/context');
		$updateContext = $updateContexts->length > 0 ? $updateContexts->item(0) : null;
		$baseContexts = $baseXpath->query('/textcompose/context');
		$oldBaseContext = $baseContexts->length > 0 ? $baseContexts->item(0) : null;
		if( $updateContext && $oldBaseContext ) {
			$sameRequest =
				$updateContext->getAttribute('editionid') == $oldBaseContext->getAttribute('editionid') &&
				$updateContext->getAttribute('exporttype') == $oldBaseContext->getAttribute('exporttype') &&
				$updateContext->getAttribute('previewtype') == $oldBaseContext->getAttribute('previewtype') &&
				$sameLayoutId;
			if( $sameRequest ) {
				LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Request params are still the same.' );
			} else {
				LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Request params have changed.' );
			}
		} else {
			$sameRequest = false;
		}

		// Determine whether we need to merge or replace information in compose_base.xml.
		$performMerge = $sameRequest && $sameLayoutVersion;
		if( $performMerge ) {
			LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Performing merge operation on compose data.' );
		} else { // performance full update
			LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Performing full replacement operation on compose data.' );
		}

		// Replace context and layout info (no matter $performMerge).
		if( $updateContext && $oldBaseContext ) {
			$newBaseContext = $baseXmlDom->importNode( $updateContext, true );
			$oldBaseContext->parentNode->replaceChild( $newBaseContext, $oldBaseContext );
		}

		// Merge or replace pages.
		if( $performMerge ) {
			// Merge pages (based on their sequence number).
			$updatePages = $updateXpath->query('/textcompose/pages/page');
			if( $updatePages ) foreach( $updatePages as $updatePage ) {
				$pageSequence = $updatePage->getAttribute('sequence');
				$basePages = $baseXpath->query('/textcompose/pages/page[@sequence="'.$pageSequence.'"]');
				$oldBasePage = $basePages->length > 0 ? $basePages->item(0) : null;
				$newBasePage = $baseXmlDom->importNode( $updatePage, true );
				if( $oldBasePage ) {
					$oldBasePage->parentNode->replaceChild( $newBasePage, $oldBasePage );
					LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Updated compose_base.xml with page ['.$pageSequence.']' );
				} else {
					$basePagesList = $baseXpath->query('/textcompose/pages');
					$basePages = $basePagesList->length > 0 ? $basePagesList->item(0) : null;
					if( $basePages ) {
						$basePages->appendChild( $newBasePage );
						LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Added new page ['.$pageSequence.'] to compose_base.xml.' );
					} else {
						LogHandler::Log( 'WebEditWorkspace', 'ERROR', 'Could not find/update "pages" element in compose_base.xml.' );
					}
				}
			}
		} else {
			// Overwrite all pages (since all compose data has been re-generated)
			$updatePagesList = $updateXpath->query('/textcompose/pages');
			$updatePages = $updatePagesList->length > 0 ? $updatePagesList->item(0) : null;
			$basePagesList = $baseXpath->query('/textcompose/pages');
			$oldBasePages = $basePagesList->length > 0 ? $basePagesList->item(0) : null;
			if( $updatePages && $oldBasePages ) {
				$newBasePages = $baseXmlDom->importNode( $updatePages, true );
				$oldBasePages->parentNode->replaceChild( $newBasePages, $oldBasePages );
				LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Overwrote entire "pages" element in compose_base.xml (taken from compose_update.xml).' );
			} else {
				LogHandler::Log( 'WebEditWorkspace', 'ERROR', 'Could not find/update "pages" element in compose_base.xml and/or compose_update.xml.' );
			}
		}

		// Merge or replace stories.
		if( $performMerge ) {
			// Merge stories (based in their guid).
			$updateStories = $updateXpath->query('/textcompose/stories/story');
			if( $updateStories ) foreach( $updateStories as $updateStory ) {
				$storyGuid = $updateStory->getAttribute('guid');
				// When story is placed twice, the guid is the same, so take the splineid into
				// account to find unique stories. The first frame (frameorder=0) is good enough.
				$oldBaseStory = null;
				$updateTextFrame = $updateXpath->query('textframes/textframe[@frameorder="0"]', $updateStory );
				$updateTextFrame = $updateTextFrame->length > 0 ? $updateTextFrame->item(0) : null;
				if( $updateTextFrame ) {
					$updateTextFrameId = $updateTextFrame->getAttribute('frameid');
					$baseTextFrame = $baseXpath->query('/textcompose/stories/story[@guid="'.$storyGuid.'"]/textframes/textframe[@frameid="'.$updateTextFrameId.'"]');
					$baseTextFrame = $baseTextFrame->length > 0 ? $baseTextFrame->item(0) : null;
					if( $baseTextFrame ) {
						$oldBaseStory = $baseTextFrame->parentNode->parentNode;
					}
				}
				$newBaseStory = $baseXmlDom->importNode( $updateStory, true );
				if( $oldBaseStory ) {
					$oldBaseStory->parentNode->replaceChild( $newBaseStory, $oldBaseStory );
					LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Updated compose_base.xml with story ['.$storyGuid.']' );
				} else {
					$baseStoriesList = $baseXpath->query('/textcompose/stories');
					$baseStories = $baseStoriesList->length > 0 ? $baseStoriesList->item(0) : null;
					if( $baseStories ) {
						$baseStories->appendChild( $newBaseStory );
						LogHandler::Log( 'WebEditWorkspace', 'ERROR', 'Added story ['.$storyGuid.'] to compose_base.xml.' );
					} else {
						LogHandler::Log( 'WebEditWorkspace', 'ERROR', 'Could not find/update "stories" element in compose_base.xml.' );
					}
				}
			}
		} else {
			// Overwrite all stories (since all compose data has been re-generated)
			$updateStoriesList = $updateXpath->query('/textcompose/stories');
			$updateStories = $updateStoriesList->length > 0 ? $updateStoriesList->item(0) : null;
			$baseStoriesList = $baseXpath->query('/textcompose/stories');
			$oldBaseStories = $baseStoriesList->length > 0 ? $baseStoriesList->item(0) : null;
			if( $updateStories && $oldBaseStories ) {
				$newBaseStories = $baseXmlDom->importNode( $updateStories, true );
				$oldBaseStories->parentNode->replaceChild( $newBaseStories, $oldBaseStories );
				LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Overwrote entire "stories" element in compose_base.xml (taken from compose_update.xml).' );
			} else {
				LogHandler::Log( 'WebEditWorkspace', 'ERROR', 'Could not find/update "stories" element in compose_base.xml and/or compose_update.xml.' );
			}
		}

		// Replace the layout's relations and their placements (no matter $performMerge).
		$updateLayouts = $updateXpath->query('/textcompose/layout');
		$updateLayout = $updateLayouts->length > 0 ? $updateLayouts->item(0) : null;
		$baseLayouts = $baseXpath->query('/textcompose/layout');
		$oldBaseLayout = $baseLayouts->length > 0 ? $baseLayouts->item(0) : null;
		if( $updateLayout && $oldBaseLayout ) {
			$newBaseLayout = $baseXmlDom->importNode( $updateLayout, true );
			$oldBaseLayout->parentNode->replaceChild( $newBaseLayout, $oldBaseLayout );
		}
	}

	/**
	 * Parses the compose XML file and builds a response structure based on the XML data found.
	 *
	 * @param DOMDocument $composeXmlDom The compose base XML file.
	 * @param string $ticket
	 * @param string $action
	 * @param integer $layoutId
	 * @param integer $editionId
	 * @param string[] $requestInfo [9.7] Pass in 'InDesignArticles' to resolve InDesignArticles and populate Placements with their frames.
	 * @param ArticleAtWorkspace[] $articles List of articles to preview.
	 * @throws BizException
	 * @return array Response structure
	 */
	private function parseComposeData( DOMDocument $composeXmlDom, $ticket, $action,
		$layoutId, $editionId, array $requestInfo, array $articles )
	{
		// Build response data
		$ret = array();
		$ret['Elements']   = array();
		$ret['Placements'] = array();
		$ret['Pages']      = array();

		$xpath = new DOMXPath( $composeXmlDom );
		$layouts = $xpath->query('/textcompose/context/layout');
		$layout = $layouts->length > 0 ? $layouts->item(0) : null;
		$ret['LayoutVersion'] = $layout ? $layout->getAttribute('version') : '';

		$composePages = $xpath->query('/textcompose/pages/page');
		if( $composePages->length == 0 ) {
			throw new BizException( 'ERR_LAY_NOT_SAVED', 'Client', $layoutId );
		}
		foreach( $composePages as $composePage ) {
			$page = new Page();
			$page->Width = $composePage->getAttribute('width');
			$page->Height = $composePage->getAttribute('height');
			$page->PageSequence = $composePage->getAttribute('sequence');
			$page->PageNumber = $composePage->getAttribute('name');
			// Not used: $composePage->getAttribute('side'); (left, right)
			$page->PageOrder = $composePage->getAttribute('order');
			$page->Instance = 'Production';
			$page->Master = '';
			$file = new Attachment();
			$file->Rendition = 'preview';
			$file->Type = 'image/jpeg';
			$file->FileUrl = SERVERURL_ROOT.INETROOT.'/previewindex.php?'.
				'ticket='.$ticket.'&workspaceid='.$this->workspace->ID.'&action='.$action.
				'&layoutid='.$layoutId.'&editionid='.$editionId.
				'&pagesequence='.$page->PageSequence;
			$page->Files = array( $file );
			$ret['Pages'][] = $page;
		}

		$articlePageIds = array();
		$composeStories = $xpath->query('/textcompose/stories/story');
		foreach( $composeStories as $composeStory ) {
			$element = new Element();
			$element->ID = $composeStory->getAttribute('guid');
			$element->Name = UtfString::unescape( $composeStory->getAttribute('label') ); // unescape(): BZ#27285/BZ#26670
			$element->LengthWords = $composeStory->getAttribute('words');
			$element->LengthChars = $composeStory->getAttribute('chars');
			$element->LengthParas = $composeStory->getAttribute('paras');
			$element->LengthLines = $composeStory->getAttribute('lines');
			$ret['Elements'][] = $element;

			$composeFrames = $xpath->query('textframes/textframe', $composeStory);
			if( $composeFrames->length > 0 ) {
				foreach( $composeFrames as $composeFrame ) {

					// Parse placement.
					$placement = new Placement();
					$placement->ElementID    = $composeStory->getAttribute('guid');
					$placement->Element      = UtfString::unescape( $composeStory->getAttribute('label') ); // unescape(): BZ#27285/BZ#26670
					$placement->FrameID      = $composeFrame->getAttribute('frameid');
					$placement->FrameOrder   = $composeFrame->getAttribute('frameorder');
					$placement->PageSequence = $composeFrame->getAttribute('pagesequence');
					$placement->PageNumber   = $composeFrame->getAttribute('pagenr');
					$placement->OversetLines = 0; // filled below
					$placement->Left   = $composeFrame->getAttribute('xpos');
					$placement->Top    = $composeFrame->getAttribute('ypos');
					$placement->Width  = $composeFrame->getAttribute('width');
					$placement->Height = $composeFrame->getAttribute('height');
					$placement->Layer  = UtfString::unescape( $composeFrame->getAttribute('layer') ); // unescape(): BZ#27285/BZ#26670
					$idArticleIds = $composeFrame->getAttribute('idarticleids');
					$placement->InDesignArticleIds = $idArticleIds ? explode( ',', $idArticleIds ) : array();
					$placement->FrameType = $composeFrame->getAttribute('frametype');
					$placement->SplineID = $composeFrame->getAttribute('splineid');

					// Parse placement tiles. Tiles are available when a text frame is on two pages of a spread.
					$composeTiles = $xpath->query('tiles/tile', $composeFrame);
					if( $composeTiles->length > 0 ) {
						$placement->Tiles = array();
						foreach( $composeTiles as $composeTile ) {
							$tile = new PlacementTile();
							$tile->PageSequence = $composeTile->getAttribute('pagesequence');
							$tile->Left   = $composeTile->getAttribute('xpos');
							$tile->Top    = $composeTile->getAttribute('ypos');
							$tile->Width  = $composeTile->getAttribute('width');
							$tile->Height = $composeTile->getAttribute('height');
							$placement->Tiles[] = $tile;
						}
					}
					$articlePageIds[ $placement->PageSequence ] = true;

					// Return placement (and its placement tiles) to caller.
					$ret['Placements'][] = $placement;
				}
				// The last text frame represents the underset/overset of one story
				$type = $composeStory->getAttribute('type');
				$overset = 0;
				switch( $type ) {
					case 'overset':  $overset =  $composeStory->getAttribute('value'); break; // positive
					case 'underset': $overset = -$composeStory->getAttribute('value'); break; // negative
				}
				end($ret['Placements'])->OversetLines = $overset;

				// Since 9.2.1 the length of the overset chars is also returned by InDesign Server
				end($ret['Placements'])->OversetChars = $composeStory->getAttribute('length');
			}
		}

		$ret['InDesignArticles'] = null;
		$ret['Relations'] = null;
		$iaPlacements = null;
		if( in_array( 'InDesignArticles', $requestInfo ) || in_array( 'Relations', $requestInfo ) ) {
			if( $xpath && $layoutId ) { // Having no layout is possible for articles with geometrical info.

				// Since 9.7, resolve the layout's InDesign Articles and their frames (placements),
				// but only do that when client has explicitly requested for that (for performance reasons).
				if( in_array( 'InDesignArticles', $requestInfo ) ) {
					require_once BASEDIR.'/server/dbclasses/DBInDesignArticle.class.php';
					$ret['InDesignArticles'] = DBInDesignArticle::getInDesignArticles( $layoutId );
					$iaPlacements = $this->composeInDesignArticlesPlacements( $xpath );
					$ret['Placements'] = array_merge( $ret['Placements'], array_values( $iaPlacements ) );
				}

				// Since 9.7, resolve the layout's placed relations so that caller (CS preview)
				// can draw boxes for the sibling frames on the page and allow image/text (re)placements.
				if( in_array( 'Relations', $requestInfo ) ) {
					$ret['Relations'] = $this->composeObjectRelations( $xpath );
					$rebuildNeeded = $this->isRebuildStoredRelationsPlacementsNeeded( $layoutId, $ret['Relations'] );
					if( $rebuildNeeded ) {
						// Although the layout resides in workspace, we want to save the placed
						// relations into the DB. This is done since SC suppresses the CreateObjectRelations
						// requests in context of IDPreview.js. The suppress is done for performance
						// reasons, because a few Object Operations may result into many of those requests,
						// which are all called synchronously and so for poor WANs this would slow down
						// the preview operation for a few seconds, while the user is waiting.
						require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
						$user = BizSession::getShortUserName();

						// Note that the DB updates below are guarded by BizObject::createSemaphoreForSaveLayout()
						// to avoid SaveObjects being executed in the same time as PreviewArticle(s)AtWorkspace (EN-86722).

						// Before delete+create object relations, make sure to remove all InDesignArticles
						// since this does a cascade delete of their object->placements, which may be
						// referenced through the relation->placements as well; Doing this after would
						// destroy the InDesignArticle placements set through the relations.
						if( !is_null( $ret['InDesignArticles'] ) ) {
							require_once BASEDIR.'/server/dbclasses/DBInDesignArticle.class.php';
							DBInDesignArticle::deleteInDesignArticles( $layoutId );
						}

						// Delete all InDesignArticle placements (v9.7)
						// Needs to be done BEFORE saveObjectPlacedRelations() since that is ALSO creating
						// InDesignArticle placements (the ones that are also relational placements).
						if( !is_null( $ret['Placements'] ) ) {
							require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
							DBPlacements::deletePlacements( $layoutId, 0, 'Placed' );
						}

						BizObject::saveObjectPlacedRelations( $user, $layoutId, $ret['Relations'], false, false );

						// Save the InDesign Articles for the layout object (v9.7).
						if( !is_null( $ret['InDesignArticles'] ) ) {
							require_once BASEDIR.'/server/dbclasses/DBInDesignArticle.class.php';
							DBInDesignArticle::createInDesignArticles( $layoutId, $ret['InDesignArticles'] );
						}

						// Save the InDesign Article Placements for the layout object (v9.7).
						if( !is_null( $iaPlacements ) ) {
							DBPlacements::insertInDesignArticlePlacementsFromScratch( $layoutId, $iaPlacements );
						}
					}

					// Optimizations for CS that does not need all info in this context.
					if( $ret['Relations'] ) {
						self::optimizeRelationInfo( $ret['Relations'], $articles, $articlePageIds, $editionId );
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Remove unwanted Relations information to optimize speed performance.
	 *
	 * Not all info under PreviewArticleAtWorkspace->Relations data tree is needed by CS.
	 * For slow WAN networks, big data trees would slow down the preview performance.
	 * This function removes this info from the tree before it gets sent back to CS.
	 *
	 * @param Relation[] $relations [input/output] Relations to be optimized.
	 * @param ArticleAtWorkspace[] $articles List of articles to preview.
	 * @param integer[] $articlePageIds List of PageSequence numbers on which the article is placed
	 * @param integer $editionId
	 */
	private function optimizeRelationInfo( array &$relations, array $articles, array $articlePageIds, $editionId )
	{
		$articleIds = array_flip( array_map( function( $article ) { return $article->ID; }, $articles ) );

		if( $relations ) foreach( $relations as $relationIndex => $relation ) {

			// The placements for the article(s) being edit are already returned
			// through response->Placements, so leave it out here to avoid returning twice.
			if( array_key_exists( $relation->Child, $articleIds ) ) {
				unset( $relations[ $relationIndex ] );
				continue;
			}

			// Relational targets are not used, so leave it out.
			$relation->Targets = null;

			// There is always an edition filter, unless the channel has no edition defined.
			// When the edition mismatches, there is no need to return the placement
			// since placements for that edition are not shown in the current preview.
			if( $editionId ) {
				if( $relation->Placements ) foreach( $relation->Placements as $placementIndex => $placement ) {
					if( isset( $placement->Edition->Id ) && $placement->Edition->Id != $editionId ) {
						unset( $relation->Placements[ $placementIndex ] );
					}
				}
			}

//>>>		EN-89020: Instead of removing the code, comment it out to make it clear that this part is taken out for a reason.
//			// When the placement is not on one of the pages the article(s) are placed
//			// onto, leave out those placements.
//			if( $articlePageIds ) {
//				if( $relation->Placements ) foreach( $relation->Placements as $placementIndex => $placement ) {
//					if( !array_key_exists( $placement->PageSequence, $articlePageIds ) ) {
//						unset( $relation->Placements[ $placementIndex ] );
//					}
//				}
//			}
//<<<
		}
	}

	/**
	 * Composes a list of Relation data objects from a given xpath (under '/textcompose/Relations').
	 *
	 * @param DOMXPath $xpath
	 * @return Relation[]|null
	 */
	private function composeObjectRelations( DOMXPath $xpath )
	{
		$relations = null;
		if( ($xmlArrayOfRelation = $this->getElement( $xpath, '/textcompose/layout/Object/Relations', null )) ) {
			$relations = array();
			$xmlRelations = $xpath->query( 'Relation', $xmlArrayOfRelation );
			if( $xmlRelations->length > 0 ) foreach( $xmlRelations as $xmlRelation ) {
				$relation = new Relation();
				$relation->Parent = $this->getTextValue( $xpath, 'Parent', $xmlRelation );
				$relation->Child = $this->getTextValue( $xpath, 'Child', $xmlRelation );
				$relation->Type = $this->getTextValue( $xpath, 'Type', $xmlRelation );
				$relation->Placements = null;
				if( ($xmlArrayOfPlacement = $this->getElement( $xpath, 'Placements', $xmlRelation )) ) {
					$relation->Placements = array();
					$xmlPlacements = $xpath->query( 'Placement', $xmlArrayOfPlacement );
					if( $xmlPlacements->length > 0 ) foreach( $xmlPlacements as $xmlPlacement ) {
						$relation->Placements[] = $this->composePlacement( $xpath, $xmlPlacement );
					}
				}
				$relation->ParentVersion = $this->getTextValue( $xpath, 'ParentVersion', $xmlRelation );
				$relation->ChildVersion = $this->getTextValue( $xpath, 'ChildVersion', $xmlRelation );
				$relation->Geometry = null;
				$relation->Rating = $this->getTextValue( $xpath, 'Rating', $xmlRelation );
				$relation->Targets = null;
				// >>> Commented out since CS does not need relational targets in this context.
				//if( ($xmlArrayOfTarget = $this->getElement( $xpath, 'Targets', $xmlRelation )) ) {
				//	$xmlTargets = $xpath->query( 'Target', $xmlArrayOfTarget );
				//	$relation->Targets = array();
				//	if( $xmlTargets->length > 0 ) foreach( $xmlTargets as $xmlTarget ) {
				// 		$target = new Target();
				// 		if( ($xmlPubChannel = $this->getElement( $xpath, 'PubChannel', $xmlTarget )) ) {
				// 			$target->PubChannel = new PubChannel();
				// 			$target->PubChannel->Id = $this->getTextValue( $xpath, 'Id', $xmlPubChannel );
				// 			$target->PubChannel->Name = $this->getTextValue( $xpath, 'Name', $xmlPubChannel );
				// 		}
				// 		if( ($xmlIssue = $this->getElement( $xpath, 'Issue', $xmlTarget )) ) {
				// 			$target->Issue = new Issue();
				// 			$target->Issue->Id = $this->getTextValue( $xpath, 'Id', $xmlIssue );
				// 			$target->Issue->Name = $this->getTextValue( $xpath, 'Name', $xmlIssue );
				// 		}
				// 		$target->Editions = null;
				// 		if( ($xmlArrayOfEdition = $this->getElement( $xpath, 'Editions', $xmlTarget )) ) {
				// 			$target->Editions = array();
				// 			$xmlEditions = $xpath->query( 'Edition', $xmlArrayOfEdition );
				// 			if( $xmlEditions->length > 0 ) foreach( $xmlEditions as $xmlEdition ) {
				// 				$edition = new Edition();
				// 				$edition->Id = $this->getTextValue( $xpath, 'Id', $xmlEdition );
				// 				$edition->Name = $this->getTextValue( $xpath, 'Name', $xmlEdition );
				// 				$target->Editions[] = $edition;
				// 			}
				// 		}
				// 		$relation->Targets[] = $target;
				//	}
				//}
				// <<<
				// >>> Not provided by SC:
				// if( ($xmlParentInfo = $this->getElement( $xpath, 'ParentInfo', $xmlRelation )) ) {
				// 	$relation->ParentInfo = new ObjectInfo();
				// 	$relation->ParentInfo->ID = $this->getTextValue( $xpath, 'ID', $xmlParentInfo );
				// 	$relation->ParentInfo->Name = $this->getTextValue( $xpath, 'Name', $xmlParentInfo );
				// 	$relation->ParentInfo->Type = $this->getTextValue( $xpath, 'Type', $xmlParentInfo );
				// 	$relation->ParentInfo->Format = $this->getTextValue( $xpath, 'Format', $xmlParentInfo );
				// }
				//
				// if( ($xmlChildInfo = $this->getElement( $xpath, 'ChildInfo', $xmlRelation )) ) {
				// 	$relation->ChildInfo = new ObjectInfo();
				// 	$relation->ChildInfo->ID = $this->getTextValue( $xpath, 'ID', $xmlChildInfo );
				// 	$relation->ChildInfo->Name = $this->getTextValue( $xpath, 'Name', $xmlChildInfo );
				// 	$relation->ChildInfo->Type = $this->getTextValue( $xpath, 'Type', $xmlChildInfo );
				// 	$relation->ChildInfo->Format = $this->getTextValue( $xpath, 'Format', $xmlChildInfo );
				// }
				$relation->ObjectLabels = null;
				$relations[] = $relation;
			}
		}
		// Resolve the ParentInfo and ChildInfo for all relations
		// (since SC does not provide this info and CS needs it).
		$relationIds = array();
		if( $relations ) foreach( $relations as $relation ) {
			$relationIds[] = $relation->Parent;
			$relationIds[] = $relation->Child;
		}
		if( $relationIds ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$relObjProps = DBObject::getObjectsPropsForRelations( $relationIds, false );
		}
		if( $relationIds ) {
			if( $relations ) foreach( $relations as $relation ) {
				if( isset($relObjProps[$relation->Parent]) ) {
					$parentProps = $relObjProps[$relation->Parent];
					$relation->ParentInfo = new ObjectInfo();
					$relation->ParentInfo->ID     = $parentProps['ID'];
					$relation->ParentInfo->Name   = $parentProps['Name'];
					$relation->ParentInfo->Type   = $parentProps['Type'];
					$relation->ParentInfo->Format = $parentProps['Format'];
				}
				if( isset($relObjProps[$relation->Child]) ) {
					$childProps = $relObjProps[$relation->Child];
					$relation->ChildInfo = new ObjectInfo();
					$relation->ChildInfo->ID     = $childProps['ID'];
					$relation->ChildInfo->Name   = $childProps['Name'];
					$relation->ChildInfo->Type   = $childProps['Type'];
					$relation->ChildInfo->Format = $childProps['Format'];
				}
			}
		}
		return $relations;
	}

	/**
	 * Composes a list of InDesign Article placements under a given xpath (under '/Object').
	 *
	 * Note that an InDesign Article placement can be found either at Object level or at
	 * at Relation level. Since the Relation level is handled elsewhere (by caller) this
	 * function ignores the placements under /Object/Relations/Relation/Placements.
	 *
	 * @param DOMXPath $xpath
	 * @return Placement[]|null
	 */
	private function composeInDesignArticlesPlacements( DOMXPath $xpath )
	{
		$placements = null;
		if( ($xmlArrayOfPlacement = $this->getElement( $xpath, '/textcompose/layout/Object/Placements', null )) ) {
			$placements = array();
			$xmlPlacements = $xpath->query( 'Placement', $xmlArrayOfPlacement );
			if( $xmlPlacements->length > 0 ) foreach( $xmlPlacements as $xmlPlacement ) {
				$placements[] = $this->composePlacement( $xpath, $xmlPlacement );
			}
		}
		return $placements;
	}

	/**
	 * Composes a Placement data object from a given placement xml node
	 *
	 * @param DOMXPath $xpath
	 * @param DOMNode $xmlPlacement
	 * @return Placement
	 */
	private function composePlacement( $xpath, $xmlPlacement )
	{
		$placement = new Placement();
		$placement->Page = $this->getTextValue( $xpath, 'Page', $xmlPlacement );
		$placement->Element = $this->getTextValue( $xpath, 'Element', $xmlPlacement );
		$placement->ElementID = $this->getTextValue( $xpath, 'ElementID', $xmlPlacement );
		$placement->FrameOrder = $this->getTextValue( $xpath, 'FrameOrder', $xmlPlacement );
		$placement->FrameID = $this->getTextValue( $xpath, 'FrameID', $xmlPlacement );
		$placement->Left = $this->getTextValue( $xpath, 'Left', $xmlPlacement );
		$placement->Top = $this->getTextValue( $xpath, 'Top', $xmlPlacement );
		$placement->Width = $this->getTextValue( $xpath, 'Width', $xmlPlacement );
		$placement->Height = $this->getTextValue( $xpath, 'Height', $xmlPlacement );
		$placement->Overset = $this->getTextValue( $xpath, 'Overset', $xmlPlacement );
		$placement->OversetChars = $this->getTextValue( $xpath, 'OversetChars', $xmlPlacement );
		$placement->OversetLines = $this->getTextValue( $xpath, 'OversetLines', $xmlPlacement );
		$placement->Layer = $this->getTextValue( $xpath, 'Layer', $xmlPlacement );
		$placement->Content = $this->getTextValue( $xpath, 'Content', $xmlPlacement );
		$placement->Edition = null;
		if( ($xmlEdition = $this->getElement( $xpath, 'Edition', $xmlPlacement )) ) {
			$placement->Edition = new Edition();
			$placement->Edition->Id = $this->getTextValue( $xpath, 'Id', $xmlEdition );
			$placement->Edition->Name = $this->getTextValue( $xpath, 'Name', $xmlEdition );
		}
		$placement->ContentDx = $this->getTextValue( $xpath, 'ContentDx', $xmlPlacement );
		$placement->ContentDy = $this->getTextValue( $xpath, 'ContentDy', $xmlPlacement );
		$placement->ScaleX = $this->getTextValue( $xpath, 'ScaleX', $xmlPlacement );
		$placement->ScaleY = $this->getTextValue( $xpath, 'ScaleY', $xmlPlacement );
		$placement->PageSequence = $this->getTextValue( $xpath, 'PageSequence', $xmlPlacement );
		$placement->PageNumber = $this->getTextValue( $xpath, 'PageNumber', $xmlPlacement );
		$placement->Tiles = null;
		if( ($xmlArrayOfTile = $this->getElement( $xpath, 'Tiles', $xmlPlacement )) ) {
			$xmlTiles = $xpath->query( 'Tile', $xmlArrayOfTile );
			$placement->Tiles = array();
			if( $xmlTiles->length > 0 ) foreach( $xmlTiles as $xmlTile ) {
				$tile = new PlacementTile();
				$tile->PageSequence = $this->getTextValue( $xpath, 'PageSequence', $xmlTile );
				$tile->Left = $this->getTextValue( $xpath, 'Left', $xmlTile );
				$tile->Top = $this->getTextValue( $xpath, 'Top', $xmlTile );
				$tile->Width = $this->getTextValue( $xpath, 'Width', $xmlTile );
				$tile->Height = $this->getTextValue( $xpath, 'Height', $xmlTile );
				$placement->Tiles[] = $tile;
			}
		}
		$placement->FormWidgetId = $this->getTextValue( $xpath, 'FormWidgetId', $xmlPlacement );
		$placement->InDesignArticleIds = null;
		if( ($xmlArrayOfInDesignArticleId = $this->getElement( $xpath, 'InDesignArticleIds', $xmlPlacement )) ) {
			$placement->InDesignArticleIds = array();
			$xmlIDArticleIds = $xpath->query( 'String', $xmlArrayOfInDesignArticleId );
			if( $xmlIDArticleIds->length > 0 ) foreach( $xmlIDArticleIds as $xmlIDArticleId ) {
				$placement->InDesignArticleIds[] = $this->getTextValue( $xpath, '', $xmlIDArticleId );
			}
		}
		$placement->FrameType = $this->getTextValue( $xpath, 'FrameType', $xmlPlacement );
		$placement->SplineID = $this->getTextValue( $xpath, 'SplineID', $xmlPlacement );
		return $placement;
	}

	/**
	 * Resolves the text of a given XML node.
	 *
	 * @param DOMXPath $xpath
	 * @param string $path
	 * @param DOMNode|null $contextNode Parent node, or NULL to specify $path from doc root.
	 * @return string
	 */
	private function getTextValue( DOMXPath $xpath, $path, $contextNode )
	{
		$retVal = null;
		$node = null;
		if( $path ) {
			$nodeList = $xpath->query( $path, $contextNode );
			if( $nodeList->length > 0 ) {
				$node = $nodeList->item(0);
			}
		} else {
			$node = $contextNode;
		}
		if( $node ) {
			if( !$this->hasNilAttribute( $node ) ) {
				$retVal = '';
				$texts = $xpath->query( 'text()', $node );
				if( $texts->length > 0 ) {
					$retVal = (string)$texts->item(0)->nodeValue;
				}
			}
		}
		return $retVal;
	}

	/**
	 * Returns a child node.
	 *
	 * @param DOMXPath $xpath
	 * @param string $path
	 * @param DOMNode|null $contextNode Parent node, or NULL to specify $path from doc root.
	 * @return DOMNode|null Found node. NULL when not present or when xsi:nil is set.
	 */
	private function getElement( DOMXPath $xpath, $path, $contextNode )
	{
		$retVal = null;
		$nodeList = $xpath->query( $path, $contextNode );
		if( $nodeList->length > 0 ) {
			$node = $nodeList->item(0);
			if( !$this->hasNilAttribute( $node ) ) {
				$retVal = $node;
			}
		}
		return $retVal;
	}

	/**
	 * Tells whether or not the xsi:nil attribute is set for a give XML node.
	 *
	 * @param DOMNode $node
	 * @return bool
	 */
	private function hasNilAttribute( DOMNode $node )
	{
		$hasNil = false;
		if( $node->attributes->length > 0 ) {
			$xmlNil = $node->attributes->getNamedItem( 'nil' );
			$hasNil = $xmlNil ? $xmlNil->nodeValue == 'true' : false;
		}
		return $hasNil;
	}

	/**
	 * Reads layout info and request info from given compose base XML file.
	 * This is used to find out this info from the previous preview operation.
	 *
	 * @param object $layoutProps Returns the layout object ID, Name and Version.
	 * @param object $requestParams Returns the requested EditionId, PreviewType and ExportType.
	 */
	private function getLayoutPropsAndRequestParamsFromComposeData( &$layoutProps, &$requestParams )
	{
		$layoutProps = null;
		$requestParams = null;
		$composeXmlFile = $this->workspace->WebEditor.'compose_base.xml';
		if( file_exists( $composeXmlFile ) ) {
			$composeXmlStr = file_get_contents( $composeXmlFile );
			if( $composeXmlStr ) {
				$composeXmlDom = new DOMDocument();
				$composeXmlDom->loadXML( $composeXmlStr );
				$xpath = new DOMXPath( $composeXmlDom );
				$layoutNodes = $xpath->query('/textcompose/context/layout');
				if( $layoutNodes && $layoutNodes->length > 0 ) {
					$layoutNode = $layoutNodes->item(0);
					$layoutProps = new stdClass();
					$layoutProps->ID = $layoutNode->getAttribute('id');
					$layoutProps->Name = $layoutNode->getAttribute('name');
					$layoutProps->Version = $layoutNode->getAttribute('version');
				}
				$contextNodes = $xpath->query('/textcompose/context');
				if( $contextNodes && $contextNodes->length > 0 ) {
					$contextNode = $contextNodes->item(0);
					$requestParams = new stdClass();
					$requestParams->EditionId = $contextNode->getAttribute('editionid');
					$requestParams->PreviewType = $contextNode->getAttribute('previewtype');
					$requestParams->ExportType = $contextNode->getAttribute('exporttype');
				}
			}
		}
	}

	/**
	 * Returns the PDF file path that resides in the workspace. (The file is generated by InDesign Server
	 * before through the PreviewArticleAtWorkspace service.) The PDF file contains all pages on which
	 * the article is placed.
	 *
	 * @param string $workspaceId
	 * @param integer $layoutId
	 * @param integer $editionId
	 * @param boolean $webEditorDir True for webEditor perspective, False for IDS perspective
	 * @return string Full local file path of the PDF file.
	 */
	public function getPdfPath( $workspaceId, $layoutId, $editionId, $webEditorDir=true )
	{
		// Validate client input parameters
		$this->validateWorkspaceId( $workspaceId );
		$this->validateId( $layoutId, 'Layout' );
		$this->validateId( $editionId, 'Edition' );

		// Get workspace data from db
		$this->openWorkspaceAtDb( $workspaceId );

		// Build and return file path
		$objectId = $layoutId ? $layoutId : 'article';
		$workspaceDir = $webEditorDir ? $this->workspace->WebEditor : $this->workspace->InDesignServer;
		$pdfPath = $workspaceDir;
		if( $editionId ) {
			$pdfPath .= $editionId . '/';
		}
		$pdfPath .= $objectId.'.pdf';
		LogHandler::Log( 'WebEditWorkspace', 'DEBUG', __METHOD__.' pdfPath = "'.$pdfPath.'".' );
		return $pdfPath;
	}

	/**
	 * Returns the preview file path that resides in the workspace. (The file is generated by InDesign Server
	 * before through the PreviewArticleAtWorkspace service.). The preview file is in JPEG format and
	 * contains just one of the pages on which the article is placed.
	 *
	 * @param string $workspaceId
	 * @param integer $layoutId
	 * @param integer $editionId
	 * @param integer $pageSequence
	 * @param boolean $webEditorDir True for webEditor perspective, False for IDS perspective
	 * @return string Full local file path of the preview file.
	 */
	public function getPreviewPath( $workspaceId, $layoutId, $editionId, $pageSequence, $webEditorDir=true )
	{
		// Validate client input parameters
		$this->validateWorkspaceId( $workspaceId );
		$this->validateId( $layoutId, 'Layout' );
		$this->validateId( $editionId, 'Edition' );
		$this->validatePageSequence( $pageSequence );

		// Get workspace data from db
		$this->openWorkspaceAtDb( $workspaceId );

		// Build and return file path
		$pageSequence = $pageSequence == 0 ? '' : $pageSequence;
		$objectId = $layoutId ? $layoutId : 'article';
		$workspaceDir = $webEditorDir ? $this->workspace->WebEditor : $this->workspace->InDesignServer;
		$previewPath = $workspaceDir;
		if( $editionId ) {
			$previewPath .= $editionId . '/';
		}
		$previewPath .= $objectId.$pageSequence.'.jpg';
		LogHandler::Log( 'WebEditWorkspace', 'DEBUG', __METHOD__.' previewPath = "' .$previewPath. '".' );
		return $previewPath;
	}

	// ------------------------------------------------------------------
	// DATABASE HANDLING
	// ------------------------------------------------------------------

	/**
	 * Refer to createArticleWorkspace().
	 *
	 * For existing articles, the $id can be specified while leaving $content set to null.
	 * In that case, the content will be automatically resolved from the filestore and the
	 * $content will be automatically populated with the DB content ($article->Content).
	 *
	 * When the $content is provided by caller, it is assumed to be the user typed content
	 * so it remains unchanged. That content should go into the workspace while the content
	 * stored in filestore won't be resolved and so $article->Content remains unset.
	 *
	 * @param int $id
	 * @param string $format For file extension. supported one is 'application/incopyicml'
	 * @param string|null $content Article content when provided. Null to auto resolve it from filestore.
	 * @throws BizException
	 * @return stdClass The article.
	 */
	private function createWorkspaceAtDb( $id, $format, &$content )
	{
		// save into DB
		$this->workspace = new stdClass();
		$this->enrichWorkspace();
		$updatedVersion = false;
		$article = $this->openArticleAtWorkspace( $id, $format, $content, $updatedVersion );

		// Store session details in DB
		DBAppSession::createSession( $this->workspace );
		if( DBAppSession::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAppSession::getError() );
		}
		return $article;
	}

	/**
	 * Retrieves workspace data from DB (smart_appsession table).
	 * Workspace data is enriched with defaults.
	 *
	 * @param string $workspaceId
	 */
	private function openWorkspaceAtDb( $workspaceId )
	{
		// Get workspace data from db
		if( !$this->workspace || $this->workspace->ID != $workspaceId ) { // avoid getting same workspace again
			$this->workspace = DBAppSession::getSession( $workspaceId );
		}
		$this->enrichWorkspace();
	}

	/**
	 * Updates an existing/pending workspace in the database.
	 * Errors when error is no longer valid in db.
	 */
	private function saveWorkspaceAtDb()
	{
		// Update workspace record at db
		$this->workspace->LastSaved = date('Y-m-d\TH:i:s');
		DBAppSession::updateSession( $this->workspace );
		if( DBAppSession::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAppSession::getError() );
		}
	}

	/**
	 * Delete the workspace folder and session data in DB
	 *
	 * @param string $workspaceId
	 * @throws BizException
	 */
	private function closeWorkspaceAtDb( $workspaceId )
	{
		// Remove workspace record from db
		DBAppSession::deleteSession( $workspaceId );
		if( DBAppSession::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAppSession::getError() );
		}
	}

	/**
	 * Get all pending workspace session data from DB
	 *
	 * @param string $userId
	 * @param string $appName
	 * @throws BizException
	 * @return string[] List of workspace session ids.
	 */
	private function getWorkspaceIdsAtDb( $userId, $appName )
	{
		$workspaceIds = DBAppSession::findSessionIds( $userId, $appName );
		if( DBAppSession::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAppSession::getError() );
		}
		return $workspaceIds;
	}

	/**
	 * Retrieves the article from filestore in case there is a newer version available.
	 * The $this->workspace information gets updated with the new found version.
	 * If the article was not in the workspace yet, it is retrieved from filestore as well.
	 * For new articles a dummy placeholder is created instead.
	 *
	 * Article content will be read from filestore when the article was not yet added to
	 * the workspace before, or when a newer version is available in the filestore
	 * (than present in workspace). In other cases, content is read from workspace.
	 *
	 * @param integer $id
	 * @param string $format
	 * @param string $content Last article content.
	 * @param boolean $updatedVersion Whether or not a newer version from filestore was retrieved.
	 * @return stdClass The article.
	 */
	private function openArticleAtWorkspace( $id, $format, &$content, &$updatedVersion )
	{
		// The workspace record ($this->workspace) can originate (be read) from DB,
		// or it can be added by the client on-the-fly.
		// As long as the article is not created, the $id is null.

		// Lookup the article in the workspace.
		$article = null;
		foreach( $this->workspace->Articles as $articleIter ) {
			if( $articleIter->ID == $id ) {
				$article = $articleIter;
				break;
			}
		}

		// If not in workspace, create dummy article and add to workspace.
		if( is_null($article) ) {
			$article = new stdClass();
			$article->ID = null;
			$article->Name = null;
			$article->Format = null;
			$article->Version = null;
			$this->workspace->Articles[] = $article;
		}

		if( is_null($id) ) { // new article?
			$article->Name = 'article';
		} else { // existing article?
			require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
			$request = new WflGetObjectsRequest( BizSession::getTicket() );

			if( $article->Version ) {
				$haveVersion = new ObjectVersion();
				$haveVersion->ID = $id;
				$haveVersion->Version = $article->Version;
				$request->HaveVersions = array( $haveVersion );
				$request->IDs = array();
			} else {
				$request->HaveVersions = null;
				$request->IDs = array( $id );
			}
			$request->Lock = false;
			$request->Rendition = 'native';
			$request->RequestInfo = array( 'MetaData' );

			$service  = new WflGetObjectsService();
			$response = $service->execute( $request );
			$object  = $response->Objects[0];
			$md = $object->MetaData;

			$article->ID      = $md->BasicMetaData->ID;
			$article->Name    = $md->BasicMetaData->Name;
			$article->Format  = $md->ContentMetaData->Format;
			$article->Version = $md->WorkflowMetaData->Version;

			$updatedVersion = isset($object->Files[0]);
			if( $updatedVersion ) {
				$transferSvr = new BizTransferServer();
				$content = $transferSvr->getContent( $object->Files[0] );
				$transferSvr->deleteFile( $object->Files[0]->FilePath );
			}
		}
		if( $format ) {
			$article->Format = $format;
		}
		if( !$content ) {
			$content = $this->getArticleFromFileSystem( $article );
		}
		return $article;
	}

	/**
	 * Enrich workspace data, typically called after open/create operations.
	 */
	private function enrichWorkspace()
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';

		if( !isset($this->workspace->ID) || !$this->workspace->ID ) {
			require_once BASEDIR.'/server/appservices/textconverters/InCopyTextUtils.php';
			$this->workspace->ID = InCopyUtils::createGUID(); // Make up new workspaceId
		}
		if( !isset($this->workspace->UserID) || !$this->workspace->UserID ) {
			$this->workspace->UserID = BizSession::getShortUserName();
		}
		if( !isset($this->workspace->AppName) || !$this->workspace->AppName ) {
			$this->workspace->AppName = DBTicket::DBappticket( BizSession::getTicket() );
		}
		if( !isset($this->workspace->LastSaved) || !$this->workspace->LastSaved ) { // init only
			$this->workspace->LastSaved = date('Y-m-d\TH:i:s');
		}
		if( !isset($this->workspace->ReadOnly) ) {
			$this->workspace->ReadOnly = false;
		}
		if( !isset($this->workspace->DOMVersion) ) {
			$this->workspace->DOMVersion = '0'; // resolved later at storeArticleAtFileSystem()
		}
		$this->workspace->WebEditor = WEBEDITDIR.$this->workspace->ID.'/';
		$this->workspace->InDesignServer = WEBEDITDIRIDSERV.$this->workspace->ID.'/';
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Workspace data: '.print_r($this->workspace,true) );
		}
		if( !isset($this->workspace->Articles) ) {
			$this->workspace->Articles = array();
		}
	}

	/**
	 * Validates the given workspace for the minimum data set.
	 * Basically, it validates record data before it gets saved into DB or after retrieval from DB.
	 * This is just to monitor if the data in DB is stored well. It does NOT protect against hackers!
	 * Therefor, it validates in DEBUG mode only.
	 *
	 * @throws BizException on validation error.
	 */
	private function validateWorkspaceAtDb()
	{
		// Only validate DB input/ouput in DEBUG mode; See function header for more details.
		if( !LogHandler::debugMode() ) {
			return;
		}
		if( !$this->workspace->ID ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Workspace has no ID.' );
		}
		if( !$this->workspace->AppName ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Workspace has no AppName: '.$this->workspace->ID );
		}
		if( !$this->workspace->UserID ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Workspace has no UserID: '.$this->workspace->ID );
		}
		if( !$this->workspace->LastSaved ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Workspace has no LastSaved: '.$this->workspace->ID );
		}
		if( !$this->workspace->DOMVersion ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Workspace has no DOMVersion: '.$this->workspace->ID );
		}
		if( count( $this->workspace->Articles ) == 0 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Workspace has no articles: '.$this->workspace->ID );
		}
		foreach( $this->workspace->Articles as $article ) {
			if( !$article->Name ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'Workspace has no Article->Name: '.$this->workspace->ID );
			}
			if( !$article->Format ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'Workspace has no Article->Format: '.$this->workspace->ID );
			}
		}
	}

	/**
	 * Validates workspace ID.
	 * When empty or invalid workspace ID is detected,
	 * this function throws BizException.
	 *
	 * @param string $workspaceId Unique GUID workspace Id to be validated.
	 * @throws BizException on validation error.
	 */
	private function validateWorkspaceId( $workspaceId )
	{
		if( !$workspaceId ){
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'No WorkspaceId given.' );
		}

		require_once BASEDIR . '/server/utils/NumberUtils.class.php';
		if( !NumberUtils::validateGUID( $workspaceId ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Illegal WorkspaceId given: "'.$workspaceId.'".' );
		}

		// Validate folder at workspace
		$workspaceDir = WEBEDITDIR.$workspaceId;
		if( !file_exists( $workspaceDir )) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid WorkspaceId given: "'.$workspaceId.'". '.
				'There is no such workspace folder: "'.$workspaceDir.'".' );
		}
		// Note: We can not validate $workspace->InDesignServer since that is from InDesign Server
		//       perspective and so it might not be accessible from our perspective.
	}

	/**
	 * Validates article format.
	 * When empty format or if the format is not 'application/incopyicml',
	 * this function throws BizException.
	 *
	 * @param string $format Format of the article to be validated.
	 * @throws BizException on validation error.
	 */
	private function validateArticleFormat( $format )
	{
		if( !$format ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'No Format given.' );
		}
		if( $format != 'application/incopyicml' && $format != 'application/incopy' ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid Format given: "'.$format.'". '.
				'Should be either "application/incopyicml" or "application/incopy".' );
		}
	}

	/**
	 * Validates several types of Id depending on $column that is passed in.
	 * When the id passed in is not null, this function checks whether it is
	 * a numeric Id, if it is not, it throws BizException.
	 *
	 * @param integer $id DB id to be validated.
	 * @param string $column The "field name" id that is validated. E.g. "Layout", "Edition", "Article"
	 * @throws BizException on validation error.
	 */
	private function validateId( $id, $column )
	{
		if( $column == 'Article' ) {
			// Because articles can be Alien objects, we can not check object ID for being numeric.
			if( is_null( $id ) ) { // Article is mandatory
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'Passed in null for article ID, which is not allowed.' );
			}
		} else { // Here we check LayoutId or EditionId
			if( is_null( $id ) ){
				return;
			}else{
				if( !is_int( $id ) || $id <= 0 ) {
					throw new BizException( 'ERR_ARGUMENT', 'Server', 'Workspace has invalid  '.$column.' ID: ' . $id );
				}
			}
		}
	}

	/**
	 * This function validates by ensuring there's only
	 * EITHER elements or content is allowed at one time.
	 * When both are found to be filled, this function
	 * throws BizException.
	 *
	 * @param array $elements array of Element object
	 * @param string $content Story contents
	 * @throws BizException on validation error.
	 */
	private function validateElementsContent( $elements, $content )
	{
		if( count( $elements ) > 0 && strlen($content) > 0 ){
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Elements and content found in workspace article, only ' .
														'one component is allowed at a time.' );
		}
	}

	/**
	 * Errors when given $action is not 'Compose', 'Preview' or' PDF'.
	 *
	 * @param string $action
	 * @throws BizException on validation error.
	 */
	private function validateActionType( $action )
	{
		if( $action != 'Compose' && $action != 'Preview' && $action != 'PDF' ){
			throw new BizException( 'ERR_ARGUMENT', 'Server',
				'The Action parameter is set to "'.$action.'" which is invalid. '.
				'Please use one of the following values: "Compose", "Preview" or "PDF". ' );
		}
	}

	/**
	 * Errors when given $previewType is not 'page', 'spread', '' or null.
	 *
	 * For backwards compatibility reasons and robustness, it auto-repears the $previewType param:
	 * - When $action is not 'Preview', $previewType is set to empty.
	 * - When $action is 'Preview' but previewType is null/empty, $previewType is set to 'page'.
	 *
	 * @param string $action
	 * @param string|null $previewType
	 * @return string the auto-repeared preview type value.
	 * @throws BizException on validation error.
	 */
	private function validatePreviewType( $action, $previewType )
	{
		if( $action == 'Preview' ) {
			if( !$previewType ) { // null or empty
				$previewType = 'page';
			} elseif( $previewType != 'page' && $previewType != 'spread' ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server',
					'The PreviewType parameter is set to "'.$previewType.'" which is invalid. '.
					'Please use one of the following values: "page" or "spread". ' );
			}
		} else { // Compose, PDF
			$previewType = '';
		}
		return $previewType;
	}

	/**
	 * Throws BizException when page postfix is
	 * not an integer or it is lower or equal to zero.
	 *
	 * @param int $pageSequence
	 * @throws BizException on validation error.
	 */
	private function validatePageSequence( $pageSequence )
	{
		if( !is_int( $pageSequence ) || $pageSequence < 0 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid page postfix is found:' .
															  $pageSequence);
		}
	}

	/**
	 * Verify whether edition id is assigned to the layout.
	 * Throws BizException when:
	 * - Edition($editionId) passed in is not assigned to the layout($layoutId).
	 * - Edition($editionId) passed in is null but there's edition assigned to the layout($layoutId).
	 *
	 * @param integer $layoutId
	 * @param integer $editionId
	 * @throws BizException on validation error.
	 */
	private function validateEditionId( $layoutId, $editionId )
	{
		require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
		$targets = DBTarget::getTargetsByObjectId( $layoutId );
		$found = false;
		$editionExists = false;
		if( $targets ) foreach ($targets as $target) {
			$editions = $target->Editions;
			if( $editions ){
				$editionExists = true;
				foreach( $editions as $edition ){
					if( $edition->Id == $editionId  ){
						$found = true;
						break;
					}
				}
			}
		}
		if( !is_null( $editionId ) && !$found ){
			throw new BizException( null, 'Server', null, 'Invalid Edition is passed in,the edition is not assigned to the layout.' );
		}

		if( is_null( $editionId ) && $editionExists ){
			throw new BizException( null, 'Server', null, 'Layout is assigned to edition(s), but null edition is passed in. ');
		}
	}

	// ------------------------------------------------------------------
	// FILE SYSTEM HANDLING
	// ------------------------------------------------------------------

	/**
	 * Get the article path as stored in the workspace. New articles have no DB ID yet
	 * and therefore the name 'article' must be passed through $artName or from Article->Name.
	 * For existing articles, no name should be provided, and so the article path contains an ID.
	 * @since 8.3.1 No longer using article names in workspace paths. (BZ#33186)
	 *
	 * @param stdClass $article
	 * @return string
	 */
	private function getArticlePath( $article )
	{
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$artId = $article->ID ? $article->ID  : 'article';
		$artExt = MimeTypeHandler::mimeType2FileExt( $article->Format, 'Article' );
		return $this->workspace->WebEditor . $artId . $artExt;
	}

	/**
	 * Returns the DOM Version of the given InCopy WCML document.
	 *
	 * @param DOMDocument $icDoc
	 * @param string $format
	 * @return int The DOM version.
	 */
	private function getDomVersion( DOMDocument $icDoc, $format )
	{
		if( $format == 'application/incopy' ) { // WWCX
			return 6;
		}
		$docDomVersion = 0;
		$icXPath = new DOMXPath( $icDoc );
		$icXPath->registerNamespace('ea', 'urn:SmartConnection_v3');
		$icStoryDocs = $icXPath->query( '/ea:Stories/ea:Story/Document' );

		// Detect the internal document model version
		foreach( $icStoryDocs as $icStoryDoc ) {
			$majorMinor = $icStoryDoc->getAttribute('DOMVersion');
			$regs = array();
			preg_match('/([0-9]+\.[0-9]+)/i', $majorMinor, $regs );
			if( count($regs) > 0 ) {
				$docDomVersion = $regs[1]; // remember for later use
				break; // stop search
			}
		}
		return $docDomVersion;
	}

	/**
	 * Create or Save article content. Updates $this->workspace->DOMVersion.
	 *
	 * When updating an article, this function expects EITHER $elements or $content ONLY.
	 * When $content is passed, the whole(full) story get replaced into the workspace article.
	 * The full story is normally made up of one or multiple stories.
	 *
	 * When $elements is passed, only the modified story(ies) are passed. It will call parseChangedStories()
	 * which will parse $elements (changed stories) and update them into the workspace article.
	 * parseChangedStories() finds the corresponding changed story by looking up for the unique GUID
	 * in the workspace article. Once found, the new story is placed into the workspace article.
	 *
	 *
	 * @param bool $save Save when TRUE, Create when False.
	 * @param stdClass $article
	 * @param array $elements Changed story(ies)(in XML Strings) from the original document.
	 * @param string $content Story contents
	 * @throws BizException
	 */
	private function storeArticleAtFileSystem( $save, $article, $elements, $content )
	{
		// create physical directory
		if( !$save ) {

			// Implicity create WebEdit root folder when missing
			$this->createWorkspaceDir( WEBEDITDIR );

			// Create workspace folder (under WebEdit root folder)
			$workspaceDir = $this->workspace->WebEditor;
			if( file_exists( $workspaceDir ) ) { // should not happen
				throw new BizException( null, 'Server', null,
					'Workspace folder already exists: "'.$workspaceDir.'".' );
			}
			$this->createWorkspaceDir( $workspaceDir );
		}

		// Determine article path
		$artPath = $this->getArticlePath( $article );

		// since 9.4: If it's about a save it could also mean that the article needs to be created, so we flip the flag.
		if( $save ){
			if( !file_exists( $artPath ) ){
				$save = false;
			}
		}

		// When a new article was first created in workspace, then only user saved article
		// into DB, the article should no longer be named as 'article', so need to rename.
		if( $save && $article->Name != 'article' ) {

			// Get the file path of new article (named 'article') which was not saved into DB.
			$newArticle = clone( $article );
			$newArticle->ID = 0;
			$newArticlePath = $this->getArticlePath( $newArticle );

			if( file_exists( $newArticlePath ) ) {
				rename( $newArticlePath, $artPath );
			}
		}
		if( $save && // we need the article at workspace
			!file_exists( $artPath ) ) { // should not happen
			throw new BizException( null, 'Server', null,
				'Could not find article at workspace: "'.$artPath.'".' );
		}

		// Parse the latest article content.
		$icDoc = new DOMDocument();
		$icDoc->loadXML( $content );

		// Update the article content with latest changes.
		if( $elements ) {
			$this->parseChangedStories( $icDoc, $article->Format, $elements );
		}

		// Determine the DOM version of the article.
		$this->workspace->DOMVersion = $this->getDomVersion( $icDoc, $article->Format );
		if( !$this->workspace->DOMVersion ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Could not determine DOM version at '.
				'article document for workspace: "'.$this->workspace->ID.'".' );
		}
		LogHandler::Log( 'BizWebEditWorkspace', 'INFO', 'Found DOMVersion "'.$this->workspace->DOMVersion.'"'.
						' for workspace ID "'.$this->workspace->ID.'".' );

		// Save the changed content into article file
		file_put_contents( $artPath, $icDoc->saveXML() ); // BZ#24337/BZ#24387 $icDoc->save() does an implicit URL decode!
		$oldUmask = umask(0); // Needed for mkdir, see http://www.php.net/umask
		chmod($artPath, 0777);
		umask($oldUmask);
	}

	/**
	 * Creates or overwrites the <id>_delta.wcml file in the workspace based on changed stories (elements).
	 *
	 * The delta file contains the dirty stories since the last CS preview request only.
	 * After each preview operation, this file is cleared/overwritten. Note that this file
	 * is later used by IDPreview.js to reflect these text changes in the layout at workspace.
	 *
	 * @param stdClass $article
	 * @param array|null $elements The changed stories.
	 */
	private function updateDeltaArticleFile( $article, $elements )
	{
		// Compose a delta story, which contains the changed stories since last preview operation.
		$deltaContent = '<?xml version="1.0"?>'."\r\n";
		$deltaContent .= '<ea:Stories xmlns:aic="http://ns.adobe.com/AdobeInCopy/2.0" xmlns:ea="urn:SmartConnection_v3" ea:WWVersion="2.0">'."\r\n";
		if( $elements ) foreach( $elements as $element ) {
			$content = $element->Content;
			// Skip intermediate xml headers since the xml file already has one
			if( substr( $content, 0, 6 ) == '<?xml ' ) {
				$endMarker = strpos( $content, '?>' );
				if( $endMarker !== false ) {
					$content = substr( $content, $endMarker + 2 );
				}
			}
			$deltaContent .= $content."\r\n";
		}
		$deltaContent .= '</ea:Stories>'."\r\n";

		// Save the delta stories into the workspace.
		$deltaFile = $this->workspace->WebEditor.'/'.$article->ID.'_delta.wcml';
		file_put_contents( $deltaFile, $deltaContent );
		$oldUmask = umask(0); // Needed for mkdir, see http://www.php.net/umask
		chmod($deltaFile, 0777);
		umask($oldUmask);
	}

	/**
	 * Removes the <id>_delta.wcml file from the workspace.
	 *
	 * This is an indicator for SCE to use the full article WCML only.
	 *
	 * @param stdClass $article
	 */
	private function deleteDeltaArticleFile( $article )
	{
		$deltaFile = $this->workspace->WebEditor.'/'.$article->ID.'_delta.wcml';
		if( file_exists( $deltaFile ) ) {
			unlink( $deltaFile );
		}
	}

	/**
	 * Creates a folder when not exists. Used to create or extend the workspace folder.
	 *
	 * @param string $workspaceDir Full path of the folder to be created.
	 * @throws BizException
	 */
	private function createWorkspaceDir( $workspaceDir )
	{
		if( !file_exists( $workspaceDir ) ) {
			$oldUmask = umask(0); // Needed for mkdir, see http://www.php.net/umask
			if( !mkdir( $workspaceDir, 0777 ) ) { // should not happen
				throw new BizException( null, 'Server', null, 'Could not create workspace folder: '.
					'"'.$workspaceDir.'". Make sure folder access rights are set for web user.' );
			}
			chmod($workspaceDir, 0777);	 // We cannot alway set access with mkdir because of umask
			umask($oldUmask);
		}
	}

	/**
	 * Read the article file from workspace
	 *
	 * @param stdClass $article
	 * @throws BizException
	 * @return string The article content.
	 */
	private function getArticleFromFileSystem( $article )
	{
		// Determine the article path at workspace
		$artPath = $this->getArticlePath( $article );
		if( !file_exists( $artPath ) ) { // we need the article at workspace
			throw new BizException( null, 'Server', null,
				'Could not find article at workspace: "'.$artPath.'".' ); // should not happen
		}

		// Read article document (XML) and return contents to caller
		return file_get_contents( $artPath );
	}


	/**
	 * Delete article and remove directory.
	 */
	private function deleteArticlesAtFileSystem()
	{
		// Remove workspace folder from workspace (fail when not exist or can not remove)
		$workspaceDir = $this->workspace->WebEditor;
		if( !file_exists( $workspaceDir )) {
			throw new BizException( null, 'Server', null,
				'Workspace folder does not exist: "'.$workspaceDir.'".' );
		}

		// Delete article files and directory
		foreach( $this->workspace->Articles as $article ) {
			$artPath = $this->getArticlePath( $article );
			if( unlink( $artPath ) ){ // when manage to clear file, only proceed to remove dir
				require_once BASEDIR . '/server/utils/FolderUtils.class.php';
				if( !FolderUtils::cleanDirRecursive( $workspaceDir ) ) {
					throw new BizException( null, 'Server', null,
						'Could not remove workspace folder: "'.$workspaceDir.'".' );
				}
			} else {
				throw new BizException( null, 'Server', null,
					'Could not remove file "'. $artPath . '".');
			}
		}
	}

	/**
	 * Refer to storeArticleAtFileSystem() function header
	 *
	 * @param DOMDocument $icDoc InCopy document from workspace
	 * @param string $format
	 * @param array $elements Updated portion of elements (XML Strings) in the original document.
	 */
	private function parseChangedStories( $icDoc, $format, $elements )
	{
		require_once BASEDIR.'/server/appservices/textconverters/InCopyTextUtils.php';
		foreach( $elements as $element ){
			// Convert XML strings into DOMDocs to able to Xpath/query data
			$domElement = new DOMDocument();
			$domElement->loadXML( $element->Content );

			// Get the original story from $icDoc(where all the Stories reside)
			$xpath = new DOMXPath($icDoc);
			if( $format == 'application/incopy' ) { // WWCX
				$storyGuid = $domElement->documentElement->getAttribute( 'GUID' ); // documentElement = Story
				$icStories = $xpath->query( '//Stories/Story[@GUID="'.$storyGuid.'"]' );
			} else { // WCML
				$xpath->registerNamespace('ea', "urn:SmartConnection_v3");
				$storyGuid = $domElement->documentElement->getAttribute( 'ea:GUID' ); // documentElement = Story
				$icStories = $xpath->query( '//ea:Stories/ea:Story[@ea:GUID="'.$storyGuid.'"]' );
			}
			if( $icStories->length != 1 ) {
				LogHandler::Log('BizWebEditWorkspace', 'ERROR', 'Corrupted article document: ' .
					'Expected exactly one GUID "'.$storyGuid.'" in one story but found '.$icStories->length.
					' stories. Article format is "'.$format.'".' );
				continue;
			}

			// 'Introduce'(import) the changedStory into original story.
			$changedStory = $icDoc->importNode( $domElement->documentElement, true);
			$icStory = $icStories->item(0);
			// Replace the changed story into the original story.
			$icStory->parentNode->replaceChild( $changedStory, $icStory );

			/*
			// Commented out since it is assumed that ContentStation will do the version GUID update.
			// And so the server doesn't need to generate the version GUID do the update (as done below).
			$newVersionGuid = InCopyUtils::createGUID();
			// replacing version GUID for WoodWing
			if( $format == 'application/incopy' ) { // WWCX
				$versionGuidWW = $xpath->query( 'StoryInfo/SI_Version/text()', $changedStory );
			} else { // WCML
				$versionGuidWW = $xpath->query( 'ea:StoryInfo/ea:SI_Version/text()', $changedStory );
			}
			if( $versionGuidWW->length != 1 || empty( $versionGuidWW->item(0)->textContent ) ){
				LogHandler::Log('BizWebEditWorkspace', 'ERROR', 'Corrupted article document: Could not find version GUID at article document' );
				continue;
			}
			$icNewVersionInfo = $icDoc->createTextNode( $newVersionGuid );
			LogHandler::Log('BizWebEditWorkspace', 'DEBUG', 'Updating StoryInfo->SI_Version version GUID "' .
								$versionGuidWW->item(0)->textContent . '" with new GUID "' . $newVersionGuid . '" for updated element in story.');
			$versionGuidWW->item(0)->parentNode->replaceChild( $icNewVersionInfo, $versionGuidWW->item(0) );

			// replacing version GUID for Adobe
			$versionGuidAdobe = $xpath->query( 'Document/Story', $changedStory );
			if( $versionGuidAdobe->length != 1 ){
				LogHandler::Log('BizWebEditWorkspace', 'ERROR', 'Corrupted data: Expected exactly one Document/Story in one story but found:'.
																$versionGuidAdobe->length );
				continue;
			}
			$versionGuidAdobe->item(0)->setAttribute( 'VersionGuid', $newVersionGuid );*/
		}
	}

	/**
	 * Compares the composed relations/placements and the stored relations/placements to determine if a total rebuild
	 * of the stored data is needed.
	 *
	 * Rebuild is needed if for example frame Ids are changed or InDesign Articles are changed or the number of stored
	 * relations and composed relations are not the same. If only a 'harmless' property of a placement is changed then
	 * the placement is just updated. E.g. if some text is added to an article component the underset/overset changes
	 * and there is no need to rebuild all relations and placements.
	 * First the stored relations are enriched with potential InDesignArticle Ids. Next structures are made of unique
	 * placements. Both for the composed data as the stored data. Finally these structures are compared to see if a
	 * rebuild is needed.
	 *
	 * @since 10.1.2
	 * @param int $layoutId The layout of the preview.
	 * @param Relation[]|null $composedRelations The relations as retrieved from the composed data.
	 * @return bool $rebuildNeeded
	 */
	private function isRebuildStoredRelationsPlacementsNeeded( $layoutId, $composedRelations )
	{
		PerformanceProfiler::startProfile( 'Preview_Check_On_Placements', 5 );
		$rebuildNeeded = false;
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		$storedRelations = BizRelation::getObjectRelations( $layoutId, true, false, null, false, false, 'Placed' );
		require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
		$storedInDesignArticlePlacements = DBPlacements::getPlacements( $layoutId, 0, 'Placed');
		$storedRelationsWithIDSArticles = $this->addInDesignArticleToRelations( $storedRelations, $storedInDesignArticlePlacements );
		$storedPlacementsByKey = $this->extractPlacementsByKeyFromRelations( $storedRelationsWithIDSArticles );
		$composedPlacementsByKey = $this->extractPlacementsByKeyFromRelations( $composedRelations );
		if( $this->numberOfPlacementsDiffer( $storedPlacementsByKey, $composedPlacementsByKey )) {
			$rebuildNeeded = true;
		} elseif( !$this->compareAndRepairPlacementProperties( $storedPlacementsByKey, $composedPlacementsByKey ) ) {
			$rebuildNeeded = true;
		}
		PerformanceProfiler::stopProfile( 'Preview_Check_On_Placements', 5 );

		return $rebuildNeeded;
	}

	/**
	 * Fills InDesignArticleIds of the placements of stored relations.
	 *
	 * The (stored) relations and the placements have the same parent. Based on the frame id and the edition a link is made.
	 *
	 * @since 10.1.2
	 * @param Relation[] $storedRelations
	 * @param Placement[] $inDesignArticlePlacements
	 * @return Relation[] $storedRelations enriched with ArticleIds
	 */
	private function addInDesignArticleToRelations( $storedRelations , $inDesignArticlePlacements )
	{
		require_once BASEDIR.'/server/bizclasses/BizWebEditWorkspace/ComparePlacements.class.php';
		if( $storedRelations ) foreach( $storedRelations as $storedRelation ) {
			if( $storedRelation->Placements ) foreach( $storedRelation->Placements as $key => $placement ) {
				if( $inDesignArticlePlacements ) foreach( $inDesignArticlePlacements as $inDesignArticlePlacement ) {
					$comparePlacements = new BizWebEditWorkspace_ComparePlacements();
					if( $comparePlacements->sameStrings( $inDesignArticlePlacement->FrameID, $placement->FrameID) &&
						 $comparePlacements->sameEdition( $inDesignArticlePlacement->Edition, $placement->Edition ) ) {
							$storedRelation->Placements[$key]->InDesignArticleIds = $inDesignArticlePlacement->InDesignArticleIds;
							break;
					}
				}
			}
		}

		return $storedRelations;
	}

	/**
	 * Relations with placements are transformed to an array with with unique placements.
	 *
	 * Placements are unique by the parent/child/type/frameid/edition. From these attributes a unique key is made. The
	 * value of the key is the placement object itself.
	 *
	 * @since 10.1.2
	 * @param Relation[]|null $relations
	 * @return Placement[] List of unique placements.
	 */
	private function extractPlacementsByKeyFromRelations( $relations )
	{
		$placements = array();
		if( $relations ) foreach ( $relations as $relation ) {
			$relationKey = strval( $relation->Parent ).self::SEPARATOR.strval( $relation->Child ).self::SEPARATOR.strval( $relation->Type );
			if( $relation->Placements ) foreach( $relation->Placements as $placement ) {
				$key = $relationKey;
				if( empty( $placement->FrameID ) ) {
					$key .= self::SEPARATOR.'0';
				} else {
					$key .= self::SEPARATOR.strval( $placement->FrameID );
				}
				if( isset( $placement->Edition->Id ) ) {
					$key .= self::SEPARATOR.( $placement->Edition->Id );
				} else {
					$key .= self::SEPARATOR.'0';
				}
				$placements[ $key ] = $placement;
			}
		}

		return $placements;
	}

	/**
	 * Checks if two arrays with placements contain the same number of placements.
	 *
	 * Not only the number of placements must be equal but also the identifiers fo the placements (keys of the arrays).
	 *
	 * @param Placement[] $lhsPlacementsByKey
	 * @param Placement[] $rhsPlacementsByKey
	 * @return bool
	 */
	private function numberOfPlacementsDiffer( $lhsPlacementsByKey, $rhsPlacementsByKey )
	{
		$numberDiffers = false;
		if( !$this->sameEmptiness( $lhsPlacementsByKey, $rhsPlacementsByKey ) ) {
			$numberDiffers = true;
		} elseif( is_array( $lhsPlacementsByKey ) && is_array( $rhsPlacementsByKey ) ) {
			if( !empty( array_diff_key( $lhsPlacementsByKey, $rhsPlacementsByKey ) ) ||
				!empty( array_diff_key( $rhsPlacementsByKey, $lhsPlacementsByKey ) ) ) {
				$numberDiffers = true;
			}
		}

		return $numberDiffers;
	}

	/**
	 * Checks for two arrays with unique placements and repairs small differences.
	 *
	 * If two unique placements differ (stored and composed) it is sometimes possible to just update the stored one with
	 * the changed properties of the composed placement. If the difference is too fundamental no repair action is done.
	 * In case there are only reparable differences the stored placement is updated with the properties of the composed
	 * one.
	 *
	 * @since 10.1.2
	 * @param Placement[] $storedPlacementsByKey
	 * @param Placement[] $composedPlacementsByKey
	 * @return bool true if no differences found or differences could be repaired, else false
	 */
	private function compareAndRepairPlacementProperties( $storedPlacementsByKey, $composedPlacementsByKey )
	{
		$isReparable = true;
		$changedPlacements = array();
		require_once BASEDIR.'/server/bizclasses/BizWebEditWorkspace/ComparePlacements.class.php';
		if( $storedPlacementsByKey ) foreach( $storedPlacementsByKey as $key => $storedPlacementByKey ) {
			$composedPlacementByKey = $composedPlacementsByKey[ $key ];
			$comparePlacements = new BizWebEditWorkspace_ComparePlacements();
			$comparePlacements->comparePlacements( $storedPlacementByKey, $composedPlacementByKey );
			if( $comparePlacements->getElementalDiff() ) {
				$isReparable = false;
				break;
			} elseif( $comparePlacements->getNonElementalDiff() ) {
				$changedPlacements[ $key ] = $composedPlacementByKey;
			}
		}

		if( $isReparable && $changedPlacements ) {
			require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
			foreach( $changedPlacements as $keyString => $placement ) {
				$keyValues = explode( self::SEPARATOR, $keyString );
				$identifier = new stdClass();
				$identifier->Parent = $keyValues[0];
				$identifier->Child = $keyValues[1];
				$identifier->Type = $keyValues[2];
				$identifier->FrameId = ( $keyValues[3] ) ? $keyValues[3] : '';
				$identifier->EditionId = $keyValues[4];
				DBPlacements::updatePlacement( $identifier, $placement );
			}
		}

		return $isReparable;
	}

	/**
	 * Checks if either both values are empty or both are not empty (exclusive or).
	 *
	 * @param mixed $lhs
	 * @param mixed $rhs
	 * @return bool
	 */
	private function sameEmptiness( $lhs, $rhs)
	{
		return empty( $lhs ) == empty( $rhs );
	}

}
