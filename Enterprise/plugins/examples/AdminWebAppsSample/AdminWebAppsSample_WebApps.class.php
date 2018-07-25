<?php
/**
 * Sample admin web application. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 *
 * @since v8.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/WebApps_EnterpriseConnector.class.php';

class AdminWebAppsSample_WebApps extends WebApps_EnterpriseConnector
{
	final public function getWebApps()
	{
		$app1 = new WebAppDefinition();
		$app1->IconUrl = 'webapps/app1_32.gif';
		$app1->IconCaption = 'Sample App 1';
		$app1->WebAppId  = 'App1';
		$app1->ShowWhenUnplugged = true;

		$app2 = new WebAppDefinition();
		$app2->IconUrl = 'webapps/app2_32.gif';
		$app2->IconCaption = 'Sample App 2';
		$app2->WebAppId  = 'App2';
		$app2->ShowWhenUnplugged = false;
		
		return array( $app1, $app2 );
	}
}
