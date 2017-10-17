<?php
/**
 * @package 	SCEnterprise
 * @subpackage 	BizClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizPage
{
	private static $pageRenditions = null;
	
	/**
	 * Retrieves page objects from database and page files from filestore for requested rendition.
	 *
	 * @param string $objid Object ID (from DB)
	 * @param string $instance Page instance ('Production' or 'Planning')
	 * @param string $storename Original object name (used for filestore)
	 * @param string $rendition Page object rendtion ('preview', 'thumb' ,etc)
	 * @param string $objVerNr Object version (major.minor)
	 * @return array of Page objects (see workflow WSDL)
	 */
	public static function getPageFiles( $objid, $instance, $storename, $rendition, $objVerNr )
	{	
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		$pages = array();
		$dbDriver = DBDriverFactory::gen();
		$sth = DBPage::getPages( $objid, $instance );
		while( ($row = $dbDriver->fetch($sth)) ) {
			$pageTypes = unserialize($row['types']);

			$pageAttachments = array();
			if( $pageTypes ) {
				foreach( $pageTypes as $pageTypeItem ) {
					$pageNr = $pageTypeItem[0];
					$pageRendition = $pageTypeItem[1];
					$pageType = $pageTypeItem[2];
	
					if( $pageRendition == $rendition ) { // found requested rendition in DB ?
						$pagenrval = preg_replace('/[*"<>?\\\\|:]/i', '', $row["pagenumber"]);
						require_once BASEDIR.'/server/bizclasses/BizStorage.php';

						// if the orientation is set use that as part of the page name. otherwise stick to the original filename.
						$pagenrString =  (!is_null($row['orientation']) && !empty($row['orientation']))
							? '-' . $pageNr . '-' . $row['orientation']
							: '-' . $pageNr;

						$pageAttachment = StorageFactory::gen($storename, $objid, 'page', $pageType, $objVerNr, $pagenrval.$pagenrString, $row['edition'] );
						$attachment = new Attachment($pageRendition, $pageType);
						$pageAttachment->copyToFileTransferServer($attachment);
						$pageAttachments[] = $attachment;
					}
				}
			}

			$edition = null;
			if( isset($row['edition']) && $row['edition'] != 0 ) {
				require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
				$edition = DBEdition::getEdition( $row['edition']);
			}			
			
			$pages[] = new Page( $row['width'], $row['height'], $row['pagenumber'], $row['pageorder'], 
						$pageAttachments, $edition, $row['master'], $row['instance'], $row['pagesequence'], null, $row['orientation'] );
		}
		return $pages;
	}

	/**
	 * Get info or one or more renditions for one or more pages of one or more objects.
	 * Implements the GetPages service. Can be called (typically after the GetPagesInfo)  
	 * to retrieve the page thumbs (or previews) for the Publication Overview.
	 *
	 * @since 8.3.3 Supercedes {@link:getPages()} since it is faster (but simplified).
	 * @param integer $issueId Issue id to get pages for.
	 * @param integer|null $editionId Edition id to get pages for. Pass null when no edition configured for brand.
	 * @param string[] $layoutIds Array of one or more layout object ids to get pages, ignored (can be null) if $queryParams supplied
	 * @param string[] $fileRenditions Array of renditions to get file(s) for or null to get no renditions, rendition 'none' is ignored.
	 * @return array of ObjectPageInfo
	 */
	public static function getPages2( $issueId, $editionId, $layoutIds, $fileRenditions )
	{
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		
		// Retrieve all pages for all layouts from DB (all at once).
		if ( $issueId ) {
			$pagesRowsByLayout = DBPage::listPagesByLayoutPerIssue( $issueId, $editionId, 'Production' );
			$layoutIds = array_keys( $pagesRowsByLayout );
		} else {
			$pagesRowsByLayout = DBPage::listPagesByLayoutPerIds( $layoutIds, $editionId, 'Production' );
		}
		
		// Retrieve essential object properties for all layouts from the DB (all at once).
		$user = BizSession::getShortUserName();
		$layoutMetaDatas = DBObject::getMultipleObjectsProperties( $layoutIds );
		$layoutIdsAuth = BizAccess::checkListRightInPubOverview( $layoutMetaDatas, $issueId, $user );

		// Retrieve messages for all layouts
		require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
		$layoutMessageList = BizMessage::getMessagesForObjects( $layoutIds );

		// Only handle the layouts the user is authorized to see in Publication Overview.
		$objectPages = array();
		if ( $layoutIdsAuth ) foreach( $layoutIdsAuth as $layoutId ) {
			$layoutPagesRows = isset($pagesRowsByLayout[$layoutId]) ? $pagesRowsByLayout[$layoutId] : null;
			if ( is_null( $layoutPagesRows )) {
				LogHandler::Log( __METHOD__,
							'WARN',
							'No pages found for layout '.$layoutMetaDatas[$layoutId]->BasicMetaData->Name.
							'. Try to open the layout and save it again.' );
				continue;  // For some reason no pages are found for the layout. A layout must have one page at least.
			}

			$layoutMetaData = $layoutMetaDatas[ $layoutId ];
			
			// Only take over the BasicMetaData to return caller. 
			// Not all MetaData is provided for performance optimization.
			$objectPage = new ObjectPageInfo;
			$objectPage->MetaData = new MetaData();
			$objectPage->MetaData->BasicMetaData = $layoutMetaData->BasicMetaData;
			$objectPage->MetaData->WorkflowMetaData = $layoutMetaData->WorkflowMetaData;
			$objectPage->MessageList = isset($layoutMessageList[$layoutId]) ? $layoutMessageList[$layoutId] : null;

			if ( $layoutPagesRows ) foreach ( $layoutPagesRows as $pageRow ) {

				// Compose the Page->Edition object.
				if( $pageRow['edition'] != 0 ) {
					$pageEdition = new Edition();
					$pageEdition->Id = $pageRow['edition'];
					$pageEdition->Name = $pageRow['editionname'];
				} else {
					$pageEdition = null;
				}
				
				// Compose the page object.
				$pageInfo = new Page();
				$pageInfo->Width       = $pageRow['width'];
				$pageInfo->Height      = $pageRow['height'];
				$pageInfo->PageNumber  = $pageRow['pagenumber'];
				$pageInfo->PageOrder   = $pageRow['pageorder'];
				$pageInfo->Files       = $fileRenditions ? array() : null;
				$pageInfo->Edition     = $pageEdition;
				$pageInfo->Master      = $pageRow['master'];
				$pageInfo->Instance    = $pageRow['instance'];
				$pageInfo->PageSequence= $pageRow['pagesequence'];
				$pageInfo->Renditions  = array();
				$pageInfo->Orientation = $pageRow['orientation'];

				// Walk through the page's renditions.
				$pageTypes = unserialize($pageRow['types']);
				if( $pageTypes ) {
					foreach( $pageTypes as $pageTypeItem ) {
						$pageNr = $pageTypeItem[0];
						$pageRendition = $pageTypeItem[1];
						$pageType = $pageTypeItem[2];

						// Get file rendition (if requested).
						if( $fileRenditions && in_array( $pageRendition, $fileRenditions ) ) {
							$pagenrString =  (!is_null($pageRow['orientation']) && !empty($pageRow['orientation']))
								? '-' . $pageNr . '-' . $pageRow['orientation']
								: '-' . $pageNr;
							$pagenrval = preg_replace('/[*"<>?\\\\|:]/i', '', $pageRow['pagenumber']);
							$layVersion = $layoutMetaData->WorkflowMetaData->Version;
							$storename = $layoutMetaData->BasicMetaData->StoreName; // internal prop (not in WSDL)
							
							require_once BASEDIR.'/server/bizclasses/BizStorage.php';
							$pageAttachment = StorageFactory::gen( $storename, $layoutId, 
								'page', $pageType, $layVersion, $pagenrval.$pagenrString, $pageRow['edition'] );
							$attachment = new Attachment();
							$attachment->Rendition = $pageRendition;
							$attachment->Type = $pageType;
							$attachment->EditionId = $pageRow['edition'] != 0 ? $pageRow['edition'] : null;
							$pageAttachment->copyToFileTransferServer( $attachment );
							$pageInfo->Files[] = $attachment;
						}
						$pageInfo->Renditions[] = $pageRendition;
					}
				}
				$objectPage->Pages[] = $pageInfo;
			}
			$objectPages[] = $objectPage;
		}
		return $objectPages;
	}
		
	/**
	 * Get info or one or more renditions for one or more pages of one or more objects.
	 * Implements the GetPages service. Can be called (typically after the GetPagesInfo)  
	 * to retrieve the page thumbs (or previews) for the Publication Overview.
	 *
	 * @param string $ticket
	 * @param string $user
	 * @param QueryParam[] $queryParams Query parameters of objects to get page(s) for, null to use $objectIds. 
	 * @param string[] $objectIds Array of one or more object ids to get pages, ignored (can be null) if $queryParams supplied
	 * @param integer[]|null $pageOrders Pagenumbers (Page Orders) to get, null to get all pages
	 * @param boolean $getObjectInfo True to also return object meta data
	 * @param integer|null $editionId Edition id to get rendition for, null to get all.
	 * @param string[] $fileRenditions Array of renditions to get file(s) for or null to get no renditions, rendition 'none' is ignored.
	 * @param string[] $infoRenditions Not used.
	 * @param integer[]|null $pageSequences
	 * @param boolean $getFileAttachments
	 * @throws BizException
	 * @return array of ObjectPageInfo
	 */
	public static function getPages( $ticket, $user, $queryParams, $objectIds, $pageOrders, $getObjectInfo, 
					$editionId, $fileRenditions, /** @noinspection PhpUnusedParameterInspection */ $infoRenditions,
					$pageSequences = null, $getFileAttachments = true )
	{
		// Do we need to query for objects?
		if( !empty($queryParams) ) {
		
			// Only layout objects have pages to the shown at Publication Overview.
			$queryParams[] = new QueryParam( 'Type', '=', 'Layout', false );

			// This could be more efficient, but this is easy:
			require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
			$request = new WflQueryObjectsRequest();
			$request->Ticket = $ticket;
			$request->Params = $queryParams;
			$request->FirstEntry = 1;
			$request->MaxEntries = 0;
			$request->Hierarchical = false;
			$request->RequestProps = array( 'ID', 'Type', 'Name' );
			require_once BASEDIR."/server/bizclasses/BizQuery.class.php";
			$resp = BizQuery::queryObjects2( $request, $user, 11 );

	// Determine the object ID column index
			$idIdx = 0;
			if( isset($resp->Columns) ) foreach( $resp->Columns as $col ) {
				if( $col->Name == 'ID' ) {
					break; // found!
				}
				$idIdx++;
			}
			// Collect the retrieved object IDs
			if( !$objectIds ) { $objectIds = array(); }
			if( isset($resp->Rows) ) foreach( $resp->Rows as $row ) {
				$objectIds[] = $row[$idIdx];
			}
		}
		
		// $objectIds now contain the objects we need to get pages for
		require_once BASEDIR."/server/bizclasses/BizObject.class.php";
		require_once BASEDIR."/server/bizclasses/BizStorage.php";
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$objectPages = array();
		foreach( $objectIds as $objid ) {
			$objectPage = new ObjectPageInfo;
			
			// Note this is a bit overkil, but we need the object version to get page files:
			$object = BizObject::getObject( $objid, $user, false, 'none', array('Targets', 'Messages') );
			$objVersion = $object->MetaData->WorkflowMetaData->Version;
			$objectPage->MetaData = $object->MetaData;
			$objectPage->Targets  = $object->Targets;
			$objectPage->Messages = $object->Messages;

			// Skip this object when not planned for requested edition
			if( !empty($editionId) ) { // request for an edition?
				$objectInEdition = false;
				if( !isset($objectPage->Targets) || count($objectPage->Targets) == 0 ) {
					$objectInEdition = true;
				} else {
					$editionCount = 0;
					foreach( $objectPage->Targets as $target ) {
						if( isset($target->Editions) && count($target->Editions) > 0 ) {
							$editionCount += count($target->Editions);
							foreach( $target->Editions as $objEdition ) {
								if( $objEdition->Id == 0 || $objEdition->Id == $editionId ) {
									$objectInEdition = true;
									break 2; // break out two for-loops
								}
							}
						}
					}
					if( $editionCount == 0 ) { // all targets have no editions
						$objectInEdition = true;
					}
				}
				if( !$objectInEdition ) { continue; }// this object is not in edition; go to next object
			}
			
			// Get storename
			$dbDriver = DBDriverFactory::gen();
			$sth = DBObject::getObject( $objid );
			if (!$sth) {
				throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
			}
			$rowobj = $dbDriver->fetch($sth);
			if (!$rowobj) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', $objid );
			}
			$storename = $rowobj['storename'];

			// Walk through all pages and get those we need:
			$objectPage->Pages = array();

			// When pagesequence supplied -> no unique check to find correct edition
			$unique = $pageSequences && $pageSequences[0] > 0 ? false : true;
			
			// Retrieve page info from DB.
			$sth = DBPage::getPages( $objid, 'Production', null, $editionId, $unique, true ); // true = resolve 'editionname' field
			if( $sth ) while( ($row = $dbDriver->fetch($sth)) ) {
				// Looking for this page?				
				if ( empty($pageOrders) ||
						( in_array( $row['pageorder'], $pageOrders ) && $row['pagesequence'] == 0 ) ||
						( in_array( $row['pageorder'], $pageOrders ) && $pageSequences == null ) ||
						$pageSequences && ( in_array( $row['pagesequence'], $pageSequences, false ) && $row['pagesequence'] != 0)
					) {
					
					// Compose the page object.
					$pageInfo = new Page();
					$pageInfo->Width       = $row['width'];
					$pageInfo->Height      = $row['height'];
					$pageInfo->PageNumber  = $row['pagenumber'];
					$pageInfo->PageOrder   = $row['pageorder'];
					$pageInfo->Files       = $fileRenditions ? array() : null;
					$pageInfo->Edition     = $row['edition'] != 0 ? new Edition( $row['edition'], $row['editionname'] ) : null;
					$pageInfo->Master      = $row['master'];
					$pageInfo->Instance    = $row['instance'];
					$pageInfo->PageSequence= $row['pagesequence'];
					$pageInfo->Renditions  = array();
					$pageInfo->Orientation = $row['orientation'];

					// Walk through the page's renditions.
					$pageTypes = unserialize($row['types']);
					if( $pageTypes ) {
						foreach( $pageTypes as $pageTypeItem ) {
							$pageNr = $pageTypeItem[0];
							$pageRendition = $pageTypeItem[1];
							$pageType = $pageTypeItem[2];

							$pagenrString =  (!is_null($row['orientation']) && !empty($row['orientation']))
								? '-' . $pageNr . '-' . $row['orientation']
								: '-' . $pageNr;
								
							// Get file rendition (if requested).
							if( $getFileAttachments && 
								!empty($fileRenditions) && in_array($pageRendition, $fileRenditions) ) {
								$pagenrval = preg_replace('/[*"<>?\\\\|:]/i', '', $row['pagenumber']);
								require_once BASEDIR.'/server/bizclasses/BizStorage.php';
								$pageAttachment = StorageFactory::gen( $storename, $objid, 
									'page', $pageType, $objVersion, $pagenrval.$pagenrString, $row['edition'] );
								$attachment = new Attachment();
								$attachment->Rendition = $pageRendition;
								$attachment->Type = $pageType;
								$attachment->EditionId = $row['edition'] != 0 ? $row['edition'] : null;
								$pageAttachment->copyToFileTransferServer( $attachment );
								$pageInfo->Files[] = $attachment;
							}
							
							// Provide available page renditions.
							$pageInfo->Renditions[] = $pageRendition;
						}
					}
					$objectPage->Pages[] = $pageInfo;
				}
			}
			if( !$getObjectInfo ) { // cut off when not requested (optimization)
				$objectPage->MetaData = null;
				$objectPage->Messages = null;
			}
			unset($objectPage->Targets); // not specified in WSDL...yet?
			$objectPages[] = $objectPage;
		}
		return $objectPages;
	}

	/**
	 * Calculates the page range property based on the page order of a layout.
	 *
	 * A layout can have different page ranges. This happens when the page order numbers of the pages are not adjacent.
	 * Contiguous pages are formatted as from_page-to_page. E.g. 1,2,3,7,10,11,12. This results in the page range 1-3,7,11-12.
	 * If there are pages for different editions the page range is calculated for each edition. If a page is the same for
	 * all editions then the edition of the page is set to null.
	 * Three combinations are possible:
	 * - All editions are null, pages are the same for all editions or no editions are used.
	 * - Some pages have an edition while others have an edition is null. Pages with edition is null are applicable for
	 *  all editions and must be added to the editions.
	 * - All pages have editions.
	 * The page ranges are returned per edition. The order is the same as the sorting order of the editions. If two
	 * editions in sequence have the same page range than only one page range is returned for both editions. Of course
	 * if no editions are used than only one page range is returned.
	 * Page ranges per editions are separated by by semicolon.
	 * For example 1-4,6;1-4,7 means page 1 to 4 for both editions and page 6 for the first and 7 for the second.
	 *
	 * @param Pages[] $pages
	 * @return string The calculated page range.
	 *
	 */
	static public function calcPageRange( array $pages )
	{
		$pageRangeByEditions = array();
		if( $pages ) {
			$editionIds = self::getEditionsFromPages( $pages );
			$pagesByEditionsSorted = self::assignPagesByEditionsSorted( $pages, $editionIds );
			if( $pagesByEditionsSorted ) foreach( $pagesByEditionsSorted as $pagesByEdition ) {
				$prevPageOrder = reset( $pagesByEdition );
				$pagesPerRange = array();
				$separator = '';
				$pageRangeString = '';
				if( $pagesByEdition ) foreach( $pagesByEdition as $pageOrder ) {
					if( $pageOrder < $prevPageOrder ) { // New page order is applied.
						$pageRangeString .= $separator.NumberUtils::createNumberRange( $pagesPerRange );
						$separator = ',';
						$pagesPerRange = array();
					}
					$pagesPerRange[] = str_pad( $pageOrder, 3, "0", STR_PAD_LEFT );;
					$prevPageOrder = $pageOrder;
				}
				$pageRangeString .= $separator.NumberUtils::createNumberRange( $pagesPerRange );
				$pageRangeByEditions[] = $pageRangeString;
			}
		}

		return self::composePageRangeOverAllEditions( $pageRangeByEditions );
	}

	/**
	 * Returns the page range of a layout based on the page ranges by edition.
	 *
	 * Page ranges of editions are concatenated using ';' as a separator. If two page ranges of adjacent editions are the
	 * same the page range is only added once.
	 *
	 * @param array $pageRangeByEditions
	 * @return string The page range of all editions.
	 */
	static private function composePageRangeOverAllEditions( array $pageRangeByEditions )
	{
		$prevRange = '';
		$uniquePageRangeByEditions = array();
		if( $pageRangeByEditions ) foreach( $pageRangeByEditions as $pageRangeByEdition ) {
			if( $pageRangeByEdition !== $prevRange ) {
				$uniquePageRangeByEditions[] = $pageRangeByEdition;
			}
			$prevRange = $pageRangeByEdition;
		}

		return implode( ';', $uniquePageRangeByEditions );
	}

	/**
	 * Returns the edition ids of all editions of the pages.
	 *
	 * The pages can be assigned to specific editions or all/no editions. In case of no specific edition the page edition
	 * is set to null.
	 *
	 * @param Pages[] $pages
	 * @return int[]
	 */
	static private function getEditionsFromPages( array $pages )
	{
		$editionIds = array();
		if( $pages ) foreach( $pages as $page ) {
			$editionId = $page->Edition == null ? 0 : intval( $page->Edition->Id );
			$editionIds[$editionId] = $editionId;
		}

		return array_values( $editionIds );
	}

	/**
	 * Sorts the pages by (sorted) edition.
	 *
	 * The pages are sorted according to the page sequence. The page sequence is just the sequence of the pages in the
	 * layout. The page order is the number assigned to the sequence. A sequence can have different orders e.g.:
	 * Sequence Order Number
	 *    1       2     II
	 *    2       3     III
	 *    3       4     IV
	 *    4       1     1
	 *    5       2     2
	 *    6       3     3
	 * So after the third page (sequence = 3) a new order is applied.
	 * Note that the page number ($page->PageNumber) is the formatted page order. So page order 3 can be formatted to
	 * B III, where prefix B is applied plus roman styling. Page numbers are not taken into account.
	 * If multiple editions are used the sorted pages are assigned to the sorted editions. Sorting is not needed in case
	 * one edition is used or all pages are assigned to all editions (only edition = 0).
	 * If some pages are assigned to a specific editions and others to all editions the pages for all editions are
	 * assigned to all the specific editions.
	 *
	 * @param Pages[] $pages
	 * @param int[] $editionIds
	 * @return array Sorted pages by (sorted) editions.
	 */
	private static function assignPagesByEditionsSorted( array $pages, array $editionIds )
	{
		$allAndSpecificMixed = self::allAndSpecificEditionsMixed( $editionIds );
		if( $allAndSpecificMixed ) {
			$key = array_search( 0, $editionIds );
			unset( $editionIds[$key] );
		}

		// Assigning pages to editions.
		$pagesByEdition = array_fill_keys( $editionIds, array() );
		if( $pages ) foreach( $pages as $page ) {
			$editionId = $page->Edition == null ? 0 : intval( $page->Edition->Id );
			if( $allAndSpecificMixed && $editionId == 0 ) {
				if( $editionIds ) foreach( $editionIds as $editionId ) {
					$pagesByEdition[ $editionId ][ $page->PageSequence ] = $page->PageOrder;
				}
			} else {
				$pagesByEdition[ $editionId ][ $page->PageSequence ] = $page->PageOrder;
			}
		}

		// Sorting pages by (sorted) editions.
		$sortedPagesBySortedEditions = array();
		if( count( $editionIds ) > 1 ) {
			require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
			$sortedEditionIds = array_keys( DBEdition::sortEditionIdsByCode( $editionIds ) );
			if( $sortedEditionIds ) foreach( $sortedEditionIds as $sortedEditionId ) {
				$sortedPagesBySortedEditions[ $sortedEditionId ] = array();
			}
		} else {
			$sortedPagesBySortedEditions[reset( $editionIds )] = array();
		}
		if( $pagesByEdition ) foreach( $pagesByEdition as $editionId => $pageOrderByPageSequence ) {
			/*success = */ ksort( $pageOrderByPageSequence, SORT_NUMERIC );
			$sortedPagesBySortedEditions[ $editionId ] = $pageOrderByPageSequence;
		}

		return $sortedPagesBySortedEditions;
	}

	/**
	 * Checks if specific editions ( id != 0 ) are mixed up with marker for all editions ( id = 0 ).
	 *
	 * The $editionsIds parameter contains unique ids of editions and/or 0 to indicate all editions.
	 *
	 * @param array $editionIds
	 * @return bool
	 */
	static private function allAndSpecificEditionsMixed( array $editionIds )
	{
		return ( in_array( 0, $editionIds ) && count( $editionIds ) > 1 );
	}
	
	/**
	 * Inserts object pages into database and filestore
	 *
	 * @param string $storeName Original object name (used in storage)
	 * @param string $objId     Object ID (used in DB)
	 * @param string $instance  Page instance ('Production' or 'Planning')
	 * @param array  $pages     List of Page objects (from workflow WSDL)
	 * @param boolean $deleteExisting Pass true for Save- or false for Create- operations
	 * @param string $oldObjVerNr  Old object version (major.minor)
	 * @param string $newObjVerNr  New object version (major.minor)
	 * @throws BizException on failure
	 */
	public static function savePages( $storeName, $objId, $instance, $pages, $deleteExisting, $oldObjVerNr, $newObjVerNr )
	{
		if( $deleteExisting ) { // to differentiate between create and save
			self::cleanPages( $storeName, $objId, $instance, $oldObjVerNr );
		}
		
		if (!empty($pages)) {
			foreach ($pages as $page) {
				self::insertPage( $storeName, $objId, $page, $newObjVerNr );
			}
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$pagerange = self::calcPageRange($pages);
			DBObject::updatePageRange($objId, $pagerange, $instance);
		}
	}
	
	/**
	 * Inserts object pages into database and filestore
	 *
	 * @param string $storename Original object name (used in storage)
	 * @param string $objid     Object ID (used in DB)
	 * @param object $page      Page object (from workflow WSDL)
	 * @param string $objVerNr  New object version (major.minor)
	 * @throws BizException on failure
	 */
	public static function insertPage( $storename, $objid, $page, $objVerNr )
	{
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		$sPageNumber = isset($page->PageNumber) && $page->PageNumber ? $page->PageNumber : $page->PageOrder;
		$pagenrval = preg_replace('/[*"<>?\\\\|:]/i', '', $sPageNumber);
		$pageNr = 0;
		$pageTypes = array();
		$edid = isset($page->Edition->Id) ? $page->Edition->Id : null;
		if( $page->Files ) foreach ( $page->Files as $file){
			$pageNr++;
			$pageTypes[] = array( $pageNr, $file->Rendition, $file->Type );

			$pagenrString =  (!is_null($page->Orientation) && !empty($page->Orientation))
				? '-' . $pageNr . '-' . $page->Orientation
				: '-' . $pageNr;

			$at = StorageFactory::gen( $storename, $objid, 'page', $file->Type, $objVerNr, $pagenrval . $pagenrString, $edid, true );
			if( !$at->saveFile( $file->FilePath ) ) {
				throw new BizException( 'ERR_ATTACHMENT', 'Server', $at->getError() );
			}
		}
		$sth = DBPage::insertPage( $objid, $page->Width, $page->Height, $sPageNumber, $page->PageOrder, $page->PageSequence, 
							$edid, $page->Master, $page->Instance, $pageNr, serialize($pageTypes), $page->Orientation );
		if (!$sth){
			$dbDriver = DBDriverFactory::gen();
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
	}
	
	/**
	 * Removes object pages from database and filestore
	 *
	 * @param string $storename Original object name (used in storage)
	 * @param string $objid     Object ID (used in DB)
	 * @param string $instance  Page instance ('Production' or 'Planning')
	 * @param string $objVerNr  Old object version (major.minor)
	 * @throws BizException on failure
	 */
	public static function cleanPages( $storename, $objid, $instance, $objVerNr )
	{
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		$dbDriver = DBDriverFactory::gen();
		if( $instance != 'Planning' ) {
			require_once BASEDIR."/server/bizclasses/BizStorage.php";
			$sth = DBPage::getPages( $objid, $instance );
			while (($row = $dbDriver->fetch($sth)) ) {
				// delete all pages
				foreach (unserialize($row['types']) as $tp) {
					$pagenrval = preg_replace('/[*"<>?\\\\|:]/i', '', $row['pagenumber']);

					$pagenrString =  (!is_null($row['orientation']) && !empty($row['orientation']))
						? '-' . $tp[0] . '-' . $row['orientation']
						: '-' . $tp[0];

					$pageobj = StorageFactory::gen($storename, $objid, 'page', $tp[2], $objVerNr, $pagenrval.$pagenrString, $row['edition']);
					$pageobj->deleteFile();
				}
			}
		}
		$ret = DBPage::cleanPages( $objid, $instance );
		if( !$ret ) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}		
	}
	
	/**
	 *	Lists all pages of an specific issue (and possibly edition).
	 * 
	 *  @deprecated since 9.4
	 *	@param $issueid integer: id of the issue to get the pages for.
	 *	@param $editionid integer: id of a specific edition
	 *	@param $sectionid integer: id of a specific section:
	 *			watch out: this param does not change the number of returned rows, it does set pages in other sections to visible = false though.
	 *	@param $layoutid integer: id of a specific layout:
	 *			watch out: this param does not change the number of returned rows, it does set pages in other layouts to visible = false though.
	 *	@param $pageordered boolean: if false, the pages are just ordered by layout. If true, pages will be ordered by pageorder.
	 *	@param $instance, either empty, production or ???planning???
	 *
	 *	@return array associative array of pagerows, the keys being the pageindex where we would expect to show the individual pages.
	 *			For each pagerow some information is returned: layoutid, pagesequence, pageorder, pagenumber, sectionid, editionid, visible
	 *			The visible-flag can only be set to false if either a sectionid or a layoutid was given, only pages in that section or layout are visible then.
	 *			When $pageordered = true it gets a bit more complicated, it may be that some pages have the same pageindex (pageorder):
	 *				So the keys are now the found pageorders and refer to an array of pagerows instead of one pagerow.
	**/	
	
	public static function listIssuePages($issueid, $editionid = 0, $pageordered = false, $sectionid = 0, $layoutid = 0, $instance = "Production")
	{
		$allpages = array();	
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		$pagerows = DBPage::listIssuePages($issueid, $editionid, $pageordered, $sectionid, $layoutid, $instance);
		
		$pageindex = 1;
		foreach ($pagerows as $pagerow) {
			$onepage = array();
			$onepage['layoutid'] = $pagerow['objid'];
			$onepage['pagesequence'] = $pagerow['pagesequence'];
			$onepage['pagenumber'] = $pagerow['pagenumber'];
			$pageorder = $pagerow['pageorder'];
			$onepage['pageorder'] = $pageorder;
			$onepage['sectionid'] = $pagerow['section'];
			$onepage['editionid'] = $pagerow['edition'];
			if (!$pageordered) {
				//when not ordered pages are returned with an unique pageindex
				$allpages[$pageindex] = $onepage;
			}
			else {
				//when ordered double pages are ommitted. Allways choosing the first one.
				if (!isset($allpages[$pageindex])) {
					$allpages[$pageindex] = $onepage;
				}
			}
			$pageindex++;
		}
		return $allpages;
	}

	/**
	 * Renames the layout page rendition files at the files store. This is needed whenever the object
	 * version changes because that version is used in the file names. If not called, typically the
	 * Publication Overview gets does not get updated.
	 *
	 * @param string $objId     Object ID (used in DB)
	 * @param string $storename Original object name (used in storage)
	 * @param string $oldVerNr  Old object version (major.minor)
	 * @param string $newVerNr  New object version (major.minor)
	 */
	public static function versionPageFiles( $objId, $storename, $oldVerNr, $newVerNr )
	{	
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		require_once BASEDIR.'/server/bizclasses/BizStorage.php'; // StorageFactory

		$dbDriver = DBDriverFactory::gen();
		$sth = DBPage::getPages( $objId, 'Production' );
		while( ($row = $dbDriver->fetch($sth)) ) {
			foreach (unserialize($row['types']) as $tp) {
				$pageNrVal = preg_replace('/[*"<>?\\\\|:]/i', '', $row['pagenumber']);

				$pagenrString =  (!is_null($row['orientation']) && !empty($row['orientation']))
					? '-' . $tp[0] . '-' . $row['orientation']
					: '-' . $tp[0];

				$pageAttachment = StorageFactory::gen( $storename, $objId, 'page', $tp[2], $oldVerNr, $pageNrVal.$pagenrString, $row['edition'] );
				$pageAttachment->backupVersion( $newVerNr );
			}
		}
	}
	
	/**
	 * Whether or not there are page previews available for the current version of the given layout.
	 * Please call initRenditionsOfFirstProductionPage() before using this function.
	 *
	 * @since 9.7.0
	 * @param integer $objId ID of Layout or Layout Module.
	 * @return boolean
	 * @throws BizException
	 */
	public static function hasPreviewRendition( $objId )
	{
		$pageRenditions = self::getRenditionsOfFirstProductionPage( $objId );
		return isset($pageRenditions['preview']);
	}

	/**
	 * Whether or not there are page PDFs available for the current version of the given layout.
	 * Please call initRenditionsOfFirstProductionPage() before using this function.
	 *
	 * @since 9.7.0
	 * @param integer $objId ID of Layout or Layout Module.
	 * @return boolean
	 * @throws BizException
	 */
	public static function hasOutputRenditionPDF( $objId )
	{
		$pageRenditions = self::getRenditionsOfFirstProductionPage( $objId );
		return isset($pageRenditions['output']) && $pageRenditions['output'] == 'application/pdf';
	}

	/**
	 * Whether or not there are page EPSs available for the current version of the given layout.
	 * Please call initRenditionsOfFirstProductionPage() before using this function.
	 *
	 * @since 9.7.0
	 * @param integer $objId ID of Layout or Layout Module.
	 * @return boolean
	 * @throws BizException
	 */
	public static function hasOutputRenditionEPS( $objId )
	{
		$pageRenditions = self::getRenditionsOfFirstProductionPage( $objId );
		return isset($pageRenditions['output']) && $pageRenditions['output'] == 'application/postscript';
	}
	
	/**
	 * Returns the file renditions and file formats of the first production page found for given layout.
	 *
	 * @since 9.7.0
	 * @param integer $objId ID of Layout or Layout Module.
	 * @return string[] List of file renditions (keys) and file formats (values).
	 * @throws BizException
	 */
	private static function getRenditionsOfFirstProductionPage( $objId )
	{
		if( !isset(self::$pageRenditions[$objId]) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 
				'The function BizPage::initRenditionsOfFirstProductionPage() was not called '.
				'or the object id '.$objId.' was not provided to that function.' );
		}
		return self::$pageRenditions[$objId];
	}
	
	/**
	 * Populates the memory cache (self::$pageRenditions) for the has...Rendition() functions.
	 *
	 * @param integer[] $objIds IDs of objects for which the cache must be setup.
	 * @throws BizException
	 */
	public static function initRenditionsOfFirstProductionPage( array $objIds )
	{
		// Retrieve the file renditions of the first Production page of the given layout ids.
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		$map = DBPage::getRenditionsOfFirstProductionPage( $objIds );
		
		// Compose cache structure as follows (e.g. objId = 123):
		//    $pageRenditions[123]['preview'] => 'image/jpeg'
		//    $pageRenditions[123]['output'] => 'application/pdf'
		//    ...
		self::$pageRenditions = array();
		foreach( $map as $objId => $rawTypes ) {
			if( $rawTypes ) {
				$pageTypes = unserialize( $rawTypes );
				if( $pageTypes ) {
					foreach( $pageTypes as $pageTypeItem ) {
						self::$pageRenditions[$objId][$pageTypeItem[1]] = $pageTypeItem[2]; // 0=pagenr, 1=rendition, 2=fileformat
					}
				}
			}
		}
		
		// After restoring a layout, all pages are removed. However, make sure that the
		// cache has an item for this layout to avoid ERR_ARGUMENT error in getRenditionsOfFirstProductionPage().
		if( $objIds ) foreach( $objIds as $objId ) {
			if( !isset(self::$pageRenditions[$objId]) ) {
				self::$pageRenditions[$objId] = array();
			}
		}
	}
}