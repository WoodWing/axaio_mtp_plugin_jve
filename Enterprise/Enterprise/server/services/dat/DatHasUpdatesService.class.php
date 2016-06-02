<?php
/**
 * HasUpdates DataSource service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/dat/DatHasUpdatesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/dat/DatHasUpdatesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class DatHasUpdatesService extends EnterpriseService
{
	public function execute( DatHasUpdatesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'DataSourceService',
			'DatHasUpdates', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( DatHasUpdatesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizDatasource.class.php';
		BizDatasource::hasUpdates( $req->DatasourceID, $this->User, $req->FamilyValue );
		return new DatHasUpdatesResponse();
	}
}
