<?php
class MetadataHandler
{
	private $fieldHandlers; //Array of FieldHandler
	private $fieldHandlersByElvisMetadata; //Array of FieldHandler
	private $metadataToReturn; //Array of string (Elvis fieldNames)
	
	/**
	 * Fills enterprise metadata object with metadata retrieved from $elvisMetadata, 
	 * using the configured field handlers. Retrieved metadata is translated 
	 * from Elvis fields to Enterprise fields.
	 *  
	 * @param Object $smartObject
	 * @param BasicMap $elvisMetadata
	 */
	public function read($smartObject, $elvisMetadata)
	{
		$meta = $this->prepareMetadataObject($smartObject);

		$this->_initFieldHandlers();
		foreach($this->fieldHandlers as $fieldHandler) {
			$fieldHandler->read($meta, $elvisMetadata);
		}
	}
	
	/**
	 * Fills enterprise metadata object with metadata retrieved from $elvisMetadata,
	 * using the metadata available in $elvisMetadata. Retrieved metadata is translated 
	 * from Elvis fields to Enterprise fields.
	 * 
	 * @param Object $smartObject
	 * @param BasicMap $elvisMetadata
	 */
	public function readByElvisMetadata($smartObject, $elvisMetadata)
	{
		$meta = $this->prepareMetadataObject($smartObject);
		
		$this->_initFieldHandlers();
		foreach($elvisMetadata as $fieldName => $fieldValue) {
			if (!array_key_exists($fieldName, $this->fieldHandlersByElvisMetadata)) {
				$message = 'No Enterprise mapping available for Elvis metadata field: ' . $fieldName;
				LogHandler::Log('ELVIS', 'INFO', $message);
				continue;
			}
			
			$fieldHandler = $this->fieldHandlersByElvisMetadata[$fieldName];
			$fieldHandler->read($meta, $elvisMetadata);
		}
	}
	
		
	/**
	 * Creates elvis metadata from enteprise metadata
	 * Provided metadata from Enterprise is translated to Elvis
	 *
	 * @param Object $entMetadataOrValues
	 * @param $elvisMetadata
	 * @return TODO: array? json?
	 */
	public function fillElvisMetadata($entMetadataOrValues, &$elvisMetadata)
	{
		$this->_initFieldHandlers();

		if( $entMetadataOrValues instanceof MetaData ) {
			foreach($this->fieldHandlers as $fieldHandler) {
				$fieldHandler->write($entMetadataOrValues, $elvisMetadata);
			}
		} else {
			foreach( $entMetadataOrValues as $metadataValue ) {
				$propertyName = $metadataValue->Property;
				if( array_key_exists( $propertyName, $this->fieldHandlers ) ) {
					$this->fieldHandlers[$propertyName]->write($metadataValue, $elvisMetadata);
				}
			}
		}
	}

	/**
	 * Updates Elvis metadata.
	 *
	 * @param $elvisId Id of asset
	 * @param $entMetadataOrValues Either full Metadata or a list of changed Metadata values
	 * @param null $file
	 */
	public function update($elvisId, $entMetadataOrValues, $file=NULL)
	{
		$elvisMetadata = array();
		$this->fillElvisMetadata($entMetadataOrValues, $elvisMetadata);
		
		if (ElvisUtils::saveObjectsDoesReleaseObjectLock()) {
			// save object detected; keep asset in checkout state on Elvis side
			$elvisMetadata['clearCheckoutState'] = 'false';
		}

		// enum editable fields
		$possibleAddFields = array();
		$allAssetInfo = ElvisSessionUtil::getAllAssetInfo();
		if ($allAssetInfo != null) {
			foreach ($allAssetInfo->fieldInfoByName as $field => $fieldInfo) {
				if ((isset($fieldInfo->name) && $fieldInfo->name == 'filename') ||
					(isset($fieldInfo->editable) && $fieldInfo->editable == true)) {
					array_push($possibleAddFields, $field);
				}
			}
		}
		// send to Elvis only editable matadata fields
		$elvisMetadata = array_intersect_key($elvisMetadata, array_flip($possibleAddFields));

		if( !empty( $elvisMetadata ) ) {
			require_once dirname(__FILE__) . '/../logic/ElvisRESTClient.php';
			ElvisRESTClient::update($elvisId, $elvisMetadata, $file);
		}
	}

	/**
	 * Updates Elvis metadata for multiple assets.
	 *
	 * @param $elvisIds Ids of assets
	 * @param $entMetadataOrValues Either full Metadata or a list of changed Metadata values
	 */
	public function updateBulk($elvisIds, $entMetadataOrValues)
	{
		$elvisMetadata = array();
		$this->fillElvisMetadata($entMetadataOrValues, $elvisMetadata);

		if (ElvisUtils::saveObjectsDoesReleaseObjectLock()) {
			// save object detected; keep asset in checkout state on Elvis side
			$elvisMetadata['clearCheckoutState'] = 'false';
		}

		if( !empty( $elvisMetadata ) ) {
			require_once dirname(__FILE__) . '/../logic/ElvisRESTClient.php';
			ElvisRESTClient::updateBulk($elvisIds, $elvisMetadata);
		}
	}
		
	/**
	 * Initializes fieldHandlers when needed
	 */
	private function _initFieldHandlers()
	{
		require_once dirname(__FILE__) . '/../config.php';
		require_once dirname(__FILE__) . '/fieldHandler/ContentSourceFieldHandler.class.php';
		require_once dirname(__FILE__) . '/fieldHandler/CopyrightMarkedFieldHandler.class.php';
		require_once dirname(__FILE__) . '/fieldHandler/NameFieldHandler.class.php';
		require_once dirname(__FILE__) . '/fieldHandler/KeywordsFieldHandler.class.php';
		require_once dirname(__FILE__) . '/fieldHandler/ReadOnlyFieldHandler.class.php';
		require_once dirname(__FILE__) . '/fieldHandler/ReadWriteFieldHandler.class.php';
		require_once dirname(__FILE__) . '/fieldHandler/ShadowIdFieldHandler.class.php';
		require_once dirname(__FILE__) . '/fieldHandler/TypeFieldHandler.class.php';
		require_once dirname(__FILE__) . '/fieldHandler/FormatFieldHandler.class.php';
		require_once dirname(__FILE__) . '/fieldHandler/VersionFieldHandler.class.php';
		require_once dirname(__FILE__) . '/fieldHandler/WriteOnlyFieldHandler.class.php';
		require_once dirname(__FILE__) . '/fieldHandler/UserFieldHandler.class.php';
		if (isset($this->fieldHandlers)) {
			return;
		}
		//FieldHandler parameters: Elvis fieldname, multivalue field, Elvis data type, Enterprise fieldname 
		
		//Get configurable field handlers from config
		$this->fieldHandlers = unserialize(ELVIS_FIELD_HANDLERS);
		
		//Special FieldHandlers
		$this->fieldHandlers['Keywords'] = new KeywordsFieldHandler(); 					//"tags", true, "text", "Keywords"
		$this->fieldHandlers['Name'] = new NameFieldHandler(); 							//"name", false, "text", "Name"
		$this->fieldHandlers['Type'] = new TypeFieldHandler(); 							//"assetDomain", false, "text", "Type"
		$this->fieldHandlers['Format'] = new FormatFieldHandler(); 						//"mimeType", false, "text", "Format"
		$this->fieldHandlers['Version'] = new VersionFieldHandler();  					//"versionNumber", false, "number", "Version"
		$this->fieldHandlers['ContentSource'] = new ContentSourceFieldHandler(); 		//"", false, "text", "ContentSource"
		$this->fieldHandlers['DocumentID'] = new ShadowIdFieldHandler(); 				//"id", false, "text", "DocumentID"
		$this->fieldHandlers['CopyrightMarked'] = new CopyrightMarkedFieldHandler();	//"", false, "", "CopyrightMarked"
		
		$this->fieldHandlers['Modifier'] = new UserFieldHandler("assetFileModifier", false, "text", "Modifier");
		$this->fieldHandlers['Modified'] = new ReadOnlyFieldHandler("assetFileModified", false, "datetime", "Modified");
		$this->fieldHandlers['Creator'] = new UserFieldHandler("assetCreator", false, "text", "Creator");
		$this->fieldHandlers['Created'] = new ReadOnlyFieldHandler("assetCreated", false, "datetime", "Created");
		
		// We can only set the user who locks the file, not the locked date, Enterprise defines it's own date
		$this->fieldHandlers['LockedBy'] = new UserFieldHandler("checkedOutBy", false, "text", "LockedBy");
		$this->fieldHandlers['FileSize'] = new ReadOnlyFieldHandler("fileSize", false, "number", "FileSize");
		
		$this->fieldHandlersByElvisMetadata = array();
		foreach ($this->fieldHandlers as $fieldHandler) {
			$this->fieldHandlersByElvisMetadata[$fieldHandler->lvsFieldName] = $fieldHandler;
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
	 * @return array metdataToReturn
	 */
	public function getMetadataToReturn()
	{
		if (!isset($this->metadataToReturn)) {
			$this->_initFieldHandlers();
			$this->metadataToReturn = array();
			foreach ($this->fieldHandlers as $fieldHandler) {
				$elvisFieldName = $fieldHandler->lvsFieldName;
				if (!($fieldHandler instanceof WriteOnlyFieldHandler) && isset($elvisFieldName) && trim($elvisFieldName) 
						&& !in_array($elvisFieldName, $this->metadataToReturn)) {
					$this->metadataToReturn[] = $elvisFieldName;
				}
			}
		}
		return $this->metadataToReturn;
	}
	
	/**
	 * Get fields mapped from Enterprise to Elvis
	 * 
	 * @return array metadataToUpdate
	 */
	public function getMetadataToUpdate()
	{
		if (!isset($this->metadataToUpdate)) {
			$this->_initFieldHandlers();
			$this->metadataToUpdate = array();
			foreach ($this->fieldHandlers as $fieldHandler) {
				if(!($fieldHandler instanceof ReadOnlyFieldHandler)) {
					$this->metadataToUpdate[] = $fieldHandler;
				}
			}
		}
	}
	
	private function prepareMetadataObject($smartObject) {
		if (!$smartObject->MetaData) {
			$smartObject->MetaData = new MetaData();
		}
		$meta = $smartObject->MetaData;
		if (!$meta->BasicMetaData) {
			$meta->BasicMetaData = new BasicMetaData();
		}
		if (!$meta->ContentMetaData) {
			$meta->ContentMetaData =  new ContentMetaData();
		}
		if(!$meta->RightsMetaData) {
			$meta->RightsMetaData = new RightsMetaData();
		}
		if(!$meta->SourceMetaData) {
			$meta->SourceMetaData = new SourceMetaData();
		}
		if(!$meta->WorkflowMetaData) {
			$meta->WorkflowMetaData = new WorkflowMetaData();
		}
		if(!$meta->ExtraMetaData) {
			//function mapMetaDataToCustomProperties() in /server/bizclasses/BizMetaDataPreview.class.php is expecting an array for ExtraMetaData.
			$meta->ExtraMetaData = array();
		}
		return $meta;
	}
}
