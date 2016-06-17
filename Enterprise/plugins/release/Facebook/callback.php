<?php
/****************************************************************************
Copyright 2008-2013 WoodWing Software BV

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

/**
 * This is the callback module that plays role in the Facebook authorization procedure.
 * After landing here it converts the code and redirects to the Facebook maintenance page
 */

// Get Enterprise ticket and start session.
if( file_exists('../../../config/config.php') ) {
    require_once '../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
    require_once '../../../Enterprise/config/config.php';
}
include_once dirname(__FILE__).'/FacebookPublisher.class.php';

// Get Access Token from Facebook.

$faceConn = new FacebookPublisher();
$faceConn->retrieveCodeFromRedirection( $_REQUEST );