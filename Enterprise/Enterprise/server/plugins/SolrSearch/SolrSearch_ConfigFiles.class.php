<?php
/**
 * @package    Enterprise
 * @subpackage SolrSearch
 * @since      v10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Provides config_solr.php options to the Config Overview page (wwinfo.php) page and the phpinfo.htm file
 * in the server logging.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/ConfigFiles_EnterpriseConnector.class.php';

class SolrSearch_ConfigFiles extends ConfigFiles_EnterpriseConnector
{
	/**
	 * @inheritdoc
	 */
	public function getConfigFiles()
	{
		return array( 'config_solr.php' => BASEDIR.'/config/config_solr.php' );
	}
}