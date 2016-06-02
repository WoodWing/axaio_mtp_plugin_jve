<?php
/**
 * OnSave DataSource service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/dat/DatOnSaveRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/dat/DatOnSaveResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class DatOnSaveService extends EnterpriseService
{
	public function execute( DatOnSaveRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'DataSourceService',
			'DatOnSave', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( DatOnSaveRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizDatasource.class.php';
		/*$resp =*/ BizDatasource::onSave( $req->DatasourceID, $req->Placements, $this->User );
		return new DatOnSaveResponse();
	}
}
