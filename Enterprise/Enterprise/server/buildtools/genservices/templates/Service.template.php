<?php
/**
 * /*SERVICE*/ /*INTFFULL*/ service.
 *
 * @since v/*SERVERVERSION*/
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once /*PROVIDERDIR*//interfaces/services//*INTFSHORTLOW*///*PLUGINSHORT*//*INTFSHORT*//*SERVICE*/Request.class.php';
require_once /*PROVIDERDIR*//interfaces/services//*INTFSHORTLOW*///*PLUGINSHORT*//*INTFSHORT*//*SERVICE*/Response.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class /*PLUGINSHORT*//*INTFSHORT*//*SERVICE*/Service extends EnterpriseService
{
	public function execute( /*PLUGINSHORT*//*INTFSHORT*//*SERVICE*/Request $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'/*PLUGINFULL*//*INTFFULL*/Service',
			'/*PLUGINSHORT*//*INTFSHORT*//*SERVICE*/',
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( /*PLUGINSHORT*//*INTFSHORT*//*SERVICE*/Request $req )
	{
		// TODO: perform the real service operation here
		return new /*PLUGINSHORT*//*INTFSHORT*//*SERVICE*/Response();
	}
}
