<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Defines additional custom properties for the admin entities Brand, PubChannel and/or Issue.
 * Along with the builtin properties, the custom properties can be shown at admin maintenance
 * screens. This could vary, depending on the action (Create or Update) or the context.
 * For that, contextual information is passed by the core server into this interface to let
 * the connector check whether or not to show its custom properties. For example, the connector
 * might want to add properties to an Issue, but only when it belongs to a Publication Channel
 * of a specific Type or System. Property definitions are provided by the connector through 
 * the DialogWidget data class, which has two attribute data classes: PropertyInfo and PropertyUsage. 
 *
 * Make sure unique property names are used by adding your internal Server Plug-in name
 * as a second prefix after the C_ prefix (that stands for 'custom'). Make sure the name
 * does not exceed 30 chars. For example:
 *    $widgets['C_MYPLUGIN_MYPROP'] = new DialogWidget( 
 *       new PropertyInfo( 'C_MYPLUGIN_MYPROP', 'My Property', null, 'string', '' ),
 *       new PropertyUsage( 'C_MYPLUGIN_MYPROP', true, false, false ) );
 *
 * For a simple sample, please check out the CustomAdminPropsDemo Server Plug-in from Labs.
 * That sample shows how to add custom properties of all different field types. Those
 * properties appear on the Brand, PubChannel and Issue Maintenance admin pages.
 * It also shows how to add seperator fields to group properties which makes the UI more clear.
 *
 * Known limitations:
 * - Publication, Issue and PubChannel entities are supported only. To be added in future: Section and Status.
 * - DialogWidget->PropertyInfo->MinValue/MaxValue: Not supported. Likely for future.
 * - DialogWidget->PropertyInfo->Category/ParentValue/DependentProperties: Not supported. Has no meaning in this context.
 *
 * @since v9.0: Publication and PubChannel entities are supported.
 */

require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class AdminProperties_EnterpriseConnector extends DefaultConnector
{
	/**
	 * On Server Plug-in initialization, this function is called by core server for each 
	 * supported admin entity. It allows the server plug-in to define all additional 
	 * custom admin properties for a given admin entity. After that, specified properties 
	 * will be created at DB (by the server). In other terms, the DB model for the admin
	 * entity gets extended with custom properties provided by this function. The returned
	 * collection should include hidden properties (not shown at dialogs) but should exclude
	 * the special dialog widget separators (shown at dialogs).
	 *
	 * Note: When making changes to the collection of properties, run the Server Plug-ins 
	 * page to reflect them to DB model!
	 *
	 * @param string $entity Admin object type: Publication, PubChannel or Issue
	 * @return DialogWidget[] List of property definitions to create in database.
	 */
	abstract public function collectDialogWidgets( $entity );

	/**
	 * Before a dialog is build, the core server calls this function to collect all possible
	 * widgets for a given context. No matter if some properties need to be hidden while others
	 * needs to be shown, this is the moment to return all widgets for a given context, 
	 * admin entity and action. In fact, these are the properties to travel along with an admin
	 * entity. This could be less properties than returned through the collectDialogWidgets() 
	 * function, for example when it is needed to extend Issue- or Publication Channel entities
	 * -only- for a certain Publication Channel Type or Publish System. But, do NOT return
	 * widgets that aren't returned through collectDialogWidgets() because they can not be stored
	 * in the DB which blocks them from traveling along and would lead into errors. The returned
	 * collection should include hidden properties (not shown at dialogs) but should exclude
	 * the special dialog widget separators (shown at dialogs).
	 *
	 * This function was added since 9.0.0. For backward compatibility reasons, it returns NULL
	 * which tells the core to call the collectDialogWidgets() instead. Obviously it is better
	 * to return properties, depending on the given context, which leads to much more efficient
	 * storage since only a subset of properties is stored in the DB per entity instance.
	 *
	 * @since 9.0.0
	 * @param AdminProperties_Context $context Publication, Issue, etc for which the properties are maintained
	 * @param string $entity Admin object type: Publication, PubChannel or Issue
	 * @param string $action User operation: Create or Update.
	 * @return DialogWidget[]|null List of property definitions. Return NULL to use collectDialogWidgets() instead.
	 */
	public function collectDialogWidgetsForContext( AdminProperties_Context $context, $entity, $action ) 
	{ 
		$context = $context; $entity = $entity; $action = $action; // keep code analyzer happy
		return null; 
	}
	
	/**
	 * When custom admin properties are about to get displayed at the maintenance pages, 
	 * this function is called by the core server. It allows the Server Plug-in to initialize
	 * or adjust properties ($showWidgets) before shown to admin user. Properties can be added,
	 * removed or re-ordered. Be careful: don't fail when expected props are suddenly not present.
	 * The collection of properties could be less than returned by collectDialogWidgetsForContext()
	 * in case some properties needs to be round-tripped with the admin entity, but should not
	 * be shown in the dialog. Those hidden properties could be used for internal usage of 
	 * the plug-in, just to track data that needs to be hidden from admin users. Another reason
	 * to hide properties is that during the Create action there is maybe nothing to fill-in yet,
	 * while for the Update action there is.
	 *
	 * @param AdminProperties_Context $context Publication, Issue, etc for which the properties are maintained
	 * @param string $entity Admin object type: Publication, PubChannel or Issue
	 * @param string $action User operation: Create or Update.
	 * @param DialogWidget[] $allWidgets Complete list all properties. Key = property name, Value = DialogWidget object.
	 * @param DialogWidget[] $showWidgets Properties that should be shown to admin user in current order. Key = sequential index, Value = DialogWidget object.
	 */
	abstract public function buildDialogWidgets( AdminProperties_Context $context, $entity, $action, $allWidgets, &$showWidgets );

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}

class AdminProperties_Context
{
	private $PublicationObj = null;
	private $PubChannelObj  = null;
	private $IssueObj       = null;
	private $SectionObj     = null;
	private $EditionObj     = null;
	
	// Future:
	/*private StatusObj = null;
	private RoutingObj = null;
	private UserObj = null;
	private UserGroupObj = null;*/

	public function setPublicationContext( $pubObj, $channelObj, $issueObj, $sectionObj, $editionObj )
	{
		$this->PublicationObj = $pubObj;
		$this->PubChannelObj  = $channelObj;
		$this->IssueObj       = $issueObj;
		$this->SectionObj     = $sectionObj;
		$this->EditionObj     = $editionObj;
	}
	
	public function setPublication( $pubObj )    { $this->PublicationObj = $pubObj;     }
	public function setPubChannel( $channelObj ) { $this->PubChannelObj  = $channelObj; }
	public function setIssue( $issueObj )        { $this->IssueObj       = $issueObj;   }
	public function setSection( $sectionObj )    { $this->SectionObj     = $sectionObj; }
	public function setEdition( $editionObj )    { $this->EditionObj     = $editionObj; }

	// Future:
	/*public function setUserContext( $pubObj, $userObj, $userGroupObj ) {}
	public function setWorkflowContext( $pubObj, $statusObj, $routingObj ) {}*/
	
	public function getPublication() { return $this->PublicationObj; }
	public function getPubChannel()  { return $this->PubChannelObj; }
	public function getIssue()       { return $this->IssueObj; }
	public function getSection()     { return $this->SectionObj; }
	public function getEdition()     { return $this->EditionObj; }
	
	// Future:
	/*public function getStatus()      { return $this->StateObj; }
	public function getRouting()     { return $this->RoutingObj; }
	public function getUser()        { return $this->UserObj; }
	public function getUserGroup()   { return $this->UserGroupObj; }*/
}
