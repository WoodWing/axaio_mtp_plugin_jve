<?php

/*
 * Callback interface called by FolderUtils::scanDirForFiles function. <br/>
 * Used to iterate through child files and folders of a specified root folder. <br/>
 *
 * @package SCEnterprise
 * @subpackage Utils
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
*/

interface FolderIterInterface
{
	public function iterFile( $filePath, $level );
	public function skipFile( $filePath, $level );
	
	public function iterFolder( $folderPath, $level );
	public function skipFolder( $folderPath, $level );
}
?>