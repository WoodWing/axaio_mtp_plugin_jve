<?php
/**
 * CopyIssues Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCopyIssuesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCopyIssuesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCopyIssuesService extends EnterpriseService
{
	public function execute( AdmCopyIssuesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCopyIssues', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmCopyIssuesRequest $req )
	{
		$copyIssueIds = array();
		try {
			require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
			if( $req->Issues ) foreach( $req->Issues as $issue ) {
				// $req->NamePrefix = Debug feature; name prefix to apply to all copied items inside publication. Just to ease recognizion.
				$namePrefix = isset( $req->NamePrefix ) ? $req->NamePrefix : '';
				$copyIssueIds[] = BizCascadePub::copyIssue( $req->IssueId, $issue, $namePrefix );
			}
			
			require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';
			$setup = new WW_Utils_ResolveBrandSetup();
			$setup->resolveIssuePubChannelBrand( $req->IssueId );
			$publicationObj= $setup->getPublication();
			$pubChannelObj = $setup->getPubChannelInfo();

			require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
			$copyIssues = BizAdmPublication::listIssuesObj( $this->User, null, $publicationObj->Id, $pubChannelObj->Id, $copyIssueIds );
			
		} catch( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', 'AdmCopyIssuesService::runCallback(): '.$e->__toString() );
			throw ($e);
		}
		return new AdmCopyIssuesResponse( $copyIssues );
	}
}
