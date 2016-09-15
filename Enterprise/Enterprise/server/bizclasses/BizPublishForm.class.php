<?php
/**
 * PublishForm Utilities.
 *
 * @package Enterprise
 * @subpackage BizClasses
 * @since v9.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
class BizPublishForm
{
	/**
	 * Retrieves the field values for the PublishForm.
	 *
	 * Retrieves field values for simple widgets from the object's MetaData fields.
	 * Retrieves field values for complex widgets from the object's Relations.
	 *
	 * If $pattern is supplied then the field name must match the pattern or it will be ommitted.
	 * FileSelector / ArticleComponentSelectors (complex widgets) do not need to match this pattern, rather
	 * all objects that have a `placed` relation are retrieved and returned.
	 *
	 * @static
	 * @param Object $publishForm An object of type 'PublishForm'.
	 * @param null|string $pattern Optional regular expression pattern which retrieved fields should match.
	 * @return array An array containing all the found fields is returned with the field name as the key.
	 * @throws BizException An exception is thrown in case placed objects cannot be retrieved.
	 */
	static public function getFormFields( $publishForm, $pattern=null )
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';

		$fieldProperties = array();
		$requestInfo = array('Pages', 'Targets', 'Relations', 'Elements');
		$rendition='native';

		// Retrieve all simple fields (retrievable by the MetaData values.)
		foreach ( $publishForm->MetaData->ExtraMetaData as $extraMetaData ) {
			if (is_null($pattern) || (preg_match($pattern, $extraMetaData->Property))){
				$fieldProperties[$extraMetaData->Property] = $extraMetaData->Values;
			}
		}

		// Retrieve all complex fields (FileSelector / ArticleComponentSelector). This can be done by resolving all
		// the 'Placed' relations. Placements aren't pattern checked.
		$filesByFormWidgetId = array();
		if (is_array($publishForm->Relations)) foreach ($publishForm->Relations as $relation ) {
			if ($relation->Type == 'Placed') {
				if (is_array($relation->Placements)) foreach ($relation->Placements as $placement) {
					$property = $placement->FormWidgetId;
					$fileObjectId = $relation->Child;
					$user = BizSession::getShortUserName();
					try {
						$file = BizObject::getObject($fileObjectId, $user, false, $rendition, $requestInfo, null, true
							, array('Workflow'));

						if (!array_key_exists( $property, $filesByFormWidgetId )) {
							$filesByFormWidgetId[$property] = array();
						}
						// Add the files by frameOrder to ensure correct sorting.
						if (!isset($filesByFormWidgetId[$property][$placement->FrameOrder])) {
							$filesByFormWidgetId[$property][$placement->FrameOrder] = array();
						}

						$filesByFormWidgetId[$property][$placement->FrameOrder][] = $file;
					} catch (BizException $e ) {
						LogHandler::Log(__CLASS__ . '::' . __FUNCTION__, 'ERROR'
							, 'Failed to retrieve placed objects for Object ID \'' . $fileObjectId . '\'.');
						throw $e;
					}
				}
			}
		}

		// We always have at least one file per formWidgetId. Restructure the fields so we can still work with Article-
		// ComponentSelectors and single FileSelectors. Multiple files become an array, single files a single object.
		// This was done to support older implementations of this utils class, and allow multi-file selectors at the
		// same time.
		if ($filesByFormWidgetId) {
			foreach ($filesByFormWidgetId as $formWidgetId => $frameOrders) {
				$filesArray = array();
				ksort($frameOrders);
				// Flatten the sorted files.
				foreach ($frameOrders as $file) {
					$filesArray[] = $file[0];
				}
				$fieldProperties[$formWidgetId] = (count($filesArray) > 1) ? $filesArray : $filesArray[0];
			}
		}
		return $fieldProperties;
	}

	/**
	 * Removes all files from the transfer server that were prepared by the getFormFields() function.
	 *
	 * @param array $fieldProperties The returned data from getFormFields()
	 */
	static public function cleanupFilesReturnedByGetFormFields( $fieldProperties )
	{
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $fieldProperties ) foreach( $fieldProperties as $formWidgetId => $objects ) {
			if( is_object( $objects ) ) {
				$objects = array( $objects );
			}
			if( $objects ) foreach( $objects as $object ) {
				if( is_object( $object ) && $object->Files ) foreach( $object->Files as $attachment ) {
					$transferServer->deleteFile( $attachment->FilePath );
				}
			}
		}
	}

	/**
	 * Removes all files from the tempfolder that were converted by the image converter and placed on the publish form.
	 *
	 * @param Object $publishForm The publishform containing all images.
	 */
	static public function cleanupPlacedFilesCreatedByConversion( $publishForm )
	{
		if( $publishForm->Relations ) foreach( $publishForm->Relations as $relation ) {
			foreach( $relation->Placements as $placement ) {
				//ImageCropAttachment == ConvertedImageToPublish->Attachment
				if( isset($placement->ConvertedImageToPublish) && file_exists( $placement->ConvertedImageToPublish->Attachment->FilePath ) ) {
					unlink( $placement->ConvertedImageToPublish->Attachment->FilePath );
				}
			}
		}
	}

	/**
	 * Returns the requested field by name from the PublishForm.
	 *
	 * First checks the Objects MetaData to find the field, if found it is returned to the user. If the field could not
	 * be found in the MetaData the Objects relations are inspected. In case there are no matching fields found an empty
	 * array is returned.
	 *
	 * @static
	 * @param Object $publishForm An object of type 'PublishForm'.
	 * @param string $fieldName The field name for which to search the form fields.
	 * @return array An array containing the found value.
	 * @throws BizException Throws an exception if a placed object was found but cannot be resolved.
	 */
	static public function getFormFieldByName( $publishForm, $fieldName )
	{
		$requestInfo = array('Pages', 'Targets', 'Relations', 'Elements');
		$rendition='native';

		// Retrieve all simple fields (retrievable by the MetaData values.)
		foreach ( $publishForm->MetaData->ExtraMetaData as $extraMetaData ) {
			if ($extraMetaData->Property == $fieldName){
				return array($extraMetaData->Property => $extraMetaData->Values);
			}
		}

		// If not found in the ExtraMetaData, find it in the placed relations.
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';

		if (is_array($publishForm->Relations)) foreach ($publishForm->Relations as $relation ) {
			if ($relation->Type == 'Placed') {
				if (is_array($relation->Placements)) foreach ($relation->Placements as $placement) {
					$property = $placement->FormWidgetId;

					if ($property == $fieldName){
						$fileObjectId = $relation->Child;
						$user = BizSession::getShortUserName();
						try {
							$file = BizObject::getObject($fileObjectId, $user, false, $rendition, $requestInfo, null
								, true, array('Workflow'));
						} catch (BizException $e ) {
							LogHandler::Log(__CLASS__ . '::' . __FUNCTION__, 'ERROR'
								, 'Failed to retrieve placed objects for Object ID \'' . $fileObjectId . '\'.');
							throw $e;
						}
						return array($property => $file);
					}
				}
			}
		}
		// None found, return an empty array.
		return array();
	}

	/**
	 * Extracts the data for a specific field from the PublishForm.
	 *
	 * @static
	 * @param object $publishForm The PublishForm to retrieve data from.
	 * @param string $fieldName The FieldName for which to retrieve the data.
	 * @param bool $extractContent In some cases it can be preferable to not get an objects content, but instead get the objects attachment.
	 * @param integer $channelId Id of the publication channel to publish in.
	 * @return array|null An array with the resolved data.
	 */
	static public function extractFormFieldDataByName ( $publishForm, $fieldName, $extractContent, $channelId )
	{
		try {
			$field = self::getFormFieldByName( $publishForm, $fieldName );
		} catch (BizException $e) {
			return null;
		}
		$keys = array_keys($field);
		$values = $field[$keys[0]];

		// If the values is a file extract the actual data.
		if (is_object($values)) {
			$metadata = $values->MetaData;
			switch( $values->MetaData->BasicMetaData->Type ) {
				case 'Article' :
					if ($extractContent) {
						$values = self::extractArticleObjectElements( $values, $channelId );
					} else {
						$values = self::getAttachments( $values );
					}
					break;
				case 'Layout' :
					if ($extractContent) {
						$values = self::extractLayoutObjectAttachments( $values );
					} else {
						$values = self::getAttachments( $values );
					}
					break;
				default:
					$values = self::getAttachments( $values );
					break;
			}

			// Add the metadata to the values.
			$values['metadata'] = $metadata;
		}

		$field[$keys[0]] = $values;
		return $field;
	}

	/**
	 * Extracts the data for a field definition from the PublishForm.
	 *
	 * @param string $fieldName The FieldName for which to retrieve the data.
	 * @param string|Object $fieldValues The raw field values to be resolved.
	 * @param integer $channelId The ID of the publication channel.
	 * @param bool $extractContent True to extract the data out of the object, false to only extract the object attachment.
	 * @return array|null An array with the resolved data.
	 */
	static public function extractFormFieldDataFromFieldValue ( $fieldName, $fieldValues, $channelId, $extractContent=true )
	{
		$values = $fieldValues;
		if (is_object($values)) {
			$metadata = $values->MetaData;
			switch( $values->MetaData->BasicMetaData->Type ) {
				case 'Article' :
					// Find the placement.
					$elementId = null;
					if (isset($values->Relations)) foreach ($values->Relations as $relation) {
						if ($relation->Type == 'Placed') {
							foreach ($relation->Placements as $placement ) {
								if ($placement->FormWidgetId == $fieldName) {
									$elementId = $placement->ElementID;
								}
							}
						}
					}

					if (!is_null($elementId) && $extractContent) {
						// Get the elements.
						$values = self::extractArticleObjectElements( $values, $channelId );

						// If we have more than one element, find the correct one.
						if (is_array($values) && count($values) > 1) {
							// Find the placed relation for the widget.
							foreach ($values as $element) {
								if ($element['elements'][0]->ID == $elementId ) {
									$values = array( $element );
								}
							}
						}
					} else {
						if (!$extractContent) {
							$values = self::getAttachments( $values );
						} else {
							$values = null;
						}
					}
					break;
				case 'Layout' :
					$values = ($extractContent)
						? self::extractLayoutObjectAttachments( $values )
						: self::getAttachments( $values );
					break;
				default:
					$values = self::getAttachments( $values );
					break;
			}
			$values['metadata'] = $metadata;
		}
		return $values;
	}

	/**
	 * Extracts file contents out of Layouts.
	 *
	 * @static
	 * @param object $layoutObject The Layout to get the Data for.
	 * @param string $rendition The rendition to retrieve.
	 * @return array An array of found Attachments.
	 */
	static public function extractLayoutObjectAttachments( $layoutObject, $rendition='native' )
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		//require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';

		$objectid = $layoutObject->MetaData->BasicMetaData->ID;
		$user = BizSession::getShortUserName();
		$tempobject = BizObject::getObject($objectid, $user, false, $rendition);

		$content = array();
		// Get the content.
		if( $rendition != 'native' && ( empty($tempobject->Files) || count($tempobject->Pages) > 1 ) ) {
			foreach($tempobject->Pages as $page) {
				if( !empty($page->Files[0]->FilePath) ) {
					$content[] = $page->Files[0];
				}
			}
		} else {
			$content[] = $tempobject->Files[0];
		}
		return $content;
	}

	/**
	 * Extracts article elements from an article object.
	 *
	 * @param Object $articleObject The object for which to get the Elements.
	 * @param integer $channelId Id of the publication channel to publish in.
	 * @return Element[] An array of elements.
	 */
	static public function extractArticleObjectElements( $articleObject, $channelId )
	{
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';

		$format = $articleObject->MetaData->ContentMetaData->Format;
		switch ($format) {
			case 'application/incopy':
			case 'application/incopyinx':
			case 'application/incopyicml':
				$elements = self::extractWcmlArticleElements( $articleObject, $channelId );
				break;
			case 'text/html':
				$elements = self::extractHtmlElements($articleObject);
				break;
			case 'text/wwea':
				$elements = self::extractWweaElements($articleObject);
				break;
			case 'text/plain':
			default:
				$elements = self::extractPlainTextElements($articleObject);
				break;
		}
		return $elements;
	}

	/**
	 * Extracts plain article elements from an article object.
	 *
	 * @param object $articleObject The Article Object to retrieve the elements from.
	 * @return Element[] An array of Element objects.
	 */
	static private function extractPlainTextElements( $articleObject )
	{
		$elements = array();
		$content = self::getArticleContents( $articleObject );

		$element = new stdClass();
		$element->Label = 'body';
		$element->Content = $content;
		$element->ContentLength = strlen($content);
		$element->ID = null;
		$element->Article = $articleObject;
		$element->InlineImages = null; // For InlineImages: Img Object and its attachment
		// find label
		if (! empty($articleObject->Elements)) {
			// TO DO - handle multi-element articles
			if ($articleObject->Elements[0]->Name != ''){
				// only filled labels
				$element->Label = $articleObject->Elements[0]->Name;
			}
		}

		if(!empty($element->Content)) {
			$elements[] = array('elements' => array($element));
		}
		return $elements;
	}

	/**
	 * Extracts HTML article elements from an article object.
	 *
	 * @static
	 * @param object $articleObject The Article Object to retrieve the elements from.
	 * @return Element[] An array of Element objects.
	 */
	static private function extractHtmlElements( $articleObject )
	{
		$elements = array();
		$content = self::getArticleContents( $articleObject );

		$element = new stdClass();
		$element->Label = 'body';
		// only get body content from html
		$artDoc = new DOMDocument();
		$content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
		$artDoc->loadHTML($content);
		$xpath = new DOMXPath($artDoc);
		$bodies = $xpath->query('/html/body');
		$body = '';
		if ($bodies->length > 0){
			$children = $bodies->item(0)->childNodes;
			foreach ($children as $child){
				$body .= $artDoc->saveXML($child);
			}
		} else {
			$body = $content;
		}
		$element->Content = $body;
		$element->ContentLength = strlen($body);
		$element->ID = null;
		$element->Article = $articleObject;
		$element->InlineImages = null; // For InlineImages: Img Object and its attachment
		// find label
		if (! empty($articleObject->Elements)) {
			if ($articleObject->Elements[0]->Name != ''){
				// only filled labels
				$element->Label = $articleObject->Elements[0]->Name;
			}
		}

		if(!empty($element->Content)) {
			$elements[] = array('elements' => array( $element));
		}
		return $elements;
	}

	/**
	 * Extracts Wwea article elements from an article object.
	 *
	 * @static
	 * @param object $articleObject The Article Object to retrieve the elements from.
	 * @return Element[] An array of Element objects.
	 */
	static private function extractWweaElements( $articleObject )
	{
		$elements = array();
		$content = self::getArticleContents( $articleObject );

		$eaDoc = new DOMDocument();
		$eaDoc->loadXML($content);

		$xpath = new DOMXPath($eaDoc);
		$xpath->registerNamespace('ea', "urn:EnterpriseArticle");

		foreach($xpath->query('/ea:article/ea:component') as $component) {
			$label = "";
			foreach($component->getElementsByTagNameNS("urn:EnterpriseArticle", 'name') as $name) {
				$label = $name->nodeValue;
			}

			$content = "";
			foreach($component->getElementsByTagNameNS("urn:EnterpriseArticle", 'data') as $text) {
				$data = $text->nodeValue;
				$data = trim($data);
				if(!empty($data)) {
					$artDoc = new DOMDocument();
					$data = mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8');
					$artDoc->loadHTML($data);
					$xpath1 = new DOMXPath($artDoc);
					$bodies = $xpath1->query('/html/body');
					if ($bodies->length > 0){
						$children = $bodies->item(0)->childNodes;
						foreach ($children as $child){
							$content .= $artDoc->saveXML($child);
							$content = trim($content);
						}
					} else {
						$content = $data;
					}
				}
			}

			if(!empty($content)) {
				$element = new stdClass();
				$element->Label = $label;
				$element->Content = $content;
				$element->ContentLength = strlen($content);
				$element->Article = $articleObject;
				$element->InlineImages = null; // For InlineImages: Img Object and its attachment
				$element->ID = null;

				$el = array();
				$el['elements'] = array($element);
				$elements[] = $el;
			}
		}
		return $elements;
	}

	/**
	 * Extracts wcml article elements from an article object.
    *
    * @static
    * @param object $articleObject The Article Object to retrieve the elements from.
	 * @param
    * @return Element[] An array of Element objects.
    */
	static private function extractWcmlArticleElements( $articleObject, $channelId )
	{
		$elements = array();
		$content = self::getArticleContents( $articleObject );
		$format = $articleObject->MetaData->ContentMetaData->Format;

		// Convert article into XHTML frames (tinyMCE compatible).
		require_once BASEDIR . '/server/appservices/textconverters/TextConverter.class.php';
		$fc = TextConverter::createTextImporter($format);
		if( $fc instanceof HtmlTextImport ) {
			$fc->setOpenHyperlinkInSameWindow();
		}
		$xFrames = array();
		$stylesCSS = '';
		$stylesMap = '';
		$domVersion = '0';
		$artDoc = new DOMDocument();
		$artDoc->loadXML($content);
		/*$processedInlineImages = */$fc->enableInlineImageProcessing(); // Enable export Inline image processing
		$fc->importBuf($artDoc, $xFrames, $stylesCSS, $stylesMap, $domVersion);

		foreach ($xFrames as $xFrame){
			$content = '';
			$data = mb_convert_encoding($xFrame->Content, 'HTML-ENTITIES', 'UTF-8');
			$data = str_replace( "\n", '', $data );
			$artDoc = new DOMDocument();
			$artDoc->loadHTML($data);
			$xpath = new DOMXPath($artDoc);
			$bodies = $xpath->query('/html/body');
			if ($bodies->length > 0){
				$children = $bodies->item(0)->childNodes;
				foreach ($children as $child){
					$content .= $artDoc->saveXML($child);
					$content = trim($content);
				}
			}

			require_once BASEDIR.'/server/bizclasses/BizImageConverter.class.php';
			$bizImageConverter = new BizImageConverter();
			$inlineImages = array();
			if( $xFrame->InlineImageIds ) {
				foreach( $xFrame->InlineImageIds as $key => $imgId ) {
					$imgInfo = $xFrame->InlineImageInfos[ $key ];
					$placement = new Placement();
					$placement->Width = $imgInfo['Width'];
					$placement->Height = $imgInfo['Height'];
					$placement->ContentDx = $imgInfo['ContentDx'];
					$placement->ContentDy = $imgInfo['ContentDy'];
					$placement->ScaleX = $imgInfo['ScaleX'];
					$placement->ScaleY = $imgInfo['ScaleY'];
					if( $bizImageConverter->loadNativeFileForInputImage( $imgId ) ) {
						if( $bizImageConverter->doesImageNeedConversion( $imgId, $placement ) ) {
							if( $bizImageConverter->convertImageByPlacement( $placement, $channelId ) ) {
								$inlineImages[ $imgId ] = $bizImageConverter->getOutputImageAttachment();
							}
							$bizImageConverter->cleanupNativeFileForInputImage();
						} else { // fallback at native rendition
							$inlineImages[ $imgId ] = $bizImageConverter->getInputImageAttachment();
						}
					}
				}
			}
			$element = new stdClass();
			$element->Label = $xFrame->Label;
			$element->Content = $content;
			$element->ContentLength = strlen($content);
			$element->Article = $articleObject;
			$element->ID = $xFrame->ID;

			if(!empty($element->Content)) {
				$el = array();
				$el['elements'] = array($element);
				$el['attachments'] = $inlineImages;
				$elements[] = $el;
			}
		}
		return $elements;
	}

	/**
	 * Returns or retrieves the attachment belonging to the object.
	 *
	 * @param object $object The Object to get the attachment(s) from.
	 * @return Attachment[] An array of attachments.
	 */
	static private function getAttachments( $object )
	{
		$attachments = array();
		if (!isset($object->Files) || !is_array($object->Files) || count($object->Files) == 0) {
			// Retrieve the object with files to ensure we have the attachments.
			require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
			require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
			$rendition = 'native';
			$requestInfo = array('Pages', 'Targets', 'Relations', 'Elements');
			$objectId = $object->MetaData->BasicMetaData->ID;
			$user = BizSession::getShortUserName();
			try {
				$tempObject = BizObject::getObject($objectId, $user, false, $rendition, $requestInfo, null, true
					, array('Workflow'));
				$attachments = $tempObject->Files;
			} catch (BizException $e ) {
				LogHandler::Log(__CLASS__ . '::' . __FUNCTION__, 'WARN', 'Failed to retrieve placed objects for Object ID: \''
					. $objectId . '\'.');
			}
		} else {
			$attachments =  $object->Files;
		}
		return $attachments;
	}

	/**
	 * Retrieves the file contents for a given article.
	 *
	 * @param Object $articleObject The article for which to get the contents.
	 * @return string
	 */
	static private function getArticleContents( $articleObject )
	{
		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();

		$attachments = self::getAttachments( $articleObject );
		$content = $transferServer->getContent( $attachments[0] );

		// Cleanup the temp images from the transfer folder.
		if( $attachments ) {
			$transferServer = new BizTransferServer();
			foreach( $attachments as $attachment ) {
				$transferServer->deleteFile( $attachment->FilePath );
			}
		}
		return $content;
	}

	/**
	 * Returns Dialog property usages for a PublishForm.
	 *
	 * $wiwiwUsages: When empty array is sent in, a three dimensional list of PropertyUsages that belong to placed
	 * objects on the form will be returned. Keys are used as follows: $wiwiwUsages[mainProp][wiwProp][wiwiwProp]
	 *
	 * @param Object $publishForm The PublishForm to get the mandatory fields from.
	 * @param string $pattern Optional regex pattern to which the Property should adhere to be returned.
	 * @param boolean $withstatics If static (default) properties should be returned as well. Depends on calling code.
	 * @param bool $explicit Request NOT to fall back at global definition levels. Specified level only.
	 * @param null|array $wiwiwUsages [writable] See header above.
	 * @return array An array of PropertyUsages.
	 */
	static public function getPropertyUsagesForForm( $publishForm, $pattern=null, $withstatics=false, $explicit=false, &$wiwiwUsages )
	{
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		$propertyUsages = array();
		$documentId = self::getDocumentId( $publishForm );
		if (!is_null($documentId)) {
			$propertyUsages = BizProperty::getPropertyUsages(0,'PublishFormTemplate','SetPublishProperties',
				$withstatics, $explicit, $documentId, $wiwiwUsages, false );

			if (!is_null($pattern)) {
				// unset the property from first and second level of widgets (mainWidgets and wiw)
				if( $propertyUsages ) foreach ($propertyUsages as $key => $propertyUsage) {
					if (!preg_match($pattern, $propertyUsage->Name)){
						unset ($propertyUsages[$key]);
					}
				}
				// unset the property from third level of widgets (wiwiw)
				if( $wiwiwUsages ) foreach( $wiwiwUsages as $mainPropName => $wiwUsages ) {
					foreach( $wiwUsages as $wiwPropName => $wiwiwUsageArray ) {
						foreach( $wiwiwUsageArray as $wiwiwPropName => $wiwiwUsage ) {
							if (!preg_match($pattern, $wiwiwUsage->Name)){
								unset ( $wiwiwUsages[$mainPropName][$wiwPropName][$wiwiwPropName] );
							}
						}
					}
				}
			}
		}
		return $propertyUsages;
	}

	static public function getDocumentId( $publishForm )
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';

		// Attempt to determine the InstanceOf relational Parent.
		$templateId = self::getTemplateId( $publishForm );
		$user = BizSession::getShortUserName();

		try {
			$publishFormTemplate = BizObject::getObject( $templateId, $user, false, null, null, null, false, null, null );
			$documentId = $publishFormTemplate->MetaData->BasicMetaData->DocumentID;
		} catch (BizException $e) {
			$documentId = null;
		}
		return $documentId;
	}

	/**
	 * Validates an array of form fields.
	 *
	 * @param array $formFields The array of form fields to validate.
	 * @param object $publishForm The PublishForm to validate against.
	 * @param null|string $pattern Optional regex pattern to which the Property should adhere to be returned.
	 * @return bool Whether or not the fields are valid.
	 */
	static public function validateFormFields( $formFields, $publishForm, $pattern=null )
	{
		$valid = true;

		// Check the mandatory fields.
		$wiwiwUsages = array();
		$fields= self::getPropertyUsagesForForm( $publishForm, $pattern, false, false, $wiwiwUsages );
		if ( $fields ) foreach ($fields as $field) {
			$fieldExists = array_key_exists($field->Name, $formFields);
			if ( !$fieldExists && $field->Mandatory ) {
				$valid = false;
				break;
			} elseif ($fieldExists && !is_array($formFields[$field->Name]) && !is_object($formFields[$field->Name]) ){
				$valid = false; // Value needs to be an array or an object.
				break;
			}
		}
		if( $wiwiwUsages ) foreach( $wiwiwUsages as /*$mainPropName => */$wiwUsages ) {
			foreach( $wiwUsages as /*$wiwPropName => */$wiwiwUsageArray ) {
				foreach( $wiwiwUsageArray as $wiwiwPropName => $wiwiwUsage ) {
					$fieldExists = array_key_exists( $wiwiwPropName, $formFields);
					if ( !$fieldExists && $wiwiwUsage->Mandatory ) {
						$valid = false;
						break;
					} elseif ($fieldExists && !is_array($formFields[$wiwiwPropName]) && !is_object($formFields[$wiwiwPropName]) ){
						$valid = false; // Value needs to be an array or an object.
						break;
					}
				}
			}
		}
		return $valid;
	}

	/**
	 * Determines and returns the PublishFormTemplateId for a PublishForm.
	 *
	 * @static
	 * @param object $publishForm The PublishForm for which to retrieve the PublishFormTemplate id.
	 * @return null|string The TemplateId.
	 */
	static public function getTemplateId( $publishForm )
	{
		$templateId = null;
		foreach ($publishForm->Relations as $relation) {
			if ($relation->Type == 'InstanceOf') {
				$templateId = $relation->Parent;
			}
		}
		return $templateId;
	}

	/**
	 * Checks if the Object is a PublishForm.
	 *
	 * A PublishForm is the only object that contains an InstanceOf relation, where the parent is always
	 * the PublishFormTemplate.
	 *
	 * A PublishFormTemplate also matches this criteria, but is purposely filtered out within the function.
	 *
	 * @static
	 * @param Object $object The Object for which it is to be determined if it is a PublishForm.
	 * @return bool Whether or not the object is a PublishForm.
	 */
	static public function isPublishForm($object)
	{
		$found = false;

		// A PublishForm should always at least have a relation of type 'InstanceOf'.
		if ( $object->Relations ) foreach ($object->Relations as $relation) {
			if ($relation->Type == 'InstanceOf') {
				$found = true;
			}
		}

		// A PublishFormTemplate is exempt from this check, due to the two-way relations recorded on the Template
		// it will be recognized as a PublishForm in the code above because it has recorded instanceof relations for
		// its PublishForm children.
		if (isset($object->MetaData) && isset($object->MetaData->BasicMetaData)
			&& $object->MetaData->BasicMetaData->Type == 'PublishFormTemplate') {
			$found = false;
		}

		return $found;
	}

	/**
	 * Retrieve all the objects(children) that are placed on the PublishForm.
	 *
	 * @since 10.1 Moved from BizPublishing class to here.
	 * @param int $publishFormId
	 * @return array List of object Ids that are placed on the PublishForm.
	 */
	static public function getObjectsPlacedOnPublishForm( $publishFormId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$objIdsPlacedOnForm = array();
		$rows = DBObjectRelation::getObjectRelations( $publishFormId, 'childs', 'Placed' );
		if( $rows ) foreach( array_values( $rows ) as $row ) {
			$objIdsPlacedOnForm[] = $row['child'];
		}
		return $objIdsPlacedOnForm;
	}

	/**
	 * Returns a Publish Form in a list of objects (typically all objects contained by a dossier).
	 *
	 * @since 10.1.0
	 * @param Object[]|null $objects List to search through
	 * @return Object|null The Publish Form object. NULL when not found.
	 */
	static public function findPublishFormInObjects( $objects )
	{
		$publishForm = null;
		if( $objects ) foreach( $objects as $object ) {
			if( $object->MetaData->BasicMetaData->Type == 'PublishForm' ) {
				$publishForm = $object;
				break;
			}
		}
		return $publishForm;
	}
}