<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v9.0.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizObjectComposer
{
	CONST NAME = 'ObjectComposer';

	/**
	 * Composes a new Object based on a parent / child object.
	 *
	 * @param string $user
	 * @param string $type
	 * @param null|Object $object
	 * @param null|Object $parent
	 * @return null|Object
	 */
	public static function compose($user, $type, $object=null, $parent=null)
	{
		require_once BASEDIR . '/server/bizclasses/BizPublishForm.class.php';

		// Determine the type of object to be created.
		$determinedType = self::determineObjectType($type, $object, $parent);
		if (is_null($determinedType)) {
			LogHandler::Log(self::NAME, 'ERROR', 'Could not determine the type of object to compose.');
			return null;
		}

		// Determine the name of the object to be created.
		$name = self::determineObjectName($user, $object, $parent);

		// Build the MetaData.
		$metaData = self::determineMetaData($user, $object, $parent, $determinedType, $name);

		$objComposed = false;
		$obj = null;
		if ( BizPublishForm::isPublishForm( $object )
				&& ( $objComposed || true )) { // Make analyzer happy.
			$relations = null;
			if (isset($object) && isset($object->Relations)) {
				$relations = $object->Relations;
			} elseif (isset($parent) && isset($parent->Relations)) {
				$relations = $parent->Relations;
			}

			$pages = null;
			if (isset($object) && isset($object->Pages)) {
				$pages = $object->Pages;
			} elseif (isset($parent) && isset($parent->Pages)) {
				$pages = $parent->Pages;
			}

			$files = null;
			if (isset($object) && isset($object->Files)) {
				$files = $object->Files;
			} elseif (isset($parent) && isset($parent->Files)) {
				$files = $parent->Files;
			}

			$messages = null;
			if (isset($object) && isset($object->Messages)) {
				$messages = $object->Messages;
			} elseif (isset($parent) && isset($parent->Messages)) {
				$messages = $parent->Messages;
			}

			$elements = null;
			if (isset($object) && isset($object->Elements)) {
				$elements = $object->Elements;
			} elseif (isset($parent) && isset($parent->Elements)) {
				$elements = $parent->Elements;
			}

			$targets = null;
			if (isset($object) && isset($object->Targets)) {
				$targets = $object->Targets;
			} elseif (isset($parent) && isset($parent->Targets)) {
				$targets = $parent->Targets;
			}

			$renditions = null;
			if (isset($object) && isset($object->Renditions)) {
				$renditions = $object->Renditions;
			} elseif (isset($parent) && isset($parent->Renditions)) {
				$renditions = $parent->Renditions;
			}

			$messageList = null;
			if (isset($object) && isset($object->MessageList)) {
				$messageList = $object->MessageList;
			} elseif (isset($parent) && isset($parent->MessageList)) {
				$messageList = $parent->MessageList;
			}

			// Join it all together.
			$obj = new Object();
			$obj->MetaData = $metaData;
			$obj->Relations = $relations;
			$obj->Pages = $pages;
			$obj->Files = $files;
			$obj->Messages = $messages;
			$obj->Elements = $elements;
			$obj->Targets = $targets;
			$obj->Renditions = $renditions;
			$obj->MessageList = $messageList;

			// Cleanup and validation of a PublishForm.
			$obj->Targets = (isset($object->Targets)) ? $object->Targets : null;
			$obj->Relations = (isset($object->Relations)) ? $object->Relations : null;

			if( self::validatePublishFormRelations( $obj ) ) {
				require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
				BizRelation::validateFormContainedByDossier( null, $obj->Targets, $obj->Relations );

				$obj->MessageList = $object->MessageList;
				$obj->Renditions = $object->Renditions;
				$obj->Elements = $object->Elements;
				$obj->Messages = $object->Messages;
				$obj->Files = $object->Files;
				$obj->Pages = $object->Pages;
				$obj->Relations = $object->Relations;
				$obj->MetaData->BasicMetaData->DocumentID = null;
				$obj->MetaData->BasicMetaData->Name = self::getPublishFormName($object);
				$obj->MetaData->BasicMetaData->ContentSource = null;
				$obj->MetaData->ContentMetaData->AspectRatio = null;
				$obj->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
				$obj->MetaData->ContentMetaData->Compression = null;
				$obj->MetaData->ContentMetaData->Encoding = null;
				$obj->MetaData->ContentMetaData->HighResFile = null;
				$obj->MetaData->ContentMetaData->ColorSpace = null;
				$obj->MetaData->ContentMetaData->FileSize = null;
				$obj->MetaData->ContentMetaData->PlainContent = null;
				$obj->MetaData->ContentMetaData->LengthChars = null;
				$obj->MetaData->ContentMetaData->LengthLines = null;
				$obj->MetaData->ContentMetaData->LengthParas = null;
				$obj->MetaData->ContentMetaData->LengthWords = null;
				$obj->MetaData->ContentMetaData->Dpi = null;
				$obj->MetaData->ContentMetaData->Height = null;
				$obj->MetaData->ContentMetaData->Width = null;
				$obj->MetaData->ContentMetaData->Columns = null;
				$obj->MetaData->WorkflowMetaData->Deleted = null;
				$obj->MetaData->WorkflowMetaData->Deletor = null;
				$obj->MetaData->WorkflowMetaData->LockedBy = null;
				$obj->MetaData->WorkflowMetaData->Created = null;
				$obj->MetaData->WorkflowMetaData->Creator = null;
				$obj->MetaData->WorkflowMetaData->Modified = null;
				$obj->MetaData->WorkflowMetaData->Modifier = null;

			// When creating a PublishForm, we need to set the default values. Otherwise these can't be reflected in the
			// GetDialog2 call. A publish form is created before the first GetDialog.
			// Fix for BZ#32583.
			if ( $obj->MetaData->ExtraMetaData ) {
				require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
				require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
				require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';

				list( $publishSystem, $templateId ) = BizObject::getPublishSystemAndTemplateId( null, $object->Relations );
				$props = DBProperty::getProperties($parent->MetaData->BasicMetaData->Publication->Id, 'PublishForm', false, $publishSystem, $templateId );

				foreach ( $props as $property ) {
					$templateCustomProperty = false;
					if ( $property->DefaultValue ) {
						$data = BizAdmProperty::getDefaultValue($property->Type, $property->DefaultValue);
						foreach ( $obj->MetaData->ExtraMetaData as &$metaData ) {
							if ( $metaData->Property == $property->Name ) {
								$metaData->Values = $data;
								$templateCustomProperty = true;
								break;
							}
						}

						// The Base template used for initially filling in the Object ExtraMetadata does not have
						// The custom properties as they were defined for the PublishForm, therefore we have to add
						// them at this stage.
						if ( !$templateCustomProperty ) {
							$extraMeta = new ExtraMetaData();
							$extraMeta->Property = $property->Name;
							$extraMeta->Values = $data;
							$obj->MetaData->ExtraMetaData[] = $extraMeta;
						}
					}
				}
			}

			// If possible, use the same Category for the PublishForm as was used for the Dossier.
			$category = self::getDossierCategoryForObject( $obj );
			if ( !is_null( $category) ) {
				$obj->MetaData->BasicMetaData->Category = $category;
			}
			$objComposed = true;
			}
		}

		return $objComposed ? $obj : null;
	}

	/**
	 * Determines the Category used for the Dossier containing the Object.
	 *
	 * Checks the Objects Relations and searches for a Contained relation. If the Contained Relation is found, the
	 * parent id is used to retrieve the category ID for the object, if this is not empty, the name is determined for
	 * the category. If both are filled in a new Category object is created and returned. In case something goes awry
	 * null is returned.
	 *
	 * @static
	 * @param Object $object The Object for which to determine the Category.
	 * @return Category|null Null or the determined Category for the containing Dossier.
	 */
	public static function getDossierCategoryForObject( $object )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';

		$category = null;
		if ( $object ) {
			$relation = null;
			foreach ( $object->Relations as $relation ) {
				if ( $relation->Type == 'Contained' ) {
					$categoryId = DBObject::getColumnValueByName($relation->Parent, 'Workflow', 'section');
					$categoryName = (!empty ( $categoryId ) ) ? DBSection::getSectionName( $categoryId ) : '';
					if ( !empty( $categoryId ) && !empty( $categoryName ) ) {
						$category = new Category( $categoryId, $categoryName );
					}
					break;
				}
			}
		}
		return $category;
	}

	/**
	 * Generates the name for a PublishForm. The returned name will be <Dossier Name>.
	 *
	 * @param Object $object
	 * @return string
	 */
	public static function getPublishFormName($object)
	{
		// The default name, should never be returned.
		$name = microtime();

		if ( $object ) {
			$relation = null;
			foreach ( $object->Relations as $relation ) {
				if ( $relation->Type == 'Contained' ) {
					require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
					$publishFormName = DBObject::getObjectName($relation->Parent); // Get the parent (Dossier) name.
					return $publishFormName;
				}
			}
		}

		return $name;
	}

	/**
	 * Determines the MetaData for an object.
	 *
	 * @static
	 * @param $user
	 * @param $object
	 * @param $parent
	 * @param $type
	 * @param $name
	 * @return MetaData|null
	 */
	private static function determineMetaData($user, $object, $parent, $type, $name)
	{
		// Find or create a MetaData node.
		$metaData = new MetaData();
		if (isset($object) && isset($object->MetaData)) {
			$metaData = $object->MetaData;
		}

		// check the basic Metadata fields.
		$metaData->BasicMetaData = self::determineBasicMetaData($metaData, $parent, $type, $name, $user);
		$metaData->ContentMetaData = self::determineContentMetaData($metaData, $parent, $user);
		$metaData->ExtraMetaData = self::determineExtraMetaData($metaData, $parent);
		$metaData->RightsMetaData = self::determineRightsMetaData($metaData, $parent);
		$metaData->SourceMetaData = self::determineSourceMetaData($metaData, $parent);
		$metaData->WorkflowMetaData = self::determineWorkflowMetaData($metaData, $parent,$user);

		return $metaData;
	}

	/**
	 * Determines a publication Id for an object.
	 *
	 * @static
	 * @param $metaData
	 * @param $parent
	 * @return null|Publication
	 */
	private static function determinePublicationId($metaData, $parent)
	{
		if (isset($metaData->BasicMetaData) && isset($metaData->BasicMetaData->Publication)) {
			return $metaData->BasicMetaData->Publication;
		}

		if (isset($parent->MetaData) && isset($parent->MetaData->BasicMetaData) && isset($parent->MetaData->BasicMetaData->Publication))
		{
			return $parent->MetaData->BasicMetaData->Publication;
		}

		return null;
	}


	/**
	 * Determines WorkflowMetaData for an object.
	 *
	 * @static
	 * @param $metaData
	 * @param $parent
	 * @param $user
	 * @return null|WorkflowMetaData
	 */
	private static function determineWorkflowMetaData($metaData,$parent, $user)
	{
		$workflowMetaData = $metaData->WorkflowMetaData;
		if (is_null($workflowMetaData)) {
			$workflowMetaData = new WorkflowMetaData();
			$metaData->WorkflowMetaData = $workflowMetaData;
		}

		$pubId = self::determinePublicationId($metaData, $parent);
		if (is_null($pubId)) {
			LogHandler::Log('BizObject', 'ERROR', 'Could not retrieve an Publication id based on the metadata.');
		}

		$id = self::getFirstissueId($parent->Targets);
		if (is_null($id)){
			LogHandler::Log('BizObject', 'ERROR', 'Could not retrieve an Issue Id based on the object targets.');
		}

		$section = self::getFirstCategory($user, $pubId->Id);
		if (is_null($section)){
			LogHandler::Log('BizObject', 'ERROR', 'Could not retrieve an Section based on the publication.');
		}

		$sectionId = ($section) ? $section->Id : null;
		$type = isset($metaData->BasicMetaData) && isset($metaData->BasicMetaData->Type) ? $metaData->BasicMetaData->Type : null;
		$state = self::getFirstState($user, $pubId->Id, $id, $sectionId, $type );
		if (is_null($state)){
			LogHandler::Log('BizObject', 'ERROR', 'Could not retrieve a State Id based on the publication.');
		}

		$workflowMetaData->State = (isset($workflowMetaData->State))
			? $workflowMetaData->State
			: $state;

		// Inherit metadata from parent.
		$deadline = null;
		$urgency = null;
		$modifier = null;
		$modified = null;
		$creator = null;
		$created = null;
		$comment = null;
		$routeTo = null;
		$lockedBy = null;
		$version = null;
		$deadlineSoft = null;
		$rating = null;
		$deletor = null;
		$deleted = null;

		if (isset($parent->MetaData) && isset($parent->MetaData->WorkflowMetaData)) {

			$deadline = $parent->MetaData->WorkflowMetaData->Deadline;
			$urgency = $parent->MetaData->WorkflowMetaData->Urgency;
			$modifier = $parent->MetaData->WorkflowMetaData->Modifier;
			$modified = $parent->MetaData->WorkflowMetaData->Modified;
			$creator = $parent->MetaData->WorkflowMetaData->Creator;
			$created = $parent->MetaData->WorkflowMetaData->Created;
			$comment = $parent->MetaData->WorkflowMetaData->Comment;
			$routeTo = $parent->MetaData->WorkflowMetaData->RouteTo;
			$lockedBy = $parent->MetaData->WorkflowMetaData->LockedBy;
			$version = $parent->MetaData->WorkflowMetaData->Version;
			$deadlineSoft = $parent->MetaData->WorkflowMetaData->DeadlineSoft;
			$rating = $parent->MetaData->WorkflowMetaData->Rating;
			$deletor = $parent->MetaData->WorkflowMetaData->Deletor;
			$deleted = $parent->MetaData->WorkflowMetaData->Deleted;
		}

		$workflowMetaData->Deadline = (isset($workflowMetaData->Deadline)) ? $workflowMetaData->Deadline : $deadline;
		$workflowMetaData->Urgency = (isset($workflowMetaData->Urgency)) ? $workflowMetaData->Urgency : $urgency;
		$workflowMetaData->Modifier = (isset($workflowMetaData->Modifier)) ? $workflowMetaData->Modifier : $modifier;
		$workflowMetaData->Modified = (isset($workflowMetaData->Modified)) ? $workflowMetaData->Modified : $modified;
		$workflowMetaData->Creator = (isset($workflowMetaData->Creator)) ? $workflowMetaData->Creator : $creator;
		$workflowMetaData->Created = (isset($workflowMetaData->Created)) ? $workflowMetaData->Created : $created;
		$workflowMetaData->Comment = (isset($workflowMetaData->Comment)) ? $workflowMetaData->Comment : $comment;
		$workflowMetaData->RouteTo = (isset($workflowMetaData->RouteTo)) ? $workflowMetaData->RouteTo : $routeTo;
		$workflowMetaData->LockedBy = (isset($workflowMetaData->LockedBy)) ? $workflowMetaData->LockedBy : $lockedBy;
		$workflowMetaData->Version = (isset($workflowMetaData->Version)) ? $workflowMetaData->Version : $version;
		$workflowMetaData->DeadlineSoft = (isset($workflowMetaData->DeadlineSoft)) ? $workflowMetaData->DeadlineSoft : $deadlineSoft;
		$workflowMetaData->Rating = (isset($workflowMetaData->Rating)) ? $workflowMetaData->Rating : $rating;
		$workflowMetaData->Deletor = (isset($workflowMetaData->Deletor)) ? $workflowMetaData->Deletor : $deletor;
		$workflowMetaData->Deleted = (isset($workflowMetaData->Deleted)) ? $workflowMetaData->Deleted : $deleted;

		return $workflowMetaData;
	}

	/**
	 * Determines the Source MetaData for an Object.
	 *
	 * @static
	 * @param $metaData
	 * @param $parent
	 * @return null|SourceMetaData
	 */
	private static function determineSourceMetaData($metaData, $parent)
	{
		$sourceMetaData = $metaData->SourceMetaData;
		if (is_null($sourceMetaData)) {
			$sourceMetaData = new SourceMetaData();
			$metaData->SourceMetaData = $sourceMetaData;
		}

		$credit = null;
		$source = null;
		$author = null;

		if (isset($parent->MetaData) && isset($parent->MetaData->SourceMetaData)) {
			$credit = $parent->MetaData->SourceMetaData->Credit;
			$source = $parent->MetaData->SourceMetaData->Source;
			$author = $parent->MetaData->SourceMetaData->Author;
		}

		// Inherit from parent if needed.
		$sourceMetaData->Credit = (isset($sourceMetaData->Credit)) ? $sourceMetaData->Credit : $credit;
		$sourceMetaData->Source = (isset($sourceMetaData->Source)) ? $sourceMetaData->Source : $source;
		$sourceMetaData->Author = (isset($sourceMetaData->Author)) ? $sourceMetaData->Author : $author;

		return $sourceMetaData;
	}

	/**
	 * Determines the RightsMetaData for an Object.
	 *
	 * @static
	 * @param $metaData
	 * @param $parent
	 * @return null|RightsMetaData
	 */
	private static function determineRightsMetaData($metaData, $parent)
	{
		$rightsMetaData = $metaData->RightsMetaData;
		if (is_null($rightsMetaData)) {
			$rightsMetaData = new RightsMetaData();
			$metaData->RightsMetaData = $rightsMetaData;
		}

		$copyrightMarked = null;
		$copyright = null;
		$copyrightURL = null;

		if (isset($parent->MetaData) && isset($parent->MetaData->RightsMetaData)) {
			$copyrightMarked = $parent->MetaData->RightsMetaData->CopyrightMarked;
			$copyright = $parent->MetaData->RightsMetaData->Copyright;
			$copyrightURL = $parent->MetaData->RightsMetaData->CopyrightURL;
		}

		// Inherit from parent if needed.
		$rightsMetaData->Copyright = (isset($rightsMetaData->Copyright)) ? $rightsMetaData->Copyright : $copyright;
		$rightsMetaData->CopyrightMarked = (isset($rightsMetaData->CopyrightMarked)) ? $rightsMetaData->CopyrightMarked : $copyrightMarked;
		$rightsMetaData->CopyrightURL = (isset($rightsMetaData->CopyrightURL)) ? $rightsMetaData->CopyrightURL : $copyrightURL;

		return $rightsMetaData;
	}

	/**
	 * Determines the Extra MetaData for an object.
	 *
	 * @static
	 * @param $metaData
	 * @param $parent
	 * @return null
	 */
	private static function determineExtraMetaData($metaData, $parent)
	{
		$extraMetaData = $metaData->ExtraMetaData;
		if (is_null($extraMetaData)) {
			$extraMetaData = (isset($parent) && isset($parent->MetaData) && isset($parent->MetaData->ExtraMetaData))
				? $parent->MetaData->ExtraMetaData
				: null;
		}

		return $extraMetaData;
	}

	/**
	 * Determines the ContentMetaData for an object.
	 *
	 * @static
	 * @param $metaData
	 * @param $parent
	 * @param $user
	 * @return ContentMetaData|null
	 */
	private static function determineContentMetaData($metaData, $parent, $user)
	{
		$contentMetaData = $metaData->ContentMetaData;
		if (is_null($contentMetaData)) {
			$contentMetaData = new ContentMetaData(null, $user);
			$metaData->ContentMetaData = $contentMetaData;
		}

		$contentMetaData->DescriptionAuthor = (isset($contentMetaData->DescriptionAuthor))
			? $contentMetaData->DescriptionAuthor
			: $user;

		// Check parent params.
		$description = null;
		$keyWords = null;
		$slugLine = null;
		$format = null;
		$columns = null;
		$width = null;
		$height = null;
		$dpi = null;
		$lengthWords = null;
		$lengthChars = null;
		$lengthParas = null;
		$lengthLines = null;
		$plainContent = null;
		$fileSize = null;
		$colorSpace = null;
		$highResFile = null;
		$encoding = null;
		$compression = null;
		$keyFrameEveryFrames = null;
		$channels = null;
		$aspectRatio = null;

		if (isset($parent->MetaData) && isset($parent->MetaData->ContentMetaData)) {
			$description = $parent->MetaData->ContentMetaData->Description;
			$keyWords = $parent->MetaData->ContentMetaData->Keywords;
			$slugLine = $parent->MetaData->ContentMetaData->Slugline;
			$format = $parent->MetaData->ContentMetaData->Format;
			$columns = $parent->MetaData->ContentMetaData->Columns;
			$width = $parent->MetaData->ContentMetaData->Width;
			$height = $parent->MetaData->ContentMetaData->Height;
			$dpi = $parent->MetaData->ContentMetaData->Dpi;
			$lengthWords = $parent->MetaData->ContentMetaData->LengthWords;
			$lengthChars = $parent->MetaData->ContentMetaData->LengthChars;
			$lengthParas = $parent->MetaData->ContentMetaData->LengthParas;
			$lengthLines = $parent->MetaData->ContentMetaData->LengthLines;
			$plainContent = $parent->MetaData->ContentMetaData->PlainContent;
			$fileSize = $parent->MetaData->ContentMetaData->FileSize;
			$colorSpace = $parent->MetaData->ContentMetaData->ColorSpace;
			$highResFile = $parent->MetaData->ContentMetaData->HighResFile;
			$encoding = $parent->MetaData->ContentMetaData->Encoding;
			$compression = $parent->MetaData->ContentMetaData->Compression;
			$keyFrameEveryFrames = $parent->MetaData->ContentMetaData->KeyFrameEveryFrames;
			$channels = $parent->MetaData->ContentMetaData->Channels;
			$aspectRatio = $parent->MetaData->ContentMetaData->AspectRatio;
		}

		// Inherit from parent if needed.
		$contentMetaData->Description = (isset($contentMetaData->Description)) ? $contentMetaData->Description : $description;
		$contentMetaData->Keywords = (isset($contentMetaData->Keywords)) ? $contentMetaData->Keywords : $keyWords;
		$contentMetaData->Slugline = (isset($contentMetaData->Slugline)) ? $contentMetaData->Slugline : $slugLine;
		$contentMetaData->Format = (isset($contentMetaData->Format)) ? $contentMetaData->Format	: $format;
		$contentMetaData->Columns = (isset($contentMetaData->Columns)) ? $contentMetaData->Columns : $columns;
		$contentMetaData->Width = (isset($contentMetaData->Width)) ? $contentMetaData->Width : $width;
		$contentMetaData->Height = (isset($contentMetaData->Height)) ? $contentMetaData->Height	: $height;
		$contentMetaData->Dpi = (isset($contentMetaData->Dpi)) ? $contentMetaData->Dpi : $dpi;
		$contentMetaData->LengthWords = (isset($contentMetaData->LengthWords)) ? $contentMetaData->LengthWords : $lengthWords;
		$contentMetaData->LengthChars = (isset($contentMetaData->LengthChars)) ? $contentMetaData->LengthChars : $lengthChars;
		$contentMetaData->LengthParas = (isset($contentMetaData->LengthParas)) ? $contentMetaData->LengthParas : $lengthParas;
		$contentMetaData->LengthLines = (isset($contentMetaData->LengthLines)) ? $contentMetaData->LengthLines : $lengthLines;
		$contentMetaData->PlainContent = (isset($contentMetaData->PlainContent)) ? $contentMetaData->PlainContent : $plainContent;
		$contentMetaData->FileSize = (isset($contentMetaData->FileSize)) ? $contentMetaData->FileSize : $fileSize;
		$contentMetaData->ColorSpace = (isset($contentMetaData->ColorSpace)) ? $contentMetaData->ColorSpace : $colorSpace;
		$contentMetaData->HighResFile = (isset($contentMetaData->HighResFile)) ? $contentMetaData->HighResFile : $highResFile;
		$contentMetaData->Encoding = (isset($contentMetaData->Encoding)) ? $contentMetaData->Encoding : $encoding;
		$contentMetaData->Compression = (isset($contentMetaData->Compression)) ? $contentMetaData->Compression : $compression;
		$contentMetaData->KeyFrameEveryFrames = (isset($contentMetaData->KeyFrameEveryFrames)) ? $contentMetaData->KeyFrameEveryFrames : $keyFrameEveryFrames;
		$contentMetaData->Channels = (isset($contentMetaData->Channels)) ? $contentMetaData->Channels : $channels;
		$contentMetaData->AspectRatio = (isset($contentMetaData->AspectRatio)) ? $contentMetaData->AspectRatio : $aspectRatio;

		return $contentMetaData;
	}

	/**
	 * Determines the Basic MetaData for an object.
	 *
	 * @static
	 * @param $metaData
	 * @param $parent
	 * @param $type
	 * @param $name
	 * @param $user
	 * @return BasicMetaData|null
	 */
	private static function determineBasicMetaData($metaData, $parent, $type, $name, $user)
	{
		$publication = self::determinePublicationId($metaData, $parent);
		if (is_null($publication)) {
			LogHandler::Log('BizObject', 'ERROR', 'Could not retrieve an Publication id based on the metadata.');
		}

		$section = self::getFirstCategory($user, $publication->Id);
		if (is_null($section)){
			LogHandler::Log('BizObject', 'ERROR', 'Could not retrieve an Section Id based on the publication.');
		}

		// Take the basic metadata if set, otherwise construct it.
		$basicMetaData = $metaData->BasicMetaData;
		if (is_null($basicMetaData)) {
			$basicMetaData = new BasicMetaData(null,null,$name,$type,$publication,$section,null);
			$metaData->BasicMetaData = $basicMetaData;
		}

		$basicMetaData->Name = (isset($basicMetaData->Name))
			? $basicMetaData->Name
			: $name;

		$basicMetaData->Type = (isset($basicMetaData->Type))
			? $basicMetaData->Type
			: $type;

		$basicMetaData->Category = (isset($basicMetaData->Category))
			? $basicMetaData->Category
			: $section;

		$basicMetaData->Publication = (isset($basicMetaData->Publication))
			? $basicMetaData->Publication
			: $publication;

		$basicMetaData->ID = (isset($basicMetaData->ID))
			? $basicMetaData->ID
			: null;

		$parentDocumentID = null;
		$parentContentSource = null;

		if (isset($parent->MetaData) && isset($parent->MetaData->BasicMetaData)) {
			$parentDocumentID = $parent->MetaData->BasicMetaData->DocumentID;
			$parentContentSource = $parent->MetaData->BasicMetaData->ContentSource;
		}

		$basicMetaData->DocumentID = (isset($basicMetaData->DocumentID))
			? $basicMetaData->DocumentID
			: $parentDocumentID;

		$basicMetaData->ContentSource = (isset($basicMetaData->ContentSource))
			? $basicMetaData->DocumentID
			: $parentContentSource;

		return $basicMetaData;
	}

	/**
	 * Determines the ObjectType for an object.
	 *
	 * @static
	 * @param $type
	 * @param null $object
	 * @param null $parent
	 * @return null|string
	 */
	private static function determineObjectType($type, $object=null, $parent=null)
	{
		if (!is_null($type)) {
			return $type;
		}

		if (!is_null($object) && isset($object->MetaData) && isset($object->MetaData->BasicMetaData) && isset($object->MetaData->BasicMetaData->Type)) {
			return $object->MetaData->BasicMetaData->Type;
		}

		if (!is_null($parent) && isset($parent->MetaData) && isset($parent->MetaData->BasicMetaData) && isset($parent->MetaData->BasicMetaData->Type)) {
			// Check if the type is a Template type, in which case we need to return only the name of the object.
			$templateType = (string)$parent->MetaData->BasicMetaData->Type;
			$pos = strrpos($templateType, 'Template');
			if ((false == $pos) || ($pos == 0)) {
				return $templateType;
			}

			return substr($templateType, 0, $pos);
		}
		return null;
	}

	/**
	 * Determines the object name for an object.
	 *
	 * @static
	 * @param $user
	 * @param $object
	 * @param $parent
	 * @return null|string
	 */
	private static function determineObjectName($user, $object, $parent)
	{
		// Take the object name if it is set.
		if (!is_null($object) && isset($object->MetaData) && isset($object->MetaData->BasicMetaData) && isset($object->MetaData->BasicMetaData->Name)) {
			return $object->MetaData->BasicMetaData->Name;
		}

		// Take the parent name if the object name is not known.
		if (!is_null($parent) && isset($parent->MetaData) && isset($parent->MetaData->BasicMetaData) && isset($parent->MetaData->BasicMetaData->Name)) {
			return $parent->MetaData->BasicMetaData->Name;
		}

		// Compose a new name if it is unknown.
		return $user . microtime();
	}

	/**
	 * Checks if the PublishForm Object is valid.
	 *
	 * Validates the relations of the object.
	 *
	 * The detailed validation of the PublishForm relations is done in
	 * BizRelation::validateFormContainedByDossier().
	 *
	 * @param Object $object
	 * @return bool True when the validation has no error; False otherwise.
	 */
	public static function validatePublishFormRelations( /** @noinspection PhpLanguageLevelInspection */
		Object $object )
	{
		// If the Object has no relations it is not valid.
		if (is_null($object->Relations)) {
			return false;
		}

		// Count the number of InstanceOf and the number of Contained relations.
		$instanceOfCount = 0;
		$containedCount = 0;

		foreach ($object->Relations as $relation) {
			if ($relation->Type == 'InstanceOf') { $instanceOfCount++; }
			if ($relation->Type == 'Contained') {
				$containedCount++;

				// Verify the relational targets for this object.
				if (!isset($relation->Targets) || !is_array($relation->Targets)){
					return false;
				}
			}
		}

		// PublishForms may only have one InstanceOf and one Contained relation.
		if ($instanceOfCount != 1 || $containedCount != 1) {
			return false;
		}

		// Passed all tests, the relations match those for a PublishForm.
		return true;
	}

	/**
	 * Returns the first Category for the entered details.
	 *
	 * @param string $user
	 * @param int $publication Publication id.
	 * @param int|null $issue Issue id.
	 * @param string $mode
	 *   - 'flat'   gives Section objects providing names and ids. (Default)
	 *   - 'browse' gives Section objects providing names and ids.
	 *   - 'full'   gives SectionInfo objects providing full details.
	 * @param bool $checkForOverruleIssue
	 * @return Category|CategoryInfo|null
	 */
	public static function getFirstCategory($user, $publication, $issue=null, $mode='flat', $checkForOverruleIssue=true)
	{
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$sections = BizPublication::getSections($user, $publication, $issue, $mode, $checkForOverruleIssue);
		$section = ($sections) ? $sections[0] : null;
		return $section;
	}

	/**
	 * Returns the first Issue linked to a list of Targets.
	 *
	 * @static
	 * @param $targets
	 * @return null|int
	 */
	public static function getFirstIssueId($targets)
	{
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		$issueIds = BizTarget::getIssueIds($targets);
		$id = (count($issueIds) > 0) ? $issueIds[0] : null;
		return $id;
	}

	/**
	 * Returns the first State for the entered details.
	 *
	 * @param string $user
	 * @param string $publicationId
	 * @param string $issue
	 * @param string $section
	 * @param string $type
	 * @param bool $checkForOverruleIssue
	 * @param bool $logon
	 * @return null|State
	 */
	public static function getFirstState($user, $publicationId, $issue = null, $section = null, $type = null, $checkForOverruleIssue=true, $logon = false)
	{
		require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
		$states = BizWorkflow::getStates($user, $publicationId, $issue, $section, $type, $checkForOverruleIssue, $logon);
		$state = ($states) ? $states[0] : null;
		if (!is_null($state) && intval($state->Id) == -1 && isset($states[1])) {
			$state = $states[1];
		}
		return $state;
	}
}
