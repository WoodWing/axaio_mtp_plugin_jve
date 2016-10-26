<?php

require_once BASEDIR.'/server/utils/ImageUtils.class.php'; // ResizeJPEG
require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/DataClasses.php';

define( 'MAXPAGEINDEX', 10000000 );

define( 'FLAG_OBJECT_CREATED', 1 );
define( 'FLAG_OBJECT_UPDATED', 2 );
define( 'FLAG_OBJECT_DELETED', 3 );

define( 'FLAG_PLACEMENT_CREATED', 4 );
define( 'FLAG_PLACEMENT_UPDATED', 5 );
define( 'FLAG_PLACEMENT_DELETED', 6 );

class BizPlnObject
{
	static public function createLayouts( $user, $layouts )
	{
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'CreateLayouts started >>>' );

		// Validate required arguments to create a layout.
		foreach( $layouts as $layout ) {
			if( !isset( $layout->NewLayout->Name ) || $layout->NewLayout->Name == '' || !isset( $layout->Template ) || empty( $layout->Template ) ||
				!isset( $layout->NewLayout->Publication ) || $layout->NewLayout->Publication == '' || !isset( $layout->NewLayout->Issue ) || $layout->NewLayout->Issue == '' ) {
				throw new BizException( 'PLAN_CREATE_LAYOUT_FAILED', 'Client', '' );
			}
		}

		// Walk through the given layouts to create.
		$createdLayouts = array();
		foreach( $layouts as $layout ) {
			$layout->NewLayout->Name = mb_substr( $layout->NewLayout->Name, 0, 63, "UTF8" );
			// Properties to set.
			$props = array(
				"name" => $layout->NewLayout->Name,
				"pub_name" => $layout->NewLayout->Publication,
				"issue_name" => $layout->NewLayout->Issue,
				"pubChannelName" => $layout->NewLayout->PubChannel,
				"section_name" => $layout->NewLayout->Section,
				"state_name" => $layout->NewLayout->Status,
				"pages" => $layout->NewLayout->Pages,
				"type" => 'Layout',
				"editions" => $layout->NewLayout->Editions,
				"deadline" => $layout->NewLayout->Deadline
			);

			//BZ#6599 Edition Id's were not assigned to pages so the editions were not written correctly when copying the layout.
			self::convertPublishNamesIntoIds( $props, $user );
			self::resolveEditions( $props['editions'], $props['publication'], $props['issue'], null );
			foreach( $layout->NewLayout->Pages as $currentPage ) {
				if( $props['editions'] && !empty( $currentPage->Edition ) ) foreach( $props['editions'] as $currentEdition ) {
					if( strtolower( $currentEdition->Name ) == strtolower( $currentPage->Edition->Name ) ) {
						$currentPage->Edition->Id = $currentEdition->Id;
					}
				}
			}

			$meta = self::createLayoutFromTemplate( 'LayoutTemplate', $layout->Template, $props, $user );

			$layout->NewLayout->Id = $meta->BasicMetaData->ID;
			$props['id'] = $layout->NewLayout->Id;
			$layout->NewLayout->Version = $meta->WorkflowMetaData->Version;

			// Tell layouter that his/her layout needs to be cuddled since planner has infected layout.
			require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';

			DBObjectFlag::setObjectFlag( $layout->NewLayout->Id, 'Plan System', FLAG_OBJECT_CREATED, 1, BizResources::localize( "OBJ_LAYOUT_CREATED" ) );
			self::resolvePublishData( $props );
			self::copyResolvedPropsFromArray( $layout->NewLayout, $props );

			$createdLayout = new PlnLayout();
			$createdLayout->Id = $layout->NewLayout->Id;
			$createdLayout->Name = $layout->NewLayout->Name;
			$createdLayout->Publication = $layout->NewLayout->Publication;
			$createdLayout->Issue = $layout->NewLayout->Issue;
			$createdLayout->PubChannel = $layout->NewLayout->PubChannel; // Todo return PubChannel or leave it?
			$createdLayout->Section = $layout->NewLayout->Section;
			$createdLayout->Status = $layout->NewLayout->Status;
			$createdLayout->Editions = $layout->NewLayout->Editions;
			$createdLayout->Editions = $layout->NewLayout->Deadline;
			$createdLayout->Version = $layout->NewLayout->Version;
			$createdLayouts[] = $createdLayout;
		}
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'CreateLayouts completed <<<' );
		return $createdLayouts;
	}

	static public function deleteLayouts( /** @noinspection PhpUnusedParameterInspection */
		$user, $layouts )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'DeleteLayouts started >>>' );

		self::checkLayoutIdAndName( $layouts );

		foreach( $layouts as $layout ) {
			$storeName = '';
			$version = '';
			if( !self::resolvePlanObjectProps( $layout->Id, $layout->Name, $layout->Publication, $layout->Issue, 'Layout', $storeName, $version ) ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'Layout:'.$layout->Name );
			}

			// Remove the planned pages.
			require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
			BizPage::cleanPages( null, $layout->Id, 'Planning', $version );
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			DBObject::updatePageRange( $layout->Id, '', 'Planning' );

			// Tell layouter that his/her layout needs to be cuddled since planner has infected layout.
			$sMsg = BizResources::localize( 'PLAN_MESS_LAYOUT_DELETED', true, array( $layout->Name ) );
			self::sendMessage( $layout->Id, $sMsg, 'Info', 'DeleteLayouts' );
			DBObjectFlag::setObjectFlag( $layout->Id, 'Plan System', FLAG_OBJECT_DELETED, 1, $sMsg );
		}
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'DeleteLayouts completed <<<' );
	}

	static private function checkLayoutIdAndName( $layouts )
	{
		foreach( $layouts as &$layout ) {
			$layout->Id = trim( $layout->Id );
			$layout->Name = trim( $layout->Name );
			if( !self::isValidId( $layout->Id ) && ( !$layout->Name ) ) {
				throw new BizException( 'PLAN_MODIFY_LAYOUT_FAILED', 'Client', '' );
			}
		}
	}

	static public function modifyLayouts( $user, $layouts )
	{
		require_once BASEDIR.'/server/bizclasses/BizStorage.php'; // StorageFactory
		require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'ModifyLayouts started >>>' );

		self::checkLayoutIdAndName( $layouts );

		$modifiedLayouts = array();
		foreach( $layouts as $layout ) {
			$storeName = '';
			$oldVersion = '';
			if( !self::resolvePlanObjectProps( $layout->Id, $layout->Name, $layout->Publication, $layout->Issue, 'Layout', $storeName, $oldVersion ) ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'Layout:'.$layout->Name );
			}

			$lay_props = array();
			isset( $layout->Id ) ? $lay_props['id'] = $layout->Id : $lay_props['id'] = '' ;
			isset( $layout->Name ) ? $lay_props['name'] = $layout->Name : $lay_props['name'] = '';
			isset( $layout->Publication ) ? $lay_props['pub_name'] = $layout->Publication : $lay_props['pub_name'] = '' ;
			isset( $layout->Issue ) ? $lay_props['issue_name'] = $layout->Issue : $lay_props['issue_name'] = '';
			isset( $layout->PubChannel ) ? $lay_props['pubChannelName'] = $layout->PubChannel : $lay_props['pubChannelName'] = '';
			isset( $layout->Section ) ? $lay_props['section_name'] = $layout->Section : $lay_props['section_name'] = '';
			isset( $layout->Status ) ? $lay_props['state_name'] = $layout->Status : $lay_props['state_name'] = '' ; // EN-4014
			isset( $layout->Editions ) ? $lay_props['editions'] = $layout->Editions : $lay_props['editions'] = '';
			isset( $layout->Deadline ) ? $lay_props['deadline'] = $layout->Deadline : $lay_props['deadline'] = '';
			$lay_props['type'] = 'Layout';
			self::modifyObject( $lay_props, $user );
			$layout->Version = $lay_props['version'];

			// Tell layouter that his/her layout needs to be cuddled since planner has infected layout.
			$sMsg = BizResources::localize( 'PLAN_MESS_LAYOUT_MODIFIED', true, array( $lay_props['name'] ) );
			DBObjectFlag::setObjectFlag( $layout->Id, 'Plan System', FLAG_OBJECT_UPDATED, 1, $sMsg );
			self::sendMessage( $layout->Id, $sMsg, 'Info', 'ModifyLayouts' );
			self::resolvePublishData( $lay_props );
			self::copyResolvedPropsFromArray( $layout, $lay_props );

			// Update planned pages (leave produced pages as-is).
			foreach( $layout->Pages as $page ) {
				$page->Files = null; // Avoid conflicts.
				// Treat property that do not occur in planning WSDL.
				$page->Instance = 'Planning';
				if( isset( $page->Number ) && !empty( $page->Number ) ) {
					$page->PageNumber = strval( $page->PageOrder );
				}
				if( $page->Edition && ( $page->Edition->Id || $page->Edition->Name ) ) {
					$pageEditions = array( $page->Edition ); // Only one edition per page.
					$err = self::resolveEditions( $pageEditions, $lay_props['publication'], $lay_props['issue'] );
					if( !empty( $err ) ) {
						throw new BizException( 'ERR_NOTFOUND', 'Client', "Edition:$err" );
					}
				}
			}
			// BizPage::savePages expects workflow pages. 
			$wflPages = array();
			foreach( $layout->Pages as $plnPage ) {
				$wflPage = self::plnPageToWflPage( $plnPage );
				$wflPages[] = $wflPage;
			}
			$layout->Pages = $wflPages;
			BizPage::savePages( null, $layout->Id, 'Planning', $layout->Pages, true, $oldVersion, $lay_props['version'] ); // replace planned pages

			$modifiedLayout = new PlnLayout();
			$modifiedLayout->Id = $layout->Id;
			$modifiedLayout->Name = $layout->Name;
			$modifiedLayout->Publication = $layout->Publication;
			$modifiedLayout->Issue = $layout->Issue;
			$modifiedLayout->PubChannel = $layout->PubChannel; // Todo return PubChannel or leave it?
			$modifiedLayout->Section = $layout->Section;
			$modifiedLayout->Status = $layout->Status;
			$modifiedLayout->Editions = $layout->Editions;
			$modifiedLayout->Editions = $layout->Deadline;
			$modifiedLayout->Version = $layout->Version;
			$modifiedLayouts[] = $modifiedLayout;
		}

		LogHandler::Log( 'PlanningServices', 'DEBUG', 'ModifyLayouts completed <<<' );
		return $modifiedLayouts;
	}

	static public function createAdverts( $user, $layoutId, $layoutName, $adverts )
	{
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'CreateAdverts started >>>' );
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'CreateAdverts, number of adverts: '.count( $adverts ) );

		if( isset( $layoutId ) ) {
			$layoutId = trim( $layoutId );
		}
		if( isset( $layoutName ) ) {
			$layoutName = trim( $layoutName );
			$layoutName = mb_substr( $layoutName, 0, 63, "UTF8" );
		}

		foreach( $adverts as $advert ) {
			if( ( !self::isValidId( $layoutId ) && ( !isset( $layoutName ) || $layoutName == '' ) ) ||
				!self::advertHasId( $advert ) || !self::advertHasContent( $advert ) ) {
				throw new BizException( 'PLAN_CREATE_ADVERT_FAILED', 'Client', '' );
			}
		}

		LogHandler::Log( 'PlanningServices', 'DEBUG', 'CreateAdverts 1' );

		// Walk through the given adverts to create.
		$createdAdverts = array();
		foreach( $adverts as $advert ) {
			$advert->Name = mb_substr( $advert->Name, 0, 63, "UTF8" );

			$props = array(
				//'id'          => $advert->Id, // editorial system determines the object id
				'documentid' => $advert->AlienId, // advert id of advertisement system
				'pub_name' => $advert->Publication,
				'issue_name' => $advert->Issue,
				'pubChannelName' => isset( $advert->PubChannel ) ? $advert->PubChannel : '',
				'section_name' => $advert->Section,
				'state_name' => $advert->Status, // Accept initial status from the planning systems.
				'name' => $advert->Name,
				'comment' => $advert->Comment,
				'source' => $advert->Source,
				'colorspace' => $advert->ColorSpace,
				'description' => $advert->Description,
				'plaincontent' => $advert->PlainContent,
				'highresfile' => $advert->HighResFile,
				'pageorder' => isset( $advert->PageOrder ) && $advert->PageOrder != '' ? $advert->PageOrder : $advert->Page->PageOrder,
				'format' => self::getMimeType( $advert ),
				'type' => 'Advert',
				'width' => $advert->Placement->Width,
				'depth' => $advert->Placement->Height,
				'editions' => $advert->Editions,
				'deadline' => $advert->Deadline,
				'pagesequence' => isset( $advert->PageSequence ) && $advert->PageSequence != '' ? $advert->PageSequence : $advert->Page->PageSequence
			);

			LogHandler::Log( 'PlanningServices', 'DEBUG', 'CreateAdverts 2' );

			// Collect attachments.
			$files = array();
			if( isset( $advert->File->FilePath ) ) {
				self::createThumbFromPreview( $advert );
				// Collect preview or output file.
				if( $advert->File->Rendition != 'none' ) {
					$attachment = new Attachment();
					$attachment->Rendition = $advert->File->Rendition;
					$attachment->Type = $advert->File->Type;
					$attachment->FilePath = $advert->File->FilePath;
					$attachment->FileUrl = $advert->File->FileUrl;
					$files[] = $attachment;
				}
			}

			LogHandler::Log( 'PlanningServices', 'DEBUG', 'CreateAdverts 3' );
			$meta = self::createAdvert( $props, $files, $user );

			$advert->Id = $meta->BasicMetaData->ID;
			$advert->Version = $meta->WorkflowMetaData->Version;
			$props['id'] = $advert->Id;

			// add to existing layout
			if( !isset( $layoutId ) || $layoutId == '' ) {
				$layoutStoreName = '';
				$layoutVersion = '';
				if( !self::resolvePlanObjectProps(
					$layoutId,
					$layoutName,
					$advert->Publication,
					$advert->Issue,
					'Layout', $layoutStoreName,
					$layoutVersion )
				) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', 'Layout:'.$layoutName );
				}
			}

			LogHandler::Log( 'PlanningServices', 'DEBUG', 'CreateAdverts 4: advert=['.$advert->Id.'] layout=['.$layoutId.']' );
			$advert->Placement->Page = $props['pageorder'];
			$advert->Placement->PageSequence = $props['pagesequence'];
			LogHandler::Log( 'PlanningServices', 'INFO', 'CreateAdverts Placement: ['.print_r( array( $advert->Placement ), true ).']' );
			$placements = array();
			if( $advert->Editions ) {
				foreach( $advert->Editions as $edition ) {
					$placement = self::plnPlacementToWflPlacement( $advert->Placement );
					$placement->Edition = $edition;
					$placements[] = $placement;
				}
			} else {
				$placement = self::plnPlacementToWflPlacement( $advert->Placement );
				$placements[] = $placement;
			}
			$rel = array( new Relation( $layoutId, $advert->Id, 'Planned', $placements ) );
			unset( $advert->Placement->FilePath );
			unset( $advert->Placement->FileUrl );

			require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
			BizRelation::createObjectRelations( $rel, $user, null, false, true );

			LogHandler::Log( 'PlanningServices', 'DEBUG', 'CreateAdverts 5' );
			// Tell layouter that his/her layout needs to be cuddled since planner has infected the layout.
			$sMsg = BizResources::localize( 'PLAN_MESS_ADVERT_CREATED', true, array( $advert->Name ) );
			self::sendMessage( $layoutId, $sMsg, 'Info', 'CreateAdverts' );
			require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
			DBObjectFlag::setObjectFlag( $layoutId, 'Plan System', FLAG_PLACEMENT_CREATED, 1, $sMsg );

			self::resolvePublishData( $props );
			self::copyResolvedPropsFromArray( $advert, $props );

			$createdAdvert = new PlnAdvert();
			$createdAdvert->Id =	$advert->Id;
			$createdAdvert->AlienId =	$advert->AlienId;
			$createdAdvert->Publication =	$advert->Publication;
			$createdAdvert->Issue =	$advert->Issue;
			$createdAdvert->PubChannel =	$advert->PubChannel; // Todo Resolve the channel?
			$createdAdvert->Section =	$advert->Section;
			$createdAdvert->Status =	$advert->Status;
			$createdAdvert->Name =	$advert->Name;
			$createdAdvert->Editions =	$advert->Editions;
			$createdAdvert->Deadline =	$advert->Deadline;
			$createdAdvert->Version =	$advert->Version;
			$createdAdverts[] = $createdAdvert;
		}
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'CreateAdverts completed <<<' );
		return $createdAdverts;
	}

	private static function createThumbFromPreview( $advert )
	{
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$content = $transferServer->getContent( $advert->File );
		// Generate and collect the thumb when the jpeg preview is given.
		if( MimeTypeHandler::isJPEG( $advert->File->Type ) && $advert->File->Rendition == 'preview' ) {
			$thumb_buffer = '';
			if( ImageUtils::ResizeJPEG( 100, $content, null, 75, null, null, $thumb_buffer ) ) {
				$thumb = new Attachment( 'thumb', 'image/jpg' );
				$transferServer = new BizTransferServer();
				$transferServer->writeContentToFileTransferServer( $thumb_buffer, $thumb );
				$files[] = $thumb;
			}
			unset( $thumb_buffer );
		}
	}

	static public function deleteAdverts( $user, $layoutId, $layoutName, $adverts )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'DeleteAdverts started >>>' );
		if( isset( $layoutId ) ) {
			$layoutId = trim( $layoutId );
		}
		if( isset( $layoutName ) ) {
			$layoutName = trim( $layoutName );
		}

		foreach( $adverts as $advert ) {
			if( ( !self::isValidId( $layoutId ) && ( !isset( $layoutName ) || $layoutName == '' ) ) || !self::advertHasId( $advert ) ) {
				throw new BizException( 'PLAN_CREATE_ADVERT_FAILED', 'Client', '' );
			}
		}

		LogHandler::Log( 'PlanningServices', 'DEBUG', 'DeleteAdverts 1' );
		foreach( $adverts as $advert ) {
			$storeName = '';
			$version = '';
			if( !self::resolvePlanObjectProps(
				$advert->Id,
				$advert->Name,
				$advert->Publication,
				$advert->Issue,
				'Advert',
				$storeName,
				$version )
			) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'Advert:'.$advert->Name );
			}

			require_once BASEDIR.'/server/bizclasses/BizDeletedObject.class.php';
			BizDeletedObject::deleteObject( $user, $advert->Id, false );

			LogHandler::Log( 'PlanningServices', 'DEBUG', 'DeleteAdverts 2: advert=['.$advert->Id.'] layout=['.$layoutId.']' );

			// Tell the layouter that his/her layout needs to be cuddled since the planner has infected layout.
			$sMsg = BizResources::localize( 'PLAN_MESS_ADVERT_DELETED', true, array( $advert->Name ) );
			DBObjectFlag::setObjectFlag( $layoutId, 'Plan System', FLAG_PLACEMENT_DELETED, 1, $sMsg );
			self::sendMessage( $layoutId, $sMsg, 'Info', 'DeleteAdverts' );
		}
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'DeleteAdverts completed <<<' );
	}

	static public function modifyAdverts( $user, $layoutId, $layoutName, $adverts )
	{
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'ModifyAdverts started >>>' );
		if( isset( $layoutId ) ) {
			$layoutId = trim( $layoutId );
		}
		if( isset( $layoutName ) ) {
			$layoutName = trim( $layoutName );
			$layoutName = mb_substr( $layoutName, 0, 63, "UTF8" );
		}

		$dbDriver = DBDriverFactory::gen();

		foreach( $adverts as $advert ) {
			if( ( !self::isValidId( $layoutId ) && ( !isset( $layoutName ) || $layoutName == '' ) ) || !self::advertHasId( $advert ) ) {
				$sErrorMessage = 'Could not update advert because layout, advert id, name, publication or issue was not specified.';
				throw new BizException( '', 'Client', 'SCEntError_ObjectNotFound', $sErrorMessage );
			}
		}

		LogHandler::Log( 'PlanningServices', 'DEBUG', 'ModifyAdverts 1' );

		$modifiedAdverts = array();
		foreach( $adverts as $advert ) {
			$storeName = '';
			$version = '';
			if( !self::resolvePlanObjectProps( $advert->Id, $advert->Name, $advert->Publication, $advert->Issue, 'Advert', $storeName, $version ) ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'Advert:'.$advert->Name );
			}

			$props = array(
				'id' => $advert->Id,
				'pub_name' => $advert->Publication,
				'issue_name' => $advert->Issue,
				'pubChannelName' => isset( $advert->PubChannel ) ? $advert->PubChannel : '',
				'section_name' => $advert->Section,
				//'state_name' => $advert->Status, // Don't accept status changes from the plannings systems.
				'name' => $advert->Name,
				'comment' => $advert->Comment,
				'source' => $advert->Source,
				'colorspace' => $advert->ColorSpace,
				'description' => $advert->Description,
				'plaincontent' => $advert->PlainContent,
				'highresfile' => $advert->HighResFile,
				'pageorder' => isset( $advert->PageOrder ) && $advert->PageOrder != '' ? $advert->PageOrder : $advert->Page->PageOrder,
				'storeName' => $storeName,
				'format' => self::getMimeType( $advert ),
				'type' => 'Advert',
				'width' => $advert->Placement->Width,
				'depth' => $advert->Placement->Height,
				"editions" => $advert->Editions,
				"deadline" => $advert->Deadline,
				'pagesequence' => isset( $advert->PageSequence ) && $advert->PageSequence != '' ? $advert->PageSequence : $advert->Page->PageSequence,
			);

			LogHandler::Log( 'PlanningServices', 'DEBUG', 'ModifyAdverts 2' );
			$files = array();
			if( isset( $advert->File->FilePath ) ) {
				self::createThumbFromPreview( $advert );
				// Collect preview or output file
				if( $advert->File->Rendition != 'none' ) {
					$attachment = new Attachment();
					$attachment->Rendition = $advert->File->Rendition;
					$attachment->Type = $advert->File->Type;
					$attachment->FilePath = $advert->File->FilePath;
					$attachment->FileUrl = $advert->File->FileUrl;
					$files[] = $attachment;
				}
			}

			LogHandler::Log( 'PlanningServices', 'DEBUG', 'ModifyAdverts 3' );
			self::modifyAdvert( $props, $files, $user );
			$advert->Version = $props['version'];
			LogHandler::Log( 'PlanningServices', 'DEBUG', 'ModifyAdverts 4' );

			if( !isset( $layoutId ) || $layoutId == '' ) {
				// Find parent layout.
				$layoutStoreName = '';
				$layoutVersion = '';
				if( !self::resolvePlanObjectProps( $layoutId, $layoutName, $advert->Publication, $advert->Issue, 'Layout', $layoutStoreName, $layoutVersion ) ) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', 'Layout:'.$layoutName );
				}
			}

			// Flag source layout when about to move the advert to a different layout.
			// Flag only when already PLACED onto source layout; ignore PLANNED since that does not affect produced layout.
			$sourceLayoutId = null;
			require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
			/** @noinspection PhpDeprecationInspection */
			$sth = DBObjectRelation::getObjectRelation( $advert->Id, false, 'Placed' );
			if( !$sth ) {
				throw new BizException( 'ERR_DATABASE', 'Server', DBObjectRelation::getError() );
			}
			while( ( $row = $dbDriver->fetch( $sth ) ) ) {
				if( $row['parent'] != $layoutId ) {
					$sourceLayoutId = $row['parent'];
				}
			}

			// Delete all known planned relations with the advert; it is assumed that adverts can be placed only once!
			DBObjectRelation::deleteObjectRelation( $advert->Id, $advert->Id, 'Planned' );

			// Place new relation.
			$advert->Placement->Page = $props['pageorder'];
			$advert->Placement->PageSequence = $props['pagesequence'];
			$rel = array( new Relation( $layoutId, $advert->Id, 'Planned', array( self::plnPlacementToWflPlacement( $advert->Placement ) ) ) );
			unset( $advert->Placement->FilePath );
			unset( $advert->Placement->FileUrl );
			require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
			BizRelation::createObjectRelations( $rel, $user, null, false, true );

			LogHandler::Log( 'PlanningServices', 'DEBUG', 'ModifyAdverts 5: advert=['.$advert->Id.'] layout=['.$layoutId.']' );
			// Tell layouter that his/her layout needs to be cuddled since the planner has infected the layout.
			require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
			if( is_null( $sourceLayoutId ) ) { // Advert is still on same the layout.
				$sMsg = BizResources::localize( 'PLAN_MESS_ADVERT_MODIFIED', true, array( $advert->Name ) );
				DBObjectFlag::setObjectFlag( $layoutId, 'Plan System', FLAG_PLACEMENT_UPDATED, 1, $sMsg );
				self::sendMessage( $layoutId, $sMsg, 'Info', 'ModifyAdverts' );
			} else { // Advert has been moved.
				$sMsg = BizResources::localize( 'PLAN_MESS_ADVERT_MOVED', true, array( $advert->Name ) );
				DBObjectFlag::setObjectFlag( $layoutId, 'Plan System', FLAG_PLACEMENT_UPDATED, 1, $sMsg );
				self::sendMessage( $layoutId, $sMsg, 'Info', 'ModifyAdverts' );
				$sMsg = BizResources::localize( 'PLAN_MESS_ADVERT_MOVED_OF', true, array( $advert->Name ) );
				DBObjectFlag::setObjectFlag( $sourceLayoutId, 'Plan System', FLAG_PLACEMENT_UPDATED, 1, $sMsg );
				self::sendMessage( $sourceLayoutId, $sMsg, 'Info', 'ModifyAdverts' );
			}

			self::resolvePublishData( $props );
			self::copyResolvedPropsFromArray( $advert, $props );

			// Return created adverts to inform caller about new object id, truncated(?) name, and implicit(?) status change.
			$modifiedAdvert = new PlnAdvert();
			$modifiedAdvert->Id = $advert->Id;
			$modifiedAdvert->AlienId = $advert->AlienId;
			$modifiedAdvert->Publication = $advert->Publication;
			$modifiedAdvert->Issue = $advert->Issue;
			$modifiedAdvert->PubChannel = $advert->PubChannel; // Todo Resolve the channel?
			$modifiedAdvert->Section = $advert->Section;
			$modifiedAdvert->Status = $advert->Status;
			$modifiedAdvert->Name = $advert->Name;
			$modifiedAdvert->Editions = $advert->Editions;
			$modifiedAdvert->Deadline = $advert->Deadline;
			$modifiedAdvert->Version = $advert->Version;
			$modifiedAdverts[] = $modifiedAdvert;
		}
		LogHandler::Log( 'PlanningServices', 'DEBUG', 'ModifyAdverts completed <<<' );
		return $modifiedAdverts;
	}

	static private function resolvePlanObjectProps( &$objId, &$objName, $pubName, $issName, $objType, &$storeName, &$version )
	{
		if( $objId ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$workflowArea = false;
			$dbRow = DBObject::getObjectRow( $objId, $workflowArea );
		} else {
			$dbRow = DBObject::getObjectByTypeAndNames( $objName, $objType, $pubName, $issName );
		}

		if( $dbRow ) {
			$objId = $dbRow['id'];
			$objName = $dbRow['name'];
			$storeName = $dbRow['storename'];
			$version = $dbRow['version'];
			return true;
		}

		return false;
	}

	/**
	 * Tells if the given id is a valid DB identifier.
	 * This is when id is set, numeric, natural and positive.
	 *
	 * @param string $objectId Object id.
	 * @return boolean True when the object id is valid, False otherwise.
	 */
	static private function isValidId( $objectId )
	{
		$objectId = trim( $objectId );
		return !empty( $objectId ) && ( (string)( $objectId ) == (string)(int)( $objectId ) ) && $objectId > 0;
	}

	static private function advertHasId( $advert )
	{
		return // id is unique but optional, so only validate other params when id is missing
			self::isValidId( $advert->Id ) ||
			( isset( $advert->Name ) && trim( $advert->Name ) != '' &&
				isset( $advert->Publication ) && trim( $advert->Publication ) != '' &&
				isset( $advert->Issue ) && trim( $advert->Issue ) != '' );
	}

	static private function advertHasContent( $advert )
	{
		return // High resolution file or attachment or plain content.
			( isset( $advert->HighResFile ) && $advert->HighResFile != '' ) ||
			( ( isset( $advert->File->FilePath ) && $advert->File->FilePath != '' ) &&
				( isset( $advert->File->Rendition ) && $advert->File->Rendition != '' ) &&
				( isset( $advert->File->Type ) && $advert->File->Type != '' ) ) ||
			( isset( $advert->PlainContent ) && $advert->PlainContent != '' ) ||
			( isset( $advert->Description ) && $advert->Description != '' );
	}

	static private function getMimeType( $advert )
	{
		if( isset( $advert->File->Type ) && trim( $advert->File->Type ) != '' ) {
			$mimeType = $advert->File->Type;
		} else if( isset( $advert->HighResFile ) && $advert->HighResFile != '' ) {
			$mimeType = MimeTypeHandler::filePath2MimeType( $advert->HighResFile );
		} else {
			$mimeType = '';
		}
		return $mimeType;
	}

	static private function createLayoutFromTemplate( $type, $name, &$arr, $user )
	{
		self::convertPublishNamesIntoIds( $arr, $user );

		$err = self::resolveEditions( $arr['editions'], $arr['publication'], $arr['issue'] );
		if( !empty( $err ) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', "Edition:$err" );
		}
		$dbDriver = DBDriverFactory::gen();

		// find template with given name
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$sth = DBObject::getTemplateObject( $name, $type );
		$objectId = null;
		$match = 0;
		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			if( $match < 1 ) {
				if( $row['publication'] == $arr['publication'] ) {
					$match = 1;
					$objectId = $row['id'];
				}
			}
			if( $match < 2 ) {
				if( $row['publication'] == $arr['publication'] && $row['issue'] == $arr['issue'] ) {
					$objectId = $row['id'];
					break;
				}
			}
		}
		if( !$objectId ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', "Template:$name" );
		}

		$meta = new MetaData();
		self::convertFlatPropertiesToMetaDataStructure( $arr, $meta );
		$targets = array( self::arrayToTarget( $arr ) );

		// Copy layout template into layout object.
		// Pre-create production pages based on template to make publication overview happy.
		// When there are more planned pages than produced pages, repeat last production page.
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$resp = BizObject::copyObject( $objectId, $meta, $user, $targets, $arr['pages'] );

		// Store planned pages, but NOT for its renditions since it would conflict with
		// production pages overwriting each other in filestore!
		// Resolve page editions; they must be one of the layout's editions!
		$pag_editions = array();
		foreach( $arr['pages'] as $key => $page ) {
			if( $page->Edition ) $pag_editions[ $key ] = $page->Edition;
		}
		$err = self::resolveEditions( $pag_editions, $arr['publication'], $arr['issue'], $arr['editions'] );
		if( !empty( $err ) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', "Edition:$err" );
		}
		foreach( $arr['pages'] as $key => $page ) {
			if( isset( $pag_editions[ $key ] ) ) $page->Edition = $pag_editions[ $key ];
		}

		// Store the planned page.
		require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
		foreach( $arr['pages'] as $page ) {
			// BizPage::insertPage() expects workflow pages. 
			$wflPage = self::plnPageToWflPage( $page );
			$wflPage->Files = null; // Avoid conflicts.
			// Set defaults for properties that do not occur in planning WSDL.
			$wflPage->Instance = 'Planning';
			if( isset( $page->PageNumber ) && !empty( $page->PageNumber ) ) {
				$wflPage->PageNumber = strval( $page->PageOrder );
			}
			// Create the planned page in DB
			BizPage::insertPage( null, $resp->MetaData->BasicMetaData->ID, $wflPage, $resp->MetaData->WorkflowMetaData->Version );
		}
		return $resp->MetaData;
	}

	/**
	 * Converts flat object property list to Target object
	 *
	 * @param array $arr Object property list
	 * @return Target object
	 */
	static private function arrayToTarget( $arr )
	{
		$target = new Target();
		$target->Issue = new Issue();
		if( isset( $arr['issue'] ) && trim( $arr['issue'] ) ) {
			$target->Issue->Id = $arr['issue'];
		}
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		$channelId = DBIssue::getChannelId( $arr['issue'] );
		$channelName = DBChannel::getPubChannelObj( $channelId )->Name;
		$target->PubChannel = new PubChannel();
		$target->PubChannel->Id = $channelId;
		$target->PubChannel->Name = $channelName;
		if( isset( $arr['issue_name'] ) && trim( $arr['issue_name'] ) ) {
			$target->Issue->Name = $arr['issue_name'];
		}
		if( isset( $arr['editions'] ) ) {
			$target->Editions = $arr['editions'];
		}

		return $target;
	}

	static private function convertFlatPropertiesToMetaDataStructure( $arr, &$meta )
	{
		if( !isset( $meta->BasicMetaData ) ) {
			$meta->BasicMetaData = new BasicMetaData();
		}
		if( isset( $arr['id'] ) && trim( $arr['id'] ) ) {
			$meta->BasicMetaData->ID = $arr['id'];
		}
		if( isset( $arr['documentid'] ) && trim( $arr['documentid'] ) ) {
			$meta->BasicMetaData->DocumentID = $arr['documentid'];
		}
		if( isset( $arr['name'] ) && trim( $arr['name'] ) ) {
			$meta->BasicMetaData->Name = $arr['name'];
		}
		$meta->BasicMetaData->Type = $arr['type']; // always mandatory!
		if( !isset( $meta->BasicMetaData->Publication ) ) {
			$meta->BasicMetaData->Publication = new Publication();
		}
		if( isset( $arr['publication'] ) && trim( $arr['publication'] ) ) {
			$meta->BasicMetaData->Publication->Id = $arr['publication'];
		}
		if( isset( $arr['pub_name'] ) && trim( $arr['pub_name'] ) ) {
			$meta->BasicMetaData->Publication->Name = $arr['pub_name'];
		}
		if( !isset( $meta->BasicMetaData->Category ) ) {
			$meta->BasicMetaData->Category = new Category();
		}
		if( isset( $arr['section'] ) && trim( $arr['section'] ) ) {
			$meta->BasicMetaData->Category->Id = $arr['section'];
		}
		if( isset( $arr['section_name'] ) && trim( $arr['section_name'] ) ) {
			$meta->BasicMetaData->Category->Name = $arr['section_name'];
		}
		if( !isset( $meta->WorkflowMetaData ) ) {
			$meta->WorkflowMetaData = new WorkflowMetaData();
		}
		if( !isset( $meta->WorkflowMetaData->State ) ) {
			$meta->WorkflowMetaData->State = new State();
		}
		if( isset( $arr['state'] ) && trim( $arr['state'] ) ) {
			$meta->WorkflowMetaData->State->Id = $arr['state'];
		}
		if( isset( $arr['state_name'] ) && trim( $arr['state_name'] ) ) {
			$meta->WorkflowMetaData->State->Name = $arr['state_name'];
		}
		if( isset( $arr['comment'] ) && trim( $arr['comment'] ) ) {
			$meta->WorkflowMetaData->Comment = $arr['comment'];
		}
		if( isset( $arr['deadline'] ) && trim( $arr['deadline'] ) ) {
			$meta->WorkflowMetaData->Deadline = $arr['deadline'];
		}
		if( !isset( $meta->SourceMetaData ) ) {
			$meta->SourceMetaData = new SourceMetaData();
		}
		if( isset( $arr['source'] ) && trim( $arr['source'] ) ) {
			$meta->SourceMetaData->Source = $arr['source'];
		}
		if( !isset( $meta->ContentMetaData ) ) {
			$meta->ContentMetaData = new ContentMetaData();
		}
		if( isset( $arr['colorspace'] ) && trim( $arr['colorspace'] ) ) {
			$meta->ContentMetaData->ColorSpace = $arr['colorspace'];
		}
		if( isset( $arr['description'] ) && trim( $arr['description'] ) ) {
			$meta->ContentMetaData->Description = $arr['description'];
		}
		if( isset( $arr['plaincontent'] ) && trim( $arr['plaincontent'] ) ) {
			$meta->ContentMetaData->PlainContent = $arr['plaincontent'];
		}
		if( isset( $arr['highresfile'] ) && trim( $arr['highresfile'] ) ) {
			$meta->ContentMetaData->HighResFile = $arr['highresfile'];
		}
		if( isset( $arr['width'] ) && trim( $arr['width'] ) ) {
			$meta->ContentMetaData->Width = $arr['width'];
		}
		if( isset( $arr['depth'] ) && trim( $arr['depth'] ) ) {
			$meta->ContentMetaData->Height = $arr['depth'];
		}
		if( isset( $arr['format'] ) && trim( $arr['format'] ) ) {
			$meta->ContentMetaData->Format = $arr['format'];
		}
	}

	static private function createAdvert( &$arr, $files, $user )
	{
		self::convertPublishNamesIntoIds( $arr, $user );
		// complete given edition names with edition ids from db
		$err = self::resolveEditions( $arr['editions'], $arr['publication'], $arr['issue'] );
		if( !empty( $err ) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', "Edition:$err" );
		}

		$object = new Object();
		$object->MetaData = new MetaData();
		self::convertFlatPropertiesToMetaDataStructure( $arr, $object->MetaData );
		$object->Targets = array( self::arrayToTarget( $arr ) );
		$object->Files = $files;
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$object = BizObject::createObject( $object, $user, false, false, null );

		return $object->MetaData;
	}

	static private function modifyAdvert( &$arr, $files, $user )
	{
		// strip HighResStore[Mac/Win] base path setting from HighResFile property before storing into db
		$highResFile = isset( $arr['highresfile'] ) ? trim( $arr['highresfile'] ) : '';
		if( $highResFile != '' ) {
			require_once BASEDIR.'/server/bizclasses/HighResHandler.class.php';
			$highResFile = HighResHandler::stripHighResBasePath( $highResFile, 'Advert' );
			$arr['highresfile'] = $highResFile;
		}

		self::convertPublishNamesIntoIds( $arr, $user );
		$err = self::resolveEditions( $arr['editions'], $arr['publication'], $arr['issue'] );
		if( !empty( $err ) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', "Edition:$err" );
		}

		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$object = BizObject::getObject( $arr['id'], $user, true, null, array( 'Targets' ) ); // lock, no rendition

		try {
			self::convertFlatPropertiesToMetaDataStructure( $arr, $object->MetaData ); // update meta data from DB with arrived
			$targets = array( self::arrayToTarget( $arr ) );
			if( count( $files ) > 0 || $highResFile != '' ) {
				$object->Targets = $targets;
				$object->Files = $files;
				BizObject::saveObject( $object, $user, true, false ); // create version, no unlock
			} else {
				BizObject::setObjectProperties( $arr['id'], $user, $object->MetaData, $targets );
			}
			$arr['version'] = $object->MetaData->WorkflowMetaData->Version;
		} catch( BizException $e ) {
			BizObject::unlockObject( $arr['id'], $user );
			throw $e;
		}
		BizObject::unlockObject( $arr['id'], $user );
	}

	/**
	 * Modify object properties
	 *
	 * @param array $arr Array of object properties
	 * @param string $user User name
	 * @throws BizException
	 */
	static private function modifyObject( &$arr, $user )
	{
		// create pub/iss/sec/state structure when missing
		self::convertPublishNamesIntoIds( $arr, $user );
		$err = self::resolveEditions( $arr['editions'], $arr['publication'], $arr['issue'] );
		if( !empty( $err ) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', "Edition:$err" );
		}
		$dbDriver = DBDriverFactory::gen();

		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		if( array_key_exists( 'id', $arr ) && trim( $arr['id'] ) != '' ) {
			$sth = DBObject::getObject( $arr['id'] );
		} else {
			$sth = DBObject::checkNameObject( $arr['publication'], $arr['issue'], $arr['name'], $arr['type'] );
		}

		if( !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBObject::getError() );
		}
		$row = $dbDriver->fetch( $sth );

		if( !$row || !array_key_exists( 'id', $row ) || trim( $row['id'] ) == '' ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $arr['name'] );
		}
		$id = $row['id'];

		// Return retrieved object properties (that were missing) to caller.
		$arr['publication'] = array_key_exists( 'publication', $arr ) ? $arr['publication'] : $row['publication'];
		$arr['issue'] = array_key_exists( 'issue', $arr ) ? $arr['issue'] : 0;
		$arr['section'] = array_key_exists( 'section', $arr ) ? $arr['section'] : $row['section'];
		$arr['state'] = array_key_exists( 'state', $arr ) ? $arr['state'] : $row['state'];
		$arr['name'] = array_key_exists( 'name', $arr ) ? $arr['name'] : $row['name'];
		$arr['id'] = array_key_exists( 'id', $arr ) ? $arr['id'] : $row['id'];
		$arr['version'] = array_key_exists( 'version', $arr ) ? $arr['version'] : $row['version'];

		if( $user ) {
			if( $arr['issue'] ) {
				$issueId = $arr['issue'];
			} else {
				// Determine the first issue which the object is assigned to.
				require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
				$targets = DBTarget::getTargetsByObjectId( $id );
				$issueId = $targets && count( $targets ) ? $targets[0]->Issue->Id : 0;
			}
			require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
			BizAccess::checkRightsForObjectRow(
				$user, 'RW', BizAccess::THROW_ON_DENIED, $row, $issueId );
		}

		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$now = date( 'Y-m-d\TH:i:s' );
		$sth = DBObject::updateObject( $id, null, $arr, $now ); // EN-36183 - Do not set modifier when modify layout through planning interface
		if( !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBObject::getError() );
		}

		// BZ#16721 Save issues and editions
		// TODO can we rewrite this by using BizObject::setObjectProperties see modifyAdvert() ?
		self::saveIssuesAndEditions( $user, $id, $arr, $row );
	}

	/**
	 * Save issues and editions by saving the targets
	 * Some rules:
	 * 1. If editions is set always change editions
	 * 2. If publication (or overrule pub issue) has been changed, assume editions are changed
	 * (no channel info here) => nil will mean all editions.
	 * 3. If issue (not overrule pub) has been changed and editions are not set, don't set editions
	 *
	 * @param string $user short username
	 * @param int $id object id
	 * @param array $arr new object properties
	 * @param array $row database row with current object properties.
	 * @throws BizException Throws BizException when the operation fails.
	 */
	private static function saveIssuesAndEditions( $user, $id, $arr, $row )
	{
		$reqIssName = isset( $arr['issue_name'] ) ? $arr['issue_name'] : '';
		if( isset( $arr['editions'] ) || $arr['publication'] != $row['publication'] || !empty( $reqIssName ) ) {
			require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
			$targets = BizTarget::getTargets( $user, $id ); // Get current targets.
			// Layout and advert should have only one target (business rule).
			if( count( $targets ) != 1 || !isset( $targets[0]->Issue->Id ) ) {
				LogHandler::Log( __CLASS__, 'DEBUG', 'targets = '.print_r( $targets, true ) );
				throw new BizException( 'ERR_NOTFOUND', 'Server', 'Could not find issue in targets' );
			}
			if( $arr['publication'] == $row['publication'] ) {
				$updateEditions = false;
				if( $arr['issue'] != $targets[0]->Issue->Id ) {
					if( DBIssue::hasOverruleIssue( array( $arr['issue'], $targets[0]->Issue->Id ) ) ) { // rule 2
						$updateEditions = true;
					}
					$targets[0]->Issue = new Issue( $arr['issue'] );
				}
				if( isset( $arr['editions'] ) ) { // rule 1
					$updateEditions = true;
				}
				if( $updateEditions ) {
					$targets[0]->Editions = $arr['editions'];
				}
			} else { // Publication has been changed, need to build a new target
				$channelId = DBIssue::getChannelId( $arr['issue'] );
				if( !is_null( $channelId ) ) {
					$targets[0] = new Target( new PubChannel( $channelId ), new Issue( $arr['issue'] ), $arr['editions'] );
				}
			}
			$metaData = null;
			BizTarget::saveTargets( $user, $id, $targets, $metaData );
		}
	}

	// Used by convertPublishNamesIntoIds() to log how names are turned into ids.
	static private function logNamesToIdsConversion( $arr, $prefix, $debugLevel = 'DEBUG' )
	{
		// By default, we log only in debug mode unless caller explicitly asked for it
		if( LogHandler::debugMode() || strtoupper( $debugLevel ) != 'DEBUG' ) {
			$log = $prefix;
			if( array_key_exists( 'id', $arr ) && $arr['id'] != '' )
				$log .= 'id=['.$arr['id'].']';
			if( array_key_exists( 'publication', $arr ) && $arr['publication'] != '' )
				$log .= 'publication=['.$arr['publication'].'] ';
			if( array_key_exists( 'pub_name', $arr ) && $arr['pub_name'] != '' )
				$log .= 'pub_name=['.$arr['pub_name'].'] ';
			if( array_key_exists( 'issue', $arr ) && $arr['issue'] != '' )
				$log .= 'issue=['.$arr['issue'].'] ';
			if( array_key_exists( 'issue_name', $arr ) && $arr['issue_name'] != '' )
				$log .= 'issue_name=['.$arr['issue_name'].'] ';
			if( array_key_exists( 'section', $arr ) && $arr['section'] != '' )
				$log .= 'section=['.$arr['section'].'] ';
			if( array_key_exists( 'section_name', $arr ) && $arr['section_name'] != '' )
				$log .= 'section_name=['.$arr['section_name'].'] ';
			if( array_key_exists( 'state', $arr ) && $arr['state'] != '' )
				$log .= 'state=['.$arr['state'].'] ';
			if( array_key_exists( 'state_name', $arr ) && $arr['state_name'] != '' )
				$log .= 'state_name=['.$arr['state_name'].'] ';
			if( array_key_exists( 'name', $arr ) && $arr['name'] != '' )
				$log .= 'name=['.$arr['name'].'] ';
			if( array_key_exists( 'type', $arr ) && $arr['type'] != '' )
				$log .= 'type=['.$arr['type'].'] ';
			LogHandler::Log( 'PlanningServices', $debugLevel, $log );
		}
	}

	// Changes pub/iss/sec/status names into ids and attempts to resolve missing fields, respecting the following rules:
	// - Objects can be identified in two ways; by Pub+Issue+Name+Type -OR- by object id.
	// - Identification by Pub+Issue+Name+Type is always name based (not using internal/database ids).
	// - When NO object id is given, Publication+Name+Type must be given and must exist.
	// - When object id is given, but any of Pub/Issue/Name/Type is not given, the current values are used (and remains unchanged).
	// - When Status and/or Section are not given, the first* ones for which the user has write access will be used. *=Respecting the configured order.
	// - When Issue and/or Section are given, but do not exist, they are created automatically.
	//
	static private function convertPublishNamesIntoIds( &$arr, $user )
	{
		global $globAuth;
		if( !isset( $globAuth ) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule();
		}
		self::logNamesToIdsConversion( $arr, '>>> convertPublishNamesIntoIds started: ' );
		$dbDriver = DBDriverFactory::gen();

		// The id -OR- pub/iss/sec/stt/name are needed to identify the object.
		$publishDataRequired = !array_key_exists( 'id', $arr ) || trim( $arr['id'] ) == '';

		// The Brand must be known in Enterprise because workflow/access definitions are assumed to be made at this level.
		$provided = array_key_exists( 'pub_name', $arr ) && trim( $arr['pub_name'] ) != '';
		$failed = $publishDataRequired && !$provided;
		if( $failed || ( $provided && self::resolvePublishNamesToIds( $arr, 'publication' ) === false ) ) {
			$publication = array_key_exists( 'publication', $arr ) && trim( ( $arr['pub_name'] ) ) != '' ? trim( $arr['pub_name'] ) : '[EMPTY]';
			self::logNamesToIdsConversion( $arr, 'convertPublishNamesIntoIds: Publication not found for ', 'ERROR' );
			throw new BizException( 'ERR_NOTFOUND', 'Client', 'Publication:'.$publication );
		}

		// automatically create ***ISSUE*** if missing (assuming workflow/access definitions are made at pub level!)
		$provided = array_key_exists( 'issue_name', $arr ) && trim( $arr['issue_name'] ) != '';
		self::resolveChannelProperties( $arr );
		$failed = $publishDataRequired && !$provided;
		if( !$failed && $provided && self::resolvePublishNamesToIds( $arr, 'issue' ) === false ) {
			$failed = trim( $arr['issue'] ) == ''; // Could not resolve issue id?
			if( $failed ) {
				$values = array( 'channelid' => $arr['pubChannelId'], 'name' => $arr['issue_name'], 'active' => 'on' );
				require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
				$id = DBBase::insertRow( 'issues', $values );
				$arr['issue'] = $id;
				$failed = false; // EN-3931
			}
		}
		if( $failed ) {
			$issue = array_key_exists( 'issue_name', $arr ) && trim( ( $arr['issue_name'] ) ) != '' ? trim( $arr['issue_name'] ) : '[EMPTY]';
			self::logNamesToIdsConversion( $arr, 'convertPublishNamesIntoIds: Issue not found for ', 'ERROR' );
			throw new BizException( 'ERR_NOTFOUND', 'Client', 'Issue:'.$issue );
		}

		// When the id is given, but 'named id' (Pub+Iss+Name+Type) is not, just fill in the current db values (Solves EN-3359)
		if( array_key_exists( 'id', $arr ) && trim( $arr['id'] ) != '' ) {
			if( ( !array_key_exists( 'name', $arr ) || trim( $arr['name'] ) == '' ) ||
				( !array_key_exists( 'type', $arr ) || trim( $arr['type'] ) == '' ) ||
				( !array_key_exists( 'publication', $arr ) || trim( $arr['publication'] ) == '' ) ||
				( !array_key_exists( 'section', $arr ) || trim( $arr['section'] ) == '' )
			) {
				require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
				$where = '`id` = ? ';
				$params = array( $arr['id'] );
				$row = DBBase::getRow( 'objects', $where, array( 'name', 'publication', 'type', 'section' ), $params );
				if( $row ) {
					if( !array_key_exists( 'name', $arr ) || trim( $arr['name'] ) == '' )
						$arr['name'] = $row['name'];
					if( !array_key_exists( 'type', $arr ) || trim( $arr['type'] ) == '' )
						$arr['type'] = $row['type'];
					if( !array_key_exists( 'publication', $arr ) || trim( $arr['publication'] ) == '' )
						$arr['publication'] = $row['publication'];
					if( !array_key_exists( 'section', $arr ) || trim( $arr['section'] ) == '' )
						$arr['section'] = $row['section'];
				}
			}
			if( !array_key_exists( 'issue', $arr ) || trim( $arr['issue'] ) == '' ) {
				require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
				$printTargets = DBTarget::getTargetsByObjectId( $arr['id'], 'print' );
				if( count( $printTargets ) == 0 ) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', "No (print) issue assigned." );
				}
				$arr['issue'] = $printTargets[0]->Issue->Id;
			}
		}

		// When ***SECTION*** and/or ***STATUS*** not provided, find first one in database for which user has write access.
		$section_provided = array_key_exists( 'section_name', $arr ) && trim( $arr['section_name'] ) != '';
		$status_provided = array_key_exists( 'state_name', $arr ) && trim( $arr['state_name'] ) != '';
		$section_found = false;
		$status_found = false;
		if( $publishDataRequired && ( $section_provided === false || $status_provided === false ) ) {
			$section_candidates = array();
			if( $section_provided === true ) {
				self::resolvePublishNamesToIds( $arr, 'section' );
			} else {
				require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
				$sth = DBSection::listSections( $arr['publication'], $arr['issue'] );
				$globAuth->getRights( $user, $arr['publication'], $arr['issue'] );
				if( $sth ) while( ( $row = $dbDriver->fetch( $sth ) ) ) {
					if( $globAuth->checkright( '', $arr['publication'], $arr['issue'], $row['id'] ) ) { // examine access to sections only
						if( $status_provided ) {
							$arr['section'] = $row['id']; // section resolved !
							$arr['section_name'] = $row['section'];
							$section_found = true;
							break; //
						} else { // When both status and section are not given, collect all sections to determine section/status combinations with write access later.
							$section_candidates[] = $row;
							LogHandler::Log( 'PlanningServices', 'INFO', 'convertPublishNamesIntoIds for ['.$arr['name'].']: No status provided. Collecting candidate section: ['.$row['section'].'].' );
						}
					}
				}
			}
			if( $status_provided === false ) {
				$type_provided = array_key_exists( 'type', $arr ) && trim( $arr['type'] ) != '';
				if( !$type_provided ) {
					self::logNamesToIdsConversion( $arr, 'convertPublishNamesIntoIds: Not able to determine status when object type is not given for ', 'ERROR' );
					throw new BizException( 'ERR_NOTFOUND', 'Client', 'Name:'.$arr['name'] );
				}
				require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
				if( $section_found === true || $section_provided === true ) {
					$states = BizWorkflow::getStates( BizSession::getShortUserName(), $arr['publication'], $arr['issue'], $arr['section'], $arr['type'] );
					$globAuth->getRights( $user, $arr['publication'], $arr['issue'], $arr['section'], $arr['type'] );
					foreach( $states as $stat ) {
						if( $globAuth->checkright( 'W', $arr['publication'], $arr['issue'], $arr['section'], $arr['type'], $stat->Id ) ) { // examine write access
							$status_found = true;
							$arr['state'] = $stat->Id;
							LogHandler::Log( 'PlanningServices', 'INFO', 'convertPublishNamesIntoIds for ['.$arr['name'].']: No status provided. Found accessable status ['.$stat->Name.'] in database.' );
							break;
						}
					}
				} else {
					foreach( $section_candidates as $section_candidate ) {
						$states = BizWorkflow::getStates( BizSession::getShortUserName(), $arr['publication'], $arr['issue'], $section_candidate['id'], $arr['type'] );
						$globAuth->getRights( $user, $arr['publication'], $arr['issue'], $section_candidate['id'], $arr['type'] );
						foreach( $states as $stat ) {
							self::logNamesToIdsConversion( $arr, 'resolvePublishData determine rights for status ['.$stat->Id.'] : ' );
							if( $globAuth->checkright( 'W', $arr['publication'], $arr['issue'], $section_candidate['id'], $arr['type'], $stat->Id ) ) { // examine write access
								$section_found = true;
								$status_found = true;
								$arr['section'] = $section_candidate['id'];
								$arr['section_name'] = $section_candidate['section'];
								$arr['state'] = $stat->Id;
								$arr['state_name'] = $stat->Name;
								LogHandler::Log( 'PlanningServices', 'INFO', 'convertPublishNamesIntoIds: No section nor status provided. Found accessable section ['.$section_candidate['section'].'] and status ['.$stat->Name.'] in database.' );
								break;
							}
						}
						if( $section_found === true && $status_found === true ) {
							break;
						}
					}
				}
				if( ( $section_provided === false && $section_found === false ) || ( $status_provided === false && $status_found === false ) ) {
					self::logNamesToIdsConversion( $arr, 'convertPublishNamesIntoIds: Could not find accessable status and section for object type for ', 'ERROR' );
					throw new BizException( 'ERR_NOTFOUND', 'Client', 'Name:'.$arr['name'] );
				}
			}
		}
		// Automatically create ***SECTION*** if provided but not present in database.
		if( $section_found === false ) {
			$provided = array_key_exists( 'section_name', $arr ) && trim( $arr['section_name'] ) != '';
			$failed = $publishDataRequired && !$provided;
			if( !$failed && $provided && self::resolvePublishNamesToIds( $arr, 'section' ) === false ) {
				$failed = !trim( $arr['section'] );
				if( !$failed ) {
					$values = array( 'publication' => $arr['publication'], 'section' => $arr['section_name'] );
					require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
					$arr['section'] = DBBase::insertRow( 'publsections', $values );
				}
			}
			if( $failed ) {
				$section = array_key_exists( 'section', $arr ) && trim( ( $arr['section_name'] ) ) != '' ? trim( $arr['section_name'] ) : '[EMPTY]';
				self::logNamesToIdsConversion( $arr, ': Section not found for ', 'ERROR' );
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'Section:'.$section );
			}
		}

		// Only check if ***STATUS*** is given: status must be known since access rights are connected to it.
		if( $status_found === false ) {
			$provided = array_key_exists( 'state_name', $arr ) && trim( $arr['state_name'] ) != '';
			$failed = $publishDataRequired && !$provided;
			if( $failed || ( $provided && self::resolvePublishNamesToIds( $arr, 'state' ) === false ) ) {
				$state = array_key_exists( 'state', $arr ) && trim( ( $arr['state_name'] ) ) != '' ? trim( $arr['state_name'] ) : '[EMPTY]';
				self::logNamesToIdsConversion( $arr, 'convertPublishNamesIntoIds: Status not found for ', 'ERROR' );
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'State:'.$state );
			}

			// Only check if ***NAME*** is given: the object cannot be found without it (in case no id is provided).
			$provided = array_key_exists( 'name', $arr ) && trim( $arr['name'] ) != '';
			$failed = $publishDataRequired && !$provided;
			if( $failed ) {
				$name = array_key_exists( 'name', $arr ) && trim( ( $arr['name'] ) ) != '' ? trim( $arr['name'] ) : '[EMPTY]';
				self::logNamesToIdsConversion( $arr, 'convertPublishNamesIntoIds: Name not found for ', 'ERROR' );
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'Name:'.$name );
			}
		}

		self::logNamesToIdsConversion( $arr, '<<< convertPublishNamesIntoIds completed: ' );
	}

	/**
	 * Resolves channel data and adds it to the properties array.
	 * If a channel name is passed in that channel name (plus brand) is used to resolve the channel data.
	 * If also an existing issue is provided then that issue must belong to the channel.
	 * If no channel name is provided the channel data is taken from the channel the issue belongs to.
	 * If the issue does not exists yet the default channel of the brand is used to resolve the channel data.
	 *
	 * @param array $arr Contains all kind of information passed in by the request.
	 * @throws BizException
	 */
	private static function resolveChannelProperties( &$arr )
	{
		$arr['pubChannelId'] = 0;
		$arr['pubChannelType'] = 'print';
		if( $arr['pubChannelName'] ) {
			require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
			$channelObject = DBChannel::getPubChannelObjByBrandAndName( $arr['publication'], $arr['pubChannelName'] );
			if( $channelObject ) {
				$arr['pubChannelId'] = $channelObject->Id;
				$arr['pubChannelType'] = $channelObject->Type;
			} else {
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'Channel Name: '.$arr['pubChannelName'] );
			}
		}

		if( $arr['issue_name'] ) {
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			if( $arr['pubChannelId'] ) {
				$issueRow = DBIssue::getIssuesByNameAndChannel( $arr['issue_name'], $arr['pubChannelId'] );
			} else {
				$issueRow = DBIssue::getIssuesByName( $arr['issue_name'] );
			}
			if( $issueRow ) {
				if( !$arr['pubChannelName'] ) {
					$channelId = DBIssue::getChannelId( $issueRow[0]['id'] );
					require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
					$channelObject = DBChannel::getPubChannelObj( $channelId );
					$arr['pubChannelId'] = $channelObject->Id;
					$arr['pubChannelType'] = $channelObject->Type;
					$arr['pubChannelName'] = $channelObject->Name;
				}
			} else {
				if( !$arr['pubChannelName'] ) {
					$arr['pubChannelId'] = self::resolveDefaultChannelOfIssue( $arr );
					if( $arr['pubChannelId'] ) {
						require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
						$channelObject = DBChannel::getPubChannelObj( $arr['pubChannelId'] );
						$arr['pubChannelType'] = $channelObject->Type;
						$arr['pubChannelName'] = $channelObject->Name;
					}
				}
			}
		}
	}

	private static function resolveDefaultChannelOfIssue( $arr )
	{
		$publicationId = $arr['publication'];
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		$publicationRow = DBPublication::getPublication( $publicationId );

		return $publicationRow['defaultchannelid'];
	}

	// Copies named Pub/Iss/Sec/Status properties from $props into $obj, as well as Name+Id
	// Assumes given $props has contains those properties (which can be resolved using resolvePISSIdProps)
	static private function copyResolvedPropsFromArray( &$obj, $props )
	{
		// Note that $props items that are 'missing' have special meaning: they are not set!
		$obj->Id = isset( $props['id'] ) ? $props['id'] : null;
		$obj->Name = isset( $props['name'] ) ? $props['name'] : null;
		$obj->Publication = isset( $props['pub_name'] ) ? $props['pub_name'] : null;
		$obj->Issue = isset( $props['issue_name'] ) ? $props['issue_name'] : null;
		$obj->Section = isset( $props['section_name'] ) ? $props['section_name'] : null;
		$obj->Status = isset( $props['state_name'] ) ? $props['state_name'] : null;
	}

	// Resolves any missing name for Pub/Iss/Sec/Status props (by querying db)
	// Assumes ids are provided in $props for the names that needs to be resolved
	static private function resolvePublishData( &$props )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		if( ( array_key_exists( 'publication', $props ) && trim( $props['publication'] ) != '' ) &&
			( !array_key_exists( 'pub_name', $props ) || trim( $props['pub_name'] ) == '' )
		) {
			$name = DBPublication::getPublicationName( $props['publication'] );
			if( $name ) {
				$props['pub_name'] = $name;
			}
		}

		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		if( ( array_key_exists( 'issue', $props ) && trim( $props['issue'] ) != '' ) &&
			( !array_key_exists( 'issue_name', $props ) || trim( $props['issue_name'] ) == '' )
		) {
			$name = DBIssue::getIssueName( $props['issue'] );
			if( $name ) {
				$props['issue_name'] = $name;
			}
		}

		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
		if( ( array_key_exists( 'section', $props ) && trim( $props['section'] ) != '' ) &&
			( !array_key_exists( 'section_name', $props ) || trim( $props['section_name'] ) == '' )
		) {
			$name = DBSection::getSectionName( $props['section'] );
			if( $name != '' ) {
				$props['section_name'] = $name;
			}
		}

		if( ( array_key_exists( 'state', $props ) && trim( $props['state'] ) != '' ) &&
			( !array_key_exists( 'state_name', $props ) || trim( $props['state_name'] ) == '' )
		) {
			require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
			$name = DBWorkflow::getStatusName( $props['state'] );
			if( $name != '' ) {
				$props['state_name'] = $name;
			}
		}
	}

	// changes passed pub/iss/sec/state names into ids
	// to determine state id, the object type must be given as well
	static private function resolvePublishNamesToIds( &$arr, $var )
	{
		$dbDriver = DBDriverFactory::gen();
		$sth = null;
		switch( $var ) {
			case 'publication':
				require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
				$sth = DBPublication::listPublicationsByNameId( $arr['pub_name'] );
				break;
			case 'issue':
				require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
				$id = DBIssue::findIssueId( $arr['publication'], $arr['pubChannelType'], $arr['issue_name'], $arr['pubChannelId'] );
				if( $id ) {
					$arr[ $var ] = $id;
					return $id;
				} else {
					$arr[ $var ] = '';
					return false;
				}
			case 'section':
				require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
				$sth = DBSection::listSections( $arr['publication'], $arr['issue'], null, $arr['section_name'] );
				break;
			case 'state':
				require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
				$sth = DBWorkflow::listStates( $arr['publication'], $arr['issue'], $arr['section'], $arr['type'], $arr['state_name'] );
				break;
		}

		if( !$sth ) {
			return false;
		}
		$row = $dbDriver->fetch( $sth );
		if( !$row ) {
			return false;
		}
		$id = $row['id'];
		$arr[ $var ] = $id;
		return $id;

	}

	/*
	* Resolves requested editions. SOAP requests could ask for Edition Id or Name.  <br/>
	* When Id or Name is not specified, it is looked up in the database to complete <br/>
	* the requested Edition element. <br/>
	*
	* @param Array $editions  List of Edition objects
	* @param string $pubId    Publication id (used to lookup editions in DB).
	* @param string $issId    Issue id (used to lookup editions in DB).
	* @param array $dbEditions  List of editions to be search through (instead of DB). Default null to use DB.
	* @return string          Name of edition that could not be resolved. Empty when all resolved.
	*/
	static private function resolveEditions( &$editions, $pubId, $issId, $dbEditions = null )
	{
		if( is_null( $dbEditions ) ) {
			require_once BASEDIR.'/server/bizclasses/PubMgr.class.php';
			$dbEditions = PubMgr::getEditions( $pubId, $issId, false );
		}
		if( $dbEditions && $editions ) {
			foreach( $dbEditions as $dbEdition ) {
				foreach( $editions as $edition ) {
					if( isset( $edition->Id ) || isset( $edition->Name ) ) {
						if( !isset( $edition->Id ) || empty( $edition->Id ) ) {
							if( $edition->Name == $dbEdition->Name ) {
								$edition->Id = $dbEdition->Id;
							}
						}
						if( !isset( $edition->Name ) || empty( $edition->Name ) ) {
							if( $edition->Id == $dbEdition->Id ) {
								$edition->Name = $dbEdition->Name;
							}
						}
					}
				}
			}
		}

		if( $editions ) foreach( $editions as $edition ) {
			if( !isset( $edition->Id ) || empty( $edition->Id ) ||
				!isset( $edition->Name ) || empty( $edition->Name )
			) {
				return empty( $edition->Name ) ? 'Unknown' : $edition->Name;
			}
		}
		return '';
	}

	static private function sendMessage( $objectId, $msgText, $msgLevel, $msgTypeDetail )
	{
		try {
			require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
			$message = new Message();
			$message->ObjectID = $objectId;
			$message->MessageType = 'system';
			$message->MessageTypeDetail = $msgTypeDetail;
			$message->Message = $msgText;
			$message->TimeStamp = date( 'Y-m-d\TH:i:s' );
			$message->MessageLevel = $msgLevel;

			$messageList = new MessageList();
			$messageList->Messages = array( $message );
			BizMessage::sendMessages( $messageList );
		} catch( BizException $e ) {
			// Suppress errors
		}
	}

	/**
	 * Converts a planning PlnPlacement object to a workflow Placement object.
	 *
	 * @param PlnPlacement $plnPlacement
	 * @return Placement
	 */
	static private function plnPlacementToWflPlacement( PlnPlacement $plnPlacement )
	{
		$wflPlacement = new Placement();
		foreach( get_object_vars( $plnPlacement ) as $key => $value ) {
			$wflPlacement->$key = $value;
		}
		return $wflPlacement;
	}

	/**
	 * Converts a PlnPage object (planned page) to a Page object (workflow).
	 *
	 * @param PlnPage $plnPage
	 * @return Page
	 */
	static private function plnPageToWflPage( PlnPage $plnPage )
	{
		$wflPage = new Page();
		foreach( get_object_vars( $wflPage ) as $key => $value ) {
			if( isset( $plnPage->$key ) ) {
				$wflPage->$key = $plnPage->$key;
			}
		}

		return $wflPage;
	}
}
