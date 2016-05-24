<?php
/**
 * Publishing SOAP client.
 *
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Client.php';

class WW_SOAP_PubClient extends WW_SOAP_Client
{
	public function __construct(array $options = array())
	{
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
		$options['classmap']['Field'] = 'PubField';
		$options['classmap']['MessageContext'] = 'PubMessageContext';
		$options['classmap']['ObjectInfo'] = 'PubObjectInfo';
		$options['classmap']['PageInfo'] = 'PubPageInfo';
		$options['classmap']['ProgressPhase'] = 'PubProgressPhase';
		$options['classmap']['PublishHistory'] = 'PubPublishHistory';
		$options['classmap']['PublishTarget'] = 'PubPublishTarget';
		$options['classmap']['PublishedDossier'] = 'PubPublishedDossier';
		$options['classmap']['PublishedIssue'] = 'PubPublishedIssue';
		$options['classmap']['PublishedObject'] = 'PubPublishedObject';
		$options['classmap']['ReportMessage'] = 'PubReportMessage';
		$options['classmap']['UserMessage'] = 'PubUserMessage';
		$options['classmap']['PublishDossiersResponse'] = 'PubPublishDossiersResponse';
		$options['classmap']['UpdateDossiersResponse'] = 'PubUpdateDossiersResponse';
		$options['classmap']['UnPublishDossiersResponse'] = 'PubUnPublishDossiersResponse';
		$options['classmap']['GetDossierURLResponse'] = 'PubGetDossierURLResponse';
		$options['classmap']['GetPublishInfoResponse'] = 'PubGetPublishInfoResponse';
		$options['classmap']['SetPublishInfoResponse'] = 'PubSetPublishInfoResponse';
		$options['classmap']['PreviewDossiersResponse'] = 'PubPreviewDossiersResponse';
		$options['classmap']['GetDossierOrderResponse'] = 'PubGetDossierOrderResponse';
		$options['classmap']['UpdateDossierOrderResponse'] = 'PubUpdateDossierOrderResponse';
		$options['classmap']['AbortOperationResponse'] = 'PubAbortOperationResponse';
		$options['classmap']['OperationProgressResponse'] = 'PubOperationProgressResponse';
		

		if( !array_key_exists( 'location', $options ) ) {
			$options['location'] = LOCALURL_ROOT.INETROOT.'/publishindex.php';
		}
		$options['uri'] = 'urn:EnterprisePublishing';
		$options['use'] = SOAP_LITERAL;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$options['soap_version'] = SOAP_1_1;

		// soap handler class
		parent::__construct( $options['location'].'?wsdl', $options );
	}
}
