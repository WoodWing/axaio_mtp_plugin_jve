<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/EnterpriseConnectorData.intf.php';

class ConnectorInfoData implements EnterpriseConnectorData
{
	// Properties determined by plugin:
	public $Interface;  // The interface class which is implemented by connector
	public $Type;       // 'WorkflowAction', 'WorkflowService' or 'WorkflowEvent'
	public $Prio;       // In which sequence the connector should be called by system
	public $RunMode;    // In what contect to run the connector: 'synchron' or 'background'

	// Properties determined by system:
	public $Id;         // Database id of connector
	public $PluginId;   // Database id of plugin
	public $ClassName;  // Php class name of connector
	public $ClassFile;   // Php class file path relative to BASEDIR
	public $Modified;   // Last source file modification date

	public function getInterfaceVersion() { return 1; }
}