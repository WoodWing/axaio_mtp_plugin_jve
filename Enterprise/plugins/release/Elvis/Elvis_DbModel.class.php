<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/DbModel_EnterpriseConnector.class.php';

require_once dirname(__FILE__).'/dbmodel/Definition.class.php';

class Elvis_DbModel extends DbModel_EnterpriseConnector
{
	/**
	 * @inheritdoc
	 */
	public function getDbModelProvider()
	{
		return new Elvis_DbModel_Definition();
	}

}
