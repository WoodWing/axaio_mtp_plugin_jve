<?php
/**
 * @package     Enterprise
 * @subpackage  BizClasses
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/BizAnaBase.class.php';

class BizAnaObject extends BizAnaBase
{
	/**
	 * Compose an Object in Analytics data format.
	 *
	 * @param Object $wflObject Enterprise workflow object.
	 * @param boolean $revealUsernames Whether to send usernames to the Analytics Server.
	 * @return stdClass Object for analytics server.
	 */
	public static function composeAnaObject( Object $wflObject, $revealUsernames = false )
	{
		$anaObject = new stdClass();
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$propArray = BizProperty::metaDataToBizPropArray( $wflObject->MetaData );
		self::typeCastPropertyValues( $propArray );
		self::composeAnaMetaData( $propArray, $anaObject, $revealUsernames );
		$anaObject->targets = self::composeAnaTargets( $wflObject->Targets );
		$anaObject->relations = self::composeAnaRelations( $wflObject->Relations );
		$objProps = self::getObjectPropsForFileInfo( $anaObject );
		$anaObject->pages = self::getAnaPages( $objProps );
		$anaObject->files = self::getAnaObjectFiles( $objProps );
		$anaObject->elements = self::composeAnaElements( $wflObject->Elements );
		$anaObject->objectlabels = self::composeAnaObjLabels( $wflObject->ObjectLabels );
		$anaObject->messages = self::composeAnaMessages( $wflObject->MetaData->BasicMetaData->ID, $wflObject->MessageList->Messages );

		return $anaObject;
	}

	/**
	 * Compose an Object of changed / updated object properties.
	 *
	 * @param MetaDataValue[] $metaDataValues The changed metadata values of the objects.
	 * @param boolean $revealUsernames Whether to send usernames to the Analytics Server.
	 * @return stdClass Object of properties for Analytics Server.
	 */
	public static function composeAnaMultiSetObjectProperties( $metaDataValues, $revealUsernames = false )
	{
		$objectProps = new stdClass();
		$propArray = self::convertMetaDataValuesToProperties( $metaDataValues );

		self::composeAnaMetaData( $propArray, $objectProps, $revealUsernames );

		self::changePropIdIntoObject( $propArray, $objectProps );

		return $objectProps;
	}

	/**
	 * Cast the list of property's value to the correct type cast.
	 *
	 * The following property type will be casted in this function:
	 * - bool
	 * - int
	 * - double
	 *
	 * multilist, multistring are already taken care of by metaDataToBizPropArray().
	 * Other property types such as string, datetime, multiline, etc will be left as it is.
	 *
	 * @param array $propArray [In/Out] List of property-value to be type-casted.
	 */
	private static function typeCastPropertyValues( &$propArray )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		foreach( $propArray as $propName => $propValue ) {
			if( BizProperty::isCustomPropertyName( $propName ) ) {
				$propType = BizProperty::getCustomPropertyType( $propName );
			} else {
				$propType = BizProperty::getStandardPropertyType( $propName );
			}
			switch( $propType ) {
				case 'bool':
					$propArray[$propName] = $propValue ? true : false;
					// Note that boolval() requires PHP 5.5
					break;
				case 'int':
					$propArray[$propName] = intval( $propValue );
					break;
				case 'double':
					$propArray[$propName] = floatval( $propValue );
					break;
				// multilist, multistring are already taken care of by metaDataToBizPropArray().
				// Other types ( string, datetime, multiline, etc ) are left as it is.
			}
		}
	}

	/**
	 * Enriches the object for analytics server with MetaData retrieved from Enterprise object.
	 *
	 * @param array $propArray List of property-value pairs.
	 * @param stdClass $anaObject
	 * @param boolean $revealUsernames Whether to send usernames to the Analytics Server.
	 */
	private static function composeAnaMetaData( $propArray, $anaObject, $revealUsernames = false )
	{
		foreach( $propArray as $propKey => $propVal ) {
			switch( $propKey ) {
				case 'ID':
					$anaObject->entid = $propVal;
					break;
				case 'PublicationId':
				case 'Publication':
					if( !isset( $anaObject->brand )) {
						$anaObject->brand = new stdClass();
					}
					if( $propKey == 'PublicationId' ) {
						$anaObject->brand->entid = intval( $propVal );
					} else {
						$anaObject->brand->name = $propVal;
					}
					break;
				case 'CategoryId':
				case 'SectionId':
				case 'Category':
				case 'Section':
					if( !isset( $anaObject->category )) {
						$anaObject->category = new stdClass();
					}
					if( $propKey == 'CategoryId' || $propKey == 'SectionId' ) {
						$anaObject->category->entid = intval( $propVal );
					} else {
						$anaObject->category->name = $propVal;
					}
					break;
				case 'StateId':
				case 'State':
				case 'StatePhase':
					if( !isset( $anaObject->status )) {
						$anaObject->status = new stdClass();
					}
					if( $propKey == 'StateId' ) {
						$anaObject->status->entid = intval( $propVal );
					} else if( $propKey == 'StatePhase' ) {
						$anaObject->status->phase = $propVal;
					} else {
						$anaObject->status->name = $propVal;
					}
					break;
				case 'StateColor':
					$anaObject->status->color = self::removeHashPrefix( $propVal );
					break;
				case 'HighResFile':
				case 'Slugline':
				case 'PlainContent':
				case 'Comment':
					// Contents can be sensitive information, so exclude them.
					break;
				default:
					$anaPropKey = strtolower( $propKey );
					$anaObject->$anaPropKey = $propVal;
					break;
			}
		}

		// Only provide the status type when it is required.
		// For single object event, status type is definitely needed, but not for multiple objects event,
		// therefore check before we assign the status type.
		if( isset( $anaObject->status->entid) && isset( $anaObject->status->name ) && isset( $anaObject->type )) {
			$anaObject->status->type = $anaObject->type;
		}
		if( $revealUsernames != true ) {
			if( isset( $anaObject->creator ) ) {
				$anaObject->creator = null;
			}
			if( isset( $anaObject->modifier ) ) {
				$anaObject->modifier = null;
			}
			if( isset( $anaObject->deletor ) ) {
				$anaObject->deletor = null;
			}
			if( isset( $anaObject->routeto ) ) {
				$anaObject->routeto = null;
			}
			if( isset( $anaObject->lockedby ) &&
				$anaObject->lockedby ) { // only set to anonymous when object is locked. When object is not locked, $anaObject->lockedby is set to empty string ''.
				$anaObject->lockedby = 'wwanonymous';
			}
		}
		// hotfix: SC (on Windows?) seems to send UTF-8 BOM marker for empty RouteTo field.
		// Even though SC should fix, we polish it away for older SC versions.
		if( isset( $anaObject->routeto ) && 
				$anaObject->routeto == chr(0xEF).chr(0xBB).chr(0xBF) ) { // UTF-8 BOM marker?
			$anaObject->routeto = ''; // remove BOM marker
		}
	}

	/**
	 * Converts a list of object properties from MetaDataValues data structure into a
	 * more flatten key-value(s) map structure.
	 *
	 * The MetaDataValues structure is used through the Workflow interface, while the
	 * key-value(s) map is ready to encode directly to JSON for the Analytics interace.
	 * The property values are type-casted to their appropriate storage type.
	 *
	 * IMPORTANT: This function might be a good candidate to move to the BizProperties class
	 *            however, it is kept inside the Analytics plugin to make it less independent
	 *            of the core since it has its own release strategy.
	 *
	 * @param MetaDataValue[]|null $mdValues Object metadata properties to be converted.
	 * @return array|null Key-value map of converted properties. (Keys = prop names, Values = prop values.)
	 */
	private static function convertMetaDataValuesToProperties( $mdValues )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		if( $mdValues ) {
			$propArray = array();
			foreach( $mdValues as $mdValue ) {
				if( $mdValue->PropertyValues ) {
					if( BizProperty::isCustomPropertyName( $mdValue->Property ) ) {
						$propType = BizProperty::getCustomPropertyType( $mdValue->Property );
					} else {
						$propType = BizProperty::getStandardPropertyType( $mdValue->Property );
					}
					switch( $propType ) {
						case 'multilist':
						case 'multistring':
							$propValues = array();
							foreach( $mdValue->PropertyValues as $propValue ) {
								$propValues[] = $propValue->Value;
							}
							break;
						case 'bool':
							$propValues = $mdValue->PropertyValues[0]->Value ? true : false;
							// Note that boolval() requires PHP 5.5
							break;
						case 'int':
							$propValues = intval( $mdValue->PropertyValues[0]->Value );
							break;
						case 'double':
							$propValues = floatval( $mdValue->PropertyValues[0]->Value );
							break;
						default: // string, multiline, datetime, etc
							$propValues = $mdValue->PropertyValues[0]->Value;
							break;
					}
				} else {
					$propValues = null;
				}
				$propArray[$mdValue->Property] = $propValues;
			}
		} else {
			$propArray = null;
		}
		return $propArray;
	}

	/**
	 * Compose targets in Analytics data format.
	 *
	 * The targets are composed from workflow object targets.
	 *
	 * @param Target[]|null $wflTargets List of worflow object targets.
	 * @return stdClass[]|null List of targets in Analytics data format
	 */
	public static function composeAnaTargets( $wflTargets )
	{
		if( $wflTargets ) {
			$anaTargets = array();
			foreach( $wflTargets as $wflTarget ) {
				$anaTarget = new stdClass();
				$anaTarget->pubchannel = new stdClass();
				$anaTarget->pubchannel->entid = intval( $wflTarget->PubChannel->Id );
				$anaTarget->pubchannel->name = $wflTarget->PubChannel->Name;
				$anaTarget->issue = new stdClass();
				$anaTarget->issue->entid = intval( $wflTarget->Issue->Id );
				$anaTarget->issue->name = $wflTarget->Issue->Name;

				$anaEditions = array();
				if( $wflTarget->Editions ) foreach( $wflTarget->Editions as $wflEdition ) {
					$anaEdition = new stdClass();
					$anaEdition->entid = intval( $wflEdition->Id );
					$anaEdition->name = $wflEdition->Name;
					$anaEditions[] = $anaEdition;
				} else {
					//When editions are empty, all editions within the issue are meant. An object can't be targeted to an issue without editions.
					require_once dirname(__FILE__).'/BizAnaIssue.class.php';
					$editions = BizAnaIssue::getEditions( $wflTarget->PubChannel->Id, $wflTarget->Issue->Id );
					if( $editions ) foreach( $editions as $edition ) {
						$anaEdition = new stdClass();
						$anaEdition->entid = $edition->entid;
						$anaEdition->name = $edition->name;
						$anaEditions[] = $anaEdition;
					}
				}
				$anaTarget->editions = $anaEditions ? $anaEditions : null;

				$anaTarget->publisheddate = self::convertDateTimeToUTC( $wflTarget->PublishedDate );
				$anaTarget->publishedversion = $wflTarget->PublishedVersion;
				$anaTargets[] = $anaTarget;
			}
		} else {
			$anaTargets = null;
		}
		return $anaTargets;
	}

	/**
	 * Compose relations in Analytics data format.
	 *
	 * The relations are composed from workflow object relations.
	 *
	 * @param Relation[]|null $wflRelations List of workflow object relations.
	 * @return stdClass[]|null List of object relations in Analytics data format
	 */
	public static function composeAnaRelations( $wflRelations )
	{
		if( $wflRelations ) {
			$anaRelations = array();
			foreach( $wflRelations as $wflRelation ) {
				$anaRelation = new stdClass();
				$anaRelation->type = $wflRelation->Type;
				$anaRelation->parent = new stdClass();
				$anaRelation->parent->entid = intval( $wflRelation->Parent );
				$anaRelation->parent->name = $wflRelation->ParentInfo->Name;
				$anaRelation->parent->type = $wflRelation->ParentInfo->Type;
				$anaRelation->parent->version = $wflRelation->ParentVersion;
				$anaRelation->child = new stdClass();
				$anaRelation->child->entid = intval( $wflRelation->Child );
				$anaRelation->child->name = $wflRelation->ChildInfo->Name;
				$anaRelation->child->type = $wflRelation->ChildInfo->Type;
				$anaRelation->child->version = $wflRelation->ChildVersion;

				// EA-505 During a copy action of a layout with placed objects in Smart Connection, relational targets are
				// not updated for the placed objects, which causes them to appear wrongly in Analytics. We solve this here
				// by taking the editions from the Placements of the relations, so the placed object shows correctly in the reports.
				if( $wflRelation->ParentInfo->Type == 'Layout' ) {
					if( $wflRelation->Type == 'Placed' ) {
						if( $wflRelation->Targets == null || empty($wflRelation->Targets ) ) {
							if( !empty($wflRelation->Placements) ) {
								require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
								$wflRelation->Targets = BizTarget::getTargets( null, $wflRelation->ParentInfo->ID );

								$editions = array();
								foreach( $wflRelation->Placements as $placement ) {
									if( $placement->Edition == null ) {
										// When editions is null, we need all editions in the channel.
										break;
									} else {
										$editions[] = $placement->Edition;
									}
								}
								if( $editions ) $wflRelation->Targets[0]->Editions = array_unique( $editions );
							}}}}

				$anaRelation->targets = self::composeAnaTargets( $wflRelation->Targets );
				$anaRelation->objectlabels = self::composeAnaObjLabels( $wflRelation->ObjectLabels );
				$anaRelations[] = $anaRelation;
			}
		} else {
			$anaRelations = null;
		}
		return $anaRelations;
	}

	/**
	 * Gets pages in Analytics data format.
	 *
	 * @param array $objProps Object properties to access files in filestore.
	 * @return stdClass[]|null List of layout pages in Analytics data format
	 */
	private static function getAnaPages( $objProps )
	{
		// Note: Even through we already have the pages in our hands, this function retrieves
		// them from DB because there is no file info given for the pages and it would be
		// quite tricky to find out which file belongs to which page exactly. Easier to get
		// all info from DB at once and build the complete data structure.

		// Produced pages ('Production') made by the redaction show what work is really done,
		// so when found, we prefer those. If not, we fall back to planned pages ('Planning')
		// created by the planner (using 3rd party plan system) so at least we can show
		// what pages are intended.
		$anaPages = null;
		if( $objProps ) {
			require_once dirname(__FILE__).'/DBAnaObject.class.php';
			$anaPages = DBAnaObject::getPages( $objProps->ID, 'Production' );
			if( !$anaPages ) {
				$anaPages = DBAnaObject::getPages( $objProps->ID, 'Planned' );
			}

			// Enrich the pages with file info (without retrieving files from filestore!).
			if( $anaPages ) foreach( $anaPages as &$anaPage ) {
				$anaPage->files = self::getAnaPageFiles( $objProps, $anaPage );
				// Remove the private page properties since those should not be
				// communicated with the Analytics server.
				unset( $anaPage->_nr );
				unset( $anaPage->_types );
				unset( $anaPage->_orientation );
			}
		}

		return $anaPages;
	}

	/**
	 * Gets object files in Analytics data format.
	 *
	 * @param array $objProps Object properties to access files in filestore.
	 * @return stdClass[]|null List of file descriptors in Analytics data format
	 */
	private static function getAnaObjectFiles( $objProps )
	{
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		$anaFiles = null;
		if( $objProps ) {
			$fileTypeMap = unserialize( $objProps->Types );
			if( $fileTypeMap ) {
				$anaFiles = array();
				foreach( $fileTypeMap as $rendition => $format ) {
					$fileStorage = StorageFactory::gen( $objProps->StoreName, $objProps->ID,
						$rendition, $format, $objProps->Version );
					$anaFile = new stdClass();
					$anaFile->rendition = $rendition;
					$anaFile->format = $format;
					$anaFile->size = $fileStorage->doesFileExist() ? $fileStorage->getSize() : 0;
					$anaFile->editionentid = null; // TODO (see WSDL: Attachment->EditionId)
					$anaFiles[] = $anaFile;
				}
			}
		}
		return $anaFiles;
	}

	/**
	 * Compose elements in Analytics data format.
	 *
	 * The elements are composed from workflow object elemenents.
	 *
	 * @param Element[]|null $wflElements
	 * @return stdClass[]|null List of text components in Analytics data format
	 */
	private static function composeAnaElements( $wflElements )
	{
		if( $wflElements ) {
			$anaElements = array();
			foreach( $wflElements as $wflElement ) {
				$anaElement = new stdClass();
				$anaElement->entid = $wflElement->ID;
				$anaElement->name = $wflElement->Name;
				$anaElement->lengthchars = $wflElement->LengthChars;
				$anaElement->lengthwords = $wflElement->LengthWords;
				$anaElement->lengthparas = $wflElement->LengthParas;
				$anaElement->lengthlines = $wflElement->LengthLines;
				$anaElements[] = $anaElement;
			}
		} else {
			$anaElements = null;
		}
		return $anaElements;
	}

	/**
	 * Compose labels in Analytics data format.
	 *
	 * The object labels are composed from worfklow object labels.
	 *
	 * @param ObjectLabel[]|null $wflObjLabels List of object labels.
	 * @return stdClass[]|null List of object labels in Analytics data format
	 */
	private static function composeAnaObjLabels( $wflObjLabels )
	{
		if( $wflObjLabels ) {
			$anaObjLabels = array();
			foreach( $wflObjLabels as $wflObjLabel ) {
				$anaObjLabel = new stdClass();
				$anaObjLabel->entid = $wflObjLabel->Id;
				$anaObjLabel->name = $wflObjLabel->Name;
				$anaObjLabels[] = $anaObjLabel;
			}
		} else {
			$anaObjLabels = null;
		}
		return $anaObjLabels;
	}

	/**
	 * Composes a list of messages in Analytics data format from a given list of
	 * messages in Workflow data format.
	 *
	 * It resolves the page number from the given page sequences.
	 * Only stickies and replies are included, others are excluded.
	 *
	 * @param inte $objId Object id for which the messages are posted.
	 * @param Message[]|null $wflMessages List of posted messages in Workflow format.
	 * @param boolean $revealUsernames Whether to send usernames to the Analytics Server.
	 * @return array|null List of messages in Analytics data format.
	 */
	private static function composeAnaMessages( $objId, $wflMessages, $revealUsernames = false )
	{
		if( $wflMessages ) {
			require_once dirname(__FILE__).'/DBAnaObject.class.php';
			$pageNumbers = DBAnaObject::getPageNumbers( $objId );
			$anaMessages = array();
			foreach( $wflMessages as $wflMessage ) {
				if( $wflMessage->MessageType == 'sticky' || $wflMessage->MessageType == 'reply' ) {
					$anaMessage = new stdClass();
					$anaMessage->entid = $wflMessage->MessageID;
					$anaMessage->type = $wflMessage->MessageType;
					$anaMessage->timestamp = self::convertDateTimeToUTC( $wflMessage->TimeStamp );
					if( $revealUsernames == true ) {
						$anaMessage->fromuser = $wflMessage->FromUser;
					} else {
						$anaMessage->fromuser = null;
					}
					if( $wflMessage->StickyInfo ) {
						$anaMessage->color = self::removeHashPrefix( $wflMessage->StickyInfo->Color );
						$pageSeq = $wflMessage->StickyInfo->PageSequence;
						if( array_key_exists( $pageSeq, $pageNumbers ) ) {
							$anaMessage->pagenumber = $pageNumbers[$pageSeq];
						} else {
							$anaMessage->pagenumber = '';
						}
					} else { // should not happen
						$anaMessage->color = null;
						$anaMessage->pagenumber = null;
					}
					$anaMessage->threadmessageentid = $wflMessage->ThreadMessageID;
					$anaMessage->replytomessageentid = $wflMessage->ReplyToMessageID;
					$anaMessage->status = $wflMessage->MessageStatus;
					$anaMessage->objectversion = $wflMessage->ObjectVersion;
					$anaMessages[] = $anaMessage;
				}
			}
		} else {
			$anaMessages = null;
		}
		return $anaMessages;
	}

	/**
	 * Gets file descriptors in Analytics data format.
	 *
	 * @param array $objProps Object properties to access files in filestore.
	 * @param stdClass $anaPage Layout page info in Analytics data format.
	 * @return stdClass[] List of file descriptors in Analytics data format
	 */
	private static function getAnaPageFiles( $objProps, $anaPage )
	{
		$anaFiles = array();
		// if the orientation is set use that as part of the page name. otherwise stick to the original filename.
		$pageNrString =  (!is_null($anaPage->_orientation) && !empty($anaPage->_orientation))
			? '-' . $anaPage->_nr . '-' . $anaPage->_orientation
			: '-' . $anaPage->_nr;
		$editionId = isset($anaPage->Edition->Id) ? $anaPage->Edition->Id : 0;
		foreach( unserialize( $anaPage->_types ) as $tp ) {
			$pageNrVal = preg_replace( '/[*"<>?\\\\|:]/i', '', $anaPage->number );
			$rendition = $tp[1];
			$format = $tp[2];
			$fileStorage = StorageFactory::gen( $objProps->StoreName, $objProps->ID,
				'page', $format, $objProps->Version,
				$pageNrVal.$pageNrString, $editionId );
			$anaFile = new stdClass();
			$anaFile->rendition = $rendition;
			$anaFile->format = $format;
			$anaFile->size = $fileStorage->doesFileExist() ? $fileStorage->getSize() : 0;
			$anaFile->editionentid = intval( $editionId );
			$anaFiles[] = $anaFile;
		}
		return $anaFiles;
	}

	/**
	 * Gets properties of an object.
	 *
	 * These properties can be used to access files in the filestore.
	 *
	 * @param stdClass $anaObject Workflow object in Analytics data format.
	 * @return null|stdClass Object properties to access files in filestore.
	 */
	private static function getObjectPropsForFileInfo( $anaObject )
	{
		require_once dirname(__FILE__).'/DBAnaObject.class.php';
		$types = null; $storeName = null;
		if( DBAnaObject::getObjectTypesAndStoreName( $anaObject->entid, $types, $storeName ) ) {
			$objProps = new stdClass();
			$objProps->ID = $anaObject->entid;
			$objProps->Version = $anaObject->version;
			$objProps->StoreName = $storeName;
			$objProps->Types = $types;
		} else {
			$objProps = null;
		}
		return $objProps;
	}


	/**
	 * Update the required property value to an object properties.
	 *
	 * For certain properties, its value has to be converted into Object properties (instead of just id).
	 * The properties involved are:
	 * - CategoryId
	 * - StateId
	 *
	 * @param array $propArray List of property and value, of which some of the value will be converted into object.
	 * @param stdClass $objectProps Object that contains all the properties that will be updated to Analytic Server.
	 */
	private static function changePropIdIntoObject( $propArray, $objectProps )
	{
		if( array_key_exists( 'CategoryId', $propArray )) {
			require_once BASEDIR . '/server/dbclasses/DBSection.class.php';
			$objectProps->category->name = DBSection::getSectionName( $propArray['CategoryId'] );
		}
		if( array_key_exists( 'StateId', $propArray )) {
			require_once BASEDIR .'/server/dbclasses/DBWorkflow.class.php';
			// Resolve status property.
			$statusId = $propArray['StateId'];
			$statusName = DBWorkflow::getStatusName( $statusId );
			$statusInfo = DBWorkflow::listStates( null, null, null, null, $statusName, true );

			// Enrich the Object properties.
			$objectProps->status->name = $statusName;
			$objectProps->status->type = $statusInfo[$statusId]['type'];
			$objectProps->status->color = self::removeHashPrefix( $statusInfo[$statusId]['color'] );
			$objectProps->status->phase = $statusInfo[$statusId]['phase'];
		}
	}
}
