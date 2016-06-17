<?php
/**
 * /*SERVICE*/ /*INTFFULL*/ service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v/*SERVERVERSION*/
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services//*INTFSHORTLOW*///*INTFSHORT*//*SERVICE*/Request.class.php';
require_once BASEDIR.'/server/interfaces/services//*INTFSHORTLOW*///*INTFSHORT*//*SERVICE*/Response.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class /*INTFSHORT*//*SERVICE*/Service extends EnterpriseService
{
	public function execute( /*INTFSHORT*//*SERVICE*/Request $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'/*INTFFULL*/Service',
			'/*INTFSHORT*//*SERVICE*/', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( /*INTFSHORT*//*SERVICE*/Request $req )
	{
		// TODO: perform the real service operation here
		return new /*INTFSHORT*//*SERVICE*/Response();
	}
}
