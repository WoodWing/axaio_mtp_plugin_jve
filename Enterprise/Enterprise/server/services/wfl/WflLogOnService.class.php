<?php
/**
 * LogOn workflow business service.
 *
 * @package SCEnterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflLogOnService extends EnterpriseService
{
	public function execute( WflLogOnRequest $req )
	{
		return $this->executeService( 
			$req, 
			null,   		// ticket
			'WorkflowService',
			'WflLogOn', 	
			false,  		// don't check ticket
			true   		// use transactions
			);
	}

	public function runCallback( WflLogOnRequest $req )
	{
		require_once BASEDIR.'/server/utils/license/license.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';

		$info = Array();
		$lic = new License();
		$shortuser = null;
		
		// When concurrent license is stored at server, clients send SCEnt_ResolveServerSide to trigger
		// server to determine the serial key itself. This is typically used for the Web Editor, but
		// could be used for other clients as well.
		if( $req->ClientAppSerial == 'SCEnt_ResolveServerSide' ) {
			$tmpSerial = $lic->getSerial( $req->ClientAppProductKey );
			// Only set when resolved... (When failed, keep $appserial unchanged to fail later!)
			if( $tmpSerial === false ) {
				// When Web Editor does re-login and there is no CS Pro license installed we try using CS Basic license... (BZ#11392)
				if( substr( $req->ClientAppProductKey, 0, strlen('ContentStationPro') ) == 'ContentStationPro' ) {
					$tmpClientAppProductKey = str_replace('ContentStationPro', 'ContentStationBasic', $req->ClientAppProductKey );
					$tmpSerial = $lic->getSerial( $tmpClientAppProductKey );
					if( $tmpSerial !== false ) {
						$req->ClientAppSerial = $tmpSerial; 
						$req->ClientAppProductKey = $tmpClientAppProductKey;
					}
				}
			} else {
				$req->ClientAppSerial = $tmpSerial; 
			}
		}
		
		// When an IDS automation job executes in background, the redaction may went home 
		// already and so all their tickets became invalid. Therefore, instead of a real ticket,
		// a so called 'ticket seal' is round-tripped through IDS and the JS module and now 
		// arrives here. Thereby the user param is set to last modifier of the layout, the 
		// password is left empty and the client app name is set to IDS. 
		// Instead of validating the password, we check whether or not the ticket seal is
		// known in the IDS job queue, and if found, we check its secrets which tells us
		// whether or not the seal is valid for that job. When valid, we allow the job to logon.
		$isValidIdsTicketSeal = false;
		$checkLicenceStatus = true;
		if( !empty($req->Ticket) && empty($req->Password) ) {
			if( $req->ClientAppName == 'InDesign Server' ) {
				require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
				$jobRow = DBInDesignServerJob::getJobByTicketSeal( $req->Ticket );
				if( $jobRow ) {
					if( !$jobRow['starttime'] && !DBInDesignServerJob::checkTicketSeal($jobRow) ) {
						throw new BizException( 'ERR_TICKET', 'Client', 'SCEntError_InvalidTicket' );
					}
					$isValidIdsTicketSeal = true;
					$checkLicenceStatus = false;
					// Resolve the acting user by taking the last modifier of the layout.
					require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
					$shortuser = DBObject::getColumnValueByName( $jobRow['objid'], 'Workflow', 'modifier' );
					if( !$shortuser ) {
						throw new BizException( 'ERR_NOTFOUND', 'Client', 'SCEntError_ObjectNotFound: '.$jobRow['objid'] );
					}
				}
			}
		}
		
		// Determine the master ticket. 
		// When SC for IDS does login while the DPS tools are enabled, SC does another login.
		// The first time login is for "InDesign Server" while the second time is for 
		// "Digital Publishing Tools InDesign Server". From then on, SC will use the first ticket
		// and second ticket one by one to make sure both tickets won't expire and 
		// the DPS seat can not be taken away by another user.
		// This behaviour is challenging since we store the ticket in the IDS job 
		// which allows us to lookup for which job web services are requested.
		// To solve this, when the second DPS ticket (slave) is used, we lookup the 
		// first SC client ticket (master), which is the one stored in the job.
		$masterTicket = '';
		if( !empty($req->Ticket) && empty($req->Password) ) {
			if( stristr( $req->ClientAppName, 'InDesign Server' ) &&            // IDS is in the name,
				strcasecmp( $req->ClientAppName, 'InDesign Server' ) !== 0 ) {  // but there is more ...
				// For example, at this point: $app == 'Digital Publishing Tools InDesign Server'
				$masterTicket = $req->Ticket;
			}
		}
		
		// --- ticket interchange service ---
		// When Web Editor's ticket expired, the first best SOAP throws ticket expiration exception.
		// This is catched at Web Editor and it will try to do a 'silent logon' which arrives here.
		// In that case, the ticket of the Web(App) is used, in attempt to get a Web Editor ticket!
		// This is all done to avoid showing the logon dialog twice; one for editor and one for apps.
		// When the ticket is given, user and password are allowed to empty. If the Web(App) ticket is 
		// also invalid, or if something goes wrong here, we throw invalid ticket error to let clients
		// intercept (again), but now show a logon dialog to let user logon manually.
		if( !$isValidIdsTicketSeal ) {
			if( !empty($req->Ticket) && empty($req->Password) ) {
				$otherUser = DBTicket::checkTicket( $req->Ticket ); // still valid ticket? (not expired)
				if( $otherUser === false ) { // same user?
					throw new BizException( 'ERR_TICKET', 'Client', 'SCEntError_InvalidTicket', null, null, 'INFO' );
				}
				// We only allow interchanging tickets between DIFFERENT apps
				$ticketRow = DBTicket::getTicket( $req->Ticket );
				if( $ticketRow === false || $ticketRow['appname'] == $req->ClientAppName ) { // diff app?
					throw new BizException( 'ERR_TICKET', 'Client', 'SCEntError_InvalidTicket', null, null, 'INFO' );
				}
				// Third parties should not fool us...
				/* // This check always fails since we don't know the serial nor prodcode for the 'other' app...
				if( !DBTicket::checkTicketHash( $ticket, $clientip, $otherUser, $clientname, $appname, $appserial, $appproductcode )) {
					throw new BizException( 'ERR_TICKET', 'Client', 'SCEntError_InvalidTicket' );
				}*/
				// Get 'other' user
				require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
				$usrRow = DBUser::findUser( null, $otherUser, null );
				if( !$usrRow ) {
					throw new BizException( 'ERR_TICKET', 'Client', 'SCEntError_InvalidTicket', null, null, 'INFO' );
				}
				// Fill-in user name (derived from valid ticket) and see if we can login
				$req->User = $otherUser;
				$shortuser =  $otherUser;

				// WebEditor will always ask for ContentStationPro license, but if it's initiated by Content Station
				// it could be that the user did logon as a Basic although he/she has Pro access. So we check if that's
				// the case, if so we downgrade requested license to Basic
				if( substr( $req->ClientAppProductKey, 0, strlen('ContentStationPro') ) == 'ContentStationPro' &&
					substr( $ticketRow['appproductcode'], 0, strlen('ContentStation') ) == 'ContentStation') {
					$req->ClientAppProductKey = $ticketRow['appproductcode'];
				}
			} else {
				//In case the user is unknown, or expired/disabled etc., an exception will be thrown.
				if( !$shortuser ) {
					$shortuser = BizSession::validateUser( $req->User, $req->Password );
				}
				BizSession::loadUserLanguage( $shortuser ); // Make sure we set language before license check BZ#10290
			}
		}

		// For Content Station the Pro Edition is requested by default, but if user has no access rights to use
		// the Pro edition we automatically turn it into a request to logon as Basic Edition.
		// Note: If Pro licenses are exceeded and the user has access rights to Pro version the logon
		// will fail even if there are Basic licenses left. For this scenario the user deliberately has
		// to logon as Basic Edition.
		// NOTE: this code must run AFTER the ticket interchange service above.
		if( substr( $req->ClientAppProductKey, 0, strlen('ContentStationPro') ) == 'ContentStationPro' ) {
			// Content Station Pro license requested, detected independent of version
			// Now check access rights for this user
			require_once BASEDIR."/server/secure.php";
			require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
			$dbDriver = DBDriverFactory::gen();
			if( !$shortuser ) { // can be filled in above in ticket exchange service, in which caser we don't have a password
				$shortuser = BizSession::validateUser( $req->User, $req->Password );
			}
			$sth = getauthorizations( $dbDriver, $shortuser, BizAccessFeatureProfiles::ACCESS_CONTENTSTATIONPRO );
			if( !$dbDriver->fetch($sth) ) {
				// No access to Pro Edition, transform product key to basic key
				$req->ClientAppProductKey = str_replace('ContentStationPro', 'ContentStationBasic', $req->ClientAppProductKey );
			}
		}

		$licenseStatus1 = null;
		$errorMessage1 = '';
		// Check the license and number of concurrent users for this application
		if( $checkLicenceStatus ) { // Original ticket has been checked.
			$licenseStatus1 = $lic->getLicenseStatus( $req->ClientAppProductKey, $req->ClientAppSerial, $info, $errorMessage1 );
			if( $licenseStatus1 > WW_LICENSE_OK_MAX ) {
				if( $req->ClientAppName == 'WebEditor' ) { // Make message more readable (BZ#6600)
					throw new BizException( 'LIC_NO_WEBEDITOR_LICENSE_INSTALLED', 'Server', $errorMessage1 );
				} else {
					throw new BizException( 'ERR_LICENSE', 'Server', $errorMessage1 );
				}
			}
		}

		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$clientip = WW_Utils_UrlUtils::getClientIP();
		// Repair clientname if not given or matches IP (happens for WebEditor)
		if( empty( $req->ClientName ) ) {
			if( isset($_SERVER['REMOTE_HOST']) ) $req->ClientName = $_SERVER[ 'REMOTE_HOST' ];
			// >>> BZ#6359 Let's use ip since gethostbyaddr could be extreemly expensive! which also risks throwing "Maximum execution time of 11 seconds exceeded" 
			if( empty($req->ClientName) ) { $req->ClientName = $clientip; }
			//if ( !$clientname || ($clientname == $clientip )) {
			//	$clientname = gethostbyaddr($clientip); 
			//}
			// <<<
		}
				
		//Check the license and number of concurrent connections for SCE Server
		//In case no license has been installed yet, appserial will be false and getLicenseStatus() will handle that
		$licenseStatus2 = null;
		$errorMessage2 = '';
		if( $checkLicenceStatus ) { // Original ticket has been checked.
			$SCEAppserial = $lic->getSerial( PRODUCTKEY );
			$licenseStatus2 = $lic->getLicenseStatus( PRODUCTKEY, $SCEAppserial, $info, $errorMessage2, '',
				$shortuser, $req->ClientAppName, $req->ClientAppVersion );
			if( $licenseStatus2 > WW_LICENSE_OK_MAX ) {
				throw new BizException( 'ERR_LICENSE', 'Server', $errorMessage2 );
			}
		}

		// Since 9.7 the RequestInfo param is introduced that supersedes RequestTicket.
		// Validate the combination of both params and correct it to support old clients.
		if( is_bool( $req->RequestTicket ) && is_array( $req->RequestInfo ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 
				'Invalid params provided for '.__METHOD__.'(). '.
				'Please use RequestTicket (obsoleted) or RequestInfo (prefered), but not both.' );
		}
		if( is_bool( $req->RequestTicket ) ) {
			if( $req->RequestTicket == true ) {
				$req->RequestInfo = array(); // resolve ticket only
			} else {
				$req->RequestInfo = null; // resolve all response info
			}
		}

		$resp = BizSession::logOn(
			$req->User,
			$shortuser,
			$req->Ticket,
			$req->Server,
			$req->ClientName,
			$req->Domain,
			$req->ClientAppName,
			$req->ClientAppVersion,
			$req->ClientAppSerial,
			$req->ClientAppProductKey,
			$req->RequestInfo,
			$masterTicket,
			$req->Password );

		if( $checkLicenceStatus ) {
			$lic->addToUserMessages( $shortuser, $licenseStatus1, $errorMessage1, $resp->MessageList->Messages );
			$lic->addToUserMessages( $shortuser, $licenseStatus2, $errorMessage2, $resp->MessageList->Messages );
		}
		// If we have a MessageList we need to ensure there is a ReadMessageIDs property.
		if (isset($resp->MessageList) && !isset($resp->MessageList->ReadMessageIDs)){
			$resp->MessageList->ReadMessageIDs = array();
		}

		// If we have a MessageList we need to ensure there is a DeleteMessageIDs property.
		if (isset($resp->MessageList) && !isset($resp->MessageList->DeleteMessageIDs)){
			$resp->MessageList->DeleteMessageIDs = array();
		}
		
		// In case a third party tries to by-pass our license check in DBNewTicket() of DBTicket.class.php, 
		// they will also have to think out a ticket id. They don't know that the first part of the ticket is a hashcode.
		// This way, we force that all LogOn calls should use the DBNewTicket() to create tickets.
		if( !DBTicket::checkTicketHash( $resp->Ticket, '', $req->User, $req->ClientName, $req->ClientAppName, 
										$req->ClientAppSerial, $req->ClientAppProductKey ) ) {
			$errorDetail = 'System error: Invalid ticket format'; //Abuse
			throw new BizException( 'ERR_SYSTEM_PROBLEM', 'Server', $errorDetail );
		}
		
		// Interchange the ticket seal with a session ticket; Store the ticket in the IDS job table.
		if( $isValidIdsTicketSeal ) {
			// When IDS job script does logon it has a ticket seal. When this seal is found valid
			// it can be 'interchanged' with a real session ticket, which can be set here.
			// For succeeding web service requests, the session ticket is passed in by the IDS script.
			// Having that in place, e.g. the job type can be resolved through the ticket.
			require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
			BizInDesignServerJobs::setTicketByTicketSeal( $resp->Ticket, $req->Ticket, $shortuser );
		}
		
		// Restructure messages from 8.0 (or newer) to 7.x (or older), to make old clients happy.
		if( $req->ClientAppVersion ) { // no version info? => then default behavior (new message structure)
			$clientMajorVersion = intval( BizSession::formatClientVersion( $req->ClientAppVersion, 1 ) );
			if( $clientMajorVersion && $clientMajorVersion <= 7 ) { // 7.x (or older) => restructure!
				$resp->Messages = $resp->MessageList->Messages;
				$resp->MessageList = null;
			}
		}

		return $resp;
	}
}
