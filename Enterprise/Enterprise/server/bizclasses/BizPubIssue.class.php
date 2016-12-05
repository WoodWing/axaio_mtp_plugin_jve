<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v7.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Issue dossier ordering biz logics for production.
 *
 * Supports saving the -production- dossier ordering within an issue.
 * This is in preparation to determine the -publishing- dossier ordering.
 * The -production- order is stored at custom issue property C_HIDDEN_DPS_DOSSIER_ORDER.
 * The -publishing- order is handled and stored by the BizPublishing class.
 *
 * The idea is that ordering can only be updated when it is based on the most recent one (at DB).
 * To avoid race conditions of two users re-ordering dossiers, a semaphore is used.
 *
 * When dossiers are added, removed or re-ordered, the custom property is simply updated.
 * This is done for performance reasons. When the current order is explicitly requested through
 * web services, the dossiers are queried using QueryObjects (instead of simply returning the  
 * custom prop value). With the complete set of dossier in our hands, the custom property field
 * gets auto repaired (by adding missing dossiers at the end and removing the ones that do not exist). 
 * This is done for robustness. Users are frequently asking for the order, and when they think there
 * is something wrong, users will Refresh, and so things get auto-repaired. This is more logic than
 * repairing orders for the other operations (add/remove/reorder). Those also happen a lot, but then
 * in different contexts, such as adjusting the dossier targets, etc etc. Doing expensive QueryObjects
 * would be very unwanted/unexpected in those contexts.
 *
 * Dossier order updates are also N-casted (to clients listening).
 */

class BizPubIssue
{
	private $suppressErrors; // See header of the suppressErrors() function.
	
	public function __construct()
	{
		$this->suppressErrors = false;
	}
	
	/**
	 * Avoid throwing BizException for successor function calls to this class.
	 * It could be the case that the dossier order needs to be changed as a litte part of a bigger
	 * service. However, that service might not want to break because of this little failure.
	 * Added and removed dossiers will be auto-repaired on any other operations, so there is no
	 * true need to raise exception and bail out in those cases. Nevertheless, when the service
	 * is all about explicitly changing the order, an error is wanted, which is thrown by default.
	 */
	public function suppressErrors()
	{
		$this->suppressErrors = true;
	}
	
	/**
	 * Return the -production- dossier order for a given issue (id).
	 * It queries for dossiers using QueryObjects. See class header for details.
	 *
	 * @param integer $issueId
	 * @throws BizException Throws BizException when there's error occurred and when only errors are requested to be shown.
	 * @return array|null List of dossier ids. NULL when issue not found or when update failed.
	 */
	public function getIssueDossierOrder( $issueId )
	{
		// Make sure user can not break half-way since we need to abort nicely to clean our stuff,
		// especially the semaphore that is created below.
		$orgAbortFlag = ignore_user_abort(1);
		$dossierIds = null;

		try {
			// Create semaphore to prevent race conditions two users doing repairing.
			$sema = $this->createSemaLock( $issueId );
			
			// Do expensive QueryObjects to make sure we collect all dossiers assigned to the issue.
			// This collection is used to auto-repair later. See class header for details.
			$dossierIdsQueried = $this->queryIssueDossierOrder( $issueId );

			// Create convenient array with dossier ids at keys, for quick access later.
			if( $dossierIdsQueried ) {
				$dossierIdsQueried = array_combine( array_values($dossierIdsQueried), array_fill(1,count($dossierIdsQueried),true) );
			}
			if( isset($dossierIdsQueried[0]) ) {
				unset($dossierIdsQueried[0]); // fix malformed id
			}

			// With the semaphore in our hands, retrieve the dossier ordering from DB.
			// That means, no-one can change the order at this point, so this is the only trueth.
			$dossierIdsSorted = $this->getDossierOrderFromIssueProps( $issueId );
			
			// Create convenient array with dossier ids at keys, for quick access later.
			if( $dossierIdsSorted ) {
				$dossierIdsSorted = array_combine( array_values($dossierIdsSorted), array_fill(1,count($dossierIdsSorted),true) );
			}
			if( isset($dossierIdsSorted[0]) ) {
				unset($dossierIdsSorted[0]); // fix malformed id
			}
		
			$outOfSync = false; 
			// When new dossiers were assigned to the issue, but that event was not saved in the 
			// custom issue prop somehow, we repair by adding them at the end of the custom prop.
			if( $dossierIdsQueried ) foreach( array_keys($dossierIdsQueried) as $dossierId ) {
				if( is_array($dossierIdsSorted) && !array_key_exists( $dossierId, $dossierIdsSorted ) ) {
					$dossierIdsSorted[$dossierId] = true;
					$outOfSync = true;
				}
			}
			
			// When certain dossiers were removed from the issue, but that event was not saved in the 
			// custom issue prop somehow, we repair by removing them from the custom prop.
			if( $dossierIdsSorted ) foreach( array_keys($dossierIdsSorted) as $dossierId ) {
				if( !array_key_exists( $dossierId, $dossierIdsQueried ) ) {
					unset( $dossierIdsSorted[$dossierId] );
					$outOfSync = true;
				}
			}
			
			$dossierIds = is_array($dossierIdsSorted) ? array_keys($dossierIdsSorted) : array();
			// Update the custom issue prop with the repaired ordering.
			if ( $outOfSync ) {
				$this->updateIssueDossierOrderSafeMode( $issueId, $dossierIds, null );
			}

		} catch( BizException $e ) {
			$catchedBizException = $e;
		}

		// Release semaphore.
		if( isset($sema) && $sema ) {
			$this->releaseSemaLock( $sema );
		}
		
		// Restore the abort flag (that was changed above).
		ignore_user_abort( $orgAbortFlag );
		
		// Re-throw exception, unless requested to hide from end user.
		if( isset($catchedBizException) && $catchedBizException && !$this->suppressErrors ) {
			throw $catchedBizException;
		}
		
		return $dossierIds;
	}
	
	/**
	 * Update the -production- dossier order for a given issue.
	 * The new order is saved in the custom issue property (in the database).
	 * It errors when the $originalOrder is different than the one stored in DB.
	 * See class header for details.
	 *
	 * @param integer $issueId
	 * @param array $newOrder
	 * @param array $originalOrder
	 * @throws BizException When issue is locked or original order differs from DB (and suppressErrors() not called)
	 */
	public function updateIssueDossierOrder( $issueId, $newOrder, $originalOrder ) 
	{
		// Make sure user can not break half-way since we need to abort nicely to clean our stuff,
		// especially the semaphore that is created below.
		$orgAbortFlag = ignore_user_abort(1);

		try {
			// Create semaphore to prevent race conditions two users doing reordering.
			$sema = $this->createSemaLock( $issueId );
			
			// With the semaphore in our hands, we can safely update the database.
			$this->updateIssueDossierOrderSafeMode( $issueId, $newOrder, $originalOrder );
			
		} catch( BizException $e ) {
			$catchedBizException = $e;
		}

		// Release semaphore.
		if( isset($sema) && $sema ) {
			$this->releaseSemaLock( $sema );
		}
		
		// Restore the abort flag (that was changed above).
		ignore_user_abort( $orgAbortFlag );
		
		// Re-throw exception, unless requested to hide from end user.
		if( isset($catchedBizException) && $catchedBizException && !$this->suppressErrors ) {
			throw $catchedBizException;
		}
	}
	
	/**
	 * Add one dossier at the end of the (existing) dossier order for a given issue.
	 * Only the custom issue property (in the database) is updated. See class header for details.
	 *
	 * @param integer $issueId
	 * @param integer $dossierId Dossier (id) to add.
	 * @throws BizException When issue is locked (and suppressErrors() not called)
	 */
	public function addDossierToOrder( $issueId, $dossierId )
	{
		// Make sure user can not break half-way since we need to abort nicely to clean our stuff,
		// especially the semaphore that is created below.
		$orgAbortFlag = ignore_user_abort(1);

		LogHandler::Log( __CLASS__, 'DEBUG', 'Adding dossier id '.$dossierId.' to dossier order for issue id '.$issueId );

		try {
			// Create semaphore to prevent race conditions two users doing reordering.
			$sema = $this->createSemaLock( $issueId );
			
			// With the semaphore in our hands, retrieve the dossier ordering from DB.
			// That means, no-one can change the order at this point, so this is the only trueth.
			$dossierIdsSorted = $this->getDossierOrderFromIssueProps( $issueId );
			
			// Create convenient array with dossier ids at keys, for quick access later.
			$dossierIdsSorted = array_combine( array_values($dossierIdsSorted), array_fill(1,count($dossierIdsSorted),true) );
			if( isset($dossierIdsSorted[0]) ) { unset($dossierIdsSorted[0]); } // fix malformed id
	
			// Add the dossier at the end of ordering.
			$dossierIdsSorted[$dossierId] = true;
			
			// Store the changed dossier ordering at DB.
			$this->updateIssueDossierOrderSafeMode( $issueId, array_keys($dossierIdsSorted), null );
			
		} catch( BizException $e ) {
			$catchedBizException = $e;
		}

		// Release semaphore.
		if( isset($sema) && $sema ) {
			$this->releaseSemaLock( $sema );
		}
		
		// Restore the abort flag (that was changed above).
		ignore_user_abort( $orgAbortFlag );
		
		// Re-throw exception.
		if( isset($catchedBizException) && $catchedBizException && !$this->suppressErrors ) {
			throw $catchedBizException;
		}
	}
	
	/**
	 * Remove one dossier from the (existing) dossier order for a given issue.
	 * Only the custom issue property (in the database) is updated. See class header for details.
	 *
	 * @param integer $issueId
	 * @param integer $dossierId Dossier (id) to remove.
	 * @throws BizException When issue is locked (and suppressErrors() not called)
	 */
	public function removeDossierFromOrder( $issueId, $dossierId )
	{
		// Make sure user can not break half-way since we need to abort nicely to clean our stuff,
		// especially the semaphore that is created below.
		$orgAbortFlag = ignore_user_abort(1);

		LogHandler::Log( __CLASS__, 'DEBUG', 'Removing dossier id '.$dossierId.' from dossier order for issue id '.$issueId );

		try {
			// Create semaphore to prevent race conditions two users doing reordering.
			$sema = $this->createSemaLock( $issueId );
			
			// With the semaphore in our hands, retrieve the dossier ordering from DB.
			// That means, no-one can change the order at this point, so this is the only trueth.
			$dossierIdsSorted = $this->getDossierOrderFromIssueProps( $issueId );
			
			// Create convenient array with dossier ids at keys, for quick access later.
			$dossierIdsSorted = array_combine( array_values($dossierIdsSorted), array_fill(1,count($dossierIdsSorted),true) );
			if( isset($dossierIdsSorted[0]) ) { unset($dossierIdsSorted[0]); } // fix malformed id
	
			// Remove the dossier from the ordering.
			if( isset($dossierIdsSorted[$dossierId]) ) {
				unset( $dossierIdsSorted[$dossierId] );
			}
			
			// Store the changed dossier ordering at DB.
			$this->updateIssueDossierOrderSafeMode( $issueId, array_keys($dossierIdsSorted), null );
			
		} catch( BizException $e ) {
			$catchedBizException = $e;
		}

		// Release semaphore.
		if( isset($sema) && $sema ) {
			$this->releaseSemaLock( $sema );
		}
		
		// Restore the abort flag (that was changed above).
		ignore_user_abort( $orgAbortFlag );
		
		// Re-throw exception.
		if( isset($catchedBizException) && $catchedBizException && !$this->suppressErrors ) {
			throw $catchedBizException;
		}
	}
	
	/**
	 * Update the -production- dossier order for a given issue.
	 * The new order is saved in the custom issue property (in the database). The function has
	 * 'SafeMode' in its name, which indicates this function is called -within- the semaphore.
	 * See class header for details.
	 *
	 * @param integer $issueId
	 * @param array|null $newOrder List of dossier ids. NULL to get latest from DB.
	 * @param array|null $originalOrder List of dossier ids upon the $newOrder was based.
	 *        Pass NULL when there was no order yet, or when adding/removing dossiers.
	 * @throws BizException Throws BizException when there's error occurred.
	 */
	private function updateIssueDossierOrderSafeMode( $issueId, $newOrder, $originalOrder ) 
	{
		// If no new order given, query the latest.
		if( is_null($newOrder) ) {
			$newOrder = $this->queryIssueDossierOrder( $issueId );
		}
		if( is_null($newOrder) ) {
			$newOrder = array();
		}
		
		// Get latest dossier order from DB.
		$dbOrder = $this->getDossierOrderFromIssueProps( $issueId );
		
		// When not found at custom props, update the structure with the initial dossier order 
		// for the first time EVER...
		if( !is_null($dbOrder) && !is_null($originalOrder) ) {
			if( implode( ',', $originalOrder ) != implode( ',', $dbOrder ) ) {
				LogHandler::Log( 'BizPubIssue','DEBUG', 'Dossier order differs from '.
					'the order read from DB for issue id "'.$issueId.'".' );
				// TODO: Add S-code to ERR_UPDATE_DOSSIERS_ORDER at TMS.
				$detail = 'Dossier order stored at DB is different. Requires to retrieve order again.';
				throw new BizException( 'ERR_UPDATE_DOSSIERS_ORDER', 'Client', $detail, null, null, 'INFO' );
			}
		}

		// Update issue at database with the new dossier order.
		$this->setDossierOrderAtIssueProps( $issueId, $newOrder );
		
		// Resolve publication channel from issue.
		require_once BASEDIR.'/server/utils/ResolveBrandSetup.class.php';
		$brandSetup = new WW_Utils_ResolveBrandSetup();
		$brandSetup->resolveIssuePubChannelBrand( $issueId );
		$pubChannelObj = $brandSetup->getPubChannelInfo();
		
		// N-cast the new dossier order (to clients listening).
		require_once BASEDIR.'/server/smartevent.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$base64DossierIds = $this->getBase64DossierIds( $pubChannelObj->Type, $newOrder );
		new smartevent_issuereorder( 
			BizSession::getTicket(), $pubChannelObj->Type, $issueId, $base64DossierIds );
	}
	
	/**
	 * Determine a default dossier order for all dossiers within a given issue.
	 * Should be called when there is no order saved yet. See class header for details.
	 *
	 * @param integer $issueId
	 * @return array dossier ids
	 */
	private function queryIssueDossierOrder( $issueId )
	{
		// Query DB for all dossiers that are assigned to the given issue.
		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		$minProps = array( 'ID', 'Type', 'Name' );
		$params = array( 
			new QueryParam( 'IssueId', '=', $issueId ),
			new QueryParam( 'Type', '=', 'Dossier' ) );
		$response = BizQuery::queryObjects(
			BizSession::getTicket(), BizSession::getShortUserName(), $params, 1, 0, null, false, null, $minProps, null, null, 0 );

		// Determine column indexes to work with.
		$indexes = array_combine( array_values($minProps), array_fill(1,count($minProps), -1) );
		foreach( array_keys($indexes) as $colName ) {
			foreach( $response->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found
				}
			}
		}
		
		// Collect the dossier ids from search results.
		$dossierIds = array();
		foreach( $response->Rows as $row ) {
			$dossierIds[] = $row[$indexes['ID']];
		}
		return $dossierIds;
	}
	
	/**
	 * Retrieve the issue dossier order from issue custom admin property C_HIDDEN_DPS_DOSSIER_ORDER.
	 *
	 * @param integer issueId
	 * @return array|null Dossier ids. NULL when property not set.
	 */
	private function getDossierOrderFromIssueProps( $issueId )
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$packedOrder = DBChanneldata::getCustomPropertyValueForIssue( $issueId, 'C_HIDDEN_DPS_DOSSIER_ORDER' );
		if( !is_null( $packedOrder ) ) {
			$dossierIds = explode( ',', $packedOrder );
		} else {
			$dossierIds = null;
		}
		return $dossierIds;
	}

	/**
	 * Update the issue dossier order at issue custom admin property C_HIDDEN_DPS_DOSSIER_ORDER.
	 *
	 * @param integer issueId
	 * @param array $dossierIds
	 */
	private function setDossierOrderAtIssueProps( $issueId, $dossierIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$packedOrder = implode( ',', $dossierIds );
		DBChanneldata::setCustomPropertyValueForIssue( $issueId, 'C_HIDDEN_DPS_DOSSIER_ORDER', $packedOrder );
	}
	
	/**
	 * Create a semaphore lock to make writing the dossier order an 'atomic' action.
	 * Whatever happens, the caller needs to exit the semaphore after usage by calling releaseSemaLock().
	 *
	 * @param integer issueId
	 * @return integer Semaphore id. 
	 * @throws BizException When the semaphore could not be established.
	 */
	private function createSemaLock( $issueId )
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$semaName = 'DPS_ProdOrder_'.$issueId;
		$bizSemaphore = new BizSemaphore();
		$lifetime = defined( 'ISSUEORDER_SEMAPHORE_LIFETIME' ) ? ISSUEORDER_SEMAPHORE_LIFETIME : 5;
		$bizSemaphore->setLifeTime( $lifetime ); // x seconds before it gets auto-killed
		$semaphoreId = $bizSemaphore->createSemaphore( $semaName );
		if( !$semaphoreId ) {
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
			$otherUserFull = $otherUser ? DBUser::getFullName( $otherUser ) : '?';
			$detail = 'User "'.$otherUserFull.'" is currently reordering. Please wait and try again.';
			throw new BizException( 'ERR_UPDATE_DOSSIERS_ORDER', 'Client', $detail );
		}
		return $semaphoreId;
	}

	/**
	 * Release the semaphore lock obtained by createSemaLock().
	 *
	 * @param integer Semaphore id.
	 */
	private function releaseSemaLock( $semaphoreId )
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		BizSemaphore::releaseSemaphore( $semaphoreId );
	}

	/**
	 * Construct the DossierIds into base64 format
	 *
	 * @param string $channelType Publication channel type
	 * @param array $dossierIdsOrder Array of dossierIds order
	 * @return unknown
	 */
	public function getBase64DossierIds( $channelType, $dossierIdsOrder )
	{
		$uint32 = '';
		if( $channelType == 'dps' ) {
			foreach( $dossierIdsOrder as $dossierId ) {
				$uint32 .= pack( "V", $dossierId );
			}
		}
		$base64 = base64_encode( $uint32 );

		return $base64;
	}
}
