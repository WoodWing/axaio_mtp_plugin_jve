<?php
// ADOBEDPS_ACCOUNTS:
//    Your Adobe ID to sign in at Adobe DPS.
//    Used when admin user is editing issues or end user is publishing articles to devices.
//    This is affective for issues and articles that are assigned to DPS Publication Channels only.
// 
//    Since Enterprise 7.6, accounts can be set up per Publication Channel and/or per Edition. This 
//    can be set up through this option which supercedes the obsoleted ADOBEDPS_CONFIG option.
//    
//    Currently the DPS solution has a limitation where if you produce a magazine to multiple devices
//    they can only be published on the same date. An Adobe solution is not likely to appear soon,
//    therefore Enterprise offers a workaround for this: support for one account per Edition.
//    
//    To set up a system-wide account, fill in 0 (zero) for the Publication Channel ID and 0 (zero) 
//    for the Edition ID. This account can be seen as a fallback, since it will be used for publishing 
//    operations on all Channels/Editions that are -not- configured through this option.
//       0 => array( // pub channel id
//          0 => array ( // edition id
//             <<< DPS account >>>
//          ),
//       ),
//    
//    To set up a Publication Channel-wide account, enter the Publication Channel ID, and 0 (zero) 
//    for the Edition ID. This account can be seen as a fallback, since it will be used for publishing 
//    operations on that Publication Channel whereby the Edition is -not- configured through this option. 
//    For example:
//       11 => array( // pub channel id
//          0 => array ( // edition id
//             <<< DPS account >>>
//          ),
//       ),
//    
//    To set up an account per Edition, fill in the Publication Channel ID and the Edition ID. This 
//    account will be used for publishing operations on the specified Edition only. For example, this 
//    enables publishing for iPad on a different date than for Android.
//       11 => array( // pub channel id
//          128 => array ( // edition id
//             <<< DPS account >>>
//          ),			
//          137 => array ( // edition id
//             <<< DPS account >>>
//          ),
//       ),
//    
//    Anything of the above can be combined, as shown below. For example:
//    - when publishing for Edition ID 150 under Channel ID 16, account A will be used;
//    - when publishing for Edition ID 140 under Channel ID 15, account B will be used;
//    - when publishing for Edition ID 141 under Channel ID 15, account C will be used.
//    Note that specific Edition IDs under the generic Publication Channel (id=0) is not allowed.
//       0 => array( // pub channel id
//          0 => array ( // edition id
//             <<< DPS account A >>>
//          ),
//       ),
//       15 => array( // pub channel id
//          0 => array ( // edition id
//             <<< DPS account B >>>
//          ),
//          141 => array ( // edition id
//             <<< DPS account C >>>
//          ),
//       ),
//    
//    TIP: The Publication Channel IDs and Edition IDs can be looked up on the Admin (Maintenance) 
//         pages. By clicking on the Channels/Editions, their database IDs will appear in the URL as 
//         shown in address bar of the browser.
//    
//    TIP: After making changes to this file, please run the Health Check page to validate your settings.
//    
//    IMPORTANT: The IDs are used as indexes in the arrays, so never repeat the same ID, or else they 
//               'overwrite' each other which causes unexpected results. Those errors will not be detected
//               by the Health Check.
//    
if( !defined('ADOBEDPS_ACCOUNTS') ) {
	define('ADOBEDPS_ACCOUNTS', serialize( array (
		0 => array(
			0 => array (
				'username'       => '', // Adobe DPS user email address
				'password'       => '', // Adobe DPS user password
				'serverurl'      => '', // Adobe DPS server URL
			),
		),
	)));
}

// ADOBEDPS_READER_VERSIONS:
//    This is a list of Adobe Content Viewer versions and is shown when
//    a user adds or edits an issue.
//
//    The values should be numeric values of available Content Viewers to
//    which the issue can be exported.
if( !defined('ADOBEDPS_READER_VERSIONS') ) {
	define('ADOBEDPS_READER_VERSIONS', serialize( array (
		'20',
		'21',
		'22',
		'23',
		'24',
		'25',
		'26',
		'27',
	)));
}

// ADOBEDPS_EXPORTDIR:
//    Root folder where Adobe DPS folio files are exported to.
//    By default based on the export directory setting: EXPORTDIRECTORY.'AdobeDps/'
//    
if( !defined('ADOBEDPS_EXPORTDIR') ) {
	define( 'ADOBEDPS_EXPORTDIR', EXPORTDIRECTORY.'AdobeDps/' );
}

// ADOBEDPS_PERSISTENT_DATADIR
// 	  Folder where persistent data like the HTML Resouce cache, is stored. This folder is used by the publish process
//	  and should not be cleaned by hand.
if( !defined('ADOBEDPS_PERSISTENTDIR') ) {
	define( 'ADOBEDPS_PERSISTENTDIR', PERSISTENTDIRECTORY.'/AdobeDps' );
}

// PARALLEL_PUBLISHING_MAX_CONNECTIONS:
// 
//    The maximum allowed number of connections (to publish Dossiers in parallel to the Adobe DPS Server),
//    for a single publishing operation of an issue. The default is a maximum of 5 connections.
//    Connections are established by the PARALLEL_PUBLISHING_ENTRY option (see above).
//    By increasing this value, more Dossiers can be uploaded in parallel, but also more PHP processes
//    will be started and more HTTP connections to Adobe DPS are established. Too many connections 
//    might overload the server machine or exceed the maximum available HTTP/socket connections. Too
//    few connections leads to unnecessary wait time, especially for remote connections (long distance).
//
if( !defined('PARALLEL_PUBLISHING_MAX_CONNECTIONS') ) {
	define( 'PARALLEL_PUBLISHING_MAX_CONNECTIONS', 5 );
}

// AllowDuplicateProductID:
//
//	  The Adobe Product ID is a property of the DPS issue and needs to be unique for each device.
//	  When a new DPS Issue is created in Enterprise, the system checks wether the Product ID is filled in and if it is 
//	  unique. However, this functionality is too restrictive for configurations where different Publication Channels are 
//	  set up for each type of device. Enabling the AllowDuplicateProductID option allows the use of duplicate Product IDs, 
//	  although not within the same Publication Channel. 
//	  
//	  WARNING: This option should only be set to true if separate Publication Channels are used for different devices. 
//	  Note that with this setting enabled it is possible to define the same product ID for the same device for separate
//	  Publication Channels. This will lead to mixed up content on the device and in the library.
//
if( !defined('AllowDuplicateProductID') ) {
	define( 'AllowDuplicateProductID', false );
}