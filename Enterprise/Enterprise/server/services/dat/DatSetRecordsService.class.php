<?php
/**
 * SetRecords DataSource service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/dat/DatSetRecordsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/dat/DatSetRecordsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class DatSetRecordsService extends EnterpriseService
{
	public function execute( DatSetRecordsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'DataSourceService',
			'DatSetRecords', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( DatSetRecordsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizDatasource.class.php';
		BizDatasource::setRecords( $this->User, $req->ObjectID, $req->DatasourceID, $req->QueryID, $req->Params, $req->Records );
		return new DatSetRecordsResponse();
	}
}
