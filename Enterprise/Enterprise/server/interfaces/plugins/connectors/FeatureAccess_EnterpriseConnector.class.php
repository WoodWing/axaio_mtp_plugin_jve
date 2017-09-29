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
	 * The Name has a format [prefix:postfix] whereby the prefix should match the internal plugin name
	 * and the postfix should be unique within the plugin and may consists of [a-zA-Z0-9_] characters.
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
	 *	   	$feature->Flag = null; // null when not needed to check server side (but handled client side only)
	 *	   	$feature->Name = 'FooPlugin-FeatureA';
	 *	   	$feature->Display = 'Feature A';
	 *	   	$feature->Default = true; // checked when creating new profile
	 *	   	$features[] = $feature;
	 *
	 *	   	$feature = new ProfileFeatureAccess();
	 *	   	$feature->Id = null; // must be null to let core determine unique value
	 *	   	$feature->Flag = '?'; // set to '?' to allow checking server side as well => the core will determine unique flag
	 *	   	$feature->Name = 'FooPlugin-FeatureB';
	 *	   	$feature->Display = 'Feature B';
	 *	   	$feature->Default = false; // unchecked when creating new profile
	 *	   	$features[] = $feature;
	 *
	 *	   	return $features;
	 *	   }
	 *
	 * @return ProfileFeatureAccess[]
	 */
	abstract public function getFeatureAccessList();

	/**
	 * Called by the Profile Maintenance admin app to compose a structured tree of feature access rights.
	 *
	 * The plugin should add all its own features (provided by getFeatureAccessList) into this structure.
	 * The WW_Utils_KeyValueArray class may ease inserting the   categories and features provided by the plugin.
	 *
	 * Example implementation:
	 *
	 *	 	public function composeFeaturesAccessProfilesDialog( &$featuresPerCategory, $pluginFeatures )
	 *		{
	 *			require_once BASEDIR.'/server/utils/KeyValueArray.class.php';
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
	 *				WW_Utils_KeyValueArray::insertBeforeKey( $featuresPerCategory, 'FEATURE_WORKFLOW', $newCategory );
	 *			}
	 *			$featuresPerCategory['FOOPLUGIN:FEATURE_A'][ $newFeature->Id ] = $newFeature;
	 *
	 *			// Add our feature B after the 'Create Dossier' feature.
	 *			$createDossierKey = BizAccessFeatureProfiles::WORKFLOW_CREATEDOSSIER; // feature provided by core
	 *			$newFeature = $pluginFeatures[ 'FooPlugin-FeatureB' ];
	 *			if( isset( $featuresPerCategory[ 'FEATURE_WORKFLOW' ][ $createDossierKey ] ) ) {
	 *			   $newEntry = array( $newFeature->Id => $newFeature );
	 *				WW_Utils_KeyValueArray::insertAfterKey( $featuresPerCategory[ 'FEATURE_WORKFLOW' ], $createDossierKey, $newEntry );
	 *			} else { // if no longer available in future version of core server, add it to the end of the menu
	 *				$featuresPerCategory[ 'FEATURE_WORKFLOW' ][ $newFeature->Id ] = $newFeature;
	 *			}
	 *		}
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