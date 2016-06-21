<?php
/**
 * Sample admin web application. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 *
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v8.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/utils/htmlclasses/EnterpriseWebApp.class.php';

class AdminWebAppsSample_App2_EnterpriseWebApp extends EnterpriseWebApp 
{
	public function getTitle()      { return 'Sample Application 2'; }
	public function isEmbedded()    { return true; }
	public function getAccessType() { return 'publadmin'; }
	
	public function getHtmlBody() 
	{
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

		$htmlTemplateFile = dirname(__FILE__).'/app2.htm';
		$htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );
		$htmlBody = str_replace ( '<!--CONTENT-->', 'What can I say?', $htmlBody );
		return $htmlBody;
	}
}
