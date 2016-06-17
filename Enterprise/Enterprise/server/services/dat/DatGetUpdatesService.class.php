<?php
/**
 * GetUpdates DataSource service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/dat/DatGetUpdatesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/dat/DatGetUpdatesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class DatGetUpdatesService extends EnterpriseService
{
	public function execute( DatGetUpdatesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'DataSourceService',
			'DatGetUpdates', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( DatGetUpdatesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizDatasource.class.php';
		$records = BizDatasource::getUpdates( $req->ObjectID, $req->UpdateID, $this->User );
		return new DatGetUpdatesResponse( $records );
	}
}
