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
 * Elvis SUPER_USER username and password, needed for creating PDF previews with InDesign Server.
 * This user is also used to give Enterprise users without Elvis credentials access to the Elvis server.
 */
if( !defined('ELVIS_SUPER_USER') ) {
	define( 'ELVIS_SUPER_USER', 'woodwing' );
}
if( !defined('ELVIS_SUPER_USER_PASS') ) {
	define( 'ELVIS_SUPER_USER_PASS', 'ww' );
}

/**
 * Enterprise Admin username and password, needed for metadata synchronisation from Elvis 
 * to Enterprise. By default we user a user which is known in both systems with the same credentials.
 */
if( !defined('ELVIS_ENT_ADMIN_USER') ) {
	define( 'ELVIS_ENT_ADMIN_USER', ELVIS_SUPER_USER );
}
if( !defined('ELVIS_ENT_ADMIN_PASS') ) {
	define( 'ELVIS_ENT_ADMIN_PASS', ELVIS_SUPER_USER_PASS );
}

/**
 * List of "archived" statuses.
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

/**
 * Field mappings between Enterprise and Elvis fields. 
 * 
 * Custom Enterprise fields and multivalue Elvis fields are not supported in this mapping
 * 
 * Special fields which are statically mapped between Enterprise and Elvis:
 * - Keywords <-> tags
 * - Name <-> name
 * - Type <- assetDomain
 * - Format <- mimeType
 * - Version <- versionNumber
 * - ContentSource - Not really mapped contains value coming from ELVIS_CONTENTSOURCEID
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
 * FieldHandler parameters: Elvis fieldname, multivalue field, Elvis data type, Enterprise fieldname 
 */

require_once dirname(__FILE__) . '/model/fieldHandler/ReadOnlyFieldHandler.class.php';
require_once dirname(__FILE__) . '/model/fieldHandler/ReadWriteFieldHandler.class.php';
require_once dirname(__FILE__) . '/model/fieldHandler/ResolutionFieldHandler.class.php';

$cfgFieldHandlers = array();

//Read Write Handlers
$cfgFieldHandlers['Comment'] =           new ReadWriteFieldHandler("versionDescription", false, "text", "Comment");
$cfgFieldHandlers['Rating'] =            new ReadWriteFieldHandler("rating", false, "number", "Rating");
$cfgFieldHandlers['Copyright'] =         new ReadWriteFieldHandler("copyright", false, "text", "Copyright");
$cfgFieldHandlers['CopyrightURL'] =      new ReadWriteFieldHandler("licensorWebsite", false, "text", "CopyrightURL");
$cfgFieldHandlers['Author'] =            new ReadWriteFieldHandler("creatorName", false, "text", "Author");
$cfgFieldHandlers['Credit'] =            new ReadWriteFieldHandler("credit", false, "text", "Credit");
$cfgFieldHandlers['Source'] =            new ReadWriteFieldHandler("source", false, "text", "Source");
$cfgFieldHandlers['Description'] =       new ReadWriteFieldHandler("description", false, "text",  "Description");
$cfgFieldHandlers['DescriptionAuthor'] = new ReadWriteFieldHandler("captionWriter", false, "text", "DescriptionAuthor");

//Read only Handlers
$cfgFieldHandlers['AspectRatio'] = new ReadOnlyFieldHandler("aspectRatio", false, "decimal", "AspectRatio");
$cfgFieldHandlers['Channels'] =    new ReadOnlyFieldHandler("audioChannels", false, "text", "Channels");
$cfgFieldHandlers['ColorSpace'] =  new ReadOnlyFieldHandler("colorSpace", false, "text", "ColorSpace");
$cfgFieldHandlers['Dpi'] =         new ResolutionFieldHandler("resolutionX", false, "number", "Dpi");
$cfgFieldHandlers['Encoding'] =    new ReadOnlyFieldHandler("videoCodec", false, "text", "Encoding");
$cfgFieldHandlers['Width'] =       new ReadOnlyFieldHandler("width", false, "number", "Width");
$cfgFieldHandlers['Height'] =      new ReadOnlyFieldHandler("height", false, "number", "Height");
$cfgFieldHandlers['Orientation'] = new ReadOnlyFieldHandler("orientation", false, "number", "Orientation");
$cfgFieldHandlers['LengthChars'] = new ReadOnlyFieldHandler("numberOfCharacters", false, "number", "LengthChars");
$cfgFieldHandlers['LengthLines'] = new ReadOnlyFieldHandler("numberOfLines", false, "number", "LengthLines");
$cfgFieldHandlers['LengthParas'] = new ReadOnlyFieldHandler("numberOfParagraphs", false, "number", "LengthParas");
$cfgFieldHandlers['LengthWords'] = new ReadOnlyFieldHandler("wordCount", false, "number", "LengthWords");

// Custom Enterprise field mapped to custom Elvis field - sample mappings
/*
$cfgFieldHandlers['C_BooleanTest'] = new ReadWriteFieldHandler("cf_BooleanTest", false, "boolean", "C_BooleanTest");
$cfgFieldHandlers['C_DateTest'] = new ReadWriteFieldHandler("cf_DateTest", false, "datetime", "C_DateTest");
$cfgFieldHandlers['C_DateTimeTest'] = new ReadWriteFieldHandler("cf_DateTimeTest", false, "datetime", "C_DateTimeTest");
$cfgFieldHandlers['C_DoubleTest'] = new ReadWriteFieldHandler("cf_DoubleTest", false, "decimal", "C_DoubleTest");
$cfgFieldHandlers['C_IntegerTest'] = new ReadWriteFieldHandler("cf_IntegerTest", false, "number", "C_IntegerTest");
$cfgFieldHandlers['C_ListTest'] = new ReadWriteFieldHandler("cf_ListTest", false, "text", "C_ListTest");
$cfgFieldHandlers['C_MultiLineTest'] = new ReadWriteFieldHandler("cf_MultiLineTest", false, "text", "C_MultiLineTest");
$cfgFieldHandlers['C_MultiListTestElvisMultiField'] = new ReadWriteFieldHandler("cf_MultiListTestElvisMultiField", true, "text", "C_MultiListTestElvisMultiField");
$cfgFieldHandlers['C_MultiStringTestElvisMultiField'] = new ReadWriteFieldHandler("cf_MultiStringTestElvisMultiField", true, "text", "C_MultiStringTestElvisMultiField");
$cfgFieldHandlers['C_StringTest'] = new ReadWriteFieldHandler("cf_StringTest", false, "text", "C_StringTest");
// In case the mapping is only applicable for a specific brand, the brand Id ( e.g. 1) can be added.
$cfgFieldHandlers['C_StringTest'] = new ReadWriteFieldHandler("cf_StringTest", false, "text", "C_StringTest", 1 );
*/

//LogHandler::logPhpObject($cfgFieldHandlers);

if( !defined('ELVIS_FIELD_HANDLERS') ) {
	define( 'ELVIS_FIELD_HANDLERS', serialize( $cfgFieldHandlers ) );
}

/**
 * Constants, should never be changed!
 */
define('ELVIS_CONTENTSOURCEID', 'ELVIS');
define('ELVIS_CONTENTSOURCEPREFIX', '_ELVIS_');
define('ELVIS_ENTERPRISE_VERSIONPREFIX', '0.');
define('ELVIS_CLIENT_PACKAGE_PATH', '/install/clients/ElvisContentStation.xml');
define('ELVIS_INTERNAL_USER_POSTFIX', ' (Elvis internal user)');