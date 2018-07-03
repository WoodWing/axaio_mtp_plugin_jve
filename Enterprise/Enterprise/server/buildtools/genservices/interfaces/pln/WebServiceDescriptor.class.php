<?php
/**
 * @package     Enterprise
 * @subpackage  BuildTools
 * @since       10.2.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Describes the Planning web service interface of the core server.
 */
require_once BASEDIR.'/server/buildtools/genservices/interfaces/WebServiceDescriptorInterface.class.php';

class WW_BuildTools_GenServices_Interfaces_Pln_WebServiceDescriptor implements WW_BuildTools_GenServices_Interfaces_WebServiceDescriptorInterface
{
	final public function getServiceNameFull()
	{
		return 'Planning';
	}

	final public function getServiceNameShort()
	{
		return 'Pln';
	}

	final public function getDataClassesFile()
	{
		return BASEDIR.'/server/interfaces/services/pln/DataClasses.php';
	}

	final public function getDataClassPrefix()
	{
		return $this->getServiceNameShort();
	}

	final public function getNameSpace()
	{
		return 'urn:SmartEditorialPlan';
	}

	final public function getExclDataClasses()
	{
		return array( 'AttachmentContent', 'Edition', 'Attachment' );
	}

	final public function getWflDataClasses()
	{
		return array( 'Edition', 'Attachment' );
	} // already defined by workflow

	final public function getSoapEntryPoint()
	{
		return "LOCALURL_ROOT.INETROOT.'/editorialplan.php'";
	}

	final public function getExternalSoapEntryPoint()
	{
		return "SERVERURL_ROOT.INETROOT.'/editorialplan.php'";
	}

	final public function getWsdlFilePath()
	{
		return BASEDIR.'/server/interfaces/SmartEditorialPlan.wsdl';
	}

	final public function getProviderBasePath()
	{
		return BASEDIR.'/server';
	}

	final public function getPluginNameFull()
	{
		return null;
	}

	final public function getPluginNameShort()
	{
		return null;
	}

	final public function getUrlToFilePath($serviceName)
	{
		return
			"\t\t\trequire_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';\n" .
			"\t\t\t\$transferServer = new BizTransferServer();\n" .
			"\t\t\tif( \$req->Adverts ) foreach( \$req->Adverts as \$advert ) {\n" .
			"\t\t\t\tif( \$advert->File ) {\n" .
			"\t\t\t\t\t\$transferServer->urlToFilePath( \$advert->File );\n" .
			"\t\t\t\t}\n" .
			"\t\t\t\tif( !is_null( \$advert->Page ) && isset( \$advert->Page->Files ) ) {\n" .
			"\t\t\t\t\tforeach( \$advert->Page->Files as \$file ) {\n" .
			"\t\t\t\t\t\t\$transferServer->urlToFilePath( \$file );\n" .
			"\t\t\t\t\t}\n" .
			"\t\t\t\t}\n" .
			"\t\t\t}\n";
	}

	final public function getFilePathToUrl($serviceName)
	{
		return
			"\n" .
			"\t\trequire_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';\n" .
			"\t\t\$transferServer = new BizTransferServer();\n" .
			"\t\tif( \$resp->Adverts ) foreach( \$resp->Adverts as \$advert ) {\n" .
			"\t\t\tif( \$advert->File ) {\n" .
			"\t\t\t\t\$transferServer->filePathToURL( \$advert->File );\n" .
			"\t\t\t}\n" .
			"\t\t\tif( !is_null( \$advert->Page ) && isset( \$advert->Page->Files ) ) {\n" .
			"\t\t\t\tforeach( \$advert->Page->Files as \$file ) {\n" .
			"\t\t\t\t\t\$transferServer->filePathToURL( \$file );\n" .
			"\t\t\t\t}\n" .
			"\t\t\t}\n" .
			"\t\t}\n";
	}
}