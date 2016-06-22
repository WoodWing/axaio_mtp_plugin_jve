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


if( !defined('REMOTE_SERVER_URL') ) {
   define( 'REMOTE_SERVER_URL', 'http://127.0.0.1/Enterprise7/index.php' );
}
if( !defined('CACHE_DIR') ) {
   define( 'CACHE_DIR', '/Temp/' );
}

require_once( dirname(__FILE__).'/SmartProxy.class.php');