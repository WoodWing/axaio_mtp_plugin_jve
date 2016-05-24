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

define ('CLARO_WW_USERNAME', 	'woodwing');
define ('CLARO_WW_USERPWD', 	'ww');
define ('CLARO_POST_STATUS', 	'Image Finished');
define ('CLARO_PRE_STATUS', 	'Image Editing');
define ('CLARO_PRE_BW_STATUS', 	'BW Image Editing');
define ('CLARO_PROCESS_STATUS', 'Image Processing');

define ('CLARO_IMAGE_TYPE', serialize(array(".jpg",".jpeg", ".tif", ".tiff", ".psd")) );

define ('CLARO_CONFIG', serialize(array(
		'WW News' => array (		// for each publicationname (case sensitve):
					'MIME' =>'image/jpg',		// MIME to CLaro, 'image/jpg' or psd 'image/vnd.adobe.photoshop'
					'EXT' => 'jpg',				// ext to Claro 'jpg' or 'psd'
					'DOCROP' => true,
					'DOROTATE' => true,
					'EXPORT_PATH' => ATTACHMENTDIRECTORY . '/_CLARO_/input/',
					'EXPORT_IMAGE_PATH' => ATTACHMENTDIRECTORY . '/_CLARO_/input/images/',
				//	'SERVER_EXPORT_IMG_PATH' => '/CLARO/to/image/',	// delete this line if not needed
					'IMPORT_PATH' => ATTACHMENTDIRECTORY . '/_CLARO_/output/'
					)
		)
));

define ('CLARODEBUG', 			false);
