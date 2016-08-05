<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Called by core server to let plugin automatically install custom object properties
 * into the database (instead of manual installation in the Metadata admin page).
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/CustomObjectMetaData_EnterpriseConnector.class.php';

class Drupal7_CustomObjectMetaData extends CustomObjectMetaData_EnterpriseConnector
{
	private $errors = array();

	/**
	 * See CustomObjectMetaData_EnterpriseConnector::collectCustomProperties function header.
	 */
	final public function collectCustomProperties( $coreInstallation )
	{
		require_once dirname(__FILE__).'/Utils.class.php';
		$props = array();

		// Because we provide an admin page that imports custom object properties definition,
		// we bail out when the core server is gathering and installing all custom properties
		// during generic installation procedure such as running the Server Plug-ins page.
		if( $coreInstallation ) {
			return $props;
		}

		// At this point, the admin user has pressed the import button of our Drupal7 import page.

		// Retrieve the PubChannelInfos
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		require_once dirname(__FILE__).'/DrupalField.class.php';
		require_once dirname(__FILE__).'/webapps/Drupal7_ImportDefinitions_EnterpriseWebApp.class.php';
		$context = Drupal7_ImportDefinitions_EnterpriseWebApp::getContext();
		if ( !is_null( $context ) ) {
			$pubChannelInfos = $context;
			Drupal7_ImportDefinitions_EnterpriseWebApp::resetContext(); // Make sure the Web App context is not used in another process.
		} else {
			$pubChannelInfos = BizAdmPublication::getPubChannelInfosForPublishSystem( 'Drupal7' );
		}

		foreach ($pubChannelInfos as $pubChannelInfo) {
			$templates = self::getTemplatesFromDB($pubChannelInfo->Id);
			foreach ($templates as $templateId => $documentId) {

				$contentType = WW_Plugins_Drupal7_Utils::convertDocumentId2ContentType($documentId);

				$fields = $this->getFieldsFromDrupal($pubChannelInfo, $contentType);
				require_once dirname(__FILE__).'/Utils.class.php';

				if( is_array($fields) && isset( $fields['fields']) ) {
					foreach( $fields['fields'] as $field ) {
						$errors = array();
						// Create a new DrupalField and get any errors from the field generation.
						$drupalField = DrupalField::generateFromDrupalFieldDefinition($field, $templateId,
											$pubChannelInfo->Id, $contentType );
						$errors = array_merge($errors, $drupalField->getErrors());

						// Attempt to create a propertyInfo, and get any errors.
						$propertyInfos = $drupalField->generatePropertyInfo( true );
						$errors = array_merge($errors, $drupalField->getErrors());

						// No errors, add the property to the list.
						if( count($errors) == 0 ) {
							if( $propertyInfos ) foreach ($propertyInfos as $objectType => $propInfos ) {
								if ($propInfos) foreach ( $propInfos as $propInfo )
								{
									if (!isset($props[0][$objectType])) {
										$props[0][$objectType] = array();
									}
									$props[0][$objectType][] = $propInfo;
								}
							}
						} else {
							$this->errors = array_merge( $this->errors, $errors );
						}
					}
				}

				// Join in properties for the Promote, Sticky and Comments, Title values.
				if (is_array($fields) && isset( $fields['publish_properties'])) {
					$drupalField = new DrupalField();
					$propertyInfos = $drupalField->getSpecialPropertyInfos($templateId, $fields['publish_properties'],
							$pubChannelInfo->Id, $contentType );

					if ( $drupalField->hasError() ) {
						$this->errors = array_merge( $this->errors, $drupalField->getErrors() );
					} else { // Just warnings or no errors
						if( $propertyInfos ) foreach ($propertyInfos as $propertyInfo) {
							$props[0]['PublishForm'][] = $propertyInfo;
						}
					}
			    }
			}
		}
		return $props;
	}

	/**
	 * Retrieves all field definitions made at Drupal (for all content types).
	 *
     * @param AdmPubChannel $pubChannelInfo
	 * @param null|string $contentType The ContentType for which to get the Fields. Default: NULL
	 * @return array List of field definitions.
	 */
	private function getFieldsFromDrupal($pubChannelInfo, $contentType=null)
	{
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php'; // PubPublishTarget
		require_once dirname(__FILE__).'/DrupalXmlRpcClient.class.php';
		$publishTarget = new PubPublishTarget( $pubChannelInfo->Id );
		$drupalXmlRpcClient = new DrupalXmlRpcClient($publishTarget);
		return $drupalXmlRpcClient->getFields( $contentType );
	}

	/**
	 * Retrieves the templates from the Enterprise database.
	 *
	 * Should maybe be moved to a different location for global use.
	 */
	private function getTemplatesFromDB($pubChannelId)
	{
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';

		$service = new WflNamedQueryService();

		$req = new WflNamedQueryRequest();
		$req->Ticket = BizSession::getTicket();
		$req->User   = BizSession::getShortUserName();
		$req->Query  = 'PublishFormTemplates';

		$queryParam = new QueryParam();
		$queryParam->Property = 'PubChannelId';
		$queryParam->Operation = '=';
		$queryParam->Value = $pubChannelId;
		$req->Params = array( $queryParam );

		$resp = $service->execute( $req );

		$templates = array();

		// Determine column indexes to work with, and map them.
		$minProps = array( 'ID', 'Type', 'Name', 'DocumentID' );
		$indexes = array_combine( array_values( $minProps ), array_fill( 1, count( $minProps ), -1 ) );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $resp->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found
				}
			}
		}

		foreach( $resp->Rows as $row ) {
			$templates[$row[$indexes['ID']]] = $row[$indexes['DocumentID']];
		}
		return $templates;
	}

	/**
	 * Returns errors collected during calling of collectCustomProperties().
	 *
	 * @return array Errors collected during custom properties collection.
	 */
	public function getErrors()
	{
		return $this->errors;
	}
}
