<?php
/**
 * GetDatasource DataSource service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/dat/DatGetDatasourceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/dat/DatGetDatasourceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class DatGetDatasourceService extends EnterpriseService
{
	public function execute( DatGetDatasourceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'DataSourceService',
			'DatGetDatasource', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( DatGetDatasourceRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizDatasource.class.php';
		$datasource = BizDatasource::getDatasource( $req->DatasourceID );
		return new DatGetDatasourceResponse( $datasource['queries'], $datasource['bidirectional'] );
	}
}
