<?php
/**
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Provide Elvis DB model.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/DbModel_EnterpriseConnector.class.php';

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
