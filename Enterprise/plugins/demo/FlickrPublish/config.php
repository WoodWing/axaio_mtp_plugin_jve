<?php
/****************************************************************************
   Copyright 2008-2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

/*
	Flick account name
*/
if( !defined('FLICKRPUBLISH_ACCOUNT') ) {
   define( 'FLICKRPUBLISH_ACCOUNT', '' );
}

/*
	Register for API Key and Secret at:
	http://www.flickr.com/services/api/ under API Keys.
	This can be used independent of the Flickr account, this is linked with the token:
*/
if( !defined('FLICKRPUBLISH_API_KEY') ) {
   define( 'FLICKRPUBLISH_API_KEY', '' );
}
if( !defined('FLICKRPUBLISH_API_SECRET') ) {
   define( 'FLICKRPUBLISH_API_SECRET', '' );
}

/*
	Flickr Token that links to your account.
	Logon, to Flickr and next open this plugin page:
	http://YOUR_SERVER/YOUR_ENTERPRISE_FOLDER/config/plugins/flickrpublish/gettoken.php
*/
if( !defined('FLICKRPUBLISH_TOKEN') ) {
   define( 'FLICKRPUBLISH_TOKEN', '' );
}

// Define whether the image publish to Flickr will be for Public, Friends, or Family.
if( !defined('FLICKRPUBLISH_PUBLIC') ) {
   define( 'FLICKRPUBLISH_PUBLIC', true );
}
if( !defined('FLICKRPUBLISH_FRIEND') ) {
   define( 'FLICKRPUBLISH_FRIEND', true );
}
if( !defined('FLICKRPUBLISH_FAMILY') ) {
   define( 'FLICKRPUBLISH_FAMILY', true );
}

// Define Flickr temp directory
if( !defined('FLICKR_DIRECTORY') ) {
   define( 'FLICKR_DIRECTORY', ATTACHMENTDIRECTORY.'/_FLICKR_' );
}