<?php
/**
 * AdmDatSrc SOAP client.
 *
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR . '/server/protocols/soap/Client.php';

class WW_SOAP_AdsClient extends WW_SOAP_Client
{
	public function __construct(array $options = array())
	{
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
		$options['classmap']['DatasourceInfo'] = 'AdsDatasourceInfo';
		$options['classmap']['DatasourceType'] = 'AdsDatasourceType';
		$options['classmap']['Publication'] = 'AdsPublication';
		$options['classmap']['Query'] = 'AdsQuery';
		$options['classmap']['QueryField'] = 'AdsQueryField';
		$options['classmap']['Setting'] = 'AdsSetting';
		$options['classmap']['SettingsDetail'] = 'AdsSettingsDetail';
		$options['classmap']['GetPublicationsResponse'] = 'AdsGetPublicationsResponse';
		$options['classmap']['GetDatasourceInfoResponse'] = 'AdsGetDatasourceInfoResponse';
		$options['classmap']['GetDatasourceResponse'] = 'AdsGetDatasourceResponse';
		$options['classmap']['GetQueryResponse'] = 'AdsGetQueryResponse';
		$options['classmap']['GetQueriesResponse'] = 'AdsGetQueriesResponse';
		$options['classmap']['GetQueryFieldsResponse'] = 'AdsGetQueryFieldsResponse';
		$options['classmap']['GetDatasourceTypesResponse'] = 'AdsGetDatasourceTypesResponse';
		$options['classmap']['GetDatasourceTypeResponse'] = 'AdsGetDatasourceTypeResponse';
		$options['classmap']['GetSettingsDetailsResponse'] = 'AdsGetSettingsDetailsResponse';
		$options['classmap']['GetSettingsResponse'] = 'AdsGetSettingsResponse';
		$options['classmap']['QueryDatasourcesResponse'] = 'AdsQueryDatasourcesResponse';
		$options['classmap']['NewQueryResponse'] = 'AdsNewQueryResponse';
		$options['classmap']['NewDatasourceResponse'] = 'AdsNewDatasourceResponse';
		$options['classmap']['SavePublicationResponse'] = 'AdsSavePublicationResponse';
		$options['classmap']['SaveQueryFieldResponse'] = 'AdsSaveQueryFieldResponse';
		$options['classmap']['SaveQueryResponse'] = 'AdsSaveQueryResponse';
		$options['classmap']['SaveDatasourceResponse'] = 'AdsSaveDatasourceResponse';
		$options['classmap']['SaveSettingResponse'] = 'AdsSaveSettingResponse';
		$options['classmap']['DeletePublicationResponse'] = 'AdsDeletePublicationResponse';
		$options['classmap']['DeleteQueryFieldResponse'] = 'AdsDeleteQueryFieldResponse';
		$options['classmap']['DeleteQueryResponse'] = 'AdsDeleteQueryResponse';
		$options['classmap']['DeleteDatasourceResponse'] = 'AdsDeleteDatasourceResponse';
		$options['classmap']['CopyDatasourceResponse'] = 'AdsCopyDatasourceResponse';
		$options['classmap']['CopyQueryResponse'] = 'AdsCopyQueryResponse';
		

		if( !array_key_exists( 'location', $options ) ) {
			$options['location'] = LOCALURL_ROOT.INETROOT.'/datasourceadminindex.php';
		}
		$options['uri'] = 'urn:PlutusAdmin';
		$options['use'] = SOAP_LITERAL;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$options['soap_version'] = SOAP_1_1;

		// soap handler class
		parent::__construct( $options['location'].'?wsdl', $options );
	}
}
