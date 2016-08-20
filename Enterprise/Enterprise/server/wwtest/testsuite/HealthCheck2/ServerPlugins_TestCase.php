<?php
/**
 * ServerPlugins TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package SCEnterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_ServerPlugins_TestCase extends TestCase
{
	public function getDisplayName() { return 'Server Plug-ins'; }
	public function getTestGoals()   { return 'Checks if the Server Plug-ins at server- and config folders can be read and are installed (registered at DB). '; }
	public function getTestMethods() { return 'Reads the plug-in info files and its connectors, and tries to initiate them. Reads the plug-ins and its connectors from DB and compares them against the ones read from file system.'; }
    public function getPrio()        { return 20; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		
		$fsPluginObjs  = array(); // EnterprisePlugin
		$fsPluginInfos = array(); // PluginInfoData
		$fsConnObjs    = array(); // EnterpriseConnector
		$fsConnInfos   = array(); // ConnectorInfoData
		$fsPluginErrs  = array(); // plugins (messages) that are in error
	
		// Scan plugins at config- and server- folders 
		BizServerPlugin::readPluginsFromFolders( $fsPluginObjs, $fsPluginInfos, $fsConnObjs, $fsConnInfos, $fsPluginErrs );

		// Report plugins that are in error
		foreach( $fsPluginErrs as $pluginKey => $fsPluginErr ) {
			if( isset( $fsPluginInfos[$pluginKey] ) ) { // plugin in error status?
				$this->setResult( 'ERROR', 'Server Plug-in "'.$fsPluginInfos[$pluginKey]->DisplayName
									.'" is in error status: '. $fsPluginErr );
			} else {
				$this->setResult( 'ERROR', 'Server Plug-in is in error status: '. $fsPluginErr );
			}
		}

		// Quit on any error status
		if( count($fsPluginErrs) > 0 ) {
			return; // quit here since comparing plugins is useless when plugins are in error status (just to avoid big mess at reports)
		}

		$dbPluginObjs  = array(); // EnterprisePlugin
		$dbPluginInfos = array(); // PluginInfoData
		$dbConnObjs    = array(); // EnterpriseConnector
		$dbConnInfos   = array(); // ConnectorInfoData
		$compDiffs     = array(); // differences between plugins/connectors read from file system and imported from DB
		
		// Scan DB for plugins (that were read/imported from folders before)
		BizServerPlugin::readPluginsFromDB( $dbPluginObjs, $dbPluginInfos, $dbConnObjs, $dbConnInfos );
		
		// Compare DB plugins with ones read from folders
		BizServerPlugin::comparePlugins( $fsPluginObjs, $fsPluginInfos, $dbPluginObjs, $dbPluginInfos, $compDiffs );

		// Report any differences
		if( count($compDiffs) > 0 ) {
			$this->setResult( 'INFO', 'Please run the <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a> admin page that automatically registers the Server Plug-ins (read from file system) at the DB. It will resolve errors reported below:' );
			foreach( $compDiffs as $compDiff ) {
				$this->setResult( 'ERROR', $compDiff );
			}
			return; // quit here since comparing connectors is useless when plugins differ already (just to avoid big mess at reports)
		}

		// Compare DB connectors with ones read from folders
		BizServerPlugin::compareConnectors( $fsConnObjs, $fsConnInfos, $dbConnObjs, $dbConnInfos, $compDiffs );

		// Report any differences
		if( count($compDiffs) > 0 ) {
			$this->setResult( 'INFO', 'Please run the <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a> admin page that automatically registers the Server Connectors (read from file system) at the DB. It will resolve errors reported below:' );
			foreach( $compDiffs as $compDiff ) {
				$this->setResult( 'ERROR', $compDiff );
			}
		}
		
		// Error when obsoleted plugins are still installed.
		// TODO: It would be better if we have a mechanism to let plug-in detect dependencies/conflicts
		//       with other plugins. This needs to be done -after- all plug-ins are installed.
		require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
		$plugins = DBServerPlugin::getPlugins();
		$obsoletedPlugins = array(
			// obsoleted plug-in         => superceding plug-in
			'SeparateSipsPreviewServer'  => 'SipsPreview', 
			'AsyncImagePreview'          => 'SipsPreview', 
			'ASynchronizedImagePreview'  => 'SipsPreview',
			'Sips'                       => 'SipsPreview',
			'SGLPlus'                    => 'IdsAutomation');
		foreach( $obsoletedPlugins as $obsoletedPlugin => $supercedingPlugin ) {
			if( isset( $plugins[$obsoletedPlugin] ) ) {
				if( BizServerPlugin::isPluginActivated( $obsoletedPlugin ) ) {
					$this->setResult( 'ERROR',
							'An obsoleted server plug-in "'.$obsoletedPlugin.'" is installed at '.
							'Enterprise Server. Please uninstall that server plug-in before you '.
							'can use the superseding "'.$supercedingPlugin.'" server plug-in.',
							'Please run <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a> to fix the problem.' );
				}
			}
		}

		// Since v10.1.0: Error for each mandatory plug-in that has been de-activated (or not installed).
		$mandadoryPlugins = array(
			'ExifTool' => 'This plug-in is especially needed for image cropping and image publishing features '.
				'that require reliable dimension and resolution information from uploaded images.'
		);
		foreach( $mandadoryPlugins as $mandadoryPlugin => $reason ) {
			if( !BizServerPlugin::isPluginActivated( $mandadoryPlugin ) ) {
				$pluginName = isset($plugins[$mandadoryPlugin]) ? $plugins[$mandadoryPlugin]->DisplayName : $mandadoryPlugin;
				$this->setResult( 'ERROR',
					'The server plug-in "'.$pluginName.'" is mandatory but is currently not activated. '.$reason.
					' Please activate that server plug-in before you take Enterprise Server in production. ',
					'Please run <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a> to fix the problem.' );
			}
		}

		// Since v9.2.0:
		// Check that plugins that implement the WflSetObjectProperties connector also implement the
		// WflMultiSetObjectProperties connector and vice versa, report if only one of the two is implemented
		// instead of both or none.
		$pluginInfos = null;
		$connectorInfos = null;
		BizServerPlugin::readPluginInfosFromDB( $pluginInfos, $connectorInfos );

		if ( $connectorInfos) foreach ( $connectorInfos as $key => $connectors) {
			$hasSingleSetObjectPropertiesConnector = false;
			$hasMultiSetObjectPropertiesConnector = false;
			$hasSendToConnector = false;
			$hasSendToNextConnector = false;
			if ($connectors) foreach ( $connectors as $data ) {
				if ($data->Interface == 'WflSetObjectProperties') {
					$hasSingleSetObjectPropertiesConnector = true;
				}
				if ($data->Interface == 'WflMultiSetObjectProperties') {
					$hasMultiSetObjectPropertiesConnector = true;
				}
				if ($data->Interface == 'WflSendTo') {
					$hasSendToConnector = true;
				}
				if ($data->Interface == 'WflSendToNext') {
					$hasSendToNextConnector = true;
				}
			}

			// If a plugin implements the MultiSetObjectProperties or the SetObjectProperties connector, its counter-
			// part needs to be implemented as well, throw an error if this is not the case.
			if ( ( $hasMultiSetObjectPropertiesConnector && !$hasSingleSetObjectPropertiesConnector )
				|| ( !$hasMultiSetObjectPropertiesConnector && $hasSingleSetObjectPropertiesConnector ) ) {
				// Get the plugin name based on the key.
				$displayName = $pluginInfos[$key]->DisplayName;
				$missingConnector = ( $hasMultiSetObjectPropertiesConnector )
					? 'WflSetObjectProperties' :
					'WflMultiSetObjectProperties';
				$matchingConnector = ( $hasMultiSetObjectPropertiesConnector )
					? 'WflMultiSetObjectProperties'
					: 'WflSetObjectProperties';

				$this->setResult( 'ERROR',
					'The Plugin "'.$displayName.'" does not have the "' . $missingConnector . '" connector implemented '
						. 'which is required when the "' . $matchingConnector . '" connector is implemented.',
					'Please implement the "' . $missingConnector . '" connector for the "' . $displayName . '" plugin.'
				);
			}
			// Since V9.2:
			// If a plugin implements the SendToNext or the SendTo connector, its counter part needs to be implemented
			// as well, throw an error if this is not the case. This is done to ensure backwards compatibility with old-
			// er clients.
			if ( ( $hasSendToNextConnector && !$hasSendToConnector )
				|| ( !$hasSendToNextConnector && $hasSendToConnector ) ) {
				// Get the plugin name based on the key.
				$displayName = $pluginInfos[$key]->DisplayName;
				$missingConnector = ( $hasSendToNextConnector )
					? 'WflSendTo' :
					'WflSendToNext';
				$matchingConnector = ( $hasSendToNextConnector )
					? 'WflSendToNext'
					: 'WflSendTo';

				$this->setResult( 'ERROR',
					'The Plugin "'.$displayName.'" does not have the "' . $missingConnector . '" connector implemented '
					. 'which is required when the "' . $matchingConnector . '" connector is implemented.',
					'Please implement the "' . $missingConnector . '" connector for the "' . $displayName . '" plugin.'
				);
			}
		}

		// Since v9.2.0
		// To support multi set properties feature, ContentSource connector, that has implemented the single object
		// 'setShadowObjectProperties' function should also implement multiSetShadowObjectProperties' that supports
		// for multiple objects. However, this is not mandatory as Enterprise will fallback to use the single object
		// functions (which of course will have impact on performance).
		// Therefore, only show Warning when those functions are not implemented.
		require_once BASEDIR . '/server/utils/PHPClass.class.php';
		$connectors = BizServerPlugin::searchConnectors( 'ContentSource', null, true, true );

		if( $connectors ) foreach( $connectors as $contentSourceName => $connector ) {

			if( WW_Utils_PHPClass::methodExistsInDeclaringClass( get_class( $connector ), 'setShadowObjectProperties' )) {
				$multiSetFunctionExists = WW_Utils_PHPClass::methodExistsInDeclaringClass(
															get_class( $connector ), 'multiSetShadowObjectProperties' );

				if( !$multiSetFunctionExists ) {
					$pluginDisplayName = $this->getPluginDisplayName( $pluginInfos, $connectorInfos, $contentSourceName );
					$warnMessage = 'The "'.$pluginDisplayName.'" Server plug-in has no efficient support for the '.
						'"Multi-set Properties" feature. This could lead to performance issues. ' .
						'Please contact your integrator. <br/><br/>' .
						'Notes for the integrator: The plug-in contains a Content Source connector which has the '.
						'"setShadowObjectProperties" function declared. <br/>'.
						'It is advisable to add the "multiSetShadowObjectProperties" function '.
						'to that connector in order to support multi-set object properties in a more efficient way. If '.
						'omitted, there will be a performance hit when the user adjusts properties for a large number '.
						'of objects. <br/>'.
						'For more information, see the ContentSource_EnterpriseConnector.class.php file.';
					$this->setResult( 'WARN', $warnMessage );
				}
			}
		}

		// Since v9.2.0
		// To support multi set properties feature, the NameValidation connector, that has implemented 
		// the single object 'validateMetaDataAndTargets' function must also implement
		// validateMetaDataInMultiMode that supports multiple objects. Note this is mandatory.
		// An error is shown when the function is not implemented.
		$connectors = BizServerPlugin::searchConnectors( 'NameValidation', null, true, true );

		if( $connectors ) foreach( $connectors as $nameValidationName => $connector ) {
			$validateMetaDataInMultiModeFunctionExists = WW_Utils_PHPClass::methodExistsInDeclaringClass(
				get_class( $connector ), 'validateMetaDataInMultiMode' );

			if( !$validateMetaDataInMultiModeFunctionExists ) {
				$pluginDisplayName = $this->getPluginDisplayName( $pluginInfos, $connectorInfos, $nameValidationName );
				$errMessage = 'The "'.$pluginDisplayName.'" Server plug-in does not implement the function '.
					'validateMetaDataInMultiMode, required for the "Multi-set Properties" feature. ' .
					'Please contact your integrator. <br/><br/>' .
					'For more information, see the NameValidation_EnterpriseConnector.class.php file.';
				$this->setResult( 'ERROR', $errMessage );
			}
		}
	}

	/**
	 * Returns display name of the plugin that has content source $contentSourceName.
	 *
	 * @param PluginInfoData[] $pluginInfos List of plugin info data.
	 * @param ConnectorInfoData[] $connectorInfos List of connector info.
	 * @param string $contentSourceName Content Source name.
	 * @return null|string The display name of the plugin that has content source $contentSourceName.
	 */
	private function getPluginDisplayName( $pluginInfos, $connectorInfos, $contentSourceName )
	{
		$displayName = null;
		foreach( $connectorInfos as $pluginName => $connectorInfo ) {
			if( $connectorInfo ) foreach( array_keys( $connectorInfo ) as $connName ) {
				if( $connName == $contentSourceName ) {
					$displayName = $pluginInfos[$pluginName]->DisplayName;
					break; // Found
				}
			}
		}
		return $displayName;
	}
}