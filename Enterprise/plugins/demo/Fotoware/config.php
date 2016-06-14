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

// URL of Fotoware server
if( !defined('FOTOWARE_URL') ) {
   define( 'FOTOWARE_URL', 'http://fotoweb.fotoware.com/fotoweb' ); // without ending slash
}

// Username and password, to download the hi-res image from Fotoware server (FOTOWARE_URL)
if( !defined('FOTOWARE_USERNAME') ) {
   define( 'FOTOWARE_USERNAME', 'demo' );
}
if( !defined('FOTOWARE_PASSWORD') ) {
   define( 'FOTOWARE_PASSWORD', 'demo' );
}

// In case you need to use a proxy to get to FIP please specify proxy server and port like http://myproxy:8080
if( !defined('FOTOWARE_PROXY') ) {
   define( 'FOTOWARE_PROXY', '' );
}

// Enterprise Brand and Category to use when importing Fotoware images
if( !defined('FOTOWARE_SHADOW_BRAND') ) {
   define( 'FOTOWARE_SHADOW_BRAND', 'Our Globe' );
}
if( !defined('FOTOWARE_SHADOW_CATEGORY') ) {
   define( 'FOTOWARE_SHADOW_CATEGORY', 'Editorial' );
}
if( !defined('FOTOWARE_SHADOW_BRAND') ) {
   //define ('FOTOWARE_SHADOW_BRAND',	'WW News' );
}
if( !defined('FOTOWARE_SHADOW_CATEGORY') ) {
   //define ('FOTOWARE_SHADOW_CATEGORY',	'News' );
}


// ----- DO NOT MAKE CHANGES BELOW -----

if( !defined('FOTOWARE_FULLURL') ) {
   define( 'FOTOWARE_FULLURL', FOTOWARE_URL.'/fwbin/fotoweb_isapi.dll/ArchiveAgent/' );
}