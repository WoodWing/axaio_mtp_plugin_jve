<?php
/**
 * @since 		v9.0.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * This interface can be implemented by a server plug-in connector to automatically configure custom 
 * object properties. This way the object model can be extended without need to manually configure 
 * them at the Metadata admin page. Custom properties can be used internally by the server plug-in only 
 * but can also be shown at workflow dialogs, depending on the requirements. When they need to show up 
 * at workflow dialogs, they need to be configured manually, which is done in the Dialog Setup admin page. 
 * Whether or not a custom property must be shown at the admin pages is determined by the AdminUI attribute 
 * of the PropertyInfo element. When this flag is 'true' the custom property is shown, but can not be 
 * changed (read-only) in the Metadata admin page. When 'false' the custom property is totally hidden 
 * from admin users.
 *
 * During the Enterprise Server installation the Server Plug-ins page is ran by the admin user whereby 
 * this plug-in this interface is called. Based on the returned custom object definitions the core 
 * server extends the DB model. In the database the created custom properties can be found in smart_properties, 
 * smart_objects, smart_deletedobjects and smart_objectversions tables. But there is normally no need
 * to access the DB directly since web services can be intercepted by another connector of the same 
 * plug-in. For that you need to know that custom properties travel along with objects during CreateObjects, 
 * SaveObjects and GetObjects web services and can be found in Objects[]->MetaData->ExtraMetaData. 
 * Also they travel through the GetDialog and GetDialog2 services at MetaDataValue elements.
 *
 * The custom properties are prefixed with a "C_". Note that the name length is limited to 30 (which 
 * is the max column name for Oracle) so there are 28 characters free of choice after the prefix. 
 * It is a good habit to prefix the plugin name to avoid conflicts with other plugins. 
 * For example: C_MYPLUGIN_MYPROP. Note that the C_HIDDEN_ prefix is an obsoleted way of hiding properties 
 * and should no longer be used. Instead, set the AdminUI attribute of the PropertyInfo element.
 *
 * When using the custom properties for Publish Form (Templates) the list of custom properties can become
 * extensive and this will slow down the system. Therefore we introduced two new internal properties:
 * PublishSystem and TemplateId. These internal properties are only used for Publish Form (Templates).
 * When these properties are set and even though the AdminUI property is true or not set these won't show up in
 * the dialog setup page.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class CustomObjectMetaData_EnterpriseConnector extends DefaultConnector
{
	/**
	 * See introduction at module header above.
	 *
	 * The $coreInstallation is set to:
	 * L> True: When the core calls it in the context of installation procedure or from server plugin page.
	 * L> False: In case you provide your own admin page that should take over this installation,
	 *           call BizProperty::validateAndInstallCustomProperties() and pass in $coreInstallation = False.
	 *           Being called in your connector, check for this flag($installAutomatically) and only return
	 *           custom properties when set to false.
	 *
	 *
	 * The connector should return the following structure:
	 *    array[brand id][object type] => array of PropertyInfo
	 * Use zero (0) for brand id or object type to indicate 'all'.
	 *
	 * For example:
	 *    $retVal = array();
	 *    // custom properties to appear for all brand, all object types:
	 *    $retVal[0][0] = array( PropertyInfo(...), PropertyInfo(...) );
	 *    // custom properties to appear for articles owned by brand id 123:
	 *    $retVal[123]['Article'] = array( PropertyInfo(...), PropertyInfo(...) );
	 *    return $retVal;
	 *
	 * Best practices for Publish Form (Templates):
	 *    $propInfo = new PropertyInfo();
	 * 	  ...
	 * 	  $propInfo->AdminUI = false;
	 * 	  $propInfo->PublishSystem = '<PublishSystemName>'; // String value - The name of the Publishing Connector Plug-in
	 * 	  $propInfo->TemplateId = <TemplateId>; // Integer value - The object id of the Publish Form Template
	 *
	 *    $retVal = array();
	 *    $retVal[0]['PublishForm'] = array( $propInfo );
	 *    return $retVal;
	 *
	 * This way the custom properties are only available for Publish Forms. This will save time because they won't be retrieved
	 * for other object types. The AdminUI property makes sure this is only available to users when opening a Publish Form. The PublishSystem
	 * and TemplateId properties are optional but very useful when having a lot of custom properties.
	 *
	 * @param bool $coreInstallation See function header above.
	 * @return array See function header above how to structure.
	 */
	abstract public function collectCustomProperties( $coreInstallation );

	
	// ===================================================================================

	// Generic methods that can be overruled by a connector:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that cannot be overruled by a connector:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
