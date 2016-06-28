<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * This plugin detects hooks into several workflow web services to detect whether or not
 * it is needed to create an IDS background job to process a layout. Whether or not processing 
 * is needed, depends on the CLIENTFEATURES option in configserver.php.
 * When the IDS job runs, it creates a thumb, preview and/or PDF/EPS file per page/edition.
 * Aside to Layout objects, also Layout Module objects are supported.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';

class IdsAutomation_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{
		$info = new PluginInfoData();
		$info->DisplayName = 'InDesign Server Automation';
		$info->Version     = '10.0.0 Build 84'; // don't use PRODUCTVERSION
		$info->Description = 'Creates a preview, PDF or EPS from layout (or Layout Module) pages '.
			'using Adobe InDesign Server. This happens in the background after saving a layout or '.
			'a placed article, image or spreadsheet or after changing a layout status. ';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}

	final public function getConnectorInterfaces()
	{
		return array(
			'WflGetObjects_EnterpriseConnector',
			'WflInstantiateTemplate_EnterpriseConnector',
			'WflCreateObjects_EnterpriseConnector',
			'WflSaveObjects_EnterpriseConnector',
			'WflRestoreVersion_EnterpriseConnector',
			'WflSetObjectProperties_EnterpriseConnector',
			'WflMultiSetObjectProperties_EnterpriseConnector',
			'WflCreateObjectOperations_EnterpriseConnector',
			'WflUnlockObjects_EnterpriseConnector',
		);
	}

	public function isActivatedByDefault()
	{
		return false;
	}
}
