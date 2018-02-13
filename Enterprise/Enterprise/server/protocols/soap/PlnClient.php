<?php
/**
 * Planning SOAP client.
 *
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR . '/server/protocols/soap/Client.php';

class WW_SOAP_PlnClient extends WW_SOAP_Client
{
	public function __construct(array $options = array())
	{
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
		$options['classmap']['Advert'] = 'PlnAdvert';
		$options['classmap']['Attachment'] = 'Attachment';
		$options['classmap']['Edition'] = 'Edition';
		$options['classmap']['Layout'] = 'PlnLayout';
		$options['classmap']['LayoutFromTemplate'] = 'PlnLayoutFromTemplate';
		$options['classmap']['Page'] = 'PlnPage';
		$options['classmap']['Placement'] = 'PlnPlacement';
		$options['classmap']['LogOnResponse'] = 'PlnLogOnResponse';
		$options['classmap']['LogOffResponse'] = 'PlnLogOffResponse';
		$options['classmap']['CreateLayoutsResponse'] = 'PlnCreateLayoutsResponse';
		$options['classmap']['ModifyLayoutsResponse'] = 'PlnModifyLayoutsResponse';
		$options['classmap']['DeleteLayoutsResponse'] = 'PlnDeleteLayoutsResponse';
		$options['classmap']['CreateAdvertsResponse'] = 'PlnCreateAdvertsResponse';
		$options['classmap']['ModifyAdvertsResponse'] = 'PlnModifyAdvertsResponse';
		$options['classmap']['DeleteAdvertsResponse'] = 'PlnDeleteAdvertsResponse';
		

		if( !array_key_exists( 'location', $options ) ) {
			$options['location'] = LOCALURL_ROOT.INETROOT.'/editorialplan.php';
		}
		$options['uri'] = 'urn:SmartEditorialPlan';
		$options['use'] = SOAP_LITERAL;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$options['soap_version'] = SOAP_1_1;

		// soap handler class
		parent::__construct( $options['location'].'?wsdl', $options );
	}
}
