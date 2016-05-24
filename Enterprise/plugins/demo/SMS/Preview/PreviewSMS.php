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

/**
 * @package 	Demo Server Plugins
 * @subpackage 	SMS plugin
 * @since 		v6.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * SMS (Mollie) integration - Preview SMS application
 */

$smstext = isset($_REQUEST['text'])  ? $_REQUEST['text']  : '';

// Get HTML file and inject the SMS text to preview:
$html = implode( '', file('PreviewSMS.htm') );
$html = str_replace('<!--CONTENT-->', $smstext, $html );
print $html;



