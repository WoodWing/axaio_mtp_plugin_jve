<?php
/**
 * Provide Elvis DB model.
 *
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/DbModel_EnterpriseConnector.class.php';
require_once BASEDIR.'/config/config_elvis.php'; // auto-loading
require_once __DIR__.'/dbmodel/Definition.class.php';

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
