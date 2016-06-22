<?php
/****************************************************************************
   Copyright 2009 WoodWing Software BV

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

// URL to the Tripolis server including / (e.g. https://api.tripolis.com/soap/1.5/DialogueService?wsdl)
if( !defined('TRIPOLIS_DIALOGSERVICE_URI') ) {
   define( 'TRIPOLIS_DIALOGSERVICE_URI', 'https://api.tripolis.com/soap/1.5/no-mtom/DialogueService?wsdl' );
}

// Tripolis Dialogue client name
if( !defined('TRIPOLIS_AUTHINFO_CLIENT') ) {
   define( 'TRIPOLIS_AUTHINFO_CLIENT', '' );
}
// Tripolis Dialogue username.
if( !defined('TRIPOLIS_AUTHINFO_USERNAME') ) {
   define( 'TRIPOLIS_AUTHINFO_USERNAME', '' );
}
// Tripolis Dialogue password.
if( !defined('TRIPOLIS_AUTHINFO_PASSWORD') ) {
   define( 'TRIPOLIS_AUTHINFO_PASSWORD', '' );
}
// Tripolis workspace to create content in.
if( !defined('TRIPOLIS_WORKSPACE') ) {
   define( 'TRIPOLIS_WORKSPACE', 'woodwingdemo' );
}
// Tripolis contact database name.
if( !defined('TRIPOLIS_DATABASE') ) {
   define( 'TRIPOLIS_DATABASE', 'woodwingdemo' );
}
// Tripolis direct email type name.
if( !defined('TRIPOLIS_DIRECTEMAIL_TYPE') ) {
   define( 'TRIPOLIS_DIRECTEMAIL_TYPE', 'detype1' );
}
