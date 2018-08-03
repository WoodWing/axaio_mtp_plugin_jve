<?php
/**
 * QueryDatasources DataSource service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/dat/DatQueryDatasourcesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/dat/DatQueryDatasourcesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class DatQueryDatasourcesService extends EnterpriseService
{
	public function execute( DatQueryDatasourcesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'DataSourceService',
			'DatQueryDatasources', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( DatQueryDatasourcesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizDatasource.class.php';
		$datasources = BizDatasource::queryDatasources( $req->PublicationID );
		return new DatQueryDatasourcesResponse( $datasources );
	}
}
