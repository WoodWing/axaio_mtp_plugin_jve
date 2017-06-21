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
 * When an asset is dragged from the Production Zone folder in Elvis (defined
 * in DEFAULT_ELVIS_PRODUCTION_ZONE) to Enterprise, define if the asset should
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