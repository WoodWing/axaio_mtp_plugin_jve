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

// Define YouTube temp directory
if( !defined('YOUTUBE_DIRECTORY') ) {
   define( 'YOUTUBE_DIRECTORY', ATTACHMENTDIRECTORY.'/_YOUTUBE_' );
}

// YouTube User Name(User email)
if( !defined('YOUTUBEPUBLISH_USERNAME') ) {
   define( 'YOUTUBEPUBLISH_USERNAME', '' );
}
// YouTube User Password
if( !defined('YOUTUBEPUBLISH_USERPWD') ) {
   define( 'YOUTUBEPUBLISH_USERPWD', '' );
}
// YouTube Client ID
if( !defined('YOUTUBEPUBLISH_CLIENT_ID') ) {
   define( 'YOUTUBEPUBLISH_CLIENT_ID', '' );
}
// YouTubeDeveloper Key
if( !defined('YOUTUBEPUBLISH_DEV_KEY') ) {
   define( 'YOUTUBEPUBLISH_DEV_KEY', '' );
}
