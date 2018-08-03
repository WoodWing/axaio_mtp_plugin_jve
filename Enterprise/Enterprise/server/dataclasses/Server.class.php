<?php
/**
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Enterprise Server configuration.
 */

class Server
{
	public $Id;          // DB record id.
	public $Name;        // Logical server name, for display only. Could be IP or host name, etc.
	public $Type;        // Supported: 'Enterprise'  (Future: 'InDesign', 'MadeToPrint', etc)
	public $URL;         // HTTP url to server. This is without any web page (e.g. index.php).
	public $Description; // For admin/displaying only.
	public $JobSupport;  // Roughly indication of what job types are configured for server: 'A' = 'All', 'N' ='None' or 'S' = 'Selected' 
	public $JobSupportDisplay; // Same as JobSupport, but then localized.
	public $JobTypes;    // Array of job type names that are configured for server.
}
