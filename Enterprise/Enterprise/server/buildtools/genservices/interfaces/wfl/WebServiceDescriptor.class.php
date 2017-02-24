<?php
/**
 * @package     Enterprise
 * @subpackage  BuildTools
 * @since       10.2.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Describes the Workflow web service interface of the core server.
 */
require_once BASEDIR.'/server/buildtools/genservices/interfaces/WebServiceDescriptorInterface.class.php';

class WW_BuildTools_GenServices_Interfaces_Wfl_WebServiceDescriptor implements WW_BuildTools_GenServices_Interfaces_WebServiceDescriptorInterface
{
	final public function getServiceNameFull()  { return 'Workflow'; }
	final public function getServiceNameShort() { return 'Wfl'; }
	final public function getDataClassesFile()  { return BASEDIR.'/server/interfaces/services/wfl/DataClasses.php'; }
	final public function getDataClassPrefix()  { return ''; } // no prefix!
	final public function getNameSpace()        { return 'urn:SmartConnection'; }
	final public function getExclDataClasses()  { return array('AttachmentContent', 'Row', 'GetStatesResponse'); }
	final public function getWflDataClasses()   { return array(); }
	final public function getSoapEntryPoint()   { return "LOCALURL_ROOT.INETROOT.'/index.php'"; }
	final public function getWsdlFilePath()     { return BASEDIR.'/server/interfaces/SCEnterprise.wsdl'; }
	final public function getProviderBasePath() { return BASEDIR.'/server'; }
	final public function getServerPluginName() { return null; }

	final public function getUrlToFilePath($serviceName)
	{
		switch ($serviceName) {
			case 'CreateObjectRelations':
			case 'UpdateObjectRelations':
				$result =
					"\t\t\trequire_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';\n" .
					"\t\t\t\$transferServer = new BizTransferServer();\n" .
					"\t\t\tif( \$req->Relations ) foreach( \$req->Relations as \$relation ) {\n" .
					"\t\t\t\tif ( \$relation->Geometry ) {\n".
					"\t\t\t\t\t\$transferServer->urlToFilePath( \$relation->Geometry );\n" .
					"\t\t\t\t}\n" .
					"\t\t\t}\n";
				break;
			default:
				$result =
					"\t\t\trequire_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';\n" .
					"\t\t\t\$transferServer = new BizTransferServer();\n" .
					"\t\t\tif( \$req->Objects ) foreach( \$req->Objects as \$object ) {\n" .
					"\t\t\t\t\$transferServer->switchURLToFilePath( \$object );\n" .
					"\t\t\t}\n";
				break;
		}
		return $result;
	}

	final public function getFilePathToUrl($serviceName)
	{
		switch ($serviceName) {
			case 'GetObjectRelations':
				$result =
					"\n" .
					"\t\trequire_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';\n" .
					"\t\t\$transferServer = new BizTransferServer();\n" .
					"\t\tif( \$resp->Relations ) foreach( \$resp->Relations as \$relation ) {\n" .
					"\t\t\tif ( \$relation->Geometry ) {\n".
					"\t\t\t\t\$transferServer->filePathToURL( \$relation->Geometry );\n" .
					"\t\t\t}\n" .
					"\t\t}\n\n";
				break;
			case 'GetVersion':
				$result =
					"\n" .
					"\t\trequire_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';\n" .
					"\t\t\$transferServer = new BizTransferServer();\n" .
					"\t\tif( \$resp->VersionInfo->File ) {\n" .
					"\t\t\t\$transferServer->filePathToURL( \$resp->VersionInfo->File );\n" .
					"\t\t}\n\n";
				break;
			case 'ListVersions':
				$result =
					"\n" .
					"\t\trequire_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';\n" .
					"\t\t\$transferServer = new BizTransferServer();\n" .
					"\t\tif( \$resp->Versions ) foreach( \$resp->Versions as \$versionInfo ) {\n" .
					"\t\t\tif ( \$versionInfo->File ) {\n".
					"\t\t\t\t\$transferServer->filePathToURL( \$versionInfo->File );\n" .
					"\t\t\t}\n" .
					"\t\t}\n\n";
				break;
			case 'GetPages':
				$result =
					"\n" .
					"\t\trequire_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';\n" .
					"\t\t\$transferServer = new BizTransferServer();\n" .
					"\t\tif( \$resp->ObjectPageInfos ) foreach( \$resp->ObjectPageInfos as \$pageInfo ) {\n" .
					"\t\t\tif( \$pageInfo->Pages ) foreach( \$pageInfo->Pages as \$page ) {\n".
					"\t\t\t\tif( \$page->Files ) foreach( \$page->Files as \$file ) {\n".
					"\t\t\t\t\t\$transferServer->filePathToURL( \$file );\n" .
					"\t\t\t\t}\n" .
					"\t\t\t}\n" .
					"\t\t}\n\n";
				break;
			default:
				$result =
					"\n" .
					"\t\trequire_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';\n" .
					"\t\t\$transferServer = new BizTransferServer();\n" .
					"\t\tif( \$resp->Objects ) foreach( \$resp->Objects as \$object ) {\n" .
					"\t\t\t\$transferServer->switchFilePathToURL( \$object );\n" .
					"\t\t}\n\n";
				break;
		}
		return $result;
	}
}
