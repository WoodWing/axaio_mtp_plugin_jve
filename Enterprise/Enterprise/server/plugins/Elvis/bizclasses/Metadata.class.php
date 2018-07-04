<?php
class Elvis_BizClasses_Metadata
{
	/** @var Elvis_FieldHandlers_ReadWrite[] */
	private $fieldHandlers;

	/** @var Elvis_FieldHandlers_ReadWrite[] */
	private $fieldHandlersByElvisMetadata;

	/** @var  string[] Elvis field names */
	private $metadataToReturn;

	/** @var string Can be used to set handler type name ( the context ) that is calling MetaData handler, e.g 'VersionHandler' */
	private $handlerName;

	/**
	 * Fill an Enterprise MetaData data object with metadata retrieved from Elvis.
	 *
	 * Retrieved metadata is translated from Elvis fields to Enterprise fields by using the configured field handlers.
	 *
	 * @param Object $entObject
	 * @param mixed[] $elvisMetadata
	 */
	public function read( Object $entObject, $elvisMetadata )
	{
		$entMetaData = $this->composeMetadataObject( $entObject );
		$this->initFieldHandlers();
		foreach( $this->fieldHandlers as $fieldHandler ) {
			if( $this->fieldsCanBeMapped( $entObject->MetaData->BasicMetaData, $fieldHandler ) ) {
				$fieldHandler->read( $entMetaData, $elvisMetadata );
			}
		}
	}

	/**
	 * Checks if there is a mapping between an Elvis property and Enterprise.
	 *
	 * Fields can be mapped on an overall level (all brands) or just for one brand.
	 * If no publication (brand) is specified the mapping holds for all publications (brands). If a specific brand
	 * is specified then the brand must be the same as the one already set on the Enterprise metadata.
	 *
	 * @param BasicMetaData $basicMetaData
	 * @param Elvis_FieldHandlers_ReadWrite $fieldHandler
	 * @return bool
	 */
	private function fieldsCanBeMapped( BasicMetaData $basicMetaData, Elvis_FieldHandlers_ReadWrite $fieldHandler )
	{
		$result = false;
		if( empty( $fieldHandler->mappedToBrand() ) ) {
			$result = true;
		} elseif( ( intval( $fieldHandler->mappedToBrand() ) == intval( $basicMetaData->Publication->Id ) ) ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Fills enterprise metadata object with metadata retrieved from $elvisMetadata,
	 * using the metadata available in $elvisMetadata. Retrieved metadata is translated
	 * from Elvis fields to Enterprise fields.
	 *
	 * @param Object $entObject
	 * @param mixed[] $elvisMetadata
	 */
	public function readByElvisMetadata( Object $entObject, $elvisMetadata )
	{
		$entMetaData = $this->composeMetadataObject( $entObject );
		$this->initFieldHandlers();
		foreach( $elvisMetadata as $fieldName => $fieldValue ) {
			if( !array_key_exists( $fieldName, $this->fieldHandlersByElvisMetadata ) ) {
				$message = 'No Enterprise mapping available for Elvis metadata field: '.$fieldName;
				LogHandler::Log( 'ELVIS', 'INFO', $message );
				continue;
			}
			$fieldHandler = $this->fieldHandlersByElvisMetadata[ $fieldName ];
			$fieldHandler->read( $entMetaData, $elvisMetadata );
		}
	}


	/**
	 * Compose Elvis metadata from Enterprise metadata.
	 *
	 * Metadata is translated from Enterprise fields to Elvis fields by using the configured field handlers.
	 *
	 * @param MetaData|MetaDataValue[] $entMetadataOrValues
	 * @param array $elvisMetadata
	 */
	public function fillElvisMetadata( $entMetadataOrValues, array &$elvisMetadata )
	{
		$this->initFieldHandlers();

		if( $entMetadataOrValues instanceof MetaData ) {
			foreach( $this->fieldHandlers as $fieldHandler ) {
				$fieldHandler->write( $entMetadataOrValues, $elvisMetadata );
			}
		} else {
			foreach( $entMetadataOrValues as $metadataValue ) {
				$propertyName = $metadataValue->Property;
				if( array_key_exists( $propertyName, $this->fieldHandlers ) ) {
					$this->fieldHandlers[ $propertyName ]->write( $metadataValue, $elvisMetadata );
				}
			}
		}
	}

	/**
	 * Update (changed) Enterprise metadata in Elvis server.
	 *
	 * @param string $assetId
	 * @param MetaData $entMetadata The (changed) metadata to update.
	 * @param Attachment|null $file
	 * @param bool $undoCheckout Set to true to check-in the object, or false to retain the checkout status of the object.
	 */
	public function update( string $assetId, MetaData $entMetadata, $file = null, $undoCheckout = false )
	{
		$elvisMetadata = array();
		$this->fillElvisMetadata( $entMetadata, $elvisMetadata );

		// Determine the Elvis fields the user is allowed to edit.
		$editableFields = Elvis_BizClasses_UserSetting::getEditableFields();
		if( $editableFields == null ) { // lazy loading; if not in our session cache, get it from Elvis
			$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
			$fieldInfos = $client->fieldInfo();
			if( $fieldInfos ) foreach( $fieldInfos->fieldInfoByName as $field => $fieldInfo ) {
				if( ( isset( $fieldInfo->name ) && $fieldInfo->name == 'filename' ) ||
					( isset( $fieldInfo->editable ) && $fieldInfo->editable == true )
				) {
					$editableFields[] = $field;
				}
			}
			Elvis_BizClasses_UserSetting::setEditableFields( $editableFields );
		}

		// Send to Elvis only editable metadata fields.
		$elvisMetadata = array_intersect_key( $elvisMetadata, array_flip( $editableFields ) );
		if( $elvisMetadata ) {
			$service = new Elvis_BizClasses_AssetService();
			$service->update( $assetId, $elvisMetadata, $file, $undoCheckout );
		}
	}

	/**
	 * Update (changed) Enterprise metadata in Elvis server for multiple assets.
	 *
	 * @param string[] $assetIds
	 * @param MetaDataValue[] $entMetadataValues Changed metadata
	 */
	public function updateBulk( array $assetIds, array $entMetadataValues )
	{
		$elvisMetadata = array();
		$this->fillElvisMetadata( $entMetadataValues, $elvisMetadata );
		if( $assetIds && $elvisMetadata ) {
			$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
			$client->updateBulk( $assetIds, $elvisMetadata );
		}
	}

	/**
	 * Initialize the configured field handlers.
	 */
	private function initFieldHandlers()
	{
		require_once BASEDIR.'/config/config_elvis.php';
		if( isset( $this->fieldHandlers ) ) {
			return;
		}
		// Elvis_FieldHandlers_... parameters: Elvis fieldname, multivalue field, Elvis data type, Enterprise fieldname

		//Get configurable field handlers from config
		$this->fieldHandlers = Elvis_Config_GetFieldHandlers();

		//Special FieldHandlers
		$this->fieldHandlers['Keywords'] = new Elvis_FieldHandlers_Keywords();               //"tags", true, "text", "Keywords"
		$this->fieldHandlers['Name'] = new Elvis_FieldHandlers_Name();                     //"name", false, "text", "Name"
		$this->fieldHandlers['Type'] = new Elvis_FieldHandlers_Type();                     //"assetDomain", false, "text", "Type"
		$this->fieldHandlers['Format'] = new Elvis_FieldHandlers_Format();                  //"mimeType", false, "text", "Format"
		$this->fieldHandlers['Version'] = new Elvis_FieldHandlers_Version();               //"versionNumber", false, "number", "Version"
		$this->fieldHandlers['ContentSource'] = new Elvis_FieldHandlers_ContentSource();      //"", false, "text", "ContentSource"
		$this->fieldHandlers['DocumentID'] = new Elvis_FieldHandlers_ShadowId();            //"id", false, "text", "DocumentID"
		$this->fieldHandlers['CopyrightMarked'] = new Elvis_FieldHandlers_CopyrightMarked();   //"", false, "", "CopyrightMarked"

		$this->fieldHandlers['Modifier'] = new Elvis_FieldHandlers_User( "assetFileModifier", false, "text", "Modifier" );
		$this->fieldHandlers['Modifier']->replaceUnknownUserWithActingUser( $this->getHandlerName() );

		$this->fieldHandlers['Modified'] = new Elvis_FieldHandlers_ReadOnly( "assetFileModified", false, "datetime", "Modified" );
		$this->fieldHandlers['Creator'] = new Elvis_FieldHandlers_User( "assetCreator", false, "text", "Creator" );
		$this->fieldHandlers['Created'] = new Elvis_FieldHandlers_ReadOnly( "assetCreated", false, "datetime", "Created" );

		// We can only set the user who locks the file, not the locked date, Enterprise defines it's own date
		$this->fieldHandlers['LockedBy'] = new Elvis_FieldHandlers_User( "checkedOutBy", false, "text", "LockedBy" );
		$this->fieldHandlers['FileSize'] = new Elvis_FieldHandlers_ReadOnly( "fileSize", false, "number", "FileSize" );

		$this->fieldHandlersByElvisMetadata = array();
		foreach( $this->fieldHandlers as $fieldHandler ) {
			$this->fieldHandlersByElvisMetadata[ $fieldHandler->lvsFieldName ] = $fieldHandler;
		}

		// Write only

		//	We don't map these for now

		//	$this->fieldHandlers['Brand'] = BasicMetadata
		//	$this->fieldHandlers['Category'] = BasicMetadata
		//	$this->fieldHandlers['Status'] = WorkflowMetadata
		//	$this->fieldHandlers['Issue'] =
		//	$this->fieldHandlers['Targets'] = Targets

		//	Not mapped because complexity > importance

		//	$this->fieldHandlers['Compression'] = new Elvis_FieldHandlers_ReadOnly("compression", false, "number", "Compression");
		//	$this->fieldHandlers['Urgency'] = new Elvis_FieldHandlers_UrgencyFieldHandler();
	}

	/**
	 * Get fields mapped from Elvis to Enterprise
	 *
	 * @return string[] metadata to return
	 */
	public function getMetadataToReturn() : array
	{
		if( !isset( $this->metadataToReturn ) ) {
			$this->initFieldHandlers();
			$this->metadataToReturn = array();
			foreach( $this->fieldHandlers as $fieldHandler ) {
				$elvisFieldName = $fieldHandler->lvsFieldName;
				if( !( $fieldHandler instanceof Elvis_FieldHandlers_WriteOnly ) && isset( $elvisFieldName ) && trim( $elvisFieldName )
					&& !in_array( $elvisFieldName, $this->metadataToReturn )
				) {
					$this->metadataToReturn[] = $elvisFieldName;
				}
			}
		}
		return $this->metadataToReturn;
	}

	/**
	 * Get fields mapped from Enterprise to Elvis and populate $this->metadataToUpdate
	 */
	public function getMetadataToUpdate()
	{
		if( !isset( $this->metadataToUpdate ) ) {
			$this->initFieldHandlers();
			$this->metadataToUpdate = array();
			foreach( $this->fieldHandlers as $fieldHandler ) {
				if( !( $fieldHandler instanceof ReadOnlyFieldHandler ) ) {
					$this->metadataToUpdate[] = $fieldHandler;
				}
			}
		}
	}

	/**
	 * Composes an empty MetaData tree structure.
	 *
	 * @param Object $smartObject
	 * @return MetaData
	 */
	private function composeMetadataObject( Object $smartObject ): MetaData
	{
		if( !$smartObject->MetaData ) {
			$smartObject->MetaData = new MetaData();
		}
		$meta = $smartObject->MetaData;
		if( !$meta->BasicMetaData ) {
			$meta->BasicMetaData = new BasicMetaData();
		}
		if( !$meta->ContentMetaData ) {
			$meta->ContentMetaData = new ContentMetaData();
		}
		if( !$meta->RightsMetaData ) {
			$meta->RightsMetaData = new RightsMetaData();
		}
		if( !$meta->SourceMetaData ) {
			$meta->SourceMetaData = new SourceMetaData();
		}
		if( !$meta->WorkflowMetaData ) {
			$meta->WorkflowMetaData = new WorkflowMetaData();
		}
		if( !$meta->ExtraMetaData ) {
			//function mapMetaDataToCustomProperties() in /server/bizclasses/BizMetaDataPreview.class.php is expecting an array for ExtraMetaData.
			$meta->ExtraMetaData = array();
		}
		return $meta;
	}

	/**
	 * Set the handler name, the context when MetaDataHandler class is called.
	 *
	 * @param string $handlerName
	 */
	public function setHandlerName( $handlerName )
	{
		$this->handlerName = $handlerName;
	}

	/**
	 * Get the handler name, the context when MetaDataHandler is called.
	 *
	 * @return string
	 */
	public function getHandlerName()
	{
		return $this->handlerName;
	}
}
