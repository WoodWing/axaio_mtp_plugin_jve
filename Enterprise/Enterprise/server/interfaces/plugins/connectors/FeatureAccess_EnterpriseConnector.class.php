<?php
/**
 * Server plug-in connector interface that allows plug-ins to provide their own feature access rights.
 *
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class FeatureAccess_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Called by the core to collect all feature access rights provided by the plugin.
	 *
	 * The Id and the Flag attributes are determined by core server and could differ per installation.
	 * For that reason, the plugin should never rely on those values, e.g. never hard-code it to lookup.
	 * Instead, the Name should be used, which should be an internal name that is system wide unique.
	 * The Name has a format [prefix-postfix] whereby the prefix should match the internal plugin name
	 * and the postfix should be unique within the plugin and may consists of [a-zA-Z0-9_] characters.
	 * The Name should not contain more than 75 characters.
	 *
	 * Example implementation:
	 *
	 *	   public function getFeatureAccessList()
	 *	   {
	 *	   	require_once BASEDIR.'/server/dataclasses/ProfileFeatureAccess.class.php';
	 *
	 *	   	$features = array();
	 *
	 *	   	$feature = new ProfileFeatureAccess();
	 *	   	$feature->Id = null; // must be null to let core determine unique value
	 *	   	$feature->Flag = null; // null when not needed to check server side (but handled client side only), see comment below
	 *	   	$feature->Name = 'FooPlugin-FeatureA';
	 *	   	$feature->Display = 'Feature A';
	 *	   	$feature->Default = true; // checked when creating new profile
	 *	   	$features[] = $feature;
	 *
	 *	   	$feature = new ProfileFeatureAccess();
	 *	   	$feature->Id = null; // must be null to let core determine unique value
	 *	   	$feature->Flag = '?'; // set to '?' to allow checking client side and server side, see comment below
	 *	   	$feature->Name = 'FooPlugin-FeatureB';
	 *	   	$feature->Display = 'Feature B';
	 *	   	$feature->Default = false; // unchecked when creating new profile
	 *	   	$features[] = $feature;
	 *
	 *	   	return $features;
	 *	   }
	 *
	 * About the Flag attribute:
	 *
	 *    Most features are client specific and therefore are checked client side only. For example applying a text style
	 *    is something a client may want to check and disable the styling buttons when the user has no access rights. When
	 *    the client application does logon to Enterprise Server it uses the LogOnResponse to find out whether or not the
	 *    user has right to use a specific feature in a certain context (brand, category and status). Nevertheless, some
	 *    features should be checked server side as well. For example workflow operations, such as an upload of a custom file
	 *    format. The server plug-in may provide its own web services or may hook into web services provided by the core server
	 *    (e.g. CreateObjects). For a feature that needs to be checked server side, the plug-in should set the feature Flag to '?'.
	 *    The core server will then generate a new flag for its internal administration. Note that this flag may differ per
	 *    Enterprise installation and therefore may not be hard-coded nor referenced by the plug-in. Instead, the plug-in
	 *    should resolve the flag through the BizAccess::checkRightsForMetaDataAndTargets() function first to obtain the flag
	 *    runtime. Then it can call any of the BizAccess functions such as BizAccess::checkRightsForMetaDataAndTargets().
	 *    (Note that the core server uses hard-coded flags and therefore it does not need to resolve the flags runtime.)
	 *    In short, to enable the plug-in to check access rights server side, it should set the Flag to '?'.
	 *
	 * Example of server side feature access right validation by a server plug-in:
	 *
	 *		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
	 *		$rights = BizAccess::resolveRightsForPluginFeatures( array( 'FooPlugin-FeatureB' ) );
	 *		$targets = isset($object->Targets) ? $object->Targets : array();
	 *		BizAccess::checkRightsForMetaDataAndTargets( BizSession::getShortUserName(),
	 *			$rights, BizAccess::THROW_ON_DENIED, $object->MetaData, $targets );
	 *
	 * @return ProfileFeatureAccess[]
	 */
	abstract public function getFeatureAccessList();

	/**
	 * Called by the Profile Maintenance admin app to compose a structured tree of feature access rights.
	 *
	 * The plugin should add all its own features (provided by getFeatureAccessList) into this structure.
	 * The WW_Utils_ArrayInjector class may ease inserting the categories and features provided by the plugin.
	 *
	 * Note that Enterprise Server may add, move or remove categories or features in future releases. However,
	 * your plug-in should be make so that its own features are added to the tree regardless whether expected
	 * categories or features can be found. The example below takes care of that by adding its categories and
	 * features at the end of the structure in case the preferred position can not be found/determined.
	 *
	 * Example implementation:
	 *
	 *	 	public function composeFeaturesAccessProfilesDialog( &$featuresPerCategory, $pluginFeatures )
	 *		{
	 *			require_once BASEDIR.'/server/utils/ArrayInjector.class.php';
	 *			require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
	 *
	 *			// For fast lookup, replace the feature ids with names in the $pluginFeatures index.
	 *			$featureNames = array_map( function( $feature ) { return $feature->Name; }, $pluginFeatures );
	 *			$pluginFeatures = array_combine( $featureNames, $pluginFeatures );
	 *
	 *			// Add our feature A to our own category. Insert the category just before the 'Workflow' category.
	 *			$newFeature = $pluginFeatures['FooPlugin-FeatureA'];
	 *			$newCategory = array( 'FOOPLUGIN:FEATURE_A' => array() ); // resource key provided by this plugin
	 *			if( isset( $featuresPerCategory[ 'FEATURE_WORKFLOW' ] ) ) { // existing menu provided by core
	 *				WW_Utils_ArrayInjector::insertBeforeKey( $featuresPerCategory, 'FEATURE_WORKFLOW', $newCategory );
	 *			}
	 *			$featuresPerCategory['FOOPLUGIN:FEATURE_A'][ $newFeature->Id ] = $newFeature;
	 *
	 *			// Add our feature B after the 'Create Dossier' feature. (This feature is part of the 'Workflow' category.)
	 *			$createDossierKey = BizAccessFeatureProfiles::WORKFLOW_CREATEDOSSIER; // feature provided by core
	 *			$newFeature = $pluginFeatures[ 'FooPlugin-FeatureB' ];
	 *			if( isset( $featuresPerCategory[ 'FEATURE_WORKFLOW' ][ $createDossierKey ] ) ) {
	 *			   $newEntry = array( $newFeature->Id => $newFeature );
	 *				WW_Utils_ArrayInjector::insertAfterKey( $featuresPerCategory[ 'FEATURE_WORKFLOW' ], $createDossierKey, $newEntry );
	 *			} else { // if no longer available in future version of core server, add it to the end of the menu
	 *				$featuresPerCategory[ 'FEATURE_WORKFLOW' ][ $newFeature->Id ] = $newFeature;
	 *			}
	 *		}
	 *
	 * Tip: You can find our which categories and features are provided by the core server as follows:
	 *    1. Enable your server plug-in and disable other plug-ins.
	 *    2. Add this line to the composeFeaturesAccessProfilesDialog() function:
	 *         LogHandler::logPhpObject( $featuresPerCategory, 'pretty_print', 'hello' );
	 *    3. Enable DEBUG logging for Enterprise Server.
	 *    4. Run the Profile Maintenance page.
	 *    5. Lookup the file in the server logging that has a 'hello' postfix and checkout the structured information.
	 *
	 * @param ProfileFeatureAccess[][] $featuresPerCategory Features grouped by category. First index is the resource key of the category. Second index is the feature id.
	 * @param ProfileFeatureAccess[] $pluginFeatures All features provided by all server plug-ins that can be used to lookup Id and Flag attributes.
	 */
	abstract public function composeFeaturesAccessProfilesDialog( &$featuresPerCategory, $pluginFeatures );

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio() { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()          { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}