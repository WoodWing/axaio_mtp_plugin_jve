<?php
/****************************************************************************
   Copyright 2007-2009 WoodWing Software BV

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

// Mollie SMS account information
if( !defined('WWSMS_USERNAME') ) {
   define( 'WWSMS_USERNAME', '' );
}
if( !defined('WWSMS_PASSWORD') ) {
   define( 'WWSMS_PASSWORD', '' );
}

// Which gateway to use, 2 is 20% cheaper, but less reliable
if( !defined('WWSMS_GATEWAY') ) {
   define( 'WWSMS_GATEWAY', 1 );
}

// Phone numbers to send SMS. Phone numbers are space separated and have international format, for example: '+316...'
// Note this is used as fallback if no phone numbers set in Issue Description
if( !defined('WWSMS_RECIPIENTS') ) {
   define( 'WWSMS_RECIPIENTS', '' );
}

// The name of sender. Do not define too long names, or else sending SMS might fail.
// This can be overruled by filling in the issue's subject.
if( !defined('WWSMS_SENDER') ) {
   define( 'WWSMS_SENDER', 'WoodWing' );
}

// The footer to add to each message. Empty for none. 
// Do not define too footers, or else there is nothing left for the message (which has max 160 characters).
if( !defined('WWSMS_FOOTER') ) {
   define( 'WWSMS_FOOTER', '(sent from WoodWing Enterprise)' );
}