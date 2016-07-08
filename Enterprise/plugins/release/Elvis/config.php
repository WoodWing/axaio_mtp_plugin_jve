<?php
/**
 * Elvis server URL (without a trailing slash)
 *
 * Used for server communication between Enterprise and Elvis Server.
 *
 * This is typically localhost (if Elvis and Enterprise are running on the same machine),
 * or the internal ip address of the Elvis Server.
 */
define('ELVIS_URL', 'http://localhost:8080');

/**
 * Elvis server URL for Enterprise client applications (without a trailing slash).
 *
 * The Elvis Content Station client uses this URL to connect to the correct Elvis Server.
 *
 * This is typically the domain (elvis.mycompany.com) or external ip address of the Elvis server.
 */
define('ELVIS_CLIENT_URL', ELVIS_URL);

/**
 * Elvis uses the credentials of the currently logged Content Station / Smart Connection 
 * user to connect to Elvis. This means that the WoodWing user also needs access to Elvis.
 */

define('ELVIS_NAMEDQUERY', 'Elvis Search');

/**
 * Elvis SUPER_USER username and password, needed for creating PDF previews with InDesign Server.
 * This user is also used to give Enterprise users without Elvis credentials access to the Elvis server.
 */
define('ELVIS_SUPER_USER', 'woodwing');
define('ELVIS_SUPER_USER_PASS', 'ww');

/**
 * Enterprise Admin username and password, needed for metadata synchronisation from Elvis 
 * to Enterprise. By default we user a user which is known in both systems with the same credentials.
 */
define('ELVIS_ENT_ADMIN_USER', ELVIS_SUPER_USER);
define('ELVIS_ENT_ADMIN_PASS', ELVIS_SUPER_USER_PASS);

/**
 * List of "archived" statuses.
 *
 * These statuses should match the statuses from the Elvis archive plugin.
 * No updates are sent to Elvis when an object is in one of theses statuses.
 */
define('ELVIS_ARCHIVED_STATUSES', serialize( array(
	'Archived',
) ) );

/**
 * By default Elvis will create a shadow object in Enterprise that is linked to the asset in Elvis.
 * When set to true, a copy of the asset will be uploaded to Enterprise and is not linked to the 
 * original in Elvis.
 */
define('ELVIS_CREATE_COPY', 'false');

/**
 * The location to which images are restored when restoring a layout from Elvis.
 * it supports the following options:
 *
 * Elvis_Copy: The image is copied in Elvis and is linked via an Enterprise shadow object.
 * Elvis_Original: The image is linked via an Enterprise shadow object.
 * Enterprise: The image is copied to Enterprise.
 */
define( 'IMAGE_RESTORE_LOCATION', 'Elvis_Copy' );

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

$cfgFieldHandlers = array();

//Read Write Handlers
$cfgFieldHandlers['Comment'] = 				new ReadWriteFieldHandler("versionDescription", false, "text", "Comment");
$cfgFieldHandlers['Rating'] = 				new ReadWriteFieldHandler("rating", false, "number", "Rating");
$cfgFieldHandlers['Copyright'] = 			new ReadWriteFieldHandler("copyright", false, "text", "Copyright");
$cfgFieldHandlers['CopyrightURL'] = 		new ReadWriteFieldHandler("licensorWebsite", false, "text", "CopyrightURL");
$cfgFieldHandlers['Author'] = 				new ReadWriteFieldHandler("creatorName", false, "text", "Author");
$cfgFieldHandlers['Credit'] = 				new ReadWriteFieldHandler("credit", false, "text", "Credit");
$cfgFieldHandlers['Source'] = 				new ReadWriteFieldHandler("source", false, "text", "Source");
$cfgFieldHandlers['Description'] = 			new ReadWriteFieldHandler("description", false, "text",  "Description");
$cfgFieldHandlers['DescriptionAuthor'] = 	new ReadWriteFieldHandler("captionWriter", false, "text", "DescriptionAuthor");

//Read only Handlers
$cfgFieldHandlers['AspectRatio'] = 			new ReadOnlyFieldHandler("aspectRatio", false, "decimal", "AspectRatio");
$cfgFieldHandlers['Channels'] = 			new ReadOnlyFieldHandler("audioChannels", false, "text", "Channels");
$cfgFieldHandlers['ColorSpace'] =			new ReadOnlyFieldHandler("colorSpace", false, "text", "ColorSpace");
$cfgFieldHandlers['Dpi'] = 					new ReadOnlyFieldHandler("resolution", false, "decimal", "Dpi");
$cfgFieldHandlers['Encoding'] = 			new ReadOnlyFieldHandler("videoCodec", false, "text", "Encoding");
$cfgFieldHandlers['Width'] = 				new ReadOnlyFieldHandler("width", false, "number", "Width");
$cfgFieldHandlers['Height'] =				new ReadOnlyFieldHandler("height", false, "number", "Height");
$cfgFieldHandlers['LengthChars'] = 			new ReadOnlyFieldHandler("numberOfCharacters", false, "number", "LengthChars");
$cfgFieldHandlers['LengthLines'] = 			new ReadOnlyFieldHandler("numberOfLines", false, "number", "LengthLines");
$cfgFieldHandlers['LengthParas'] = 			new ReadOnlyFieldHandler("numberOfParagraphs", false, "number", "LengthParas");
$cfgFieldHandlers['LengthWords'] = 			new ReadOnlyFieldHandler("wordCount", false, "number", "LengthWords");

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

define('ELVIS_FIELD_HANDLERS', serialize($cfgFieldHandlers));

/**
 * Constants, should never be changed!
 */
define('ELVIS_CONTENTSOURCEID', 'ELVIS');
define('ELVIS_CONTENTSOURCEPREFIX', '_ELVIS_');
define('ELVIS_ENTERPRISE_VERSIONPREFIX', '0.');
define('ELVIS_CLIENT_PACKAGE_PATH', '/install/clients/ElvisContentStation.xml');
define('ELVIS_INTERNAL_USER_POSTFIX', ' (Elvis internal user)');