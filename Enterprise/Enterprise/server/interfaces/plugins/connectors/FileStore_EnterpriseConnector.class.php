<?php
/**
 * @package 	Enterprise
 * @subpackage  ServerPlugins
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class FileStore_EnterpriseConnector extends DefaultConnector
{
	/**
	 * In case the file format ($format) can not be found in the EXTENSIONMAP option of
	 * the configserver.php file, this function is called by the core server to allow the
	 * connector to return a postfix to be used in the filename at the time a file is 
	 * about to get saved in the FileStore.
	 *
	 * IMPORTANT: Once this function has returned a postfix, the invoked file can no longer 
	 * be found in case the Server Plug-in gets plugged out, or when it composes the postfix
	 * in an inconsistent manner. And when the postfix does not make the file path unique,
	 * files may get mixed up. Please pay attention for these risks when implementing this
	 * function.
	 *
	 * @param integer $id         Object id.
	 * @param string $rendition   Rendition (native, preview, thumb, etc).
	 * @param string $format      File format (mime type).
	 * @param string $version     Object version in major.minor notation.
	 * @param string $page        The layout page number. Typically used for thumb/preview/pdf per page.
	 * @param integer $edition    The object edition id.
	 * @return string Postifx to used in filename.
	 */
	public function mapFormatToPostfix( $id, $rendition, $format, $version, $page, $edition )
	{
		return '';
	}
	
	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}