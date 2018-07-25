<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Automatically install custom object properties into the database 
 * (instead of manual installation in the Metadata admin page).
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/CustomObjectMetaData_EnterpriseConnector.class.php';

class AdobeDps2_CustomObjectMetaData extends CustomObjectMetaData_EnterpriseConnector
{
	final public function collectCustomProperties( $coreInstallation )
	{
		$props = array();
		$props[0]['Layout'] = array(
			new PropertyInfo( 'C_DPS2_UPLOADSTATUS', 
				BizResources::localize('AdobeDps2.AP_ARTICLE_UPLOAD_STATUS'), 
				null, 'string', '' ),
		);
		
		// The C_WIDGET_MANIFEST property might be already created manually in MetaData Setup 
		// because the former AdobeDPS plugin does require so. Therefor, here we automatically 
		// add the property only when missing.
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		if( !DBProperty::getObjectPropertyByName( 'C_WIDGET_MANIFEST' ) ) {
			$props[0]['Other'] = array(
				new PropertyInfo( 'C_WIDGET_MANIFEST', 'Widget Manifest', null, 'multiline', '' ),
			);
		}
		return $props;
	}
}
