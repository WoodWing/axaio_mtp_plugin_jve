<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v8.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Sample that shows how to let a server plug-in automatically install custom object
 * properties into the database (instead of manual installation in the Metadata admin page).
**/

require_once BASEDIR . '/server/interfaces/plugins/connectors/CustomObjectMetaData_EnterpriseConnector.class.php';

class CustomObjectPropsDemo_CustomObjectMetaData extends CustomObjectMetaData_EnterpriseConnector
{
	final public function collectCustomProperties( $coreInstallation )
	{
		$coreInstallation = $coreInstallation; // To make the analyser happy
		$props = array();
		$hiddenString = new PropertyInfo( 'C_CUSTOBJPROPDEMO_HIDDENSTR', 'Hidden String', null, 'string', 'Hidden Text' );
		$hiddenString->AdminUI = false; // explicitly set to false, else by default is true. (AdminUI is not defined in WSDL, so use it here internally)
		$props[0][0] = array(
			new PropertyInfo( 'C_CUSTOBJPROPDEMO_USERNAME', 'User', null, 'string', '' ),
			new PropertyInfo( 'C_CUSTOBJPROPDEMO_TRAFFIC', 'Traffic', null, 'list', 'orange', array('red','orange','green') ),
			new PropertyInfo( 'C_CUSTOBJPROPDEMO_SHOPPING', 'Shopping', null, 'multilist', 'butter', array('vegetables','butter','milk') ),
			new PropertyInfo( 'C_CUSTOBJPROPDEMO_KEYWORDS', 'Keywords', null, 'multistring', 'web,cms,content,management' ),
			new PropertyInfo( 'C_CUSTOBJPROPDEMO_STORY', 'Story', null, 'multiline', 'Once upon a time, there was a Drupal integration...' ),
			new PropertyInfo( 'C_CUSTOBJPROPDEMO_PROFITS', 'Profits', null, 'double', 1.5 ),
			new PropertyInfo( 'C_CUSTOBJPROPDEMO_HITCOUNT', 'Hit Count', null, 'int', 1.5 ),
			new PropertyInfo( 'C_CUSTOBJPROPDEMO_SINCE', 'Since', null, 'date', '' ),
			new PropertyInfo( 'C_CUSTOBJPROPDEMO_SAVE', 'Save result', null, 'bool', '' ),
			new PropertyInfo( 'C_CUSTOBJPROPDEMO_VISIBLESTR', 'Visible String', null, 'string', 'Visible Text' ),
			$hiddenString,
		);
		return $props;
	}
}
