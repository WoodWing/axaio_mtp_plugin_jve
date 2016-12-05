<?php
/**
 * @package    Enterprise
 * @subpackage SipsPreview
 * @since      v10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Provides config_sips.php options to the Config Overview page (wwinfo.php) page and the phpinfo.htm file
 * in the server logging.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/ConfigFiles_EnterpriseConnector.class.php';

class SipsPreview_ConfigFiles extends ConfigFiles_EnterpriseConnector
{
	/**
	 * @inheritdoc
	 */
	public function getConfigFiles()
	{
		return array( 'config_sips.php' => BASEDIR.'/config/config_sips.php' );
	}
}