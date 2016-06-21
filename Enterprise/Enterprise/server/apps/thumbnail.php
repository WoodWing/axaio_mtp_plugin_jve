<?php
//
// +--------------------------------------------------------------------+
// | server\apps\thumbnail.php											|
// +--------------------------------------------------------------------+
// | This page displays an object's thumbnail.							|
// | The following HTTP parameters are accepted:						|
// | - $id (unique object DBID)											|
// | - $type (object type, such as Layout/Article/Image)				|
// | - $rendition (resource file, such as thmub/preview/native)			|
// | - $version (object version number, or empty for active version)	|
// +--------------------------------------------------------------------+
//

$id = $_GET['id']; // can this be an alien id?
@$type = $_GET['type'];
$rendition = $_GET['rendition'];
$version = @$_GET['version']; // string e.g. "1.5"

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR."/server/secure.php";

$ticket = checkSecure();

echo "<html><head><title>".formvar($type)." "
	.BizResources::localize("ACT_PREVIEW")
	."</title></head><body>";
//echo 'thumbnail.php: id=['.$id.'] type =['.$type.'] rendition =['.$rendition.'] version=['.$version.']<br>';
echo "<img src=\"image.php?id=".urlencode($id)."&amp;type=".urlencode($type)."&amp;rendition=".urlencode($rendition)."&amp;version=".urlencode($version)."\">";
echo "</body></html>";
?>
