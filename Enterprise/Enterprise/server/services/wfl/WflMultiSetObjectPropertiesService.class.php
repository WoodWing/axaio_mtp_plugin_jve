<?php
/**
 * MultiSetObjectProperties Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v9.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflMultiSetObjectPropertiesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflMultiSetObjectPropertiesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflMultiSetObjectPropertiesService extends EnterpriseService
{
	public function execute( WflMultiSetObjectPropertiesRequest $req )
	{
		// Validate ticket, call restructureRequest() here, call the runBefore() of the server plugins, 
		// call runCallback() here and call the runAfter() of the server plugins.
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflMultiSetObjectProperties', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	/**
	 * @inheritdoc
	 * @param WflMultiSetObjectPropertiesRequest $req
	 * @throws BizException
	 */
	protected function restructureRequest( &$req )
	{
		// Validate that the IDs parameter is passed and contains identifiers.
		if( count( $req->IDs ) == 0 ) {
			$detail = 'The IDs parameter given for the MultiSetObjectProperties request '.
						'does not contain any object ids.';
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', $detail );
		}

		// Validate / set invokable objects. Resolve a minimum set of object properties (metadata) to make life easier
		// for server plugins hooked into this web service. Based on these properties they can quickly decide whether
		// or not custom action is needed without the need to resolve all kinds of properties in the database over and
		// over again.
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$req->InvokedObjects = BizObject::resolveInvokedObjectsForMultiSetProps( $req->IDs );
		$invokedObjectIds = array_keys( $req->InvokedObjects ); // excludes alien ids

		$firstObjectMetadata = reset( $req->InvokedObjects );
		if ( $firstObjectMetadata ) {
			$req->ObjectType = $firstObjectMetadata->BasicMetaData->Type;
			$req->PublicationId = $firstObjectMetadata->BasicMetaData->Publication->Id;
		} else { // If there are no standard / shadow objects we have to throw an error since there are no valid Ids.
			// IDs parameter validation Alien objects are not counted as valid objects to be handled, since these
			// objects do not have a PublicationId and possibly don't have an ObjectType we filter them out and ignore
			// them.
			$detail = 'The IDs parameter given for the MultiSetObjectProperties request '.
				'does not contain any valid object ids.';
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', $detail );
		}

		// Validate that the requested objects all belong to the same Publication and have the same ObjectType.
		if( !BizObject::isSameObjectTypeAndPublication( $invokedObjectIds )) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'Object IDs ('.implode(',', $invokedObjectIds ).') provided must be of the same object '.
					'type and from the same publication.');
		}

		// Collect information on the overrule issues for the Objects.
		require_once BASEDIR .'/server/dbclasses/DBIssue.class.php';
		$req->SendToNext = false;
		$sameOverruleIssue = DBIssue::isSameOverruleIssue( $invokedObjectIds );
		$req->OverruleIssueId = 0;

		if ( !is_null( $sameOverruleIssue ) ) {
			// When we receive an overrule issue we expect ALL objects to belong to that same Overrule issue, otherwise
			// an exception should be thrown.
			if ( $sameOverruleIssue === false ) {
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
					'Object IDs ('.implode(',', $invokedObjectIds ).') provided must be of the same overrule issue.' );
			}

			// Pass the overrule Issue Id to be used when updating the Objects.
			$overruleIds = DBIssue::getOverruleIssueIdsFromObjectIds( array( $firstObjectMetadata->BasicMetaData->ID ) );
			$req->OverruleIssueId = reset( $overruleIds );
		}

		// Validate that the MetaData parameter contains data.
		if( count( $req->MetaData ) == 0 ) {
			$detail = 'The MetaData parameter given for the MultiSetObjectProperties request '.
						'does not contain any MetaDataValue items.';
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', $detail );
		}

		// Fetch allowed ObjectProperties and validate the MetaData.
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$objectTypeProperties = BizProperty::getPropertiesForObjectType( $req->PublicationId, $req->ObjectType );
		$blockedProperties = array( 'PublicationId', 'IssueId'); // Cannot be set using MultiSetProperties.
		$illegalProperties = array();
		foreach ( $req->MetaData as $metaDataValue ) {
			if ( $metaDataValue->Property == 'StateId' && empty( $metaDataValue->PropertyValues[0]->Value ) ) {
				$req->SendToNext = true; // An empty state means the client wants to move the Objects to their next state.
			}

			if ( !in_array( $metaDataValue->Property, $objectTypeProperties )
				|| in_array( $metaDataValue->Property, $blockedProperties ) ) {
				$illegalProperties[] = $metaDataValue->Property;
			}
		}

		// Report the illegal properties.
		if ( $illegalProperties ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'Provided MetaData properties ( ' . implode( ',', $illegalProperties ) . ' ) are not legal database '
					. 'properties for the object(s).');
		}

		// Make sure errors get collected in a list of ErrorReport objects.
		$this->enableReporting();
	}

	public function runCallback( WflMultiSetObjectPropertiesRequest $req )
	{
		// Call the biz layer that implements this core feature.
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$updatedProps = BizObject::multiSetObjectProperties( 
			$req->IDs, // requested objects (ids) to change
			$req->InvokedObjects, // found objects to change
			$req->MetaData, // changed properties to apply for all objects
			$req->PublicationId, // publication id for the invoked objects
			$req->ObjectType, // type of the invoked objects
			$req->OverruleIssueId, // id of the overrule issue if present.
			$req->SendToNext // whether to send the Objects to their next status.
		);

		// Compose the service response.
		$response = new WflMultiSetObjectPropertiesResponse();
		$response->Reports = BizErrorReport::getReports();

		$response->MetaData = $updatedProps;
		if ( $req->SendToNext ) {
			$response->MetaData = $updatedProps['MetadataValues'];
			$response->RoutingMetaDatas = $updatedProps['RoutingMetaDatas'];
		}

		return $response;
	}
}