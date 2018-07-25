<?php
/**
 * @since       10.2.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */
interface WW_BuildTools_GenServices_WebServiceProviderInterface
{
	/**
	 * Provides a list of interfaces implemented by the web service provider.
	 *
	 * The interfaces are abbreviated names of web service entry points of the provider.
	 * Those names should exist as sub-folder on the file system wherein a class could be
	 * found that describes the interface.
	 *
	 * The name of the class is composed as follows:
	 *    server: WW_BuildTools_GenServices_Interfaces_<Interface>_WebServiceProvider
	 *    plugin: <plugin>_BuildTools_GenServices_Interfaces_<Interface>_WebServiceProvider
	 *
	 * The file of the module is composed as follows:
	 *    server: Enterprise/server/buildtools/genservices/interfaces/<interface>/WebServiceInterfaceDescriptor.class.php
	 *    plugin: Enterprise/[config|server]/buildtools/genservices/interfaces/<interface>/WebServiceInterfaceDescriptor.class.php
	 *
	 * @return string[] List of abbreviated names of interfaces.
	 */
	public function getInterfaces();

	/**
	 * Provides a list of protocols implemented by the web service provider.
	 *
	 * @return string[]|null List of protocols.
	 */
	public function getProtocols();

}