<?php
/**
 * @since       10.2.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * PHP interface class to describe a web service interface provided by the core server or a server plugin.
 */
interface WW_BuildTools_GenServices_Interfaces_WebServiceDescriptorInterface
{
	public function getServiceNameFull();
	public function getServiceNameShort();
	public function getDataClassesFile();
	public function getDataClassPrefix();
	public function getNameSpace();
	public function getExclDataClasses();
	public function getWflDataClasses();
	public function getSoapEntryPoint();
	public function getExternalSoapEntryPoint();
	public function getWsdlFilePath();
	public function getProviderBasePath();
	public function getPluginNameFull();
	public function getPluginNameShort();

	public function getUrlToFilePath( $serviceName );
	public function getFilePathToUrl( $serviceName );
}