<?php
/**
 * DataSource SOAP client.
 *
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR . '/server/protocols/soap/Client.php';

class WW_SOAP_DatClient extends WW_SOAP_Client
{
	public function __construct(array $options = array())
	{
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
		$options['classmap']['Attribute'] = 'DatAttribute';
		$options['classmap']['DatasourceInfo'] = 'DatDatasourceInfo';
		$options['classmap']['List'] = 'DatList';
		$options['classmap']['PlacedQuery'] = 'DatPlacedQuery';
		$options['classmap']['Placement'] = 'DatPlacement';
		$options['classmap']['Property'] = 'DatProperty';
		$options['classmap']['Query'] = 'DatQuery';
		$options['classmap']['QueryParam'] = 'DatQueryParam';
		$options['classmap']['Record'] = 'DatRecord';
		$options['classmap']['RecordField'] = 'DatRecordField';
		$options['classmap']['QueryDatasourcesResponse'] = 'DatQueryDatasourcesResponse';
		$options['classmap']['GetDatasourceResponse'] = 'DatGetDatasourceResponse';
		$options['classmap']['GetRecordsResponse'] = 'DatGetRecordsResponse';
		$options['classmap']['SetRecordsResponse'] = 'DatSetRecordsResponse';
		$options['classmap']['HasUpdatesResponse'] = 'DatHasUpdatesResponse';
		$options['classmap']['OnSaveResponse'] = 'DatOnSaveResponse';
		$options['classmap']['GetUpdatesResponse'] = 'DatGetUpdatesResponse';
		

		if( !array_key_exists( 'location', $options ) ) {
			$options['location'] = LOCALURL_ROOT.INETROOT.'/datasourceindex.php';
		}
		$options['uri'] = 'urn:PlutusDatasource';
		$options['use'] = SOAP_LITERAL;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$options['soap_version'] = SOAP_1_1;

		// soap handler class
		parent::__construct( $options['location'].'?wsdl', $options );
	}
}
