<?php

/**
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Call this script from from windows command line scheduler task to run 
 * FlickrBulkImport.php to import latest uploaded photo from Flickr.
 * 
 */

$url = $argv[2]; // Get the url parameter that pass in through command line

$ch = curl_init($url);

curl_exec($ch);

curl_close($ch);
?>