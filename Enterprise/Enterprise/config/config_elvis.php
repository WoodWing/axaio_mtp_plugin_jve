<?php
/**
 * Elvis server URL (without a trailing slash)
 *
 * Used for server communication between Enterprise and Elvis Server.
 *
 * This is typically localhost (if Elvis and Enterprise are running on the same machine),
 * or the internal ip address of the Elvis Server.
 */
if( !defined('ELVIS_URL') ) {
	define( 'ELVIS_URL', 'http://localhost:8080' );
}

/**
 * Elvis server URL for Enterprise client applications (without a trailing slash).
 *
 * The Elvis Content Station client uses this URL to connect to the correct Elvis Server.
 *
 * This is typically the domain (elvis.mycompany.com) or external ip address of the Elvis server.
 */
if( !defined('ELVIS_CLIENT_URL') ) {
	define( 'ELVIS_CLIENT_URL', ELVIS_URL );
}

/**
 * Network connection time-out (in seconds) that Enterprise applies to the Elvis server URL.
 *
 * When it takes longer than the configured time to connect to Elvis, Enterprise stops trying to connect.
 * (Note that this should not be confused with the network operation time-out, which is fixed to 1 hour.)
 *
 * During workflow operations, Enterprise may need to connect to Elvis to synchronize data that is changed by the user.
 * When users are about to update an Elvis image in Enterprise, they may have a good understanding that a connection
 * is needed between the two systems. However, when an article is changed that is placed on a layout containing
 * placed images, users may not realize that such a connection is also needed.
 * In other words, for some workflow operations this backend communication may be unexpected for end users.
 *
 * USING A SHORT OR LONG TIME-OUT PERIOD?
 * Using a long time-out period may confuse Enterprise users because this could lead to a situation where the
 * connection is lost while the user is not yet informed about this. During this period, the user may retry or abort
 * the action where instead they should be patient and should be informed by the system.
 *
 * Using a short time-out period could lead to Elvis server connection errors for no valid reason (for example because
 * an Elvis server node is busy serving other user requests). Workflow operations that break halfway because of Elvis
 * connection errors is something that should be avoided because it may lead to users retrying the same operation over
 * and over again, causing more stress on the Elvis nodes (that seem to be busy already in this case).
 *
 * Note that this option works in conjunction with the ELVIS_CONNECTION_REATTEMPTS option. Both options together will
 * define the maximum time the user may have to wait before the Elvis connection error is thrown. For 5 reattempts and
 * a connection time-out of 3 seconds, the wait time could be 5x3=15 seconds.
 *
 * The Enterprise-Elvis communication is synchronous: the user waits for Enterprise and Enterprise waits for Elvis.
 * Any Load Balancer that is used (such as ELB on Amazon AWS) must support sticky sessions to stick a certain Enterprise
 * session to an Elvis session.
 * Basically, the whole route from the user via Enterprise to Elvis is sticky. But, when an Elvis node suddenly becomes
 * unhealthy, this route should change; Enterprise should pick a healthy Elvis node to continue with.
 * This should happen silently (without the user noticing) and is crucial for a stable integration. The next paragraph
 * describes how this works and what options are important to make this happen.
 *
 * When any kind of fatal communication error occurs (HTTP 5xx, time-out, and so on) Enterprise will interpret this as
 * a connection
 * error. In case the error is returned immediately, it will wait for the configured time-out seconds. It will then
 * try to re-connect to Elvis for ELVIS_CONNECTION_REATTEMPTS times.
 * This is done by a re-login service call, which clears the Elvis session cookie that was set on the connection.
 * That releases the stickiness and gives the Load Balancer the opportunity to redirect the request to a different
 * Elvis node than the one used before, in the hope that node is healthy. In the meantime, the Load Balancer continuously
 * polls all Elvis nodes to monitor their health state.
 * The interval and threshold settings applied for these polls should therefore 'match' the ELVIS_CONNECTION_TIMEOUT
 * and ELVIS_CONNECTION_REATTEMPTS settings.
 * The idea is that the Load Balancer should be able to find out that the Elvis node became unhealthy before the last
 * re-logon attempt took place. In that case the Elvis connection error should never occur, even when Enterprise was
 * initially talking to an Elvis node that became unhealthy.
 *
 * The following is an example of a health check configuration for ELB on AWS:
 * - Health check time-out:  3 seconds
 * - Health check interval: 5 seconds
 * - Unhealthy threshold:   2 times
 * - Healthy threshold:     4 times
 * In this configuration, for a specific Elvis node that becomes unhealthy, the ELB will find out in 5x2=10 seconds earliest.
 * In the most unlucky situation where Enterprise calls an Elvis service at the very moment the Elvis node becomes unhealthy,
 * it will fail and wait. It will then re-login, which is counted as the first 'reattempt'. As long as the ELB did not detect
 * the unhealthy Elvis node and by coincidence the next following Enterprise call gets picked up by the unhealthy node again,
 * it will wait and re-login again for ELVIS_CONNECTION_REATTEMPTS times in total.
 * Assuming that the default settings are applied for ELVIS_CONNECTION_TIMEOUT and ELVIS_CONNECTION_REATTEMPTS, in this
 * scenario, after 4x3=12 seconds it has done 4 attempts but still did not give up. At the 5th attempt (after 5x3=15 seconds)
 * it will almost certainly find a healthy node.
 *
 * This option is available since Enterprise 10.1.4.
 * The default value for this option is 3.
 */
if( !defined('ELVIS_CONNECTION_TIMEOUT') ) {
	define( 'ELVIS_CONNECTION_TIMEOUT', 3 );
}

/**
 * Number of attempts reconnecting to Elvis server in case the connection with a node has dropped.
 *
 * This option is available since Enterprise 10.1.4.
 * The default value for this option is 5.
 */
if( !defined( 'ELVIS_CONNECTION_REATTEMPTS' ) ) {
	define( 'ELVIS_CONNECTION_REATTEMPTS', 5 );
}

/**
 * Elvis uses the credentials of the currently logged Content Station / Smart Connection 
 * user to connect to Elvis. This means that the WoodWing user also needs access to Elvis.
 */

if( !defined('ELVIS_NAMEDQUERY') ) {
	define( 'ELVIS_NAMEDQUERY', 'Elvis Search' );
}

/**
 * Client ID and secret
 *
 * These settings are used for setting up a trusted back-end connection between Enterprise Server and Elvis Server so
 * that data can be synchronized between these systems. See ELVIS_DEFAULT_USER option for more information.
 *
 * Available since Enterprise Server 10.5.0.
 */
if( !defined('ELVIS_CLIENT_ID') ) {
	define( 'ELVIS_CLIENT_ID', '' );
}
if( !defined('ELVIS_CLIENT_SECRET') ) {
	define( 'ELVIS_CLIENT_SECRET', '' );
}

/**
 * Elvis user with SUPER_USER access rights
 *
 * This user is required for data synchronization from Enterprise Server to Elvis Server. During production, data is
 * directly synchronized in context of workflow operations in Enterprise. For example, when a workflow user makes
 * changes to the metadata of a shadow image object in Enterprise, those changes are reflected to its corresponding image
 * asset in Elvis. Because synchronization runs over a trusted connection between both back-ends, there is no need for
 * a password. Instead, the ELVIS_CLIENT_ID and ELVIS_CLIENT_SECRET options are used to establish the trusted connection.
 */
if( !defined('ELVIS_DEFAULT_USER') ) {
	define( 'ELVIS_DEFAULT_USER', 'admin' );
}

/**
 * Enterprise user with system administrator rights
 *
 * This user is required for data synchronization from Elvis to Enterprise. During production, this is done by a Crontab
 * or Scheduler that continuously runs the sync.php module. For each run, this module logs in to Enterprise using the
 * configured admin user. Note that in this process, the default user (ELVIS_DEFAULT_USER) is used to retrieve data from Elvis,
 * while the admin user (ELVIS_ENT_ADMIN_USER) is used to update data in Enterprise.
 */
if( !defined('ELVIS_ENT_ADMIN_USER') ) {
	define( 'ELVIS_ENT_ADMIN_USER', 'woodwing' );
}
if( !defined('ELVIS_ENT_ADMIN_PASS') ) {
	define( 'ELVIS_ENT_ADMIN_PASS', 'ww' );
}

/**
 * List of "archived" statuses
 *
 * These statuses should match the statuses from the Elvis archive plugin.
 * No updates are sent to Elvis when an object is in one of theses statuses.
 */
if( !defined('ELVIS_ARCHIVED_STATUSES') ) {
	define('ELVIS_ARCHIVED_STATUSES', serialize( array(
		'Archived',
	) ) );
}

/**
 * Defines how the system should link or copy an Elvis asset when the user is about to use it in Enterprise.
 *
 * Use one of the following options:
 * - 'Copy_To_Production_Zone'  Copy the asset to the Production Zone in Elvis and create a shadow object in Enterprise that is linked to the copy.
 * - 'Hard_Copy_To_Enterprise'  Copy the asset from Elvis directly to Enterprise. No link or shadow object is created.
 * - 'Shadow_Only'              Create a shadow object in Enterprise that is linked to the asset in Elvis. No copy is created.
 *
 * Note that this option has changed since Enterprise 10.1.1.
 */
if( !defined('ELVIS_CREATE_COPY') ) {
	define( 'ELVIS_CREATE_COPY', 'Shadow_Only' );
}

/**
 * Default value for the Production Zone property shown on the Brand Maintenance page.
 * It defines the folder location in Elvis where images are copied to just before they are used by Enterprise.
 *
 * The following placeholders can be used:
 *   ${brand}         => This will be replaced with the Brand name once the Brand is created.
 *   ${date:<format>} => This will be replaced with the current date once the Elvis asset is copied.
 *                       The <format> is specified here: http://php.net/manual/en/function.date.php
 *
 * This option is available since Enterprise 10.1.1 and requires Elvis 5.18 (or newer).
 */
if( !defined('DEFAULT_ELVIS_PRODUCTION_ZONE') ) {
	define( 'DEFAULT_ELVIS_PRODUCTION_ZONE', '/Production Zone/${brand}/${date:Y-m}' );
}

/**
 * The location to which images are restored when restoring a layout from Elvis.
 *
 * It supports the following options:
 * - 'Elvis_Copy'     The image is copied in Elvis and is linked via an Enterprise shadow object.
 *                    When the ELVIS_CREATE_COPY option is set to 'Copy_To_Production_Zone' Smart Connection will no longer
 *                    raise a message to let the user specify the Elvis folder. Instead, the Production Zone is used as
 *                    configured for the Brand.
 *                    This value can NOT be used when the ELVIS_CREATE_COPY option is set to 'Hard_Copy_To_Enterprise'.
 * - 'Elvis_Original' The image is linked via an Enterprise shadow object. No copy is created.
 *                    This value can NOT be used when the ELVIS_CREATE_COPY option is set to 'Copy_To_Production_Zone'.
 *                    This option requires Elvis Server version 5.14 or higher.
 * - 'Enterprise'     The image is copied to Enterprise. No link or shadow object is created.
 */
if( !defined('IMAGE_RESTORE_LOCATION') ) {
	define( 'IMAGE_RESTORE_LOCATION', 'Elvis_Copy' );
}

/**
 * When an image is dragged from the Production Zone folder in Elvis (defined
 * in DEFAULT_ELVIS_PRODUCTION_ZONE) to Enterprise, define if the image should
 * be copied in Elvis and linked to Enterprise.
 *
 * When set to 'true', the ELVIS_CREATE_COPY option has to be set to 'Copy_To_Production_Zone'.
 *
 * Available since Enterprise Server 10.1.3.
 */
if( !defined('ELVIS_CREATE_COPY_WHEN_MOVED_FROM_PRODUCTION_ZONE' )) {
	define( 'ELVIS_CREATE_COPY_WHEN_MOVED_FROM_PRODUCTION_ZONE', false );
}
// Specify a Unique ID & prefix for this contentSource

/**
 * ------------------------------- ADVANCED config --------------------------------
 * Don't make changes in the settings beneath if you don't know what you're doing!
 * --------------------------------------------------------------------------------
 */

// Enable autoload PHP classes defined by the Elvis plugin.
require_once BASEDIR.'/server/utils/Autoloader.class.php';
WW_Utils_Autoloader::registerServerPlugin( 'Elvis' );

/**
 * Mapping fields between Enterprise and Elvis fields
 * 
 * Note: custom Enterprise fields and multi-value Elvis fields cannot be mapped with other fields.
 * 
 * Special fields which are statically mapped between Enterprise and Elvis:
 * - Keywords <-> tags
 * - Name <-> name
 * - Type <- assetDomain
 * - Format <- mimeType
 * - Version <- versionNumber
 * - ContentSource - Not really mapped, contains values from ELVIS_CONTENTSOURCEID
 * - DocumentID <- id
 * - CopyrightMarked <- copyright
 * 
 * Not mapped:
 * - Brand
 * - Category
 * - Status
 * - Issue
 * - Targets
 * - Compression
 * - Urgency
 * 
 * Since 10.5.0 the field handlers are renamed as follows:
 * - ReadWriteFieldHandler => Elvis_FieldHandlers_ReadWrite
 * - ReadOnlyFieldHandler  => Elvis_FieldHandlers_ReadOnly
 * - NameFieldHandler      => Elvis_FieldHandlers_Name
 * - ... etc
 * If your Elvis/config.php file still contains field handlers in the old notation, please adjust accordingly.
 *
 * Since 10.5.0 it is recommended to add your custom field handlers in the config_overrule.php file.
 * Please add them in a function named Elvis_Config_GetAdditionalFieldHandlers as follows:
 *    function Elvis_Config_GetAdditionalFieldHandlers()
 *    {
 *       // Field handler parameters: Elvis fieldname, multivalue field, Elvis data type, Enterprise fieldname
 *       $cfgFieldHandlers = array();
 *       $cfgFieldHandlers['C_BooleanTest'] = new Elvis_FieldHandlers_ReadWrite("cf_BooleanTest", false, "boolean", "C_BooleanTest");
 *       $cfgFieldHandlers['C_DateTest'] = new Elvis_FieldHandlers_ReadWrite("cf_DateTest", false, "datetime", "C_DateTest");
 *       $cfgFieldHandlers['C_DateTimeTest'] = new Elvis_FieldHandlers_ReadWrite("cf_DateTimeTest", false, "datetime", "C_DateTimeTest");
 *       $cfgFieldHandlers['C_DoubleTest'] = new Elvis_FieldHandlers_ReadWrite("cf_DoubleTest", false, "decimal", "C_DoubleTest");
 *       $cfgFieldHandlers['C_IntegerTest'] = new Elvis_FieldHandlers_ReadWrite("cf_IntegerTest", false, "number", "C_IntegerTest");
 *       $cfgFieldHandlers['C_ListTest'] = new Elvis_FieldHandlers_ReadWrite("cf_ListTest", false, "text", "C_ListTest");
 *       $cfgFieldHandlers['C_MultiLineTest'] = new Elvis_FieldHandlers_ReadWrite("cf_MultiLineTest", false, "text", "C_MultiLineTest");
 *       $cfgFieldHandlers['C_MultiListTestElvisMultiField'] = new Elvis_FieldHandlers_ReadWrite("cf_MultiListTestElvisMultiField", true, "text", "C_MultiListTestElvisMultiField");
 *       $cfgFieldHandlers['C_MultiStringTestElvisMultiField'] = new Elvis_FieldHandlers_ReadWrite("cf_MultiStringTestElvisMultiField", true, "text", "C_MultiStringTestElvisMultiField");
 *       $cfgFieldHandlers['C_StringTest'] = new Elvis_FieldHandlers_ReadWrite("cf_StringTest", false, "text", "C_StringTest");
 *       // In case the mapping is only applicable for a specific brand, the brand Id ( e.g. 1) can be added.
 *       $cfgFieldHandlers['C_StringTest'] = new Elvis_FieldHandlers_ReadWrite("cf_StringTest", false, "text", "C_StringTest", 1 );
 *       return $cfgFieldHandlers;
 *    }
 * In the very exceptional case that you want to change the field definitions listed below, it is recommended
 * to add them to the Elvis_Config_GetAdditionalFieldHandlers function in the config_overrule.php file.
 * This makes it easier to maintain compared to adjusting the definitions below.
 *
 * Since 10.5.0 the field handlers are listed in a new function named Elvis_Config_GetFieldHandlers.
 * If that function does not exist in your Elvis/config.php file, please add as shown below:
 */
if( !function_exists( 'Elvis_Config_GetFieldHandlers' ) ) {
	function Elvis_Config_GetFieldHandlers()
	{
		// Field handler parameters: Elvis fieldname, multivalue field, Elvis data type, Enterprise fieldname
		$cfgFieldHandlers = array();

		// Read Write Handlers
		$cfgFieldHandlers['Comment'] = new Elvis_FieldHandlers_ReadWrite( "versionDescription", false, "text", "Comment" );
		$cfgFieldHandlers['Rating'] = new Elvis_FieldHandlers_ReadWrite( "rating", false, "number", "Rating" );
		$cfgFieldHandlers['Copyright'] = new Elvis_FieldHandlers_ReadWrite( "copyright", false, "text", "Copyright" );
		$cfgFieldHandlers['CopyrightURL'] = new Elvis_FieldHandlers_ReadWrite( "licensorWebsite", false, "text", "CopyrightURL" );
		$cfgFieldHandlers['Author'] = new Elvis_FieldHandlers_ReadWrite( "creatorName", false, "text", "Author" );
		$cfgFieldHandlers['Credit'] = new Elvis_FieldHandlers_ReadWrite( "credit", false, "text", "Credit" );
		$cfgFieldHandlers['Source'] = new Elvis_FieldHandlers_ReadWrite( "source", false, "text", "Source" );
		$cfgFieldHandlers['Description'] = new Elvis_FieldHandlers_ReadWrite( "description", false, "text", "Description" );
		$cfgFieldHandlers['DescriptionAuthor'] = new Elvis_FieldHandlers_ReadWrite( "captionWriter", false, "text", "DescriptionAuthor" );

		// Read only Handlers
		$cfgFieldHandlers['AspectRatio'] = new Elvis_FieldHandlers_ReadOnly( "aspectRatio", false, "decimal", "AspectRatio" );
		$cfgFieldHandlers['Channels'] = new Elvis_FieldHandlers_ReadOnly( "audioChannels", false, "text", "Channels" );
		$cfgFieldHandlers['ColorSpace'] = new Elvis_FieldHandlers_ReadOnly( "colorSpace", false, "text", "ColorSpace" );
		$cfgFieldHandlers['Dpi'] = new Elvis_FieldHandlers_Resolution( "resolutionX", false, "number", "Dpi" );
		$cfgFieldHandlers['Encoding'] = new Elvis_FieldHandlers_ReadOnly( "videoCodec", false, "text", "Encoding" );
		$cfgFieldHandlers['Width'] = new Elvis_FieldHandlers_ReadOnly( "width", false, "number", "Width" );
		$cfgFieldHandlers['Height'] = new Elvis_FieldHandlers_ReadOnly( "height", false, "number", "Height" );
		$cfgFieldHandlers['Orientation'] = new Elvis_FieldHandlers_ReadOnly( "orientation", false, "number", "Orientation" );
		$cfgFieldHandlers['LengthChars'] = new Elvis_FieldHandlers_ReadOnly( "numberOfCharacters", false, "number", "LengthChars" );
		$cfgFieldHandlers['LengthLines'] = new Elvis_FieldHandlers_ReadOnly( "numberOfLines", false, "number", "LengthLines" );
		$cfgFieldHandlers['LengthParas'] = new Elvis_FieldHandlers_ReadOnly( "numberOfParagraphs", false, "number", "LengthParas" );
		$cfgFieldHandlers['LengthWords'] = new Elvis_FieldHandlers_ReadOnly( "wordCount", false, "number", "LengthWords" );

		if( function_exists( 'Elvis_Config_GetAdditionalFieldHandlers' ) ) {
			$extraFieldHanders = Elvis_Config_GetAdditionalFieldHandlers();
			$cfgFieldHandlers = array_merge( $cfgFieldHandlers, $extraFieldHanders );
		}

		return $cfgFieldHandlers;
	}
}
if( defined( 'ELVIS_FIELD_HANDLERS' ) ) { // Warn incomplete migrations.
	LogHandler::Log( 'EVLIS', 'ERROR', 'The ELVIS_FIELD_HANDLERS option is no longer supported. '.
		'To configure field handlers in your config_overrule.php file, please define a function named '.
		'Elvis_Config_GetFieldHandlers. In the config_elvis.php file there is the default implementation '.
		'of this function which could be used as an example. And, please remove your ELVIS_FIELD_HANDLERS option definition. '
);
}

/* --------------------------------------------------------------------------------
 * Constants. Please do NOT make changes below!
 * --------------------------------------------------------------------------------
 */
define('ELVIS_CONTENTSOURCEID', 'ELVIS');
define('ELVIS_CONTENTSOURCEPREFIX', '_ELVIS_');
define('ELVIS_ENTERPRISE_VERSIONPREFIX', '0.');
define('ELVIS_INTERNAL_USER_POSTFIX', ' (Elvis internal user)');
define('ELVIS_CONTENTSOURCE_PUBLIC_PROXYURL', SERVERURL_ROOT.INETROOT.'/server/plugins/Elvis/restproxyindex.php');
define('ELVIS_CONTENTSOURCE_PRIVATE_PROXYURL', LOCALURL_ROOT.INETROOT.'/server/plugins/Elvis/restproxyindex.php');
define('ELVIS_MINVERSION', '6.15.0.0'); // minimum required version // TODO: fine tune this version once known