<?php
/**
 * @package 	Enterprise
 * @subpackage 	AdobeDps2
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * System-wide settings that can be made for Adobe DPS by the system administrator.
 */

// DSP2_AUTHENTICATION_URL:
//    End point of the authentication server.
//
if( !defined('DSP2_AUTHENTICATION_URL') ) {
	define( 'DSP2_AUTHENTICATION_URL', 'https://ims-na1.adobelogin.com' );
}

// DSP2_AUTHORIZATION_URL:
//    End point of the authorization server.
//
if( !defined('DSP2_AUTHORIZATION_URL') ) {
	define( 'DSP2_AUTHORIZATION_URL', 'https://authorization.publish.adobe.io' );
}

// DSP2_PRODUCER_URL:
//    End point of the producer server.
//
if( !defined('DSP2_PRODUCER_URL') ) {
	define( 'DSP2_PRODUCER_URL', 'https://pecs.publish.adobe.io' );
}

// DSP2_INGESTION_URL:
//    End point of the ingestion server.
//
if( !defined('DSP2_INGESTION_URL') ) {
	define( 'DSP2_INGESTION_URL', 'https://ings.publish.adobe.io' );
}

// -------------------------------------------------------------------------------------------------
// System internals
// ===> DO NOT MAKE CHANGES TO THE FOLLOWING SECTION
// -------------------------------------------------------------------------------------------------

define( 'DPS2_PLUGIN_DISPLAYNAME', 'Adobe AEM' );
