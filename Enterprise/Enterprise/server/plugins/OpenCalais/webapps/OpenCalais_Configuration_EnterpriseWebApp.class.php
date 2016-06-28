<?php
/**
 * Admin web application to configure this plugin. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 *
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/utils/htmlclasses/EnterpriseWebApp.class.php';

class OpenCalais_Configuration_EnterpriseWebApp extends EnterpriseWebApp
{
	public function getTitle()      { return 'OpenCalais'; }
	public function isEmbedded()    { return true; }
	public function getAccessType() { return 'admin'; }
	
	/**
	 * Called by the core server. Builds the HTML body of the web application.
	 *
	 * @return string HTML
	 */
	public function getHtmlBody() 
	{
		// Intercept user input.
		$saveBtnPressed = isset($_REQUEST['save']);
		$key = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';

		// Build the HTML form.
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$htmlTemplateFile = dirname(__FILE__).'/configuration.htm';
		$htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );

		require_once BASEDIR.'/server/plugins/OpenCalais/OpenCalais.class.php';
		$storedKey = OpenCalais::getApiKey();

		if( $saveBtnPressed ) {
			// Store the API key.

			$storedResult = OpenCalais::storeApiKey( $key );
			$saveResult =  $storedResult ?
				'API Key saved successfully.' :
				'<font color=red>API Key could not be saved.</font>';
			$htmlBody = str_replace( '<!--IMPORT_STATUS-->', $saveResult, $htmlBody );
			$htmlBody = str_replace( '<!--KEY_VALUE-->', $key, $htmlBody );
		} else {
			// Retrieve the stored key and display it in the input field.
			$storedKey = (is_null($storedKey)) ? '' : $storedKey;

			$htmlBody = str_replace( '<!--KEY_VALUE-->', $storedKey, $htmlBody );
			$htmlBody = str_replace( '<!--IMPORT_STATUS-->', '', $htmlBody );
		}
		return $htmlBody;
	}

	/**
	 * List of stylesheet files (urls) to include in the HTML page.
	 *
	 * @return array of strings (css include urls)
	 */
	public function getStyleSheetIncludes()
	{
		return array( 'webapps/plugin.css' );
	}
}