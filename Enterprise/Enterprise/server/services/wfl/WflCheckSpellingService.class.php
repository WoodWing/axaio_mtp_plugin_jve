<?php
/**
 * CheckSpelling Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCheckSpellingRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflCheckSpellingResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflCheckSpellingService extends EnterpriseService
{
	public function execute( WflCheckSpellingRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflCheckSpelling', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflCheckSpellingRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';
		$bizSpelling = new BizSpelling();
		$misspelledWords = $bizSpelling->checkSpelling( $req->PublicationId, $req->Language, $req->WordsToCheck );
		
		$resp = new WflCheckSpellingResponse();
		$resp->MisspelledWords = $misspelledWords;
		return $resp;
	}
}
