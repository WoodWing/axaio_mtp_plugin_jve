<?php
/**
 * @since       v9.6
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Utils class for Adobe DPS to work with folio files.
 */

class AdobeDps2_Utils_Folio
{
	const CONTENTTYPE = 'application/vnd.adobe.article+zip';
	const RENDITION   = 'output';
	const CHANNELTYPE = 'dps2';
	
	/**
	 * Validates and collect the layout information that can be published or updated to Adobe DPS.
	 *
	 * Function validates the criteria needed for a layout to be published or updated to Adobe DPS.
	 * When the workflow status of a layout is not set to 'Ready to be published', the layout is
	 * considered as not yet ready to be published/updated, therefore no action will be taken.
	 *
	 * @param string $docIdOrId For 'createObjects' action, object id doesn't exists yet, therefore, document id is used.
	 * @param Target[] $targets Should only contain one target in the Target list.
	 * @param Attachment[] $files List of file attachments that belong to the object passed in.
	 * @param int $statusId Workflow status id
	 * @param string $action Possible values: 'createObjects', 'saveObjects', 'setObjectProperties'
	 * @return array
	 */
	public static function validateAndCollect( $docIdOrId, $targets, $files, $statusId, $action )
	{
		do {
			$pubChannelId = 0;
			$pubChannelName = '';
			$isFolioAvailable = false;
			$isStatusReadyToPublish = false;

			// Determine whether or not the layout is stored in a the 'dps2' channel.
			$hookedLayout = array();
			$pubChannelEditions = array();
			$skipCollect = true;
			$isLayoutInAP = self::isAdobeDps2Channel( $targets, $pubChannelEditions );
			if( !$isLayoutInAP ) {
				break; // Since no 'dps2' pub channel, no further check needed.
			}
			$pubChannelId = $pubChannelEditions['pubChannelId'];
			$pubChannelName = $pubChannelEditions['pubChannelName'];
			$skipCollect = false;

			// Determine if the layout is in 'Ready To be published' status.
			if( $statusId ) {
				$isStatusReadyToPublish = self::isStatusReadyToPublish( $statusId );
			}
			if( !$isStatusReadyToPublish ) {
				$skipCollect = true;
				break; // The layout is not yet ready to be published, therefore no further check needed.
			}

			// Determine whether or not a folio file is uploaded along with the layout.
			if( $files ) {
				foreach( $files as $file ) {
					if( $file->Rendition == self::RENDITION &&
						$file->Type == self::CONTENTTYPE ) {
						$isFolioAvailable = true;
						break; // exit foreach
					}
				}
			} else if( $action != 'createObjects' ) {
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

				$objectsProps = DBObject::getObjectsPropsForRelations( array( $docIdOrId ));
				$isFolioAvailable = self::isFolioAvailable( $objectsProps, $docIdOrId, $pubChannelEditions );

			}
		} while( false ); // once only

		// When all tests are positive, remember the layout for further processing in runAfter().
		if( $isLayoutInAP && !$skipCollect && $isFolioAvailable ) {
			$hookedLayout = array( 
				'pubChannelId' => $pubChannelId, // No Object ID yet, so use DocumentID instead.
				'pubChannelName' => $pubChannelName
			);
		}

		if( $isLayoutInAP && !$skipCollect && !$isFolioAvailable ) {
			LogHandler::Log( 'AdobeDps2', 'INFO', 'Layout (id/documentid="'.$docIdOrId.'") is targeted to "'.
				AdobeDps2_Utils_Folio::CHANNELTYPE .'" publication channel, however, server job is ' .
				'not created because no folio file is found or layout is not set to \'Ready to be published\' status.' );
		}

		if( !$isLayoutInAP ) {
			LogHandler::Log( 'AdobeDps2', 'INFO', 'Layout (document id="'.$docIdOrId.'") '.
				'is not targeted to "'.AdobeDps2_Utils_Folio::CHANNELTYPE .
				'" publication channel. No server job is created.' );
		}
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'AdobeDps2', 'DEBUG', 'validateAndCollect: Hooked layout: '.print_r($hookedLayout,true) );
		}
		return $hookedLayout;
	}

	/**
	 * Collect all the objects that needs a server job to be created.
	 *
	 * @param MetaData[] $metaDataList
	 * @param int $statusId
	 * @return array
	 */
	public static function validateAndCollectMultiObjects( $metaDataList, $statusId )
	{
		require_once BASEDIR . '/server/dbclasses/DBTargetEdition.class.php';

		$hookedLayouts = array();
		$dpsLayouts = array();

		$objIds = array_keys( $metaDataList );
		$rows = DBTargetEdition::listTargetEditionRowsByObjectId( $objIds, self::CHANNELTYPE );

		// Collect All objects' 'dps2' pubChannelId and its editionIds.
		if( $rows ) foreach( $rows as $row ) {
			$layoutId = $row['objectid'];
			if( !isset( $dpsLayouts[$layoutId] )) {
				// Initialize
				$dpsLayouts[$layoutId] = array( 
					'pubChannelId' => $row['channelid'],
					'pubChannelName' => $row['channelname'],
                    'editionIds' => array()
                );
			}
			if ( $row['editionid'] != 0 ) {
				$dpsLayouts[$layoutId]['editionIds'][] = $row['editionid'];
			}
		}

		if( $dpsLayouts ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

			$dpsLayoutIds = array_keys( $dpsLayouts );

			// Collect the workflow statuses for -all- objects.
			$objsReadyPublishStatus = self::filterObjectIdsOnStatusReadyToPublish( $statusId, $dpsLayoutIds );

			// Collect the object properties for -all- objects.
			$objectsProps = DBObject::getObjectsPropsForRelations( $dpsLayoutIds );

			// Checking per object starts here.
			foreach( $dpsLayouts as $layoutId => $pubChannelEditions ) {
				do {
					$skipCollect = false;
					$isFolioAvailable = false;
					if( !isset( $objsReadyPublishStatus[$layoutId] )) { // Whether this layout is changed to 'Ready to be Published' status.
						$skipCollect = true;
						break; // The layout is not yet ready to be published, therefore no further check needed.
					}

					$isFolioAvailable = self::isFolioAvailable( $objectsProps, $layoutId, $pubChannelEditions );
				} while ( false );

				// When all tests are positive, remember the layout for further processing in runAfter().
				if( !$skipCollect && $isFolioAvailable ) {
					$hookedLayouts[$layoutId] = array( 
						'pubChannelId' => $dpsLayouts[$layoutId]['pubChannelId'],
						'pubChannelName' => $dpsLayouts[$layoutId]['pubChannelName']
					);
				}

				if( !$skipCollect && !$isFolioAvailable ) {
					LogHandler::Log( 'AdobeDps2', 'INFO', 'Layout (id="'.$layoutId.'") is targeted to "'.
						AdobeDps2_Utils_Folio::CHANNELTYPE .'" publication channel, however, server job is ' .
						'not created because no folio file is found or layout is not set to \'Ready to be published\' status.' );
				}
			}
			$nonDpsChannel = array_diff( $objIds, $dpsLayoutIds );
			if( $nonDpsChannel ) {
				LogHandler::Log( 'AdobeDps2', 'INFO', 'Layouts (ids=["'.implode( ',', $nonDpsChannel ).'"]) '.
					'are not targeted to "'.AdobeDps2_Utils_Folio::CHANNELTYPE .
					'" publication channel. No server job is created for those layouts.' );
			}
		}
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'AdobeDps2', 'DEBUG', 'validateAndCollectMultiObjects: Hooked layouts: '.print_r($hookedLayouts,true) );
		}
		return $hookedLayouts;
	}

	/**
	 * Checks and returns the 'dps2' publication channel id.
	 *
	 * Function checks if the passed in Targets ( assumed that it contains only one Target )
	 * is targeted to 'dps2' channel.
	 *
	 * $pubChannelEditions: When there's 'dps2' channel defined, the structure will be returned
	 * as below:
	 *      $pubChannelEditions = array(
	 *                  'pubChannelId' => 9,
	 *                  'pubChannelName' => 'Adobe DPS channel',
	 *                  'editionIds' => array( 5, 18, 6 )
	 *                 )
	 *
	 * @param Target[] $targets
	 * @param array $pubChannelEditions [IN/OUT] The 'dps2' publication channel id with its editions. Refer to function header for its structure.
	 * @return bool Whether there is 'dps2' publication channel.
	 */
	public static function isAdobeDps2Channel( $targets, &$pubChannelEditions )
	{
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		$pubChannelId = 0;
		if( $targets ) foreach( $targets as $target ) { // Should have only one target!
			$pubChannelObj = DBChannel::getPubChannelObj( $target->PubChannel->Id );
			if( $pubChannelObj && $pubChannelObj->Type == AdobeDps2_Utils_Folio::CHANNELTYPE ) {
				$pubChannelId = $target->PubChannel->Id; // Assume to always have only one Target, so one PubChannelId.
				$pubChannelEditions['pubChannelId'] = $pubChannelId;
				$pubChannelEditions['pubChannelName'] = $target->PubChannel->Name;
				$pubChannelEditions['editionIds'] = array();
				if( $target->Editions ) foreach( $target->Editions as $edition ) {
					$pubChannelEditions['editionIds'][] = $edition->Id;
				}
				break; // exit foreach
			}
		}
		return $pubChannelId ? true : false;
	}

	/**
	 * Checks if the given layout has a folio in the filestore.
	 *
	 * As long as one edition of the Layout has the folio, the function returns true.
	 *
	 * $pubChannelEditions: should be defined as below:
	 *      $pubChannelEditions = array(
	 *                  'pubChannelId' => 9,
	 *                  'editionIds' => array( 5, 18, 6 )
	 *
	 * @param array $objectsProps List of ids as the key and its properties.
	 * @param string $docIdOrId Object id or document id (if the layout has no object id yet).
	 * @param array $pubChannelEditions Refer to function header.
	 * @return bool
	 */
	private static function isFolioAvailable( $objectsProps, $docIdOrId, $pubChannelEditions )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectRenditions.class.php';

		$isFolioAvailable = false;
		if( !$objectsProps[$docIdOrId] ) {
			LogHandler::Log( 'AdobeDps2', 'ERROR', 'Failed retrieving object info and object version for layout ' .
				'(id="'.$docIdOrId.'"). ' );
		} else {
			$layoutProps = $objectsProps[$docIdOrId];
			$storeName = $layoutProps['StoreName'];
			$version = $layoutProps['Version'];
			$rendition = self::RENDITION; // folio
			if( $pubChannelEditions['editionIds'] ) foreach( $pubChannelEditions['editionIds'] as $editionId ) {
				$formats = DBObjectRenditions::getEditionRenditionFormats( $docIdOrId, $version, $editionId, $rendition );
				if( $formats ) foreach( $formats as $format ) {
					if( $format == self::CONTENTTYPE ) { // folio
						require_once BASEDIR.'/server/bizclasses/BizStorage.php';
						$attachObj = StorageFactory::gen( $storeName, $docIdOrId, $rendition, $format, $version, null, $editionId );
						if( $attachObj->doesFileExist() ) {
							$isFolioAvailable = true;
							break; // As long as one edition has folio, it's good enough.
						}
					}
				}
			}
		}
		return $isFolioAvailable;
	}

	/**
	 * Checks if the "Ready to be Published" flag is configured for a given workflow status.
	 *
	 * @param int $statusId Workflow status id
	 * @return bool TRUE when the flag is set, else FALSE.
	 */
	public static function isStatusReadyToPublish( $statusId )
	{
		require_once BASEDIR . '/server/bizclasses/BizAdmStatus.class.php';
		$statusObj = BizAdmStatus::getStatusWithId( $statusId );
		return isset( $statusObj->ReadyForPublishing ) && $statusObj->ReadyForPublishing;
	}

	/**
	 * Checks if the workflow status for the layouts being set to 'ReadyForPublishing' status.
	 *
	 * It first checks the original status, if it is already in 'ReadyForPublishing' status, meaning the
	 * user did not update the workflow status during the multisetproperties.
	 * The function will return a list of object ids and its value:
	 *      L> value = true: The layout is being set to 'ReadyForPublishing' status.
	 *      L> value = false: The layout was already in 'ReadyForPublishing' status, so no new server job needed.
	 *
	 * @param int $statusId Layout status of which user has selected in the dialog.
	 * @param array[] $objectIds
	 * @return array List of id(key) and boolean(value) whether or not the layout is ready to be published.
	 */
	private static function filterObjectIdsOnStatusReadyToPublish( $statusId, $objectIds )
	{
		require_once BASEDIR . '/server/bizclasses/BizAdmStatus.class.php';

		$admStatusObject = BizAdmStatus::getStatusWithId( $statusId );
		$objsReadyPublishStatus = array();
		if( isset( $admStatusObject->ReadyForPublishing ) && $admStatusObject->ReadyForPublishing ) {
			// First we flag -all- the layouts to be status not changed.
			$objsReadyPublishStatus = array_fill_keys( $objectIds, false );

			// Retrieves the object(s) that are previously not yet in the 'readyforpublishing' status.
			// *** We are only interested for layouts that will be moving from 'non-readyforpublishing' to 'readyforpublishing' state.
			// In other terms, when a layout was already in 'readyforpublishing' state, and the multisetproperties action
			// is set as 'readyforpublishing', there's no reason to flag it as status changed to creates server job for
			// this layout.
			$dbDriver = DBDriverFactory::gen();
			$odb = $dbDriver->tablename( 'objects' );
			$sdb = $dbDriver->tablename( 'states' );
			$sql = 'SELECT o.`id`, o.`state` FROM '.$odb.' o '
				. 'INNER JOIN ' . $sdb. ' s ON ( o.`state` = s.`id` ) '
				. 'WHERE o.`id` IN (' . implode( ',', $objectIds ).') AND '
				. 's.`readyforpublishing` = ? '; // Refer to *** above.
			$params = array( '' ); // '' means disabled.
			$sth = $dbDriver->query( $sql, $params );

			// Here, we flag the layouts that has changed the status from 'non-readyforpublishing' to 'readyforpublishing'.
			while (( $row = $dbDriver->fetch( $sth ))) {
				$objsReadyPublishStatus[$row['id']] = true;
			}
		}
		return $objsReadyPublishStatus;
	}
	
	/**
	 * Tells whether or not the given output format is supported for upload to Adobe DPS.
	 *
	 * Enterprise uses a homebrewed file format for article images that can be uploaded
	 * to Adobe DPS. It is a combined format whereby the left-hand-side tells the
	 * native mime type of the image file and the right-hand-side tells the homebrewed image type.
	 * 
	 * @param string $format The combined format.
	 * @return boolean TRUE when provided article image format is known, else FALSE.
	 */
	public static function isSupportedOutputImageFormat( $format )
	{
		static $formats = array(
			// social images:
			'image/jpeg|application/adobedps-social-image' => true,
			'image/png|application/adobedps-social-image' => true,
			// article images:
			'image/jpeg|application/adobedps-article-image' => true,
			'image/png|application/adobedps-article-image' => true,
		);
		return array_key_exists( $format, $formats );
	}
	
	/**
	 * Splits a homebrewed format into a native dime type and a homebrewed image type.
	 *
	 * See isSupportedOutputImageFormat() for explanation of the homebrewed format.
	 * Please call isSupportedOutputImageFormat() before calling this parse function.
	 *
	 * @param string $format The homebrewed (combined) file format
	 * @return string[] Index 0 has dime type of image file, index 1 has homebrewed image type.
	 */
	public static function parseSupportedOutputImageFormat( $format )
	{
		$retVal = array();
		if( strpos( $format, '|' ) !== false ) {
			$retVal = explode( '|', $format );
		}
		return $retVal;
	}
	
	/**
	 * Lists the article access options that are supported by Adobe DPS.
	 *
	 * Supported options are: 'protected','metered' and'free'
	 *
	 * @return string[]
	 */
	public static function getArticleAccessOptions()
	{
		return array( 0 => 'protected', 1 => 'metered', 2 => 'free' );
		// L> Please do not change the indexes [0..2] since those are stored in the 
		//    custom admin property named C_DPS2_CHANNEL_ART_ACCESS for the PubChannels.
		//    So adding options is no problem, but when inserting, preserve the indexes.
	}
}
