<?php
/**
 * @package     Enterprise
 * @subpackage  BuildTools
 * @since       10.2.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Describes the DataSource Administration web service interface of the core server.
 */
require_once BASEDIR.'/server/buildtools/genservices/interfaces/WebServiceDescriptorInterface.class.php';

class WW_BuildTools_GenServices_Interfaces_Ads_WebServiceDescriptor implements WW_BuildTools_GenServices_Interfaces_WebServiceDescriptorInterface
{
	final public function getServiceNameFull()  { return 'AdmDatSrc'; }
	final public function getServiceNameShort() { return 'Ads'; }
	final public function getDataClassesFile()  { return BASEDIR.'/server/interfaces/services/ads/DataClasses.php'; }
	final public function getDataClassPrefix()  { return $this->getServiceNameShort(); }
	final public function getNameSpace()        { return 'urn:PlutusAdmin'; } // not used
	final public function getExclDataClasses()  { return array(); }
	final public function getWflDataClasses()   { return array(); }
	final public function getSoapEntryPoint()   { return "LOCALURL_ROOT.INETROOT.'/datasourceadminindex.php'"; }
	final public function getWsdlFilePath()     { return BASEDIR.'/server/interfaces/PlutusAdmin.wsdl'; }
	final public function getProviderBasePath() { return BASEDIR.'/server'; }
	final public function getServerPluginName() { return null; }

	final public function getUrlToFilePath($serviceName)
	{
		return "\\\\ Warning no url to filepath translation.\n\n";
	}

	final public function getFilePathToUrl($serviceName)
	{
		return "\\\\ Warning no filepath to url translation.\n\n";
	}
}