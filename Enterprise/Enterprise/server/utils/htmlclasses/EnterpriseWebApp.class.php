<?php
/**
 * Abstract base class that helps building a HTML/JS web application.
 * The URL to the application should be Enterprise/server/admin/webappindex.php.
 * Parameters on the URL tell webappindex where to find the web app.
 * Once opened by the admin user the webappindex creates the subclass
 * that implements the web application.
 *
 * Although this is a more generic class, it is currently used by server
 * plugin-ins only to offer admin web apps at the Integrations admin page. 
 *
 * @since v8.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

abstract class EnterpriseWebApp
{
	/**
	 * Application title to show on top of the web page
	 *
	 * @return string The title
	 */
	abstract public function getTitle();
	
	/**
	 * Whether or not the web app should be embbedded into the Enterprise web apps.
	 * When returning true, the core draws the main menu and applies CSS styles.
	 * Else the web app is stand alone and should take care of menus and styling by itself.
	 *
	 * @return boolean Embedded
	 */
	abstract public function isEmbedded();
	
	/**
	 * Tells which users can access the web page.
	 * Return 'admin' to allow system admin users only.
	 * Return 'publadmin' to allow brand admin and system admin users.
	 *
	 * @return string 
	 */
	abstract public function getAccessType();
	
	/**
	 * Called by the core server when it is time to build the web app in HTML.
	 * When isEmbedded returns true, this function should not use <htm> or <body> markers, 
	 * but simply provide HTML that can be place inside the HTML body.
	 *
	 * @return string HTML
	 */
	abstract public function getHtmlBody();
	
	/**
	 * List of javascript files (urls) to include in the HTML page.
	 *
	 * @return array of strings (JS include urls)
	 */
	public function getJavaScriptIncludes()
	{
		return array();
	}

	/**
	 * List of stylesheet files (urls) to include in the HTML page.
	 *
	 * @return array of strings (css include urls)
	 */
	public function getStyleSheetIncludes()
	{
		return array();
	}
}
