<?php
/**
 * SendToNext Workflow service.
 *
 * @since v9.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSendToNextRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflSendToNextResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflSendToNextService extends EnterpriseService
{
	/**
	 * Sends Objects to their next status.
	 *
	 * This service sends a list of objects that all should belong to the same Brand / Overrule Issue and are of the
	 * same ObjectType to the next workflow status.
	 *
	 * @param WflSendToNextRequest $stnRequest The request data.
	 * @return WflSendToNextResponse The service response.
	 */
	public function execute( WflSendToNextRequest $stnRequest )
	{
		require_once BASEDIR.'/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';

		// Redirect SendToNext (stn) to MultiSetObjectProperties (msp) request
		$mspRequest = new WflMultiSetObjectPropertiesRequest();
		$mspRequest->Ticket = $stnRequest->Ticket;
		$mspRequest->IDs = $stnRequest->IDs;

		// Add the Metadata to the request:
		$metadataValues = array();
		$metadataValue = new MetaDataValue();
		$metadataValue->Property = 'StateId';
		$propertyValue = new PropertyValue();
		$propertyValue->Value = '';
		$metadataValue->PropertyValues = array( $propertyValue );
		$metadataValues[] = $metadataValue;
		$mspRequest->MetaData = $metadataValues;
		
		// Redirect SendToNext to MultiSetObjectProperties service
		$mspService = new WflMultiSetObjectPropertiesService();
		$mspResponse = $mspService->execute( $mspRequest );

		// Redirect MultiSetObjectProperties (msp) to SendToNext (stn) response
		$stnResponse = new WflSendToNextResponse();
		$stnResponse->Reports = $mspResponse->Reports;
		$stnResponse->RoutingMetaDatas = $mspResponse->RoutingMetaDatas;
		return $stnResponse;
	}
}
