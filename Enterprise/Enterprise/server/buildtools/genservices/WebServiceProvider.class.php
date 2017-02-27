<?php
/**
 * @package     Enterprise
 * @subpackage  BuildTools
 * @since       10.2.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Provides the supported web service interfaces and protocols of the core server.
 */
require_once BASEDIR.'/server/buildtools/genservices/WebServiceProviderInterface.class.php';

class WW_BuildTools_GenServices_WebServiceProvider implements WW_BuildTools_GenServices_WebServiceProviderInterface
{
	/**
	 * @inheritdoc
	 */
	public function getInterfaces()
	{
		return array( 'adm', 'ads', 'dat', 'pln', 'pub', 'wfl' );
	}

	/**
	 * @inheritdoc
	 */
	public function getProtocols()
	{
		return array( 'soap', 'json', 'amf' );
	}
}