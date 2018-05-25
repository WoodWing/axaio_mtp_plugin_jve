<?php
/**
 * GetRecords DataSource service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/dat/DatGetRecordsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/dat/DatGetRecordsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class DatGetRecordsService extends EnterpriseService
{
	public function execute( DatGetRecordsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'DataSourceService',
			'DatGetRecords', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( DatGetRecordsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizDatasource.class.php';
		$records = BizDatasource::getRecords( $this->User, $req->ObjectID, $req->QueryID, $req->DatasourceID, $req->Params );
		return new DatGetRecordsResponse( $records );
	}
}
