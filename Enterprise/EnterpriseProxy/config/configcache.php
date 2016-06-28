<?php


// -------------------------------------------------------------------------------------------------
// MIME Type
// -------------------------------------------------------------------------------------------------

// EXTENSIONMAP:
//    Mapping of file types (extensions, for example image.jpg) to their MIME type and object type
//    as stored in the Enterprise database.
//
define ('EXTENSIONMAP', serialize( array(
	'.jpg' => array( 'image/jpeg', 'Image'),
	'.jpeg' => array( 'image/jpeg', 'Image'),
	'.gif' => array( 'image/gif', 'Image'),
	'.tif' => array( 'image/tiff', 'Image'),
	'.tiff' => array( 'image/tiff', 'Image'),
	'.png' => array( 'image/png', 'Image'),
	'.psd' => array( 'image/x-photoshop', 'Image'),
	'.eps' => array( 'application/postscript', 'Image'),
	'.ai' => array( 'application/illustrator', 'Image'),
	'.pdf' => array( 'application/pdf', 'Image'),
	'.wwcx' => array( 'application/incopy', 'Article'),
	'.wwct' => array( 'application/incopyinx', 'ArticleTemplate'),
	'.wcml' => array( 'application/incopyicml', 'Article'),
	'.wcmt' => array( 'application/incopyicmt', 'ArticleTemplate'),
	'.wwea' => array( 'text/wwea', 'Article'),
	'.wweat' => array( 'text/wwea', 'ArticleTemplate'), // BZ# 19176: To ensure the article template has the correct icon.
	'.incd' => array( 'application/incopy', 'Article'),
	'.incx' => array( 'application/incopy', 'Article'),
	'.indd' => array( 'application/indesign', 'Layout'),
	'.indt' => array( 'application/indesign', 'LayoutTemplate'),
	'.indl' => array( 'application/indesignlibrary', 'Library'), // BZ#10231: Changed indesign into indesignlibrary
	'.htm' => array( 'text/html', 'Article'),
	'.html' => array( 'text/html', 'Article'),
	'.txt' => array( 'text/plain', 'Article'),
	'.rtf' => array( 'text/richtext', 'Article'),
	'.xml' => array( 'xml', ""),

	// Audio / Video
	'.au' => array( 'audio/basic', 'Audio'),
	'.snd' => array( 'audio/basic', 'Audio'),
	'.mid' => array( 'audio/midi', 'Audio'),
	'.midi' => array( 'audio/midi', 'Audio'),
	'.kar' => array( 'audio/midi', 'Audio'),
	'.mp3' => array( 'audio/mpeg', 'Audio'), // BZ#6564: moved on top of audio/mpeg sublist
	'.mpga' => array( 'audio/mpeg', 'Audio'),
	'.mp2' => array( 'audio/mpeg', 'Audio'),
	'.aif' => array( 'audio/x-aiff', 'Audio'),
	'.aiff' => array( 'audio/x-aiff', 'Audio'),
	'.aifc' => array( 'audio/x-aiff', 'Audio'),
	'.m3u' => array( 'audio/x-mpegurl', 'Audio'),
	'.ram' => array( 'audio/x-pn-realaudio', 'Audio'),
	'.rm' => array( 'audio/x-pn-realaudio', 'Audio'),
	'.rpm' => array( 'audio/x-pn-realaudio-plugin', 'Audio'),
	'.ra' => array( 'audio/x-realaudio', 'Audio'),
	'.wav' => array( 'audio/x-wav', 'Audio'),
	'.mpg' => array( 'video/mpeg', 'Video'),
	'.mpeg' => array( 'video/mpeg', 'Video'),
	'.mov' => array( 'video/quicktime', 'Video'),
	'.avi' => array( 'video/x-msvideo', 'Video'),
	'.asf' => array( 'video/x-ms-asf', 'Video'),
	'.asx' => array( 'video/x-ms-asf', 'Video'),
	'.wma' => array( 'video/x-ms-wma', 'Video'),
	'.wmv' => array( 'video/x-ms-wmv', 'Video'),
	'.wmx' => array( 'video/x-ms-wmx', 'Video'),
	'.wmz' => array( 'video/x-ms-wmz', 'Video'),
	'.wmd' => array( 'video/x-ms-wmd', 'Video'),
	'.wm' => array( 'video/x-ms-wm', 'Video'),
	'.flv' => array( 'video/x-flv', 'Video'),
	'.swf' => array( 'application/x-shockwave-flash', 'Video'),
	'.mp4' => array( 'video/mp4', 'Video'),
	'.m4v' => array( 'video/x-m4v', 'Video'), // BZ#20713

	// MS Office 2003/2004				(some are commented out to avoid duplicate mime types + object types)
	'.doc' => array( 'application/msword',            'Article'),  // Word document
	'.dot' => array( 'application/msword',            'ArticleTemplate'), // Word template
	'.xls' => array( 'application/vnd.ms-excel',      'Spreadsheet' ), // Excel sheet
	//'.xlt' => array( 'application/vnd.ms-excel',      'ArticleTemplate' ), // Excel template
	//'.xlw' => array( 'application/vnd.ms-excel',      'Article' ), // Excel workbook
	//'.xla' => array( 'application/vnd.ms-excel',      'Other' ),   // Excel add-in
	//'.xlc' => array( 'application/vnd.ms-excel',      'Article' ), // Excel chart
	//'.xlm' => array( 'application/vnd.ms-excel',      'Article' ), // Excel macro
	'.ppt' => array( 'application/vnd.ms-powerpoint', 'Presentation' ),   // PowerPoint presentation (BZ#10482)
	//'.pps' => array( 'application/vnd.ms-powerpoint', 'Other' ),   // PowerPoint slideshow
	//'.pot' => array( 'application/vnd.ms-powerpoint', 'Other' ),   // PowerPoint template
	//'.ppz' => array( 'application/vnd.ms-powerpoint', 'Other' ),   // PowerPoint animation
	//'.ppa' => array( 'application/vnd.ms-powerpoint', 'Other' ),   // PowerPoint add-in

	// MS Office 2007
	'.docx' => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'Article' ), // Word document
	'.docm' => array( 'application/vnd.ms-word.document.macroEnabled.12',                        'Article' ), // " (macro-enabled)
	'.dotx' => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.template', 'ArticleTemplate' ), // Word template
	'.dotm' => array( 'application/vnd.ms-word.template.macroEnabled.12',                        'Article' ), // " (macro-enabled)
	'.xlsx' => array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',       'Spreadsheet' ), // Excel workbook
	'.xlsm' => array( 'application/vnd.ms-excel.sheet.macroEnabled.12',                          'Spreadsheet' ), // " (macro-enabled)
	'.xltx' => array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',    'Spreadsheet' ), // Excel template
	'.xltm' => array( 'application/vnd.ms-excel.template.macroEnabled.12',                       'Spreadsheet' ), // " (macro-enabled)
	'.xlsb' => array( 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',                   'Spreadsheet' ), // Excel binary workbook (macro-enabled)
	'.xlam' => array( 'application/vnd.ms-excel.addin.macroEnabled.12',                          'Other' ),   // Excel add-in
	'.pptx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'Presentation' ), // PowerPoint presentation
	'.pptm' => array( 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',              'Presentation' ),   // " (macro-enabled)
	'.ppsx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',  'Presentation' ),   // PowerPoint slideshow
	'.ppsm' => array( 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',                 'Presentation' ),   // " (macro-enabled)
	'.potx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.template',   'Other' ),   // PowerPoint presentation template
	'.potm' => array( 'application/vnd.ms-powerpoint.template.macroEnabled.12',                  'Other' ),   // " (macro-enabled)
	'.ppam' => array( 'application/vnd.ms-powerpoint.addin.macroEnabled.12',                     'Other' ),   // PowerPoint add-in
	'.sldx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.slide',      'Presentation' ),   // PowerPoint presentation
	'.sldm' => array( 'application/vnd.ms-powerpoint.slide.macroEnabled.12',                     'Presentation' ),   // " (macro-enabled)

	// Open Office
	'.odt' => array( 'application/vnd.oasis.opendocument.text',                  'Article' ),
	'.ott' => array( 'application/vnd.oasis.opendocument.text-template',         'ArticleTemplate' ),
	'.oth' => array( 'application/vnd.oasis.opendocument.text-web',              'Article' ),
	'.odm' => array( 'application/vnd.oasis.opendocument.text-master',           'ArticleTemplate' ),
	'.ods' => array( 'application/vnd.oasis.opendocument.spreadsheet',           'Spreadsheet' ),
	'.ots' => array( 'application/vnd.oasis.opendocument.spreadsheet-template',  'Spreadsheet' ),
	'.odp' => array( 'application/vnd.oasis.opendocument.presentation',          'Presentation' ),
	'.otp' => array( 'application/vnd.oasis.opendocument.presentation-template', 'Other' ),

	// iWork
	'.numbers' => array( 'application/x-apple-numbers', 'Spreadsheet' ),
	'.pages' => array( 'application/x-apple-pages', 'Article' ),
	'.key' => array( 'application/x-apple-keynote', 'Presentation' ),

	// Compressed
	'.zip' => array( 'application/zip', 'Archive'),
	'.gz'  => array( 'application/x-gzip', 'Archive' ),
	'.dmg' => array( 'application/x-apple-diskimage', 'Other' ),
	'.htmlwidget' => array( 'application/ww-htmlwidget', 'Other'),
	'.ofip' => array( 'application/x-ofip+zip', 'Other'), // Obsoleted, files can still be downloaded from the system
	'.folio' => array( 'application/vnd.adobe.folio+zip', 'Other'), // For DPS
)));

// -------------------------------------------------------------------------------------------------
// System internals
// ===> DO NOT MAKE CHANGES TO THE FOLLOWING SECTION
// -------------------------------------------------------------------------------------------------

// ATTACHMODULO:
//    Maximum number of objects to be stored within one folder. Note one object can itself contain
//    many files so the number of files per folder will be many times greater. Default value: 100.
//
define ('ATTACHMODULO', 100);

