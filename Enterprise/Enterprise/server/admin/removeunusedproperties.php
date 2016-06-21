<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

// Set timeout to one hour.
set_time_limit(3600);

// Validate user, access rights and ticket.
require_once( BASEDIR . '/server/dbclasses/DBTicket.class.php' );
$ticket = checkSecure('publadmin');
DBTicket::checkTicket( $ticket );

// Retrieve request parameters.
$serverPlugin = isset($_REQUEST['serverplugin']) ? trim($_REQUEST['serverplugin']) : '';
$show = isset($_REQUEST['show']) ? (bool)$_REQUEST['show'] : false;
$cleanup = isset($_REQUEST['clean']) ? (bool)$_REQUEST['clean'] : false;

// Load HTML template
$tpl = HtmlDocument::loadTemplate( 'removeunusedproperties.htm' );

// Show the total unused table rows and columns as HTML table and inject at HTML template to show end user.
$txt = '';
if( $serverPlugin ) {
	$cleanupApp = new ServerPluginCustomPropertyCleanupApp();

	// Get all the unused publish form properties, object columns and action properties
	$unusedPublishFormProperties = array();
	// For now, the cleanup is suit for Drupal server plugin implementation.
	if( substr($serverPlugin,0,6) == 'Drupal' ) {
		$unusedPublishFormProperties = $cleanupApp->getUnusedPublishFormProperties( $serverPlugin );
		$unusedObjectColumns = $cleanupApp->getUnusedObjectColumns( $unusedPublishFormProperties );
		$unusedActionProperties = $cleanupApp->getUnusedActionProperties( $serverPlugin );
	}

	$tpl = str_replace ("<!--SERVERPLUGINNAME-->",$serverPlugin, $tpl);
	$serverPluginDesc = BizResources::localize('PLN_INVOLVED').' '. $serverPlugin;
	$tpl = str_replace ("<!--SERVERPLUGIN-->",$serverPluginDesc, $tpl);

	if( count($unusedPublishFormProperties) > 0 ) {
		if( $cleanup === false ) {
			$warnMsg = BizResources::localize('WARN_CLEAN_UP_UNUSED_PROPERTIES');
			$warnMsg = str_replace( "\\n", '<br/>', $warnMsg );
			$warnMsg = BizResources::localize('WARNING') . ': '. $warnMsg;
			$tpl = str_replace ("<!--VAR:WARNINGMESSAGE-->", $warnMsg, $tpl);
			$txt = $cleanupApp->showData( $serverPlugin, $unusedPublishFormProperties, $unusedObjectColumns, $unusedActionProperties, $show );
		} else {
			$txt = $cleanupApp->cleanup( $unusedPublishFormProperties, $unusedObjectColumns, $unusedActionProperties );
		}
	} else {
		$txt .= '<table class="text"><tr><td>';
		$txt .= BizResources::localize( 'ACT_STATUS_NO_UNUSED_PROP', true,	array( $serverPlugin ) );
		$txt .= '</td></tr>';
		$txt .= '<tr><td><br/></td></tr>';
		$txt .= '<tr><td><a href="javascript:showData();">';
		$txt .= '<img src="../../config/images/srch_16.gif" border="0" title="'.BizResources::localize('ACT_SEARCH').'"/>';
		$txt .= BizResources::localize('ACT_SEARCH').'</a></td></tr>';
		$txt .= '</table>';
		$txt .= inputvar('serverplugin', $serverPlugin, 'hidden');
		$txt .= inputvar('show', '', 'hidden');
	}
	$tpl = str_replace ("<!--CONTENT-->", $txt, $tpl);
} else {
	$message = BizResources::localize("PLN_SERVERPLUGIN").' '.BizResources::localize("ACT_FIELD_IS_REQUIRED");
	$tpl = str_replace ("<!--ERROR-->", $message, $tpl);
}

// Build the entire HTML page.
print HtmlDocument::buildDocument( $tpl, true );

class ServerPluginCustomPropertyCleanupApp
{
	/**
	 * Retrieve unused publish form properties from a publish form that has been removed.
	 *
	 * @param string $publishSystem Publish system name.
	 * @return array $unusedProperties Array of unused properties.
	 */
	public function getUnusedPublishFormProperties( $publishSystem )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$unusedProperties = BizProperty::getUnusedPublishFormProperties( $publishSystem );
		return $unusedProperties;
	}

	/**
	 * Get a list of unused object columns by filtering unused properties with the excluded object fields.
	 *
	 * @param array $unusedPublishFormProperties Array of unused publish form properties.
	 * @return array $unusedObjectColumns Array of unused object columns.
	 */
	public function getUnusedObjectColumns( $unusedPublishFormProperties )
	{
		$unusedObjectColumns = $unusedPublishFormProperties;
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		$excludeObjectType = BizCustomField::getExcludedObjectFields();
		if( $unusedObjectColumns ) foreach( $unusedObjectColumns as $key => $unusedObjectColumn ) {
			if( in_array($unusedObjectColumn['type'], $excludeObjectType) ) {
				unset( $unusedObjectColumns[$key] );
			}
		}
		return $unusedObjectColumns;
	}

	/**
	 * Delete unused object columns.
	 *
	 * @param array $unusedObjectColumns Array of object columns.
	 * @throws BizException
	 */
	private function deleteObjectColumns( $unusedObjectColumns )
	{
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		if( count($unusedObjectColumns) == 1 ) {
			BizCustomField::deleteFieldAtModel( 'objects', $unusedObjectColumns[0]['name']);
		} elseif( count($unusedObjectColumns) > 1 ) {
			$columnNames = array();
			foreach( $unusedObjectColumns as $unusedObjectColumn ) {
				$columnNames[] = $unusedObjectColumn['name'];
			}
			$deleted = BizCustomField::deleteFieldsAtModel( 'objects', $columnNames );
			if( $deleted ) {
				BizCustomField::deleteFieldsAtModel( 'deletedobjects', $columnNames );
			}
		}
	}

	/**
	 * Get a list of unused action properties from removed publish form template.
	 *
	 * @param string $publishSystem Publish system name.
	 * @return array $unusedActionProperties Array of unused action properties.
	 */
	public function getUnusedActionProperties( $publishSystem )
	{
		require_once BASEDIR.'/server/dbclasses/DBActionproperty.class.php';
		$unusedActionProperties = DBActionproperty::getUnusedActionProperties( $publishSystem );
		return $unusedActionProperties;
	}

	/**
	 * Delete unused properties.
	 *
	 * @param array $properties Array of property
	 */
	private function deleteProperties( $properties )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$propertyIds = array();
		if( $properties ) foreach( $properties as $property ) {
			$propertyIds[] = $property['id'];
		}
		if( $propertyIds ) {
			BizProperty::deleteProperties( $propertyIds );
		}
	}

	/**
	 * Delete unused action properties.
	 *
	 * @param array $unusedActionProperties Array of unused action properties.
	 */
	private function deleteActionProperties( $unusedActionProperties )
	{
		require_once BASEDIR.'/server/dbclasses/DBActionproperty.class.php';
		$actionPropertyIds = array();
		if( $unusedActionProperties ) foreach( $unusedActionProperties as $unusedActionProperty ) {
			$actionPropertyIds[] = $unusedActionProperty['id'];
		}
		if( $actionPropertyIds ) {
			DBActionproperty::deleteActionProperties( $actionPropertyIds );
		}
	}

	/**
	 * Perform cleanup on unused properties, unused object columns and unused action properties.
	 *
	 * @param array $unusedPublishFormProperties Array of unused publish form properties.
	 * @param array $unusedObjectColumns Array of unused object columns.
	 * @param array $unusedActionProperties Array of unused action properties.
	 * @return string $message
	 */
	public function cleanup( $unusedPublishFormProperties, $unusedObjectColumns, $unusedActionProperties )
	{
		$message = '';
		try {
			$this->deleteObjectColumns( $unusedObjectColumns );
			$this->deleteProperties( $unusedPublishFormProperties );
			$this->deleteActionProperties( $unusedActionProperties );

			$message = BizResources::localize('ACT_MESS_SUCCES_UNUSED_PROP');
		}  catch( BizException $e ) {
			$e = $e;
			$message = '<font color="red">'.$e->getMessage().'<br/>'.$e->getDetail().'</font>';
		}
		return $message;
	}


	/**
	 * Summary of total unused properties, unused table columns and unused action properties.
	 * It allows end user to show all the detail properties and columns in a table.
	 *
	 * @param string $serverPlugin Server Plugin name.
	 * @param array $unusedPublishFormProperties Array of unused Publish Form properties in smart_properties table.
	 * @param array $unusedObjectColumns Array of unused table columns in smart_objects table.
	 * @param array $unusedActionProperties Array of unused action properties in smart_actionproperties table.
	 * @param boolean $show True to show all table rows and columns. False to show summary of counts, table and details only.
	 *
	 * @return string HTML stream representing the data records table (or summary) to show.
	 */
	function showData( $serverPlugin, $unusedPublishFormProperties, $unusedObjectColumns, $unusedActionProperties, $show )
	{
		$txt = '';
		$totalUnusedProperties = count( $unusedPublishFormProperties );
		$totalUnusedColumns = count( $unusedObjectColumns );
		$totalUnusedActionProperties = count( $unusedActionProperties );

		if( $show === false ) {
			if( $totalUnusedProperties > 0 ) {
				$txt = '<table class="text"><tr>';
				$txt .='<th>Table</th><th>Details</th><th>Total</th></tr>';
				$txt .= '<tr bgcolor="#DDDDDD"><td>properties</td><td>Unused properties</td><td>'. $totalUnusedProperties . '</td></tr>';
				if( $totalUnusedColumns > 0 ) {
					$txt .= '<tr bgcolor="#DDDDDD"><td>objects</td><td>Unused table columns</td><td>'. $totalUnusedColumns . '</td></tr>';
				}
				if( $totalUnusedActionProperties > 0 ) {
					$txt .= '<tr bgcolor="#DDDDDD"><td>actionproperties</td><td>Unused action properties</td><td>'. $totalUnusedActionProperties . '</td></tr></table>';
				}
				$txt .= '<table border="0" class="text"><tr><td><br/>';
				$txt .= '<a href="javascript:showData();">';
				$txt .= '<img src="../../config/images/prefs_16.gif" border="0" title="'.BizResources::localize('ACT_SHOW').'"/>'.BizResources::localize('ACT_SHOW').'</a>&nbsp;';
				$txt .=	'<a href="javascript:cleanup();"><img src="../../config/images/remov_16.gif" border="0" title="'.BizResources::localize('ACT_CLEAN_UP').'"/>';
				$txt .= BizResources::localize('ACT_CLEAN_UP') . '</a>';
				$txt .=	'</td></tr></table>';
				$txt .=	inputvar( 'serverplugin', $serverPlugin, 'hidden' );
				$txt .= inputvar( 'show', '', 'hidden' );
				$txt .= inputvar( 'clean', '', 'hidden' );
			}
		} else {
			if( $totalUnusedProperties > 0 ) {
				$count= 1;
				$txt .= '<table><tr><td><b>smart_properties</b></td></tr></table>';
				$txt .= '<table class="text">';
				$txt .= '<tr><th>No</th><th>Custom Property</th></tr>';
				foreach( $unusedPublishFormProperties as $unusedPublishFormProperty ) {
					$txt .= '<tr bgcolor="#DDDDDD"><td>'.$count.'</td><td>'.$unusedPublishFormProperty['name'].'</td></tr>';
					$count++;
				}
				$txt .= '</table></td>';
				if( $totalUnusedColumns > 0 ) {
					$count= 1;
					$txt .= '<td>';
					$txt .= '<table><tr><td><b>smart_objects</b></td></tr></table>';
					$txt .= '<table class="text"><tr>';
					$txt .= '<th>No</th><th>Column</th></tr>';
					foreach( $unusedObjectColumns as $unusedObjectColumn ) {
						$txt .= '<tr bgcolor="#DDDDDD"><td>'.$count.'</td><td>'.$unusedObjectColumn['name'].'</td></tr>';
						$count++;
					}
					$txt .= '</table></td>';
				}
				if( $totalUnusedActionProperties > 0 ) {
					$count= 1;
					$txt .= '<td>';
					$txt .= '<table><tr><td><b>smart_actionproperties</b></td></tr></table>';
					$txt .= '<table class="text"><tr>';
					$txt .= '<th>No</th><th>property</th></tr>';
					foreach ($unusedActionProperties as $unusedActionProperty) {
						$txt .= '<tr bgcolor="#DDDDDD"><td>'.$count.'</td><td>'.formvar($unusedActionProperty['property']).'</td></tr>';
						$count++;
					}
					$txt .= '</table></td>';
				}
				$txt .= '</tr></table>';
				if( $totalUnusedProperties > 0 || $totalUnusedColumns > 0 || $totalUnusedActionProperties > 0 ) {
					$txt .= '<table class="text"><tr><td><a href="javascript:showData();">';
					$txt .= '<img src="../../config/images/ref_16.gif" border="0" title="'.BizResources::localize('ACT_REFRESH').'"/>';
					$txt .= BizResources::localize('ACT_REFRESH').'</a>&nbsp;';
					$txt .=	'<a href="javascript:cleanup();"><img src="../../config/images/remov_16.gif" border="0" title="'.BizResources::localize('ACT_CLEAN_UP').'"/>';
					$txt .= BizResources::localize('ACT_CLEAN_UP') . '</a></td></tr></table>';
				}
				$txt .= inputvar('serverplugin', $serverPlugin, 'hidden');
				$txt .= inputvar('show', '', 'hidden');
				$txt .= inputvar( 'clean', '', 'hidden' );
			}
		}
		return $txt;
	}
}