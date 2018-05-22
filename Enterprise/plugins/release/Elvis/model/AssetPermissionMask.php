<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class AssetPermissionMask {
	
	/**
	 * Allow assets to be viewed. Metadata and thumbnail can be viewed. If no
	 * VIEW permissions are available for individual assets or their
	 * zone/folder, they will not be displayed in search results. Metadata is
	 * itself filtered by field permissions. Can be set for zones/folders and/or
	 * individual assets.
	 */
	const VIEW = 2;

	/**
	 * Allows assets to be previewed. This permission is an extension on the
	 * VIEW permission. Can be set for zones/folders and/or individual assets.
	 */
	const VIEW_PREVIEW = 4;

	/**
	 * Allows original asset to be used, for example by downloading it or
	 * exporting it to other systems. Can be set for zones/folders and/or
	 * individual assets.
	 */
	const USE_ORIGINAL = 16;

	/**
	 * Allow metadata of assets to be modified. Can be set for zones/folders,
	 * NOT for individual assets.
	 */
	const EDIT_METADATA = 8;

	/**
	 * Allows asset to be updated with a new file, the original will be
	 * overwritten. Can be set for zones/folders, NOT for individual assets.
	 */
	const EDIT = 32;

	/**
	 * Allows assets to be renamed.
	 *
	 * Technically this is the same operation as move, the distinction is that a
	 * rename only changes the filename and not the folderPath.
	 */
	const RENAME = 256;

	/**
	 * Allows assets to be moved between folders.
	 *
	 * Technically this is the same operation as rename, the distinction is that
	 * a rename only changes the filename and not the folderPath.
	 */
	const MOVE = 512;

	/**
	 * Allow subfolders and assets to be created in the specified zone/folder.
	 * Can be set for zones/folders, NOT for individual assets.
	 */
	const CREATE = 1;

	/**
	 * Allows assets to be deleted. Can be set for zones/folders, NOT for
	 * individual assets.
	 */
	const DELETE = 64;

	/**
	 * Do not use yourself!
	 *
	 * This is a very special permission used only internally by the server to
	 * differentiate between full VIEW and VIEW_ON_BROWSE only permissions on
	 * folders. These folders have no permissions, but to make their children
	 * visible they have to be browseable. VIEW_ON_BROWSE permissions are NOT
	 * inherited by subfolders.
	 *
	 * This permission should NEVER be set from the admin!
	 */
	const VIEW_ON_BROWSE = 128;
	
}