<?php
class MetadataHandler
{
	/** @var ReadWriteFieldHandler[] $fieldHandlers */
	private $fieldHandlers;
	/** @var ReadWriteFieldHandler[] $fieldHandlersByElvisMetadata */
	private $fieldHandlersByElvisMetadata;
	/** @var  string[] $metadataToReturn Elvis field names */
	private $metadataToReturn;

	/**
	 * Fills enterprise metadata object with metadata retrieved from $elvisMetadata,
	 * using the configured field handlers. Retrieved metadata is translated
	 * from Elvis fields to Enterprise fields.
	 *
	 * @param Object $smartObject
	 * @param mixed[] $elvisMetadata
	 */
	public function read( $smartObject, $elvisMetadata )
	{
		$meta = $this->prepareMetadataObject( $smartObject );

		$this->_initFieldHandlers();
		foreach($this->fieldHandlers as $fieldHandler) {
			if( $this->fieldsCanBeMapped( $smartObject->MetaData->BasicMetaData, $fieldHandler ) ) {
				$fieldHandler->read($meta, $elvisMetadata);
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
	 * @param ReadWriteFieldHandler $fieldHandler
	 * @return bool
	 */
	private function fieldsCanBeMapped( BasicMetaData $basicMetaData, ReadWriteFieldHandler $fieldHandler  )
	{
		$result = false;
		if( empty( $fieldHandler->mappedToBrand() )  ) {
			$result = true;
		} elseif( ( intval($fieldHandler->mappedToBrand() ) == intval( $basicMetaData->Publication->Id ) ) ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Fills enterprise metadata object with metadata retrieved from $elvisMetadata,
	 * using the metadata available in $elvisMetadata. Retrieved metadata is translated
	 * from Elvis fields to Enterprise fields.
	 *
	 * @param Object $smartObject
	 * @param mixed[] $elvisMetadata
	 */
	public function readByElvisMetadata( $smartObject, $elvisMetadata )
	{
		$meta = $this->prepareMetadataObject( $smartObject );

		$this->_initFieldHandlers();
		foreach( $elvisMetadata as $fieldName => $fieldValue ) {
			if( !array_key_exists( $fieldName, $this->fieldHandlersByElvisMetadata ) ) {
				$message = 'No Enterprise mapping available for Elvis metadata field: '.$fieldName;
				LogHandler::Log( 'ELVIS', 'INFO', $message );
				continue;
			}

			$fieldHandler = $this->fieldHandlersByElvisMetadata[ $fieldName ];
			$fieldHandler->read( $meta, $elvisMetadata );
		}
	}


	/**
	 * Creates Elvis metadata from Enterprise metadata
	 * Provided metadata from Enterprise is translated to Elvis
	 *
	 * @param MetaData|MetaDataValue[] $entMetadataOrValues
	 * @param mixed[] $elvisMetadata
	 */
	public function fillElvisMetadata( $entMetadataOrValues, &$elvisMetadata )
	{
		$this->_initFieldHandlers();

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
	 * Updates Elvis metadata.
	 *
	 * If a user logs on the cache of editable fields is cleared. The very first time a user changes a property of an
	 * asset the cache is rebuild. For all subsequent modify actions the cache is reused. The advantage of this approach
	 * is that if a user does not modify properties the cache can remain empty. But when he does the cache is rebuild
	 * with the latest information of Elvis.
	 *
	 * @param string $elvisId Id of asset
	 * @param MetaData|MetaDataValue[] $entMetadataOrValues Either full Metadata or a list of changed Metadata values
	 * @param Attachment|null $file
	 * @param bool|null $clearCheckOutState Set to true or null(default) to checkin the object during update, false to retain the checkout status of the object.
	 */
	public function update( $elvisId, $entMetadataOrValues, $file=null, $clearCheckOutState=null )
	{
		$elvisMetadata = array();
		$this->fillElvisMetadata( $entMetadataOrValues, $elvisMetadata );

		require_once dirname( __FILE__ ).'/../util/ElvisSessionUtil.php';
		// Determine the Elvis fields the user is allowed to edit.
		$editableFields = ElvisSessionUtil::getEditableFields();
		if( $editableFields == null ) {
			require_once dirname( __FILE__ ).'/../logic/ElvisRESTClient.php';
			$fieldInfos = ElvisRESTClient::fieldInfo();
			require_once __DIR__.'/../util/ElvisUtils.class.php';
			$editableFields = ElvisUtils::extractEditableFieldsFromFieldInfos( $fieldInfos );
			ElvisSessionUtil::setEditableFields( $editableFields );
		}

		// Send to Elvis only editable metadata fields.
		$elvisMetadata = array_intersect_key( $elvisMetadata, array_flip( $editableFields ) );
		if( $elvisMetadata ) {
			require_once dirname( __FILE__ ).'/../logic/ElvisRESTClient.php';
			ElvisRESTClient::update( $elvisId, $elvisMetadata, $file, $clearCheckOutState );
		}
	}

	/**
	 * Updates Elvis metadata for multiple assets.
	 *
	 * @param string[] $elvisIds Ids of assets
	 * @param MetaData|MetaDataValue[] $entMetadataOrValues Changed metadata
	 */
	public function updateBulk( $elvisIds, $entMetadataOrValues )
	{
		$elvisMetadata = array();
		$this->fillElvisMetadata( $entMetadataOrValues, $elvisMetadata );

		if( !empty( $elvisMetadata ) ) {
			require_once dirname( __FILE__ ).'/../logic/ElvisRESTClient.php';
			ElvisRESTClient::updateBulk( $elvisIds, $elvisMetadata );
		}
	}

	/**
	 * Initializes fieldHandlers when needed
	 */
	private function _initFieldHandlers()
	{
		require_once dirname( __FILE__ ).'/../config.php';
		require_once dirname( __FILE__ ).'/fieldHandler/ContentSourceFieldHandler.class.php';
		require_once dirname( __FILE__ ).'/fieldHandler/CopyrightMarkedFieldHandler.class.php';
		require_once dirname( __FILE__ ).'/fieldHandler/NameFieldHandler.class.php';
		require_once dirname( __FILE__ ).'/fieldHandler/KeywordsFieldHandler.class.php';
		require_once dirname( __FILE__ ).'/fieldHandler/ReadOnlyFieldHandler.class.php';
		require_once dirname( __FILE__ ).'/fieldHandler/ReadWriteFieldHandler.class.php';
		require_once dirname( __FILE__ ).'/fieldHandler/ShadowIdFieldHandler.class.php';
		require_once dirname( __FILE__ ).'/fieldHandler/TypeFieldHandler.class.php';
		require_once dirname( __FILE__ ).'/fieldHandler/FormatFieldHandler.class.php';
		require_once dirname( __FILE__ ).'/fieldHandler/VersionFieldHandler.class.php';
		require_once dirname( __FILE__ ).'/fieldHandler/WriteOnlyFieldHandler.class.php';
		require_once dirname( __FILE__ ).'/fieldHandler/UserFieldHandler.class.php';
		if( isset( $this->fieldHandlers ) ) {
			return;
		}
		//FieldHandler parameters: Elvis fieldname, multivalue field, Elvis data type, Enterprise fieldname 

		//Get configurable field handlers from config
		$this->fieldHandlers = unserialize( ELVIS_FIELD_HANDLERS );

		//Special FieldHandlers
		$this->fieldHandlers['Keywords'] = new KeywordsFieldHandler();               //"tags", true, "text", "Keywords"
		$this->fieldHandlers['Name'] = new NameFieldHandler();                     //"name", false, "text", "Name"
		$this->fieldHandlers['Type'] = new TypeFieldHandler();                     //"assetDomain", false, "text", "Type"
		$this->fieldHandlers['Format'] = new FormatFieldHandler();                  //"mimeType", false, "text", "Format"
		$this->fieldHandlers['Version'] = new VersionFieldHandler();               //"versionNumber", false, "number", "Version"
		$this->fieldHandlers['ContentSource'] = new ContentSourceFieldHandler();      //"", false, "text", "ContentSource"
		$this->fieldHandlers['DocumentID'] = new ShadowIdFieldHandler();            //"id", false, "text", "DocumentID"
		$this->fieldHandlers['CopyrightMarked'] = new CopyrightMarkedFieldHandler();   //"", false, "", "CopyrightMarked"

		$this->fieldHandlers['Modifier'] = new UserFieldHandler( "assetFileModifier", false, "text", "Modifier" );
		$this->fieldHandlers['Modified'] = new ReadOnlyFieldHandler( "assetFileModified", false, "datetime", "Modified" );
		$this->fieldHandlers['Creator'] = new UserFieldHandler( "assetCreator", false, "text", "Creator" );
		$this->fieldHandlers['Created'] = new ReadOnlyFieldHandler( "assetCreated", false, "datetime", "Created" );

		// We can only set the user who locks the file, not the locked date, Enterprise defines it's own date
		$this->fieldHandlers['LockedBy'] = new UserFieldHandler( "checkedOutBy", false, "text", "LockedBy" );
		$this->fieldHandlers['FileSize'] = new ReadOnlyFieldHandler( "fileSize", false, "number", "FileSize" );

		$this->fieldHandlersByElvisMetadata = array();
		foreach( $this->fieldHandlers as $fieldHandler ) {
			$this->fieldHandlersByElvisMetadata[ $fieldHandler->lvsFieldName ] = $fieldHandler;
		}

		//Write only

//		We don't map these for now

// 		$this->fieldHandlers['Brand'] = BasicMetadata
// 		$this->fieldHandlers['Category'] = BasicMetadata
// 		$this->fieldHandlers['Status'] = WorkflowMetadata
// 		$this->fieldHandlers['Issue'] = 
// 		$this->fieldHandlers['Targets'] = Targets

//		Not mapped because complexity > importance

//		$this->fieldHandlers['Compression'] = new ReadOnlyFieldHandler("compression", false, "number", "Compression");
//		$this->fieldHandlers['Urgency'] = new UrgencyFieldHandler();

	}

	/**
	 * Get fields mapped from Elvis to Enterprise
	 *
	 * @return string[] metadata to return
	 */
	public function getMetadataToReturn()
	{
		if( !isset( $this->metadataToReturn ) ) {
			$this->_initFieldHandlers();
			$this->metadataToReturn = array();
			foreach( $this->fieldHandlers as $fieldHandler ) {
				$elvisFieldName = $fieldHandler->lvsFieldName;
				if( !( $fieldHandler instanceof WriteOnlyFieldHandler ) && isset( $elvisFieldName ) && trim( $elvisFieldName )
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
			$this->_initFieldHandlers();
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
	private function prepareMetadataObject( $smartObject )
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
}