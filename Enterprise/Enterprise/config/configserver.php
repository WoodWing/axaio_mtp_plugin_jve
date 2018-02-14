<?php
// -------------------------------------------------------------------------------------------------
// Required includes
// -------------------------------------------------------------------------------------------------
if( !defined('AS_CLASSNAME_PREFIX') ) {
	define( 'AS_CLASSNAME_PREFIX', 'com.woodwing.enterprise.interfaces.services' ); // used for AMF object type mapping
}
require_once BASEDIR.'/server/vendor/autoload.php'; // install auto loaders for 3rd party components
require_once BASEDIR.'/server/serverinfo.php';

// -------------------------------------------------------------------------------------------------
// Database - advanced configuration
// -------------------------------------------------------------------------------------------------

// DBTYPE: 
//    The type of database used:
//       'mysql'   MySQL. Default option.
//       'mssql'   MS SQL Server.
//    Value must be in lower case.
//
if( !defined('DBTYPE') ) {
	define( 'DBTYPE', 'mysql' );
}
// DBSERVER:
//    The database server address. By default the same machine as the
//    application server of which this configserver.php file is part.
//    Default value: '127.0.0.1'. (MySQL)
//    For MS SQL the database machine must be listed. For MySQL use the IP address.
//    MS SQL:
//       If your machine is called 'MyPC'.
//          define ('DBSERVER', 'MyPC' );
//    MySQL:
//       Add host and port number if not the default port is used. For example: define( 'DBSERVER', '127.0.0.1:3307' );
//       To connect to a local DB use '127.0.0.1', for MAMP/XAMP installations use 'localhost' instead.
//       If a socket is used, add the path of the socket. For example: define( 'DBSERVER', '/opt/local/tmp/mysql.sock' );
//
if( !defined('DBSERVER') ) {
	define( 'DBSERVER', '127.0.0.1' );
}

// DBMAXQUERY:
//    Maximum number of records returned in query results. Default value is 50. By default, when
//    the results exceed 50 files, they will be grouped in groups of 50. When set to zero (0), there
//    is no limit and all records are returned. This can have a negative impact on performance, 
//    so doing this is not recommended. The DBMAXQUERY specifies the maximum number of rows that
//    will be returned at the highest level. So, when using hierarchical view, the number of returned
//    rows can be many times greater than specified in DBMAXQUERY. 
//
if( !defined('DBMAXQUERY') ) {
	define( 'DBMAXQUERY', 50 );
}

// -------------------------------------------------------------------------------------------------
// FileStore - advanced configuration
// -------------------------------------------------------------------------------------------------

// ATTACHMODULO:
//    Maximum number of objects to be stored within one folder. Note one object can itself contain
//    many files so the number of files per folder will be many times greater. Default value: 100.
//
if( !defined('ATTACHMODULO') ) {
	define( 'ATTACHMODULO', 100 );
}

// -------------------------------------------------------------------------------------------------
// File Transfer Server
// -------------------------------------------------------------------------------------------------

// HTTP_FILE_TRANSFER_REMOTE_URL:
//    URL to the entry point of the File Transfer Server. This URL is used by clients to
//    upload and download files. The clients are located outside the server farm, and thus
//    we speak of a 'remote' URL, as accessible from outside. By default, the URL points
//    to 'this' Enterprise Server, assuming it can act as a Transfer Server too.
//    The web server must accept the HTTP methods GET, PUT, POST and DELETE (at least for
//    the configured URL) to make the Transfer Server fully functional.
//
if( !defined('HTTP_FILE_TRANSFER_REMOTE_URL') ) {
	define( 'HTTP_FILE_TRANSFER_REMOTE_URL', SERVERURL_ROOT.INETROOT.'/transferindex.php' );
}

// HTTP_FILE_TRANSFER_LOCAL_URL:
//    Same logical web location as specified for the HTTP_FILE_TRANSFER_REMOTE_URL option.
//    This time, the URL to the File Transfer Server, as accessible from within the server farm.
//    This URL is used by test scripts and the Health Check running at a certain Enterprise Server
//    instance within the server farm. When Enterprise Server can act as a Transfer Server too,
//    there is no need to adjust the default value. But, when there is a dedicated Transfer Server
//    configured on a different web location than the Enterprise Server, the LOCALURL_ROOT.INETROOT
//    value needs to be replaced with a specific URL pointing to the Transfer Server machine.
//
if( !defined('HTTP_FILE_TRANSFER_LOCAL_URL') ) {
	define( 'HTTP_FILE_TRANSFER_LOCAL_URL', LOCALURL_ROOT.INETROOT.'/transferindex.php' );
}

// FILE_TRANSFER_LOCAL_PATH:
//    When files are uploaded or downloaded, the Transfer Server needs a folder to temporary
//    store the files during client-server communication. For performance reasons, this folder 
//    should reside on the same logical disk as the filestore. Therefore the default value 
//    WOODWINGSYSTEMDIRECTORY should not be changed without very good reason.
//    But, when a dedicated Transfer Server is configured for the server farm, and you want 
//    to replicate the very same configserver.php file over all server instances (Enterprise 
//    Servers and the Transfer Server), the settings might differ.
//    This option is only used by Transfer Server, but not by the Enterprise Servers.
//
if( !defined('FILE_TRANSFER_LOCAL_PATH') ) {
	define( 'FILE_TRANSFER_LOCAL_PATH', WOODWINGSYSTEMDIRECTORY.'/TransferServerCache' ); // DO NOT end with a separator, use forward slashes
}

// -------------------------------------------------------------------------------------------------
// Functional logging
// -------------------------------------------------------------------------------------------------

// LOGLEVEL:
//    High-level monitoring (audit trails). Possible values are:
//       '0'   High-level logging not enabled. Default option.
//       '1'   Logon and Logoff only.
//       '2'   All SOAP calls (note that this creates a large number of rows in the smartlog table.) 
//
if( !defined('LOGLEVEL') ) {
	define( 'LOGLEVEL', '0' );
}

// -------------------------------------------------------------------------------------------------
// Object file format support
// -------------------------------------------------------------------------------------------------

// XMLTYPE:
//    System internal. Do never change. It is used by the Geometry feature.
//
if( !defined('XMLTYPE') ) {
	define( 'XMLTYPE', 'xml' );
}

// EXTENSIONMAP:
//    Mapping of file types (extensions, for example image.jpg) to their MIME type and object type 
//    as stored in the Enterprise database.
//
if( !defined('EXTENSIONMAP') ) {
	define ('EXTENSIONMAP', serialize( array(
		'.jpg' => array( 'image/jpeg', 'Image'),
		'.jpeg' => array( 'image/jpeg', 'Image'),
		'.gif' => array( 'image/gif', 'Image'),
		'.tif' => array( 'image/tiff', 'Image'),
		'.tiff' => array( 'image/tiff', 'Image'),
		'.png' => array( 'image/png', 'Image'),
		'.psd' => array( 'image/x-photoshop', 'Image'),
		'.eps' => array( 'application/postscript', 'Image'),
		'.ai' => array( 'application/illustrator', 'Image'),
		'.pdf' => array( 'application/pdf', 'Image'),
		'.wwcx' => array( 'application/incopy', 'Article'),
		'.wwct' => array( 'application/incopyinx', 'ArticleTemplate'),
		'.wcml' => array( 'application/incopyicml', 'Article'),
		'.wcmt' => array( 'application/incopyicmt', 'ArticleTemplate'),
		'.wwea' => array( 'text/wwea', 'Article'),
		'.wweat' => array( 'text/wwea', 'ArticleTemplate'), // BZ# 19176: To ensure the article template has the correct icon.
		'.digital' => array( 'application/ww-digital+json', 'Article'), // added since 10.2.0 to support Content Station Digital Editor articles
		'.digitmpl' => array( 'application/ww-digitmpl+json', 'ArticleTemplate'), // added since 10.2.0 to support Content Station Digital Editor articles
		'.incd' => array( 'application/incopy', 'Article'),
		'.incx' => array( 'application/incopy', 'Article'),
		'.indd' => array( 'application/indesign', 'Layout'),
		'.indt' => array( 'application/indesign', 'LayoutTemplate'),
		'.indl' => array( 'application/indesignlibrary', 'Library'), // BZ#10231: Changed indesign into indesignlibrary
		'.htm' => array( 'text/html', 'Article'),
		'.html' => array( 'text/html', 'Article'),
		'.txt' => array( 'text/plain', 'Article'),
		'.rtf' => array( 'text/richtext', 'Article'),
		'.xml' => array( XMLTYPE, ""),

		// Audio / Video
		'.au' => array( 'audio/basic', 'Audio'),
		'.snd' => array( 'audio/basic', 'Audio'),
		'.mid' => array( 'audio/midi', 'Audio'),
		'.midi' => array( 'audio/midi', 'Audio'),
		'.kar' => array( 'audio/midi', 'Audio'),
		'.mp3' => array( 'audio/mpeg', 'Audio'), // BZ#6564: moved on top of audio/mpeg sublist
		'.mpga' => array( 'audio/mpeg', 'Audio'),
		'.mp2' => array( 'audio/mpeg', 'Audio'),
		'.aif' => array( 'audio/x-aiff', 'Audio'),
		'.aiff' => array( 'audio/x-aiff', 'Audio'),
		'.aifc' => array( 'audio/x-aiff', 'Audio'),
		'.m3u' => array( 'audio/x-mpegurl', 'Audio'),
		'.ram' => array( 'audio/x-pn-realaudio', 'Audio'),
		'.rm' => array( 'audio/x-pn-realaudio', 'Audio'),
		'.rpm' => array( 'audio/x-pn-realaudio-plugin', 'Audio'),
		'.ra' => array( 'audio/x-realaudio', 'Audio'),
		'.wav' => array( 'audio/x-wav', 'Audio'),
		'.mpg' => array( 'video/mpeg', 'Video'),
		'.mpeg' => array( 'video/mpeg', 'Video'),
		'.mov' => array( 'video/quicktime', 'Video'),
		'.avi' => array( 'video/x-msvideo', 'Video'),
		'.asf' => array( 'video/x-ms-asf', 'Video'),
		'.asx' => array( 'video/x-ms-asf', 'Video'),
		'.wma' => array( 'video/x-ms-wma', 'Video'),
		'.wmv' => array( 'video/x-ms-wmv', 'Video'),
		'.wmx' => array( 'video/x-ms-wmx', 'Video'),
		'.wmz' => array( 'video/x-ms-wmz', 'Video'),
		'.wmd' => array( 'video/x-ms-wmd', 'Video'),
		'.wm' => array( 'video/x-ms-wm', 'Video'),
		'.flv' => array( 'video/x-flv', 'Video'),
		'.swf' => array( 'application/x-shockwave-flash', 'Video'),
		'.mp4' => array( 'video/mp4', 'Video'),
		'.m4v' => array( 'video/x-m4v', 'Video'), // BZ#20713

		// MS Office 2003/2004				(some are commented out to avoid duplicate mime types + object types)
		'.doc' => array( 'application/msword',            'Article'),  // Word document
		'.dot' => array( 'application/msword',            'ArticleTemplate'), // Word template
		'.xls' => array( 'application/vnd.ms-excel',      'Spreadsheet' ), // Excel sheet
		//'.xlt' => array( 'application/vnd.ms-excel',      'ArticleTemplate' ), // Excel template
		//'.xlw' => array( 'application/vnd.ms-excel',      'Article' ), // Excel workbook
		//'.xla' => array( 'application/vnd.ms-excel',      'Other' ),   // Excel add-in
		//'.xlc' => array( 'application/vnd.ms-excel',      'Article' ), // Excel chart
		//'.xlm' => array( 'application/vnd.ms-excel',      'Article' ), // Excel macro
		'.ppt' => array( 'application/vnd.ms-powerpoint', 'Presentation' ),   // PowerPoint presentation (BZ#10482)
		//'.pps' => array( 'application/vnd.ms-powerpoint', 'Other' ),   // PowerPoint slideshow
		//'.pot' => array( 'application/vnd.ms-powerpoint', 'Other' ),   // PowerPoint template
		//'.ppz' => array( 'application/vnd.ms-powerpoint', 'Other' ),   // PowerPoint animation
		//'.ppa' => array( 'application/vnd.ms-powerpoint', 'Other' ),   // PowerPoint add-in

		// MS Office 2007
		'.docx' => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'Article' ), // Word document
		'.docm' => array( 'application/vnd.ms-word.document.macroEnabled.12',                        'Article' ), // " (macro-enabled)
		'.dotx' => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.template', 'ArticleTemplate' ), // Word template
		'.dotm' => array( 'application/vnd.ms-word.template.macroEnabled.12',                        'Article' ), // " (macro-enabled)
		'.xlsx' => array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',       'Spreadsheet' ), // Excel workbook
		'.xlsm' => array( 'application/vnd.ms-excel.sheet.macroEnabled.12',                          'Spreadsheet' ), // " (macro-enabled)
		'.xltx' => array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',    'Spreadsheet' ), // Excel template
		'.xltm' => array( 'application/vnd.ms-excel.template.macroEnabled.12',                       'Spreadsheet' ), // " (macro-enabled)
		'.xlsb' => array( 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',                   'Spreadsheet' ), // Excel binary workbook (macro-enabled)
		'.xlam' => array( 'application/vnd.ms-excel.addin.macroEnabled.12',                          'Other' ),   // Excel add-in
		'.pptx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'Presentation' ), // PowerPoint presentation
		'.pptm' => array( 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',              'Presentation' ),   // " (macro-enabled)
		'.ppsx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',  'Presentation' ),   // PowerPoint slideshow
		'.ppsm' => array( 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',                 'Presentation' ),   // " (macro-enabled)
		'.potx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.template',   'Other' ),   // PowerPoint presentation template
		'.potm' => array( 'application/vnd.ms-powerpoint.template.macroEnabled.12',                  'Other' ),   // " (macro-enabled)
		'.ppam' => array( 'application/vnd.ms-powerpoint.addin.macroEnabled.12',                     'Other' ),   // PowerPoint add-in
		'.sldx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.slide',      'Presentation' ),   // PowerPoint presentation
		'.sldm' => array( 'application/vnd.ms-powerpoint.slide.macroEnabled.12',                     'Presentation' ),   // " (macro-enabled)

		// Open Office
		'.odt' => array( 'application/vnd.oasis.opendocument.text',                  'Article' ),
		'.ott' => array( 'application/vnd.oasis.opendocument.text-template',         'ArticleTemplate' ),
		'.oth' => array( 'application/vnd.oasis.opendocument.text-web',              'Article' ),
		'.odm' => array( 'application/vnd.oasis.opendocument.text-master',           'ArticleTemplate' ),
		'.ods' => array( 'application/vnd.oasis.opendocument.spreadsheet',           'Spreadsheet' ),
		'.ots' => array( 'application/vnd.oasis.opendocument.spreadsheet-template',  'Spreadsheet' ),
		'.odp' => array( 'application/vnd.oasis.opendocument.presentation',          'Presentation' ),
		'.otp' => array( 'application/vnd.oasis.opendocument.presentation-template', 'Other' ),

		// iWork
		'.numbers' => array( 'application/x-apple-numbers', 'Spreadsheet' ),
		'.pages' => array( 'application/x-apple-pages', 'Article' ),
		'.key' => array( 'application/x-apple-keynote', 'Presentation' ),

		// Compressed
		'.zip' => array( 'application/zip', 'Archive'),
		'.gz'  => array( 'application/x-gzip', 'Archive' ),
		'.dmg' => array( 'application/x-apple-diskimage', 'Other' ),
		'.htmlwidget' => array( 'application/ww-htmlwidget', 'Other'),
		'.ofip' => array( 'application/x-ofip+zip', 'Other'), // Obsoleted, files can still be downloaded from the system
	)));
}

// Discontinued settings:
//    MIMEMAP:
//       Has been superseded by EXTENSIONMAP.

// -------------------------------------------------------------------------------------------------
// Ticket expiration - Logon ticket expiration time in seconds
// -------------------------------------------------------------------------------------------------
if( !defined('EXPIREDEFAULT') ) {
	define( 'EXPIREDEFAULT', 24 * 3600 );   // Generic logon expiration time. Default value: 24*3600.
}
if( !defined('EXPIREWEB') ) {
	define( 'EXPIREWEB', 3600 );      // Logon expiration time for Web based clients. Default value: 3600.
}

// -------------------------------------------------------------------------------------------------
// Versioning - Maximum number of versions to store per object type
// -------------------------------------------------------------------------------------------------
if( !defined('MAX_ARTICLE_VERSION') ) {
	define( 'MAX_ARTICLE_VERSION', 10 ); // also used for ArticleTemplate
}
if( !defined('MAX_IMAGE_VERSION') ) {
	define( 'MAX_IMAGE_VERSION', 5 );
}
if( !defined('MAX_VIDEO_VERSION') ) {
	define( 'MAX_VIDEO_VERSION', 5 );
}
if( !defined('MAX_AUDIO_VERSION') ) {
	define( 'MAX_AUDIO_VERSION', 5 );
}
if( !defined('MAX_LAYOUT_VERSION') ) {
	define( 'MAX_LAYOUT_VERSION', 5 ); // also used for LayoutTemplate, LayoutModule and LayoutModuleTemplate
}
if( !defined('MAX_LIBRARY_VERSION') ) {
	define( 'MAX_LIBRARY_VERSION', 5 );
}

// Discontinued settings:
//    CREATEVERSION_ONSTATECHANGE:
//       Obsoleted since v6.0. Has been replaced by the "Create Permanent Version" option for a Workflow Status. 
//    SAVEFIRST_ARTICLE_VERSION, SAVEFIRST_IMAGE_VERSION, SAVEFIRST_VIDEO_VERSION, 
//    SAVEFIRST_AUDIO_VERSION, SAVEFIRST_LAYOUT_VERSION, SAVEFIRST_LIBRARY_VERSION:
//       Obsoleted since v6.0. Enterprise now displays the full user name in all situations.

// -------------------------------------------------------------------------------------------------
// Multicasting - See /server/admin/mcast_control.php for more details.
// -------------------------------------------------------------------------------------------------

// MULTICAST_TTL:
//    Time To Live options for multicast messages. Possible values are: Scopes: 0=Host, 1=Subnet, 
//    32=Site, 64=Region, 128=Continent, 255=Unrestricted. Default value: 32.
//
if( !defined('MULTICAST_TTL') ) {
	define( 'MULTICAST_TTL', 32 );
}

// MULTICAST_IF:
//    The network interface used to send out multicast messages. If your server machine has more 
//    than one network card, make sure this setting points to the right interface. Default value: '127.0.0.1'.
//
if( !defined('MULTICAST_IF') ) {
	define( 'MULTICAST_IF', '127.0.0.1' );
}

// MC_MEDIATOR_ADDRESS:
//    Address on which Enterprise talks to the Multicast Mediator. Default value: '127.0.0.1'.
//
if( !defined('MC_MEDIATOR_ADDRESS') ) {
	define( 'MC_MEDIATOR_ADDRESS', '127.0.0.1' );
}

// MC_MEDIATOR_PORT:
//    A UDP port used for the IP address indicated by the MC_MEDIATOR_ADDRESS setting.
//    Default value: 8094.
//
if( !defined('MC_MEDIATOR_PORT') ) {
	define( 'MC_MEDIATOR_PORT', 8094 );
}

// -------------------------------------------------------------------------------------------------
// E-mail notification
// -------------------------------------------------------------------------------------------------

// EMAIL_SMTP:
//    SMTP server to send e-mail. For example: 'smtp.mydomain.com'. Default value: ''.
//
if( !defined('EMAIL_SMTP') ) {
	define( 'EMAIL_SMTP', '' );
}

// EMAIL_SMTP_USER:
//    Log-in user name for your SMTP server. Leave empty when log-in is not required. Default value: ''.
//
if( !defined('EMAIL_SMTP_USER') ) {
	define( 'EMAIL_SMTP_USER', '' );
}

// EMAIL_SMTP_USER:
//    Log-in user password for your SMTP server. Leave empty when log-in is not required. Default value: ''.
//
if( !defined('EMAIL_SMTP_PASS') ) {
	define( 'EMAIL_SMTP_PASS', '' );
}

// EMAIL_PORT:
//    SMTP server port. Allows overruling the SMTP server port. Default value: 25.
//
if( !defined('EMAIL_PORT') ) {
	define( 'EMAIL_PORT', 25 );
}

// EMAIL_SSL:
//    Whether or not to use a secure connection to the email server. Use 'ssl' or 'tls' SSL types  
//    for secure connections. Leave empty '' for no security. Default value: ''.
//
if( !defined('EMAIL_SSL') ) {
	define( 'EMAIL_SSL', '' );
}

// EMAIL_SENDER_ADDRESS:
//    E-mail address to use as the sender of the e-mail.
//    When left empty, the e-mail address defined on the User Maintenance page of the user who sent the e-mail is used instead.
//    Default value: ''.
//
if( !defined('EMAIL_SENDER_ADDRESS') ) {
	define( 'EMAIL_SENDER_ADDRESS', '' );
}

// EMAIL_SENDER_NAME:
//    User name to use for the sender of the e-mail.
//    When left empty, the name defined on the User Maintenance page of the user who sent the e-mail is used instead.
//    Default value: ''.
//
if( !defined('EMAIL_SENDER_NAME') ) {
	define( 'EMAIL_SENDER_NAME', '' );
}

// -------------------------------------------------------------------------------------------------
// InDesign Server / Web Editor settings
// -------------------------------------------------------------------------------------------------

// INDESIGNSERV_APPSERVER:
//    The logical name of the application server to logon to (from an InDesign Server point of view).
//    This should match the ServerInfo name in the WWSettings.xml file or the APPLICATION_SERVERS setting.
//
if( !defined('INDESIGNSERV_APPSERVER') ) {
	define( 'INDESIGNSERV_APPSERVER', SERVERNAME );
}

// INDESIGNSERV_JOBQUEUES:
//    For each job type a priority can be specified that overrules the default priority
//    that is hard-coded in the job implementation itself. High priority jobs are handled first.
//
//    There are 5 priorities:
//       1 = Very High    (foreground)
//       2 = High         (background)
//       3 = Medium       (background)
//       4 = Low          (background)
//       5 = Very Low     (background)
//
//    Specifying which Job priorities an InDesign Server instance should pick up is done on
//    the InDesign Server Maintenance page.
//
//    This priority system can also be seen as a job queue: on the one hand an InDesign Server
//    processes the highest priority jobs first while on the other hand it only processes jobs
//    of which the Job Priority matches the Job Priority configuration of the InDesign Server
//    instance.
//    Example: when a pool of InDesign Servers is configured to process a Job with priority 2 and 3,
//    they will never pick up a Job with priority 1, even when they are idle. When they are
//    all busy processing priority 3 Jobs while a priority 2 Job arrives, the priority 2 Job has
//    to wait until the first InDesign Server of that pool is ready processing its current Job.
//
//    Priority 1 is reserved for foreground jobs. Those are executed in the context of the workflow
//    operation itself while letting the end-user wait for the results. The other priorities are
//    reserved for background jobs. Those jobs are created by workflow operations without letting
//    the end-user wait for the results. A background process takes care of the job execution.
//
//    The job type for the preview operations of the Multi-Channel Text Editor in Content
//    Station is named 'WEB_EDITOR' and has a priority of 1 (Very High). This cannot be changed
//    because this job type runs in foreground.
//
//    The job type for the InDesign Server Automation feature is named 'IDS_AUTOMATION' and has a
//    default priority of 4 (Low). Because this job type runs in the background, it can be changed
//    into any value in the range of [2 - 5]. For normal usage there is no need to change the priority,
//    but when many custom job types are installed it could be useful to tweak the priorities.
//    Example: Two custom job types exist, one with priority 4 and the other with priority 5. It is
//    decided that both are less urgent to execute than the 'IDS_AUTOMATION' which has a default
//    priority of 4. In that case you may want to assign priority 3 to the InDesign Server Automation feature.
//    This can be done by adding the following line (without the leading //) to the INDESIGNSERV_JOBQUEUES option:
//       'IDS_AUTOMATION' => 3,
//
if( !defined('INDESIGNSERV_JOBQUEUES') ) {
	define( 'INDESIGNSERV_JOBQUEUES', serialize(array(
		// job type name => job priority number
		// ...
	)));
}

// WEBEDITDIR:
//    Defines the location where the Enterprise Server will put files for use by InDesign Server 
//    (including trailing /). By default this is a folder as defined by WOODWINGSYSTEMDIRECTORY in 
//    the config.php file. Its default values are: /FileStore/_SYSTEM_ for Mac OS / Linux and
//    c:/FileStore/_SYSTEM_ for Windows. Therefore, the default location of WEBEDITDIR is:
//       Mac OS:   /FileStore/_SYSTEM_/WebEdit
//       Linux:    /FileStore/_SYSTEM_/WebEdit
//       Windows:  c:/FileStore/_SYSTEM_/WebEdit
//    It is important that Enterprise Server has Read and Write access to this folder, which acts as user:
//       Mac OS:   'www user'
//       Linux:    'nobody'
//       Windows:  'IUSR_<machine>'
//
if( !defined('WEBEDITDIR') ) {
	define( 'WEBEDITDIR', WOODWINGSYSTEMDIRECTORY.'/WebEdit/' );
}

// WEBEDITDIRIDSERV:
//    Path to (mounted) WEBEDITDIR location from InDesign Server perspective (including trailing /).
//
if( !defined('WEBEDITDIRIDSERV') ) {
	define( 'WEBEDITDIRIDSERV', WOODWINGSYSTEMDIRECTORY.'/WebEdit/' );
}

// CURL:
//	InDesign Server background job needs to have CURL installed and defined.
//	The default path is '/usr/bin/curl. If Curl is stored on different location
//	you can uncomment the define and enter the location.
// define ('CURL', 'C:/<path>/curl.exe' );

// Discontinued settings: 
//    TEMPLATEDIR:
//       Obsoleted since v7.0. Use the Content Source server plug-in technology instead.
//    WEBEDIT_MAXELEMENTS, LABELHEIGHTS and WEBDISABLEMARKUP:
//       Obsoleted since v6.0.

// -------------------------------------------------------------------------------------------------
// Spelling settings
// -------------------------------------------------------------------------------------------------

// WORDCHARS_...:
//    For spelling checking, text needs to be split-up in words. This can be configured per dictionary.
//    You need to specify the characters that are part of words for that dictionary / language. That way,
//    symbols and numbers are excluded. For example, when the copyright symbol is sticked at the end of
//    a valid word, only the word is taken for spelling checking, without the symbol. Also characters 
//    from foreign languages can be excluded the same way (by simply not specifying). What characters to 
//    include is specified through ranges of Unicode (UTF-16) index numbers. A range is specified in such 
//    way that it can be used directly into regular expressions. For example 'A-Z' means all alphabetic 
//    characters in uppercase. 
//    The WORDCHARS_ options are helper defines for this job. Those can be filled in for the 'wordchars' 
//    option in the ENTERPRISE_SPELLING setting below. There it is placed as-is into the regular expression. 
//    Those expressions are sent to client applications (at logon) to let them split-up text into words. 
//    (Those words are sent through the spelling services of the workflow interface to let the server
//    perform the spelling checking.)
//    See http://localhost/Enterprise/server/wwtest/spelling/unicodeblocks.php to study the used Unicode block ranges.
//    See http://localhost/Enterprise/server/wwtest/spelling/workbench.php to study how words are split-up.
//
if( !defined('WORDCHARS_LATIN') ) {
	define( 'WORDCHARS_LATIN', 'A-Za-z-\x{00C0}-\x{024F}\x{1E00}-\x{1EFF}\x{0250}-\x{02AF}' ); // American and European languages
//                            L> Range [A-Za-z] includes alphabetic characters
//                            L> Range [00C0-024F] partially includes "Latin-1 Supplement" and includes "Latin Extended A" and "Latin Extended B"
//                            L> Range [1E00-1EFF] includes "Latin Extended Additional"
//                            L> Range [0250-02AF] includes "IPA Extensions" (International Phonetic Alphabet)
}
if( !defined('WORDCHARS_RUSSIAN') ) {
	define( 'WORDCHARS_RUSSIAN', WORDCHARS_LATIN.'\x{0400}-\x{0523}\x{2DE0}-\x{2DFF}\x{A640}-\x{A697}' ); // Russian language
//                            L> WORDCHARS_LATIN includes all ranges specified for Latin (see above).
//                            L> Range [0400-0523] includes "Cyrillic" and "Cyrillic Supplement"
//                            L> Range [2DE0-2DFF] includes "Cyrillic Extended-A"
//                            L> Range [A640-A697] includes "Cyrillic Extended-B"
}

// ENTERPRISE_SPELLING:
//    Defines 3rd party spelling dictionaries installed for Enterprise. This affects only articles in
//    Adobe InCopy CS5 format (or newer), as opened in the Multi Channel editor. This editor is shipped 
//    with Content Station 7.4 (or newer).
//
if( !defined('ENTERPRISE_SPELLING') ) {
	define('ENTERPRISE_SPELLING', serialize( array(
		/* configuration template:
		brand => array( // brand (database id for brand specific, zero for system-wide)
			dictionary => array( // display name of dictionary to be shown to end-users (must be unique per brand)
				'language'     => '', // Language code in [llCC] format (l = language code, C = county code). Used by custom Server Plug-ins to recognize a language and take action.
				'wordchars'    => '', // Valid characters that are used in words. (See detailed comments above.) Other characters are assumed to be word separators.
				'serverplugin' => '', // Internal name of the Server Plug-in that does the integration
				'location'     => '', // Full file path to executable, or web url. Leave empty for PHP integrations.
				'dictionaries' => array( '' ), // The names of dictionaries installed for the engine. Those are internal names, depending on the engine.
				'suggestions'  => #,  // The maximum number of suggestions to show end-users for a misspelled word.
				'doclanguage'  => '', // [Optional] Document's language code. Used to pre-select the dictionary for a certain article text fragment for spelling checking.
			),
		*/
		0 => array(
			'American English' => array( // Hunspell shell
				'language'     => 'enUS',
				'wordchars'    => '/(['.WORDCHARS_LATIN.']+)/u',
				'serverplugin' => 'HunspellShellSpelling',
				'location'     => '/opt/local/bin/hunspell',
				'dictionaries' => array( 'en_US' ),
				'suggestions'  => 10,
			),
			/*'American English' => array( // Google web
				'language'     => 'enUS',
				'wordchars'    => '/(['.WORDCHARS_LATIN.']+)/u',
				'serverplugin' => 'GoogleWebSpelling',
				'location'     => 'https://www.google.com/tbproxy/spell',
				'dictionaries' => array( 'en' ),
				'suggestions'  => 10,
			),*/
			/*'American English' => array( // Aspell shell
				'language'     => 'enUS',
				'wordchars'    => '/(['.WORDCHARS_LATIN.']+)/u',
				'serverplugin' => 'AspellShellSpelling',
				'location'     => '/opt/local/bin/aspell',
				'dictionaries' => array( 'en' ),
				'suggestions'  => 10,
			),*/
			/*'American English' => array( // Enchant PHP
				'language'     => 'enUS',
				'wordchars'    => '/(['.WORDCHARS_LATIN.']+)/u',
				'serverplugin' => 'EnchantPhpSpelling',
				'location'     => '/opt/local/bin/enchant', // only used for version checking
				'dictionaries' => array( 'en_US' ),
				'suggestions'  => 10,
			),*/
		), // Make sure this is included when dictionary is defined.
	)));
}

// -------------------------------------------------------------------------------------------------
// MadeToPrint server settings
// -------------------------------------------------------------------------------------------------
if( !defined('MTP_SERVER_DEF_ID') ) {
	define( 'MTP_SERVER_DEF_ID', '' ); // server name used to log-in to the Enterprise Server (WWSettings.xml)
}
if( !defined('MTP_USER') ) {
	define( 'MTP_USER', '' ); // the user name used to log-in in Enterprise
}
if( !defined('MTP_PASSWORD') ) {
	define( 'MTP_PASSWORD', '' ); // the password used to log-in in Enterprise
}
if( !defined('MTP_SERVER_FOLDER_IN') ) {
	define( 'MTP_SERVER_FOLDER_IN', '' ); // MTP input folder from server perspective
}
if( !defined('MTP_CALLAS_FOLDER_IN') ) {
	define( 'MTP_CALLAS_FOLDER_IN', '' ); // MTP input folder from MTP perspective
}
if( !defined('MTP_CALLAS_FOLDER_OUT') ) {
	define( 'MTP_CALLAS_FOLDER_OUT', '' ); // MTP output folder result from MTP perspective (the location where the resulting xml is placed)
}
if( !defined('MTP_JOB_NAME') ) {
	define( 'MTP_JOB_NAME', '' ); // //Default My Targets pane job. Fail-safe setting. If not defined in the server, this setting is used.
}
if( !defined('MTP_POSTPROCESS_LOC') ) {
	define( 'MTP_POSTPROCESS_LOC', SERVERURL_ROOT.INETROOT.'/server/MadeToPrintPostProcess.php' ); //location of the post processing file
}

// -------------------------------------------------------------------------------------------------
// Server Features
// -------------------------------------------------------------------------------------------------

// SERVERFEATURES:
//    Additional server or client application options that can be activated:
//       AlwaysSaveDocIntoDatabase
//          (Client feature.) When and InDesign or InCopy user uses the File > Save command, the 
//          file is not saved locally but saved to the server.
//       Broadcasting (default)
//          (Server feature.) Enables event mechanism.
//       ContentStationNumberOfItemsToPrefetch
//          For Content Station 9.5 up to 9.x
//          Sets the maximum number of files and objects for which the preview and metadata information should be prefetched from the server.
//          This makes sure that when a user selects a different file or object in a Document pane, the information is readily available.
//       ContentStationNumberOfSimultaneousDownloads
//          For Content Station 9.5 up to 9.x
//          Sets the maximum number of simultaneous downloads (such as thumbnails in the Publication Overview Application) that Content Station will handle.
//       CompanyLanguage (default)
//          (Server feature.) The company language is used as the default language. This means that 
//          when the user is not known yet, the language is displayed in the set company language. 
//          For example: the login dialog box could initially be displayed in the company language; 
//          once the user is logged in, the application switches to the language set in the users 
//          profile. Also, when a new user is created via the User Maintenance screen, the default 
//          language set is the company language. Default value: 'enUS'. Supported values: czCS, 
//          deDE, enUS, esES, frFR, itIT, jaJP, jaJP, koKR, nlNL, plPL, ptBR, ruRU, zhCN and zhTW.
//       EventPort (default)
//          (Server feature.) The port on which messages are sent. Default value: 8093.
//       FullResolutionGraphics
//          (Client feature) Allows InDesign to download image data from the sever when creating a 
//          page preview. Apart from this, InDesign is always allowed to download image data when 
//          creating PDFs or EPS files for production purposes (with output set for a state).
//       GeometryPreviewQuality
//          (Server feature.) Quality of the layout preview when opening an article in InCopy. 
//          Possible values: 1 (low), 2 (good), 3 (excellent), 4 (great).
//       HideMissingPages
//          Turns off the Show Missing Pages feature on the server. Useful for installations that 
//          use page numbers with huge gaps in between.
//       InCopyImages
//          (Server feature.) Allows InDesign users to include an image frame to an article. An 
//          InCopy user can then select the frame and place an image into the image frame (this could 
//          either be a local image or an image stored within Enterprise). When the feature is not 
//          enabled (which is default), the InDesign user can select the frame and check the article 
//          in but the frame is not identified as being part of the article.
//       Messaging (default)
//          (Server feature.) Allows Users to send messages to other users.
//       KeepCheckedOut (default)
//          (Client feature.) Enables the Close for Offline Usage command in the Smart Connection 
//          menu of InDesign and InCopy.
//       SuppressCreateImagePreview
//          (Server feature.) Specifies that ImageMagick should be used to generate thumbnails, 
//          previews and metadata of created or updated images.
//       SuppressGeometryPreview
//          (Server feature.) Suppresses the layout preview information that is carried by geometry files.
//          This improves performance but prevents InCopy users from viewing the opened article in the layout.
//       UseXMLGeometry
//          (Server feature.) Lets InDesign save a tiny geometry file and geometry preview file so 
//          that no large layout file needs to be loaded when the article is opened in InCopy. 
//          Note 1: Currently the XML Geometry Update feature is not working with Editions and 
//                  not fully working with Sticky Notes.
//          Note 2: When UseXMLGeometry is enabled, UPDATE_GEOM_SAVE must be disabled and vice versa.
// 		DateFormat
//			Formatter used by ContentStation to format dates, for example DD/MM/YYYY or MM-DD-YYYY
//			This format is also used for the date input components. The width of these components is fixed to 
//			10 characters
//			Formatting options:
//				Y	Year. If the number of pattern letters is two, the year is truncated to two digits; otherwise, 
//					it appears as four digits. The year can be zero-padded, as the third example shows in the following set of examples: 
//					Examples:
//						YY = 05
//						YYYY = 2005
//						YYYYY = 02005
//				M	Month in year. The format depends on the following criteria:
//					If the number of pattern letters is one, the format is interpreted as numeric in one or two digits.
//					If the number of pattern letters is two, the format is interpreted as numeric in two digits.
//					If the number of pattern letters is three, the format is interpreted as short text.
//					If the number of pattern letters is four, the format is interpreted as full text.
//					
//					Examples:
//						M = 7
//						MM= 07
//						MMM=Jul
//						MMMM= July
//				D	Day in month. While a single-letter pattern string for day is valid, you typically use a two-letter pattern string.
//					Examples:
//						D=4
//						DD=04
//						DD=10
//				Other text	You can add other text into the pattern string to further format the string. You can use punctuation, numbers, and all lowercase letters. 
//				You should avoid uppercase letters because they may be interpreted as pattern letters.
//		TimeFormat
//			Formatter used by ContentStation to format times, for example HH:NN:SS (01:11:12), L:NN A (1:12 AM)
//			Formatting options:
//				E	Day in week. The format depends on the following criteria:
//					If the number of pattern letters is one, the format is interpreted as numeric in one or two digits.
//					If the number of pattern letters is two, the format is interpreted as numeric in two digits.
//					If the number of pattern letters is three, the format is interpreted as short text.
//					If the number of pattern letters is four, the format is interpreted as full text.
//					Examples:
//						E = 1
//						EE = 01
//						EEE = Mon
//						EEEE = Monday
//				A	 am/pm indicator.
//				J	Hour in day (0-23).
//				H	Hour in day (1-24).
//					Examples:
//						H = 1
//						HH = 01
//				K	Hour in am/pm (0-11).
//				L	Hour in am/pm (1-12).
//				N	Minute in hour. 
//					Examples:
//						N = 3
//						NN = 03
//				S	Second in minute. 
//					Examples:
//						SS = 30
//		UseTwelveHourFormat
//			If set Contentstation will use the 12 hour format for the time input components
//			For ContentStation up to 9.x
//      CSPlainText
//			If set ContentStation will use plain text for new articles
//			For ContentStation up to 9.x
//		ContentStationRTL
//			Enables right-to-left support in ContentStation
//			For ContentStation up to 9.x
//		PublicationOverviewCombineRequests
//			This will combine the requests for the thumbs and previews in the publication overview into one request. This can fix communication errors with IIS or proxy servers
//			Note: A downside of this is that the user does not see any thumbs or previews before the complete request has finished.
//			For ContentStation up to 9.x
//		ClientFeedback
//			This will enable the diagnosis functionality at client application side. The user can send feedback or a crash report is send when
//			the client application encounters an unexpected error.
//			For ContentStation up to 9.x
//      ContentStationUseWWEAEditor
//			For ContentStation 7.4 up to 9.x
//			If set ContentStation uses the wwea editor instead of the multi channel editor
//      ContentStationDisableEditorPreview
//			For ContentStation 7.4 and higher
//          If set the preview in the multi channel editor is disabled
//      ContentStationReadOnlyEditor
//			For Content Station 10 and higher
//          Articles in Content Station 10 are always opened in read/write mode, unless this feature is set
//		ContentStationAcceptAllChanges
//			For Content Station 10 and higher
//			If set, Content Station 10 will accept all changes when opening an article, otherwise when opening an article
//			with changes the editor will show an error
//		MaxPDFPreviewSize (default)
//			For ContentStation 7.6.7 up to 9.x
//			If set, Content Station will download the 'native' PDF as long as the filesize of the PDF does not exceed
//			the maximum. The download refers to viewing the PDF in the 'Preview' pane. In case the PDF file exceeds the
//			maximum, Content Station will ask for the preview of the PDF. The size is in Kb. So 1024 equals 1 Mb. Default value: 1024.
//		JPEGQualityForPDFImages
//			(Client feature.) Specifies the quality to be used for JPEG images in PDFs exported to Adobe DPS.
//			Possible values:
//			max : Best quality
//			high : Excellent quality
//			med : Good quality
//			low : Good quality
//			min : Fair quality
//			The default value is 'high'.
//		ResolutionForPDFImages
//			(Client feature.) Specifies the image resolution to be used for images in content and overlay assets for PDFs exported to Adobe DPS.
//			The value is specified in pixels per inch (ppi) and must be greater than 0 (zero).
//			The default value is 144.
//		ContentStationHideGlobalNewArticle
//			Hide the new article button in the home tab of Content Station
//			For ContentStation up to 9.x
//		UseElementLabelOrder
//			(Client feature for Smart Connection v8.3 or higher). When an
//			InDesign user creates a new article or article template, the order of the
//			components is based on the order of the Element Labels as defined in the
//			Element Label preferences. If this feature is not enabled, the order of
//			the components is based on the position of the frames on the page (the
//			same order as used in Smart Connection v8.2 or lower, namely from top-left to bottom-right).
//		PublicationOverviewMaxGridViewZoom
// 			The PublicationOverviewMaxGridViewZoom setting limits the maximum zoom level in the Publication Overview.
// 			By default, when the zoom level surpasses the maximum size of the thumbnail preview (default size 255 pixels), 
// 			page previews are loaded instead. This can negatively affect performance.
// 			By matching the PublicationOverviewMaxGridViewZoom setting to the maximum thumbnail preview size, zooming is restricted 
// 			up to that size and page previews are prevented from being loaded.
// 			The default setting of PublicationOverviewMaxGridViewZoom would therefore be '255'.
//			For ContentStation up to 9.x
//		RespectPlannedAdvertGeometry
//			For Smart Connection.
//			The RespectPlannedAdvertGeometry setting controls if geometry changes(location and dimension) of an Advert 
//			page item are respected (accepted) or not.
//			When enabled, the location and dimension of the advert as defined by the Planning System is always respected.
//			When not enabled, the location and dimension of the Advert may be changed by the user.
//		MulticastGroup
//			Address of the multicast group.
//			Either 'Broadcasting' or 'Multicasting' is used to send messages to the client applications.
//			When Multicast is enabled, Broadcasting must be disabled and vice versa.
//			Default value: '224.0.252.1'
//      UpdateAllContentAfterOpenArticle
//          (Client feature for Smart Connection v10.0.1 or higher) Specifies that when opening an article in InCopy
//          that is placed on a layout, all other content on the layout should also be updated. Because updating all
//          content will require more interaction with the server, this setting has impact on the performance of opening
//          InCopy files. The advantage is that always the latest information on the layout is displayed in the
//          InCopy Layout View mode.
//      MCEHideListButtons
//          For Content Station 9.5.1 up to 9.x
//          When specific paragraph styles are set up for creating numbered lists and bulleted lists, it is important
//          that the user uses those styles and not uses the buttons in the toolbar for creating such lists.
//          Using this feature, the buttons for creating lists are hidden from the toolbar in the Multi Channel Editor.
//      ContentStationInlineArticleCompare
//          Enables the Inline Article Compare feature in Content Station 9.6 up to 9.x.
//          When enabled, Track Changes are automatically disabled in Content Station.
if( !defined('SERVERFEATURES') ) {
	define ('SERVERFEATURES', serialize(array(
		new Feature( 'Messaging' ),
		new Feature( 'Broadcasting' ),
		new Feature( 'EventPort' , 8093 ),
		new Feature( 'CompanyLanguage' , 'enUS'),
		new Feature( 'KeepCheckedOut' ),
	)));
}

// Discontinued SERVERFEATURES options: 
//    ServerCreateImagePreview:
//       Obsoleted since v6.0. Has been replaced by the ImageMagick Preview server plug-in.
//    StoreSettings:
//       Obsoleted since v6.0.

// -------------------------------------------------------------------------------------------------
// Client features
// -------------------------------------------------------------------------------------------------

// CLIENTFEATURES:
//    Additional client application specific options that can be activated:
//       CreatePageEPS
//          (SC for InDesign feature.) When a Layout is saved in the Filestore, for each page
//          an EPS file is generated and uploaded along with the document. When a page varies per Edition,
//          for each Edition an EPS file is generated. 
//          It is not allowed to have both CreatePagePDF and CreatePageEPS options enabled.
//       CreatePageEPSOnProduce
//          (SC for InDesign feature.) Same as CreatePageEPS, but only when the Layout is saved in a Status
//          for which the Output option is selected in the Workflow Status options.
//          It is not allowed to have both CreatePagePDFOnProduce and CreatePageEPSOnProduce options enabled.
//          This option has no effect when the CreatePageEPS option is enabled.
//       CreatePagePDF
//          (SC for InDesign feature.) When a layout is saved in the Filestore, a PDF file is generated for each page
//          and uploaded along with the document. When a page varies per Edition, a PDF file is generated for each Edition.
//          A value can be specified for this option which should match one of the Adobe PDF Presets.
//          Example: '[Smallest File Size]'.
//          When this option is defined without a PDF preset value, the '[Press Quality]' will be used as the default
//          preset.
//          It is not allowed to have both CreatePagePDF and CreatePageEPS options enabled.
//       CreatePagePDFOnProduce (default)
//          (SC for InDesign feature.) Same as CreatePagePDF, but only when the layout is saved in a status for which
//          the Output option is selected in the Workflow Status options.
//          A value can be specified for this option which should match one of the Adobe PDF Presets.
//          Example: '[High Quality Print]'.
//          Default value: '[Press Quality]'.
//          This option can be left empty if the Workflow Status name is the same as the PDF preset name and when the
//          'Output' option is selected for that Workflow Status.
//          This option supersedes the CreatePagePDF option for Statuses that have the Output option selected.
//       CreatePagePreview (default)
//          (SC for InDesign feature.) When a Layout is saved in the Filestore, for each page
//          a preview (JPEG) file is generated and uploaded along with the document. When a page varies per Edition,
//          for each Edition a preview (JPEG) file is generated.
//          This option can be combined with the CreatePagePDF(OnProduce) or CreatePageEPS(OnProduce) options.
//       CreatePagePreviewOnProduce
//          (SC for InDesign feature.) Same as CreatePagePreview, but only when the Layout is saved in a Status
//          for which the Output option is selected in the Workflow Status options.
//          This option has no effect when the CreatePagePreview option is enabled.
//       PagePreviewQuality
//          (SC for InDesign feature.) Controls the quality (compression) of the Layout page previews, as shown in the
//          Publication Overview application in Content Station or preview panes in Smart Connection or Content Station. 
//          This option is only affective when the CreatePagePreview or CreatePagePreviewOnProduce option is enabled too.
//          Possible values: 1 (low), 2 (good), 3 (excellent), 4 (great [default])
//       PagePreviewResolution
//          (SC for InDesign feature.) Controls the resolution (DPI) of the Layout page previews, as shown in the
//          Publication Overview application in Content Station or preview panes in Smart Connection or Content Station.
//          This option is only affective when the CreatePagePreview or CreatePagePreviewOnProduce option is enabled too.
//       PageSyncDefaultsToNo (default)
//          (Smart Connection for InDesign feature.) When produced pages are out-of-sync with planned pages, Smart Connection
//          shows a message asking the user if the produced pages should be synchronized with the planned pages.
//          The user can choose between Yes and No. By default, the Yes button is enabled.
//          Note that synchronizing the pages may result in content loss (pages that are removed may contain content).
//          It is therefore safer to set the No button as the default button. Do this by enabling the PageSyncDefaultsToNo option.
//          This is especially important for InDesign Server which always uses the default button.
//
//          Changing the behavior for InDesign Server
//          For InDesign Server, the PageSyncDefaultsToNo option can be set either in the configserver.php file or
//          in the WWSettings.xml file. Note that for the Indesign Server Automation feature, the PageSyncDefaultsToNo
//          option is already enabled in the configserver.php file.
//          Changing the behavior for InDesign
//          For InDesign, the PageSyncDefaultsToNo option can only be enabled by setting the SCEnt:PageSyncDefaultsToNo
//          option in the WWSettings.xml file. (InDesign ignores the option set in the configserver.php file.)
//
//    Define where the generation of layout previews, thumbnails, PDFs or DPS articles 
//    should or may take place: in InDesign 'local', InDesign 'remote' or in InDesign Server.
//    Use any of the features that are listed in the description above.
//    
//    Structure:
//    Level 1: should be a client app name such as 'InDesign' or InDesign Server'.
//    Level 2 for 'InDesign' should be 'local' or 'remote'.
//    Level 2 for 'InDesign Server' should be 'default' and 'IDS_AUTOMATION'.
//    Level 3: Any feature.
//       
//    InDesign Workflow:
//    Client logs in. When a matching client name is found on the 1st level of the structure,
//    the client location is determined by the server by checking the client IP against the 
//    REMOTE_LOCATIONS_INCLUDE and REMOTE_LOCATIONS_EXCLUDE options.
//    When the client is 'local', the configured features under the 'local' entry of the structure 
//    are taken, else those under 'remote'.
//    
//    InDesign Server Workflow:
//    The entries 'default' and 'IDS_AUTOMATION' refer to InDesign Server job types.
//    When InDesign Server logs in, it will use the ticket to check if there is a matching job in  
//    the InDesign Server job queue. 
//    When found, it will resolve the job type and take the features listed under the matching job type entry. 
//    When not found, it will take the features listed under the 'default' entry.
//    
//    Notes:
//    - The structure can also be used by custom Server plug-ins that introduce custom InDesign Server job types
//      by simply adding another job type to the structure.
// 
//    - When features are found in the CLIENTFEATURES option, they are merged with the options configured 
//      for SERVERFEATURES and sent back to the client that is about to log on.
// 
//    - It is advisable to have at least the CreatePagePreview feature listed for every 2nd level to make sure 
//      that the Publication Overview always shows a preview for each page.
// 
//    - When InDesign Server is installed, and you would like to offload the PDF creations from the client, 
//      remove the CreatePagePDFOnProduce feature under InDesign > local and InDesign > remote and add it under 
//      InDesign Server > IDS_AUTOMATION in the structure.    
// 
//    - For remote workers with a slow internet connection, consider improving the upload speed by leaving out 
//      the PDF from the upload stream. Do this by removing the CreatePagePDFOnProduce feature from InDesign > remote.
//      Alternatively you can let InDesign generate a low-quality preview and PDFs with the CreatePagePDFOnProduce 
//      and PagePreviewQuality features, but let InDesign Server generate a high-quality preview by providing the 
//      feature on both levels but with different values.
//
if( !defined('CLIENTFEATURES') ) {
	define ('CLIENTFEATURES', serialize(array(
		'InDesign' => array(
			'local' => array(
				new Feature( 'CreatePagePreview' ),
				new Feature( 'CreatePagePDFOnProduce', '[Press Quality]' ), // Consider removing this option when IDS Automation is installed.
			),
			'remote' => array(
				new Feature( 'CreatePagePreview' ),
			),
		),
		'InDesign Server' => array(
			'default' => array(
				new Feature( 'CreatePagePreview' ),
			),
			'IDS_AUTOMATION' => array(
				new Feature( 'CreatePagePreview' ),
				new Feature( 'CreatePagePDFOnProduce', '[Press Quality]' ),
				new Feature( 'PageSyncDefaultsToNo' ),
			),
		),
	)));
}

// -------------------------------------------------------------------------------------------------
// Date/Time display patterns
// -------------------------------------------------------------------------------------------------

// LANGPATDATE:
//    Controls date display in the Maintenance applications. Default value: 'd-m-y'.
//
if( !defined('LANGPATDATE') ) {
	define( 'LANGPATDATE', 'd-m-y' );
}

// LANGPATAMPM:
//    Controls time display in the Maintenance applications and date/time formatting for creating/using 
//    custom metadata property of type "date" or "datetime". Possible values true (for am/pm time display) 
//    or false. Default value: false.
//
if( !defined('LANGPATAMPM') ) {
	define( 'LANGPATAMPM', false );
}

// LANGPATTIMEDIFF:
//    Controls time display in the Maintenance applications. Shows the time in relative format using 
//    letters (TSMTag/Stunde/Minute for German, DUMDag/Uur/Minuut for Dutch, etc.). Default value: 'DHM'.
//
if( !defined('LANGPATTIMEDIFF') ) {
	define( 'LANGPATTIMEDIFF', 'DHM' );
}

// -------------------------------------------------------------------------------------------------
// Update geometry
// -------------------------------------------------------------------------------------------------

// UPDATE_GEOM_SAVE:
//    Notifies an InDesign/InCopy user when a geometry update is available by displaying a dialog box.
//    Possible options are ON/OFF. Default value: 'OFF'.
//    Note: The UseXMLGeometry option of SERVERFEATURES setting is not compatible with the 
//          UPDATE_GEOM_SAVE setting. (When UseXMLGeometry is enabled, UPDATE_GEOM_SAVE needs to be
//          disabled and vice versa.) The Update will not be sent if UseXMLGeometry is enabled.
//
if( !defined('UPDATE_GEOM_SAVE') ) {
	define( 'UPDATE_GEOM_SAVE', 'OFF' );
}

// -------------------------------------------------------------------------------------------------
// Personal status
// -------------------------------------------------------------------------------------------------

// PERSONAL_STATE:
//    The Personal status feature adds an additional Workflow status to the Enterprise system named 
//    Personal. Possible options are ON/OFF. Default value: 'OFF'.
//    When a user sets a file to this status, the following takes place:
//    - The file is not made part of the production workflow but is available to the user for "private" use.
//    - The file is automatically routed to the user.
//    The following users have access to files set to the Personal status:
//    - The user (or users, when routed to a user group) to whom the file has been routed to.
//      (If routed to a user group all users of that group will have access tot the file.
//    - Any admin users (users that belong to a user group that has got the Admin option set).
//
if( !defined('PERSONAL_STATE') ) {
	define( 'PERSONAL_STATE', 'OFF' );
}

// PERSONAL_STATE_COLOR:
//    Color of Personal status. Default value '#F6A124' (orange).
//
if( !defined('PERSONAL_STATE_COLOR') ) {
	define( 'PERSONAL_STATE_COLOR', '#F6A124' );
}

// -------------------------------------------------------------------------------------------------
// Deadline settings
// -------------------------------------------------------------------------------------------------

// DEADLINE_WARNTIME:
//    Seconds before a deadline that counts as the warning period (soft deadline). Default value: 2*24*3600.
//
if( !defined('DEADLINE_WARNTIME') ) {
	define( 'DEADLINE_WARNTIME', 2 * 24 * 3600 );
}

// NONWORKDAYS:
//    Defines the days of the week NOT being worked. Sunday=0, Monday=1, ..., Saturday=6. Default values: 0,6
//
if( !defined('NONWORKDAYS') ) {
	define ('NONWORKDAYS', serialize(array(
			0, 6
	)));
}

// HOLIDAYS:
//   Days specified as a non-working day. Possible setting: y-m-d or m-d. By including the year (y) 
//   makes it specific to that year only. When leaving out the year (y), it means each year.
//
if( !defined('HOLIDAYS') ) {
	define ('HOLIDAYS', serialize(array(
			'01-01',				// New Year
			'04-30',				// Queen's Day (Dutch; Birthday of Queen-Mother Juliana)
			'12-25', '12-26',		// Christmas
			// 2012
			'2012-04-06',			// Good Friday (Christian; Friday before Easter)
			'2012-04-09',			// Easter Monday (Christian; Monday after Easter)
			'2012-05-17',			// Ascension Day [Hemelvaart] (Dutch; Thursday, 40 days after Easter)
			'2012-05-28',			// Pentecost Monday [Pinksteren] (Dutch; Monday after Pentecost)
			// 2013
			'2013-03-29',			// Good Friday (Christian; Friday before Easter)
			'2013-04-01',			// Easter Monday (Christian; Monday after Easter)
			'2013-05-09',			// Ascension Day [Hemelvaart] (Dutch; Thursday, 40 days after Easter)
			'2013-05-20',			// Pentecost Monday [Pinksteren] (Dutch; Monday after Pentecost)
			// 2014
			'2014-04-18',			// Good Friday (Christian; Friday before Easter)
			'2014-04-21',			// Easter Monday (Christian; Monday after Easter)
			'2014-05-29',			// Ascension Day [Hemelvaart] (Dutch; Thursday, 40 days after Easter)
			'2014-06-09',			// Pentecost Monday [Pinksteren] (Dutch; Monday after Pentecost)
	)));
}

// -------------------------------------------------------------------------------------------------
// First day of the week
// -------------------------------------------------------------------------------------------------
// When a user searches the system by 'Last week' or 'Next week', the defined first day of the week is used as the starting point.

// FIRST_DAY_OF_WEEK:
//    First working day of the week, Sunday = 0, Monday = 1, ..., Saturday = 6. Default value: 1.
//
if( !defined('FIRST_DAY_OF_WEEK') ) {
	define( 'FIRST_DAY_OF_WEEK', 1 );
}

// -------------------------------------------------------------------------------------------------
// Password management configuration
// -------------------------------------------------------------------------------------------------
if( !defined('PASSWORD_EXPIRE') ) {
	define( 'PASSWORD_EXPIRE', 90 );         // default for UI: days a password expires: 90 days, 0 = never
}

if( !defined('PASSWORD_MIN_CHAR') ) {
	define( 'PASSWORD_MIN_CHAR', 1 );      // Minimum number of characters that a password should have. (0 = no restriction)
}
if( !defined('PASSWORD_MIN_LOWER') ) {
	define( 'PASSWORD_MIN_LOWER', 0 );      // Minimum number of lower case characters that a password should have. (0 = no restriction)
}
if( !defined('PASSWORD_MIN_UPPER') ) {
	define( 'PASSWORD_MIN_UPPER', 0 );      // Minimum number of upper case characters that a password should have. (0 = no restriction)
}
if( !defined('PASSWORD_MIN_SPECIAL') ) {
	define( 'PASSWORD_MIN_SPECIAL', 0 );      // Minimum number of special characters that a password should have. (0 = no restriction)
}

// -------------------------------------------------------------------------------------------------
// Path to private RSA encryption key and base64 encoded public key
// Using these options can have a significant performance hit on password encryptions done on server-side.
// -------------------------------------------------------------------------------------------------
// To do: As long as there is a 4-second delay, password encryption is not enabled (default setting)
//define ('ENCRYPTION_PRIVATEKEY_PATH', BASEDIR.'/config/encryptkeys/privkey_1024.pem' ); // use forward slashes
//define ('ENCRYPTION_PUBLICKEY_PATH',  BASEDIR.'/config/encryptkeys/pubkey_1024.pem' );  // use forward slashes

// -------------------------------------------------------------------------------------------------
// Sorting query results 
// -------------------------------------------------------------------------------------------------
// SORT_ON_STATE_ORDER:
//	Defines if query results should be sorted on sorting order or on the names of states (alphabetically).
//	Solr sorts the states  on name. In case the states must be sorted on sorting order the query will not be
//	passed to Solr but directly to the database. This can have a negative impact on performance.
//	If Solr is not used  the states are always sorted on sorting order.
//	Default value: false
if( !defined('SORT_ON_STATE_ORDER') ) {
	define( 'SORT_ON_STATE_ORDER', false );
}

// -------------------------------------------------------------------------------------------------
// Web server entry points
// -------------------------------------------------------------------------------------------------

// SERVERURL:
//    Server web root location (URL). This is the workflow SOAP entry point from external(!) point
//    of view. Should NOT be used by SOAP clients written in PHP; use LOCALURL_ROOT base instead!
//
if( !defined('SERVERURL') ) {
	define( 'SERVERURL', SERVERURL_ROOT.INETROOT.'/index.php' );
}

// APPLICATION_SERVERS:
//    List of available application servers (returned through GetServersReponse SOAP call).
//    To return only 'this' application server, just comment out the APPLICATION_SERVERS definition as follows:
//       //define( 'APPLICATION_SERVERS', serialize( array() ) );
//    To return NO application servers (and so let clients use wwsettings.xml), define empty APPLICATION_SERVERS as follows:
//       define( 'APPLICATION_SERVERS', serialize( array() ) );
//
if( !defined('APPLICATION_SERVERS') ) {
	define( 'APPLICATION_SERVERS', serialize( array(
	   //  ServerInfo( Name, URL [, Developer] [, Implementation] [, Technology] [, Version] [, array of Feature] [, Cryptkey (file path)] )
	   new ServerInfo( SERVERNAME, SERVERURL, SERVERDEVELOPER, SERVERIMPLEMENTATION, SERVERTECHNOLOGY, SERVERVERSION,
	                  unserialize(SERVERFEATURES), defined('ENCRYPTION_PUBLICKEY_PATH') ? ENCRYPTION_PUBLICKEY_PATH : null ), // this server
	   new ServerInfo( 'WoodWing.net', 'http://demo.woodwing.net/Enterprise/index.php',
	                  SERVERDEVELOPER, SERVERIMPLEMENTATION, SERVERTECHNOLOGY,
	                  '', 	// version unknown - may not be null
	                  array() )// feature set unknown - may not be null
	)));
}

// NETWORK_DOMAINS:
// Obsolete since version 8.0, domains are picked up from LDAP_SERVERS.

// -------------------------------------------------------------------------------------------------
// Proxy Server
// -------------------------------------------------------------------------------------------------

// ENTERPRISE_PROXY:
//    The proxy is a secure gateway through which Enterprise Server can safely access external servers.
//    By default, the proxy is disabled but it can optionally be configured.
//    Although this is a generic setting, currently only the Adobe DPS Server integration is using it.
//    
//    When all the proxy options are commented out (by starting with // characters) the proxy is 
//    disabled. To enable it, at least the proxy_host option must be uncommented (by removing the 
//    leading // characters) and a valid IP or server name needs to be specified (note that specifying 
//    a URL does not work.) When the proxy server listens for other network on a port other than 1080 
//    (=default), also the proxy_port must be uncommented and a valid number needs to be filled in. 
//    Do NOT place the port number between quotes.
//    
//    Optionally, Basic Authentication can be enabled, for which the proxy_user and proxy_pass should 
//    be uncommented and specified with the user credentials as registered on the proxy server.
//    
//    To make the proxy secure, it should run over an SSL connection. The proxy should therefore
//    support HTTPS / SSL. The proxy server should also support the HTTP CONNECT method since 
//    Enterprise Server tries to shake hands with the proxy before it connects to the external server. 
//    
if( !defined('ENTERPRISE_PROXY') ) {
	define( 'ENTERPRISE_PROXY', serialize( array(
	//	'proxy_host' => '',  // Required: IP or network name. (Do NOT specify a URL.)
	//	'proxy_port' => 0,   // Optional: Network port the proxy is listening for. Default 1080. (Do not use quotes.)
	//	'proxy_user' => '',  // Optional: User name as registered on the proxy server.
	//	'proxy_pass' => '',  // Optional: User password as registered on the proxy server.
	)));
}

// -------------------------------------------------------------------------------------------------
// CA Bundle
// -------------------------------------------------------------------------------------------------

// ENTERPRISE_CA_BUNDLE:
//    The Enterprise CA Bundle contains a list of CA certificates for secure connections. This file
//    is used by cURL to check the certificate of the server to connect to.
//
//    Currently this option is only used by the Adobe DPS Server integration. The Adobe DPS health check
//    will check this file. When the file doesn't exist, it will be created.
//
if( !defined('ENTERPRISE_CA_BUNDLE') ) {
	define( 'ENTERPRISE_CA_BUNDLE', WOODWINGSYSTEMDIRECTORY.'/Certificates/ca-bundle.crt' );
}

// -------------------------------------------------------------------------------------------------
// Cookies
// -------------------------------------------------------------------------------------------------

// COOKIES_OVER_SECURE_CONNECTIONS_ONLY:
//    If the Enterprise Server instance is only accessible over secure (HTTPS) connections,
//    this setting can be set to 'true' for extra security. Clients that support
//    cookie based authentication are forced to send cookies only over a secure connection.
//    When direct access from InDesign Server or direct access to the Admin pages over a regular (HTTP)
//    connection is needed, this setting can't be set to true.
//    True to enable. Default value: false.
//
if( !defined('COOKIES_OVER_SECURE_CONNECTIONS_ONLY') ) {
	define( 'COOKIES_OVER_SECURE_CONNECTIONS_ONLY', false );
}

/*
// -------------------------------------------------------------------------------------------------
// List of LDAP servers. See '/server/dataclasses/LDAPServer.class.php' for more info.
// -------------------------------------------------------------------------------------------------
require_once BASEDIR.'/server/dataclasses/LDAPServer.class.php';

// Options for the LDAP server.
// To manage groups in Enterprise and not LDAP set GROUPMEMBER_ATTRIB to null.
//    For Windows Active Directory usually: 
//       'AUTH_USER' => '%username%@myldap.mycompany.local', 
//       'AUTH_PASSWORD' => '%password%', 
//       'USERNAME_ATTRIB' => 'sAMAccountName',
//       'GROUPMEMBER_ATTRIB' => 'memberof', 
//       'ATTRIB_MAP' => array('FullName' => array( 'name' ), 'EmailAddress' => 'mail'), 
//		 'FULLNAME_SEPARATOR' => ', ',
//       'GROUP_CLASS' => 'group',
//       'EXCLUDE_USERNAMES' => array('woodwing'),
//       'EMAIL_NOTIFICATIONS' => true
//    For OpenLDAP usually: 
//       'AUTH_USER' => null, 
//       'AUTH_PASSWORD' => null, 
//       'USERNAME_ATTRIB' => 'uid', 
//       'GROUPMEMBER_ATTRIB' => 'memberof',
//       'ATTRIB_MAP' => array('FullName' => array( 'name' ), 'EmailAddress' => 'mail'),
//		 'FULLNAME_SEPARATOR' => ', ', 
//       'GROUP_CLASS' => 'posixGroup',
//       'EXCLUDE_USERNAMES' => array('woodwing'),
//       'EMAIL_NOTIFICATIONS' => true
//
$ldap_options = array(
	'AUTH_USER' => '%username%@myldap.mycompany.local', // %username% will be replaced by entered username
	'AUTH_PASSWORD' => '%password%',                    // %password% will be replaced by entered password
	'BASE_DN' => 'DC=myldap,DC=mycompany,DC=local',     // Search Base e.g. 'dc=myldap,dc=mycompany,dc=local'
	'USERNAME_ATTRIB' => 'sAMAccountName',              // LDAP attribute that will be matched against entered username
	'GROUPMEMBER_ATTRIB' => 'memberof',                 // LDAP attribute that will be used to find usergroups, null if you manage groups in Enterprise
	// Map Enterprise attributes to LDAP attributes. Only Ent att. 'FullName', 'EmailAddress', 
	// 'Language', 'TrackChangesColor', 'Organization', 'Location' are allowed.
 	// The FullName can be made up from more than one LDAP attribute.
	// LDAP attributes are always lowercase.
	'ATTRIB_MAP' => array( 
		'FullName' => array( 'name' ),
		'EmailAddress' => 'mail'), 
 	'FULLNAME_SEPARATOR' => ', ',						// Used when the FullName is made up from two (or more) LDAP attributes
	'GROUP_CLASS' => 'group',                           // LDAP objectClass for groups (e.g. 'group', 'posixGroup')
	'EXCLUDE_USERNAMES' => array('woodwing'),           // Usernames to exclude from LDAP authentication, wildcards (*, ?) can be used
	'EMAIL_NOTIFICATIONS' => true						// Whether or not the email notification options should be enabled for new users imported from LDAP
);

// The port number is mandatory in case an IP-address is used. It will be ignored when an URL is used.
if( !defined('LDAP_SERVERS') ) {
	define( 'LDAP_SERVERS', serialize( array(
	   //  LDAPServer( LDAP server IP, port number, Primary DNS Suffix, Options: see above )
	   new LDAPServer( 'myldap_server', 389, 'myldap.mycompany.local', $ldap_options )
	)));
}
*/

// -------------------------------------------------------------------------------------------------
// Diagnostics settings 
// -------------------------------------------------------------------------------------------------

// DIAGNOSTICS_EMAIL_TO:
//    Enter the email addresses to send the diagnostics to. Normally an email is sent to WoodWing Support.
//    Another address can be added to send the reports to e.g. the system administrator. 
//	  Default value: 'address' => 'diagnostics@woodwing.com', fullname' => 'WoodWing Diagnostics' 
if( !defined('DIAGNOSTICS_EMAIL_TO') ) {
	define ('DIAGNOSTICS_EMAIL_TO', serialize( array(
		array( 'address' => 'diagnostics@woodwing.com', 'fullname' => 'WoodWing Diagnostics' ),
	//	array( 'address' => '<Your email address>', 'fullname' => '<Your name>' ),
	)));
}

// DIAGNOSTICS_EMAIL_FROM:
//    Enter the 'from' email addresses used for the diagnostice reporting.
//    Make sure that a valid email address is added so the email containing the diagnostic report is not blocked.
if( !defined('DIAGNOSTICS_EMAIL_FROM') ) {
	define ('DIAGNOSTICS_EMAIL_FROM', serialize(
		array('address' => '', 'fullname' => '' )
	));
}

// -------------------------------------------------------------------------------------------------
// ImageMagick settings
// -------------------------------------------------------------------------------------------------

// IMAGE_MAGICK_APP_PATH:
//    Full path to the folder of the ImageMagick application.
//    Use forward slashes and do NOT end with a slash.
//    Linux/Mac:
//       define( 'IMAGE_MAGICK_APP_PATH', '/usr/local/bin' );
//    Windows:
//       define( 'IMAGE_MAGICK_APP_PATH', 'C:/Program Files/ImageMagick-6.6.3-Q16' );
//
if( !defined('IMAGE_MAGICK_APP_PATH') ) {
	define( 'IMAGE_MAGICK_APP_PATH', '' );
}

// IMAGE_MAGICK_OPTIONS:
//    Options passed to the ImageMagick application to influence the quality of previews and thumbnails.
//    For more information see: http://www.imagemagick.org/script/command-line-options.php
//    The size (-size) is set by Enterprise and must not be set here.
//    The default value is ' -colorspace sRGB -quality 92 -sharpen 5 -layers merge -depth 8 -strip -density 72x72 '
//
if( !defined('IMAGE_MAGICK_OPTIONS') ) {
	define( 'IMAGE_MAGICK_OPTIONS', '-colorspace sRGB -quality 92 -sharpen 5 -layers merge -depth 8 -strip -density 72x72' );
}

// IMAGE_MAGICK_PUBLISH_OPTIONS:
//    Options passed to the ImageMagick application to influence the quality of image conversions when published to
//    Publication Channels of type 'Web'. Conversion takes place when images are cropped or scaled on a Publish Form and
//    published to Drupal, Wordpress, Twitter or Facebook.
//
//    The following options are set by Enterprise and must not be set here:
//       -verbose, -units, -density, -resize, -crop, -rotate, -flip, -flop
//
//    The default setting as shipped with Enterprise is defined as follows:
//       '-colorspace %colorspace% -quality %quality% -sharpen %sharpen% -depth %depth% -strip -background %background% -layers %layers%'
//
//    Before using the default setting, the options are automatically filled in by Enterprise as follows:
//       -colorspace sRGB -quality 92 -sharpen 5 -depth 8 -strip -background none -layers merge
//
//    To change one of these options, replace the placeholder with a fixed value.
//    Example:
//       replace: -quality %quality%
//       with:    -quality 100
//    In this example the %quality% placeholder will no longer be replaced by Enterprise. Instead of using
//    the default quality value 92, it is using the configured quality value 100.
//
//    Notes:
//    - Other ImageMagick options that are not mentioned here can also be added to the setting.
//    - Any of the listed default options can be removed.
//    - For more information about ImageMagick options see: http://www.imagemagick.org/script/command-line-options.php
//
if( !defined('IMAGE_MAGICK_PUBLISH_OPTIONS') ) {
	define( 'IMAGE_MAGICK_PUBLISH_OPTIONS', '-colorspace %colorspace% -quality %quality% -sharpen %sharpen% -depth %depth% -strip -background %background% -layers %layers%' );
}

// GHOST_SCRIPT_APP_PATH:
//    Full path to the folder of the GhostScript application.
//    Use forward slashes and do NOT end with a slash.
//    Linux/Mac:
//       define( 'GHOST_SCRIPT_APP_PATH', '/usr/local/bin' );
//    Windows:
//       define( 'GHOST_SCRIPT_APP_PATH', 'C:/Program Files/gs/gs8.71/bin' );
//       If you use a different executable than gswin32c.exe, add the executable to the path.
//       Example for using a 64-bits version: 'C:/Program Files/gs/gs8.71/bin/gswin64c.exe'
//
if( !defined('GHOST_SCRIPT_APP_PATH') ) {
	define( 'GHOST_SCRIPT_APP_PATH', '' );
}

// -------------------------------------------------------------------------------------------------
// ExifTool settings
// -------------------------------------------------------------------------------------------------

// EXIFTOOL_APP_PATH:
//    Full path to the folder of the ExifTool application.
//    Use forward slashes and do NOT end with a slash.
//    Windows:
//       define( 'EXIFTOOL_APP_PATH', 'C:/Program Files/ExifTool' );
//    Linux/Mac:
//       define( 'EXIFTOOL_APP_PATH', '/usr/local/bin' );
//
if( !defined('EXIFTOOL_APP_PATH') ) {
	if( OS == 'WIN' ) {
		define( 'EXIFTOOL_APP_PATH', 'C:/Program Files/ExifTool' );
	} else {
		define( 'EXIFTOOL_APP_PATH', '/usr/local/bin' );
	}
}

// -------------------------------------------------------------------------------------------------
// Cross-origin Header settings
// -------------------------------------------------------------------------------------------------

// CROSS_ORIGIN_HEADERS:
//    Extra HTTP headers for compatibility with JavaScript applications in web browsers.
//
//    Javascript applications that are served from a different host/URL can't access
//    Enterprise Server without correct cross-origin headers.
//
//    In default Enterprise installations this option doesn't need to be configured. Developers
//    of JavaScript applications can use this setting when needed. If so, the installation
//    instructions for that particular application should mention the configuration to use in
//    this option.
//
//    Developer notes:
//    - This setting is only used in combination with the JSON-RPC interface.
//
//    - The format:
//        '<origin>' => array(
//            '<header name>' => '<header value>',
//        ),
//
//    - Example:
//        'http://example.com' => array(
//            'Access-Control-Allow-Credentials' => 'true',
//            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS, DELETE, PUT, HEAD',
//            'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Accept, X-WoodWing-Application'
//        ),
//
//    - The set of headers to return is determined by the Origin header that is send by the client.
//      If this header isn't available the first entry in this option is selected as a fallback.
//
//    Disabled by default. The system will not add any headers.
//
//if( !defined('CROSS_ORIGIN_HEADERS') ) {
//    define( 'CROSS_ORIGIN_HEADERS', serialize(array(
//        'http://example.com' => array(
//            'Access-Control-Allow-Credentials' => 'true',
//            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS, DELETE, PUT, HEAD',
//            'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Accept, X-WoodWing-Application'
//        ),
//    )));
//}

// -------------------------------------------------------------------------------------------------
// Push notifications via RabbitMQ integration
// -------------------------------------------------------------------------------------------------

// MESSAGE_QUEUE_CONNECTIONS:
//    The message queue connections defined here are used by Enterprise Server and its clients.
//    The functionality is based on a RabbitMQ integration using the AMQP protocol. It works for WAN (remote users)
//    and is the successor of the broadcast/multicast integration (which worked for LAN only).
//    
//    It is possible to define public and private connections for all RabbitMQ communication. Enterprise Server will serve
//    public connections to clients asking for MessageQueueConnections, and it will use the private connections by default
//    for its own communication.
//    
//    To enable this feature, do the following:
//    1. Uncomment the required MessageQueueConnection definitions below by removing the leading slashes (//).
//    2. For those definitions, replace 'localhost' with the hostname (or IP) of the system on which RabbitMQ is installed.
//
if( !defined('MESSAGE_QUEUE_CONNECTIONS') ) {
	define( 'MESSAGE_QUEUE_CONNECTIONS', serialize(array(
	// - - - - Unsecure connection over TCP: - - - -
	//  new MessageQueueConnection( 'RabbitMQ', 'AMQP', 'amqp://localhost:5672', true, 'woodwing', 'ww' ),
	//  new MessageQueueConnection( 'RabbitMQ', 'REST', 'http://localhost:15672', true, 'woodwing', 'ww' ),
	//  new MessageQueueConnection( 'RabbitMQ', 'STOMPWS', 'ws://localhost:15674/ws', true, 'woodwing', 'ww' ),
	// - - - - Secure connection over SSL: - - - -
	//  new MessageQueueConnection( 'RabbitMQ', 'AMQP', 'amqps://localhost:5671', true, 'woodwing', 'ww' ),
	//  new MessageQueueConnection( 'RabbitMQ', 'REST', 'https://localhost:15671', true, 'woodwing', 'ww' ),
	//  new MessageQueueConnection( 'RabbitMQ', 'STOMPWS', 'wss://localhost:15673/ws', true, 'woodwing', 'ww' ),
	)));
}

// -------------------------------------------------------------------------------------------------
// Debugging - Low-level logging of SQL, SOAP details etc.
// -------------------------------------------------------------------------------------------------

// DEBUGLEVELS:
//    Enables low-level debugging. Possible values: NONE, ERROR, WARN, INFO, DEBUG. Default value: 'NONE'.
//    The amount of information gets richer from left to right. NONE disables the low-level logging. 
//    The option FATAL has been removed since Enterprise 6. Fatal errors caught by PHP are now logged  
//    in the php.log file. When there are no (catchable) fatal errors, this file does not exist.
//    Since Enterprise 8, DEBUGLEVEL is renamed to DEBUGLEVELS and allows to specify level per client IP.
//    For the keys, fill in the client IPs and for the values, fill in the  debug levels.
//    There must be one item named 'default' which is used for all clients that are not explicitly configured.
//
if( !defined('DEBUGLEVELS') ) {
	define ('DEBUGLEVELS', serialize( array(
		// CLIENT IP => DEBUGLEVEL
		'default' => 'INFO', // 'default' entry is mandatory
		'127.0.0.1' => 'DEBUG',
	)));
}

// OUTPUTDIRECTORY:
//    The path to write log files to (including '/'), e.g. "c:/enterpriselog/output/". 
//    Empty to disable. Default value: ''.
//
if( !defined('OUTPUTDIRECTORY') ) {
	define( 'OUTPUTDIRECTORY', '' );
}

// PROFILELEVEL:
//    Used for profiling PHP code. Default value: 0. Requires OUTPUTDIRECTORY to be set in order to
//    work, else the value of profile level is ignored. Possible settings are: 0 to 5:
//       0: No profiling
//       1: Web Service => Handling one service call of client application (excl network traffic)
//       2: PHP Service => Handling one service call without SOAP/AMF/JSON wrapping/unwrapping.
//       3: Ext Service => Call to external system, search engine, integrated system, shell scripts, etc
//       4: Data Store  => SQL calls to DB, file store, etc
//       5: PHP Script  => Potential expensive PHP operations, such as loops, regular expressions, etc
//
if( !defined('PROFILELEVEL') ) {
	define( 'PROFILELEVEL', 0 );
}

// LOGSQL:
//    Used for logging all SQL statements to the main log file. Requires DEBUGLEVELS to be set to 
//    'INFO' or 'DEBUG' in order to work, else the value of LOGSQL is ignored. Default value: false.
//
if( !defined('LOGSQL') ) {
	define( 'LOGSQL', false );
}

// LOG_INTERNAL_SERVICES:
//    Used for logging internal Web services in the service log folder. The services are logged in the 
//    service folder (in OUTPUTDIRECTORY/soap) and have a .txt file extension. The log files contain a  
//    PHP dump of request and response data. All services are logged, including the ones fired by  
//    client applications (InDesign, InCopy, Content Station, Server Maintenance pages, etc), but also  
//    the ones fired internally by PHP. It shows the data as it is run against the server plug-in 
//    connectors. This means -after- some justifications that could be applied by the service layer, 
//    such as converting obsolete data structures into new structures. 
//    (Note that this differs from the .xml log files, which contain services as fired by the clients.)
//    This option might be very useful for system integrators developing server plug-ins. Note that
//    it will dump all data, including file attachments, so this should never be used for production.
//    The option requires DEBUGLEVELS to be set to 'DEBUG' and OUTPUTDIRECTORY to be specified in order
//    to work, else its value is ignored. Default value: false.
//
if( !defined('LOG_INTERNAL_SERVICES') ) {
	define( 'LOG_INTERNAL_SERVICES', false );
}

// LOG_DPS_SERVICES:
//    Used for logging Adobe AEM Web services in the service log folder. When enabled, requests
//    fired by Enterprise Server to Adobe AEM Server and the corresponding responses are logged.
//    Adobe AEM is a so called REST server. The REST services are logged in the service folder
//    (in OUTPUTDIRECTORY/soap) and have an AdobeDps2_ prefix and a .txt file extension.
//    By default, this log feature is disabled. It can be temporary enabled to troubleshoot Adobe AEM traffic.
//    
if( !defined('LOG_DPS_SERVICES') ) {
	define( 'LOG_DPS_SERVICES', false );
}

// LOG_RABBITMQ_SERVICES:
//    Used for logging RabbitMQ Web services in the service log folder. When enabled, RabbitMQ requests 
//    that are fired by Enterprise Server to RabbitMQ Server as well as the corresponding responses are logged.
//    Enterprise Server integrates with RabbitMQ via two protocols:
//    1. REST for administration tasks (such as creating queues, users and permissions).
//    2. AMQP to push messages (events) into the queues.
//    The services (requests and responses) are logged in the server log folder (OUTPUTDIRECTORY) and have a
//    RabbitMQ_ prefix and a .txt file extension.
//   
//    This log feature is disabled by default and can be temporarily enabled for troubleshooting RabbitMQ traffic.
//    Note that clients read messages from RabbitMQ directly, but that traffic is not logged here.
//   
if( !defined('LOG_RABBITMQ_SERVICES') ) {
	define( 'LOG_RABBITMQ_SERVICES', false );
}

// LOGFILE_FORMAT:
//    The log file always contains UTF-8 characters, but aside to that, there are two formats
//    supported: 'html' and 'plain'. The HTML format is better readable in web browser. The plain
//    text format can be easier searched through using command line tools like grep.
//
if( !defined('LOGFILE_FORMAT') ) {
	define( 'LOGFILE_FORMAT', 'html' );
}

// -------------------------------------------------------------------------------------------------
// Web services validation
// -------------------------------------------------------------------------------------------------

// SERVICE_VALIDATION:
//    The service validation feature validates the client-server traffic to check if all the request
//    data sent by the clients and all the response data returned from the server is fully
//    respecting the interface definition (WSDL). The aim of this technical checksum is to detect potential  
//    problems at an early stage. By default, the option is set to false (no validation performed) in order to
//    avoid raising problems during production (or demos). For testers, developers, and integrators, this option 
//    should be set to true (validation enabled).
if( !defined('SERVICE_VALIDATION') ) {
	define( 'SERVICE_VALIDATION', false );
}
// SERVICE_VALIDATION_IGNORE_PATHS:
//    Used to suppress specific validation errors reported as: "Invalid property (S1020)".
//    You can add a reported data path to this option to suppress an error raised by the clients.
//    When facing service validation problems, you can suppress the error by adding the reported
//    path to this option. While doing so, please also report the defect to WoodWing Support.
//    When the first element of the reported path ends with "Request", it is most likely a client defect.
//    When it ends with "Response" it is a server defect.
//    Example of problem raised by a client: GetPagesInfoRequest->Issue->Name
//    Example of problem raised by a server: LogOnResponse->MessageList->ReadMessageIDs
//    Note that errors are raised in DEBUG mode only (because validation is time consuming).
//
if( !defined('SERVICE_VALIDATION_IGNORE_PATHS') ) {
	define( 'SERVICE_VALIDATION_IGNORE_PATHS', serialize( array(
	)));
}

// -------------------------------------------------------------------------------------------------
// Testing - Options used by TestSuite modules (at wwtest page)
// -------------------------------------------------------------------------------------------------
if( !defined('TESTSUITE') ) {
	define( 'TESTSUITE', serialize( array(
		'User'     => 'woodwing',   // User name => for automatic login during tests
		'Password' => 'ww',         // User password => "    "
		'Brand'    => 'WW News',    // Brand name => picked to create/retrieve test data
		'Issue'    => '2nd Issue',  // Issue name => "      "
		'SoapUrlDebugParams' => '', // Debug params posted by SoapClient at SOAP entry point URL (only applied
		                            // when DEBUGLEVELS is set to 'DEBUG'). Typically useful to trigger Zend/Komodo debuggers.
	)));
}

// HTMLLINKFILES:
//    Obsoleted since v9.2: still functional, but will be removed in v10.0 completely and default is false 
//
//    When enabled, for each object created in the system, a HTML link file is created in the
//    filestore/_BRANDS_ folder. In case of database failure, the HTML link files can be used by the 
//    system admins to lookup produced documents. Creation of HTML link files is made as an optional 
//    feature since Enterprise 8.0. HTML link files are not used by Enterprise Server, and 
//    therefore it is safe to remove them at any time. When having an automatic database backup in 
//    place, and you aim to optimize disk usage, you might want to consider to disable this option.
//    When set to true, the link files will be created for new objects and new versions.
//    When set to false, new link files won't be created and existing files will not be updated.
//    When changing from true to false, the existing link files needs to be removed manually.
//    Vice versa, when changing from false to true, the link files will be created from the 
//    moment the option is changed. Obviously, link files for earlier created objects or versions
//    are not present.
//
if( !defined('HTMLLINKFILES') ) {
	define( 'HTMLLINKFILES', false ); // Default is false.
}

// -------------------------------------------------------------------------------------------------
//  Enterprise Server Job Auto Cleanup
// -------------------------------------------------------------------------------------------------

//  Automatically deletes Enterprise Server Jobs of a particular status when they become older than a specified number
//  of days.

// AUTOCLEAN_SERVERJOBS_COMPLETED:
//    Deletes Enterprise Server Jobs of status 'Completed' and InDesign Server Jobs of status 'OK' and 'QUEUED'.
//    Default value is 14 days, which means all completed jobs in the queue that are older than 14 days will be deleted.
//    When set to zero (0), this feature is disabled.
if( !defined('AUTOCLEAN_SERVERJOBS_COMPLETED') ) {
	define( 'AUTOCLEAN_SERVERJOBS_COMPLETED', 14 );
}

// AUTOCLEAN_SERVERJOBS_UNFINISHED:
//    Deletes Enterprise Server Jobs that have a status other than 'Completed' and InDesign Server Jobs of status 'ERROR'.
//    Default value is 30 days, which means all jobs in the queue that were never picked up, failed or not completed for
//    some reasons, and that are older than 30 days will be deleted.
//    When set to zero (0), this feature is disabled.
if( !defined('AUTOCLEAN_SERVERJOBS_UNFINISHED') ) {
	define( 'AUTOCLEAN_SERVERJOBS_UNFINISHED', 30 );
}

// -------------------------------------------------------------------------------------------------
// Remote users
// -------------------------------------------------------------------------------------------------

// REMOTE_LOCATIONS_INCLUDE:
//    When the client IP matches one (or more) of the specified IP ranges below, the
//    client user is seen as a remote worker. This is determined when the user is about 
//    to log in. For these remote workers, data compression is applied (for example when 
//    downloading/uploading articles).
//
//    Even though compression costs little processing time, the remote user will gain 
//    performance when the network connection has low bandwidth (less throughput) or high 
//    latency (long distance). On the other hand, for local users on a highspeed LAN, 
//    compression most likely results in less performance, and therefore the LAN should NOT 
//    be included. The best results can be determined experimentally by uploading/downloading 
//    articles with Content Station.
//
//    When both options are left empty, all workers are treated as local.
//
//    The following notations can be used to specify a range:
//    - '192.0.0.1'    => matches with '192.0.0.1' only
//    - '192.0.0.*'    => matches with ['192.0.0.0'...'192.0.0.255']
//    - '192.0.*.*'    => matches with ['192.0.0.0'...'192.0.255.255']
//    - '192.0.0.0/16' => matches with ['192.0.0.0'...'192.0.255.255']
//    - '192.0.0.0/24' => matches with ['192.0.0.0'...'192.0.0.255']
//    - '192.0.0.0/255.255.0.0'   => matches with ['192.0.0.0'...'192.0.255.255']
//    - '192.0.0.0/255.255.255.0' => matches with ['192.0.0.0'...'192.0.0.255']
//    - '192.0.0.0-192.0.0.255'   => matches with ['192.0.0.0'...'192.0.0.255']
//    - '192.0.0.0-192.0.255.255' => matches with ['192.0.0.0'...'192.0.255.255']
//    - '2001:db8::1'             => matches with '2001:db8:0:0:0:0:0:1' only
//    - '2001:db8::/32'           => matches with ['2001:db8:0:0:0:0:0:0'...'2001:db8:ffff:ffff:ffff:ffff:ffff:ffff']
//    - '2001:db8::/64'           => matches with ['2001:db8:0:0:0:0:0:0'...'2001:db8:0:0:ffff:ffff:ffff:ffff']
//    - '2001:db8::-2001:db9::'   => matches with ['2001:db8:0:0:0:0:0:0'...'2001:db9:0:0:0:0:0:0']
//    - '2001:db8::-2001:db8::ff' => matches with ['2001:db8:0:0:0:0:0:0'...'2001:db8:0:0:0:0:0:ff']
//
if( !defined('REMOTE_LOCATIONS_INCLUDE') ) {
	define( 'REMOTE_LOCATIONS_INCLUDE', serialize( array(
	)));
}

// REMOTE_LOCATIONS_EXCLUDE:
//    This option does the opposite of the include option (REMOTE_LOCATIONS_INCLUDE).
//    When the client IP matches one (or more) of the specified IP ranges below, the user
//    is NOT a remote worker.
//    
//    The Exclude option 'overrules' the Include option: when the client IP matches one of 
//    the IP ranges listed in the exclude option, the include option is no longer checked.
//     
//    Note that the same range notations can be used as for the include option.
//    
//    When both options are left empty, all workers are treated as local.
//    
//    The following example defines all remote users, except your LAN [192.0.0.0-192.0.255.255]:
//       define( 'REMOTE_LOCATIONS_INCLUDE', serialize( array(
//          '*.*.*.*', // all IPv4 addresses (remote users)
//          '::/0' // all IPv6 addresses (remote users)
//       ));
//       define( 'REMOTE_LOCATIONS_EXCLUDE', serialize( array(
//          '192.0.*.*' // your LAN (local users)
//       ));
//   
//    For debugging, search for 'IsRemoteUser' in the server logging to check the decision of
//    the server whether or not a certain Content Station logon is from a remote worker.
//
if( !defined('REMOTE_LOCATIONS_EXCLUDE') ) {
	define( 'REMOTE_LOCATIONS_EXCLUDE', serialize( array(
	)));
}

// -------------------------------------------------------------------------------------------------
// Discontinued settings 
// -------------------------------------------------------------------------------------------------

// RETURN_SHORT_USERNAMES:
//    Obsoleted since v6.0. Enterprise now displays the full user name in all situations.
// MOVE_PLACEMENTS:
//    Obsoleted since v7.0.
// DEBUGLEVEL:
//    Obsoleted since v8.0. Use DEBUGLEVELS instead.

// -------------------------------------------------------------------------------------------------
// System internals
// ===> DO NOT MAKE CHANGES TO THE FOLLOWING SECTION
// -------------------------------------------------------------------------------------------------

ini_set('display_errors', '0'); // Debug option; should ALWAYS be zero for production!
if( defined('OUTPUTDIRECTORY') && OUTPUTDIRECTORY != '' ) {
	ini_set('log_errors', '1'); // use 'error_log'
	ini_set('error_log', OUTPUTDIRECTORY.'php.log'); // Log PHP Errors, Warnings and Noticed to file
}

// Override cookie settings for the PHPSESSION cookie for extra security. The session id is the same as the Enterprise ticket. 
ini_set('session.cookie_path', INETROOT);
ini_set('session.cookie_secure', COOKIES_OVER_SECURE_CONNECTIONS_ONLY);
ini_set('session.cookie_httponly', true);

if( !defined('DBPREFIX') ) {
	define( 'DBPREFIX', 'smart_' ); // Prefix used for all database table names. This must not be changed.
}

if(!defined('sLanguage_code')){
	$sLanguage_code = null;
}

//DEFAULT_USER_COLOR: Including hash(#).
if( !defined('DEFAULT_USER_COLOR') ) {
	define( 'DEFAULT_USER_COLOR', '#FF9900' ); // The default user color that is assigned during user creation.(Only take default when no color given)
}

// Zend Framework 1 requires the library folder to be in php path:
ini_set('include_path', BASEDIR.'/server/ZendFramework/library'.PATH_SEPARATOR.ini_get('include_path'));

// Init autoloader for Zend Framework 2:
$loader = new Zend\Loader\StandardAutoloader(array(
    'autoregister_zf' => true,
));
$loader->register();

require_once BASEDIR.'/server/utils/LogHandler.class.php';
LogHandler::init();

require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once BASEDIR.'/server/interfaces/services/BizException.class.php';
require_once BASEDIR.'/server/interfaces/services/BizErrorReport.class.php';
require_once BASEDIR.'/server/utils/PerformanceProfiler.class.php';
require_once BASEDIR.'/config/configlang.php';
require_once BASEDIR.'/server/dbdrivers/dbdriver.php';
require_once BASEDIR.'/server/bizclasses/BizSettings.class.php';
require_once BASEDIR.'/server/bizclasses/BizResources.class.php';
