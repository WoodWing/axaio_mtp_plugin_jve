<html>
<head>
	<title>Enterprise Server - Administration Service test page</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<?php 
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/utils/StopWatch.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

// Max length of name fields. Should match the DB model!
define( 'ADMTEST_USER_SHORTNAMELEN', 40 );
define( 'ADMTEST_USER_FULLNAMELEN', 255 );
define( 'ADMTEST_USERGROUP_NAMELEN', 100 );
define( 'ADMTEST_PUBLICATION_NAMELEN', 255 );
define( 'ADMTEST_PUBCHANNEL_NAMELEN', 255 );
define( 'ADMTEST_ISSUE_NAMELEN', 255 );
define( 'ADMTEST_SECTION_NAMELEN', 255 );
define( 'ADMTEST_EDITION_NAMELEN', 255 );

set_time_limit(3600);
if( !defined('TESTSUITE') ) {
	showError( 'The TESTSUITE setting was not found. '.
		'Please add the TESTSUITE setting to your configserver.php file.', 'CONFIGURATION ERROR' );
	die();
}
$suiteOpts = unserialize( TESTSUITE );

$watch = new StopWatch();

if( array_key_exists( 'mode', $_REQUEST ) ) {
	switch( $_REQUEST['mode'] ) {
		case 'business':
			AdminTestApp:: runBusinessTest( $watch, $suiteOpts['User'], $suiteOpts['Password'] );
			break;
		case 'presence':
			AdminTestApp:: runPresenceTest( $watch, $suiteOpts['User'], $suiteOpts['Password'] );
			break;
	}
} else {
	AdminTestApp:: runPresenceTest( $watch, $suiteOpts['User'], $suiteOpts['Password'] );
}
?>
</body>	
</html>

<?php
class AdminTestApp
{
	static public function runPresenceTest( $watch, $user, $password )
	{
		$return = null;
		$ticket = null;
		
		$admintest = new AdminTestSoapProxy( $watch );
		
		//Log On
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnRequest.class.php';
		$logon = new AdmLogOnRequest();
		$logon->AdminUser 			= $user;
		$logon->Password 			= $password;
		$logon->ClientName 			= 'My machine IP';
		$logon->ClientAppName 		= 'Web';
		$logon->ClientAppVersion	= 'v'.SERVERVERSION;
		$ticket = $admintest->logOn( $logon, 'Log On' );

		//	Create new usergroup
		$adminusergrp = new AdmUserGroup();
		$adminusergrp->Name 		= 'AdminGroup_' . date('dmy_his');
		$adminusergrp->Description 	= 'Admin Group';
		$adminusergrp->Admin 		= true;
		$adminusergrp->Routing 		= false;
		
		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateUserGroupsRequest.class.php';
		$newusergrps = new AdmCreateUserGroupsRequest();
		$newusergrps->Ticket 		= $ticket;
		$newusergrps->RequestModes 	= array();
		$newusergrps->UserGroups 	= array($adminusergrp);
		$return = $admintest->createUserGroups( $newusergrps, 'Create UserGroups' );
		
		// Get the new created usergroups
		$usergroupIds = array();
		$usergroups = $return->UserGroups;
		foreach( $usergroups as $usergroup ) {
			$usergroupIds[] = $usergroup->Id;
		}
		
		//	Create new user
		$newuser = new AdmUser();
		$newuser->Name				= 'User_'. date('dmy_his');
		$newuser->Password			= 'ww';
		$newuser->EmailAddress		= 'user@woodwing.com';
		$newuser->Deactivated 		= true;
		$newuser->FixedPassword 	= true;
		$newuser->EmailUser			= true;
		$newuser->EmailGroup		= true;
		$newuser->ValidFrom			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d') , date('Y')));
		$newuser->ValidTill			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+90, date('Y')));
		$newuser->TrackChangesColor	= '00FFFF'; // cyan
		$newuser->PasswordExpired	= 90;
		$newuser->Language			= 'nlNL';
		$newuser->Organization		= 'WoodWing';
		$newuser->Location			= 'Zaandam';
		
		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateUsersRequest.class.php';
		$newusers = new AdmCreateUsersRequest();
		$newusers->Ticket 			= $ticket;
		$newusers->RequestModes 	= array();
		$newusers->Users 			= array($newuser);
		$return = null;
		$return = $admintest->createUsers( $newusers, 'Create Users' );
		
		// Get the new created users
		$userIds = array();
		$users = $return->Users;
		foreach( $users as $usr ) {
			$userIds[] = $usr->Id;
		}
		
		// Add Users To Group
		require_once BASEDIR.'/server/interfaces/services/adm/AdmAddUsersToGroupRequest.class.php';
		$adduserstogroup = new AdmAddUsersToGroupRequest();
		$adduserstogroup->Ticket	= $ticket;
		$adduserstogroup->UserIds 	= array( $users[0]->Id );
		$adduserstogroup->GroupId 	= $usergroups[0]->Id;
		$admintest->addUsersToGroup( $adduserstogroup );
		
		//	Remove Users From Group
		require_once BASEDIR.'/server/interfaces/services/adm/AdmRemoveUsersFromGroupRequest.class.php';
		$removeusersfromgroup = new AdmRemoveUsersFromGroupRequest();
		$removeusersfromgroup->Ticket	= $ticket;
		$removeusersfromgroup->UserIds 	= array($users[0]->Id);
		$removeusersfromgroup->GroupId 	= $usergroups[0]->Id;
		$admintest->removeUsersFromGroup( $removeusersfromgroup );
		
		//	Add Groups To User
		require_once BASEDIR.'/server/interfaces/services/adm/AdmAddGroupsToUserRequest.class.php';
		$addgroupstouser = new AdmAddGroupsToUserRequest();
		$addgroupstouser->Ticket	= $ticket;
		$addgroupstouser->GroupIds 	= array($usergroups[0]->Id);
		$addgroupstouser->UserId 	= $users[0]->Id;
		$admintest->addGroupsToUser( $addgroupstouser );
		
		//	Remove Groups From User
		require_once BASEDIR.'/server/interfaces/services/adm/AdmRemoveGroupsFromUserRequest.class.php';
		$removegroupsfromuser = new AdmRemoveGroupsFromUserRequest();
		$removegroupsfromuser->Ticket	= $ticket;
		$removegroupsfromuser->GroupIds	= array($usergroups[0]->Id);
		$removegroupsfromuser->UserId 	= $users[0]->Id;
		$admintest->removeGroupsFromUser( $removegroupsfromuser );
		
		//	Add Groups To User
		require_once BASEDIR.'/server/interfaces/services/adm/AdmAddGroupsToUserRequest.class.php';
		$addgroupstouser = new AdmAddGroupsToUserRequest();
		$addgroupstouser->Ticket	= $ticket;
		$addgroupstouser->GroupIds 	= array($usergroups[0]->Id);
		$addgroupstouser->UserId 	= $users[0]->Id;
		$admintest->addGroupsToUser( $addgroupstouser );
		
		//	Get all usergroups
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetUserGroupsRequest.class.php';
		$usergroup = new AdmGetUserGroupsRequest();
		$usergroup->Ticket 		= $ticket;
		$usergroup->RequestModes = array('GetUsers');
		$admintest->getUserGroups( $usergroup, 'Get All User Groups' );
		
		//	Get all usergroups on specific user
		$usergroup->UserId 		= $users[0]->Id;
		$admintest->getUserGroups( $usergroup, 'Get All UserGroups on Specific User' );
		
		//	Get specific usergroups
		$usergroup->UserId 		= null;
		$usergroup->GroupIds 	= $usergroupIds;
		$admintest->getUserGroups( $usergroup, 'Get Specific UserGroups' );
		
		//	Get all users
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetUsersRequest.class.php';
		$user = new AdmGetUsersRequest();
		$user->Ticket 			= $ticket;
		$user->RequestModes 	= array('GetUserGroups');
		$admintest->getUsers( $user, 'Get All Users' );
		
		//	Get all users within specific group
		$user->GroupId 		= $usergroups[0]->Id;
		$admintest->getUsers( $user, 'Get All Users on Specific UserGroups' );
		
		//	Get specific users
		$user->GroupId 		= null;
		$user->UserIds		= $userIds;
		$admintest->getUsers( $user, 'Get Specific Users' );
		
		//	Modify user
		$moduser = new AdmUser();
		$moduser->Id				= $users[0]->Id;
		$moduser->Name				= 'User_Modified_' . date('dmy_his');
		$moduser->Password			= 'www';
		$moduser->EmailAddress		= 'user@woodwing.com';
		$moduser->Deactivated 		= false;
		$moduser->FixedPassword 	= false;
		$moduser->EmailUser			= false;
		$moduser->EmailGroup		= false;
		$moduser->Deactivated 		= true;
		$moduser->FixedPassword 	= true;
		$moduser->ValidFrom			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d') , date('Y')));
		$moduser->ValidTill			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+180, date('Y')));
		$moduser->PasswordExpired	= 180;
		$moduser->Organization		= 'WoodWing';
		$moduser->Location			= 'Zaandam';

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyUsersRequest.class.php';
		$modusers = new AdmModifyUsersRequest();
		$modusers->Ticket 			= $ticket;
		$modusers->RequestModes 	= array();
		$modusers->Users 			= array($moduser);
		$admintest->modifyUsers( $modusers, 'Modify Users' );
		
		// Modify usergroups with nil Id
		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyUserGroupsRequest.class.php';
		$modusergroup = new AdmModifyUserGroupsRequest();
		$modusergroup->Id				= $usergroups[0]->Id;
		$modusergroup->Name				= 'NormalGroup_' . date('dmy_his');
		$modusergroup->Description 		= 'Normal Group';
		$modusergroup->Admin 			= false;
		$modusergroup->Routing 			= true;

		$modusergroups = new AdmModifyUserGroupsRequest();
		$modusergroups->Ticket 			= $ticket;
		$modusergroups->RequestModes 	= array();
		$modusergroups->UserGroups 		= array( $modusergroup );
		$admintest->modifyUserGroups( $modusergroups, 'Modify UserGroups' );
		
		//	Create publications
		$newpub = new AdmPublication();
		$newpub->Name			= "New Pub's_". date('dmy_his');
		$newpub->Description 	= 'New Publication for Admin SOAP';
		$newpub->EmailNotify 	= false;
		$newpub->SortOrder 		= 1;
		$newpub->ReversedRead 	= false;
		$newpub->AutoPurge		= 1;

		$ch_pub = new AdmPublication();
		$ch_pub->Name			= AdminTestDataFactory::buildDifficultName('ch_uni_name');
		$kr_pub = new AdmPublication();
		$kr_pub->Name			= AdminTestDataFactory::buildDifficultName('kr_uni_name');
		$ja_pub = new AdmPublication();
		$ja_pub->Name			= AdminTestDataFactory::buildDifficultName('ja_uni_name');
		$ru_pub = new AdmPublication();
		$ru_pub->Name			= AdminTestDataFactory::buildDifficultName('ru_uni_name');

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreatePublicationsRequest.class.php';
		$newpubs = new AdmCreatePublicationsRequest();
		$newpubs->Ticket 		= $ticket;
		$newpubs->RequestModes 	= array();
		$newpubs->Publications	= array($newpub, $ch_pub, $kr_pub, $ja_pub, $ru_pub);
		$return = null;
		$return = $admintest->createPublications( $newpubs, 'Create Publications' );
		
		// Get the new created publications
		$pubIds	= array();
		$pubs = $return->Publications;
		foreach( $pubs as $pub ) {
			$pubIds[] = $pub->Id;
		}
		
		//	Get all publications
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPublicationsRequest.class.php';
		$publications = new AdmGetPublicationsRequest();
		$publications->Ticket 		=  $ticket;
		$publications->RequestModes = array('GetIssues', 'GetUsers');
		$admintest->getPublications( $publications, 'Get All Publications' );
		
		//	Get specific publications
		$publications->PublicationIds 	= $pubIds;
		$admintest->getPublications( $publications, 'Get Specific Publications' );
		
		//	Modify publication
		$modpub = new AdmPublication();
		$modpub->Id 			= $pubs[0]->Id;
		$modpub->Name			= "WW Pub's_M_" . date('dmy_his');
		$modpub->Description 	= 'Publication Modified';
		$modpub->EmailNotify 	= true;
		$modpub->SortOrder 		= 1;
		$modpub->ReversedRead 	= true;
		$modpub->AutoPurge		= 7;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyPublicationsRequest.class.php';
		$modifypubs = new AdmModifyPublicationsRequest();
		$modifypubs->Ticket 		= $ticket;
		$modifypubs->RequestModes 	= array();
		$modifypubs->Publications 	= array($modpub);
		$admintest->modifyPublications( $modifypubs, 'Modify Publications' );

		// 	Create new pubchannel
		$newpubchannel = new AdmPubChannel();
		$newpubchannel->Name			= 'PubChannel_' . date('dmy_his');
		$newpubchannel->Type			= 'print';
		$newpubchannel->Description		= 'Publication Channel';

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreatePubChannelsRequest.class.php';
		$newpubchannels = new AdmCreatePubChannelsRequest();
		$newpubchannels->Ticket 			= $ticket;
		$newpubchannels->RequestModes 		= array();
		$newpubchannels->PublicationId 		= $pubs[0]->Id;
		$newpubchannels->PubChannels 		= array($newpubchannel);
		$return = null;
		$return = $admintest->createPubChannels( $newpubchannels, 'Create PubChannels' );
		
		// Get the new created pubchannels
		$newpubchannelIds	= array();
		$newpubchannels 	= $return->PubChannels;
		foreach( $newpubchannels as $pubchannel ) {
			$newpubchannelIds[] = $pubchannel->Id;
		}

		//	Get all pubchannels
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPubChannelsRequest.class.php';
		$pubchannels = new AdmGetPubChannelsRequest();
		$pubchannels->Ticket 		=  $ticket;
		$pubchannels->RequestModes 	= array('GetIssues', 'GetEditions');
		$pubchannels->PublicationId	= $pubs[0]->Id;
		$admintest->getPubChannels( $pubchannels, 'Get All PubChannels' );
		
		//	Get specific pubchannels
		$pubchannels->PubChannelIds = $newpubchannelIds;
		$admintest->getPubChannels( $pubchannels, 'Get Specific PubChannels' );

		//	Modify pubchannel
		$modpubchannel = new AdmPubChannel();
		$modpubchannel->Id			=  $newpubchannels[0]->Id;
		$modpubchannel->Name 		= "PubChannel_Modified_" . date('dmy_his');
		$modpubchannel->Description = 'Publication Channel description modified';

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyPubChannelsRequest.class.php';
		$modifypubchannels = new AdmModifyPubChannelsRequest();
		$modifypubchannels->Ticket 			= $ticket;
		$modifypubchannels->RequestModes 	= array('GetIssues', 'GetEditions');
		$modifypubchannels->PublicationId	= $pubs[0]->Id;
		$modifypubchannels->PubChannels 	= array($modpubchannel);
		$admintest->modifyPubChannels( $modifypubchannels, 'Modify PubChannel' );

		// 	Create new issue with nil name
		$newiss = new AdmIssue();
		$newiss->Name					= 'Issue_' . date('dmy_his');
		$newiss->Description 			= 'New Issue';
		$newiss->SortOrder				= 2;
		$newiss->EmailNotify			= true;
		$newiss->ReversedRead			= true;
		$newiss->OverrulePublication	= true;
		$newiss->Deadline				= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+3, date('Y')));
		$newiss->ExpectedPages			= 32;
		$newiss->Subject				= 'World Cup';
		$newiss->Activated				= true;
		$newiss->Deadline				= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+3, date('Y')));

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateIssuesRequest.class.php';
		$newissues = new AdmCreateIssuesRequest();
		$newissues->Ticket 				= $ticket;
		$newissues->RequestModes 		= array();
		$newissues->PublicationId 		= $pubs[0]->Id;
		$newissues->PubChannelId		= $newpubchannels[0]->Id;
		$newissues->Issues 				= array($newiss);
		$return = null;
		$return = $admintest->createIssues( $newissues, 'Create Issues' );
		
		// Get new created issues
		$issueIds = array();
		$issues = $return->Issues;
		foreach( $issues as $iss ) {
			$issueIds[] = $iss->Id;
		}
		
		//	Get all issues
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetIssuesRequest.class.php';
		$issue = new AdmGetIssuesRequest();
		$issue->Ticket 			= $ticket;
		$issue->RequestModes 	= array( 'GetSections', 'GetEditions');
		$issue->PublicationId 	= $pubs[0]->Id;
		$issue->PubChannelId	= $newpubchannels[0]->Id;
		$admintest->getIssues( $issue, 'Get All Issues' );
		
		//	Get specific issues
		$issue->IssueIds 		= $issueIds;
		$admintest->getIssues( $issue, 'Get Specific Issues' );
		
		// 	Modify issue
		$modiss = new AdmIssue();
		$modiss->Id						= $issues[0]->Id;
		$modiss->Name					= 'Issue_M_' . date('dmy_his');
		$modiss->Description 			= 'Issue Modified';
		$modiss->SortOrder				= 2;
		$modiss->EmailNotify			= true;
		$modiss->ReversedRead			= true;
		$modiss->OverrulePublication	= true;
		$modiss->Deadline				= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+3, date('Y')));
		$modiss->ExpectedPages			= 32;
		$modiss->Subject				= 'World Cup';
		$modiss->Activated				= true;
		$modiss->PublicationDate		= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d'), date('Y')));

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyIssuesRequest.class.php';
		$modissues = new AdmModifyIssuesRequest();
		$modissues->Ticket 				= $ticket;
		$modissues->RequestModes 		= array();
		$modissues->PublicationId 		= $pubs[0]->Id;
		$modissues->PubChannelId		= $newpubchannels[0]->Id;
		$modissues->Issues 				= array($modiss);
		$admintest->modifyIssues( $modissues, 'Modify issue' );

		//	Create new sections
		$newsec = new AdmSection();
		$newsec->Name				= 'Sport_' . date('dmy_his');
		$newsec->Description 		= 'Sport Section';
		$newsec->SortOrder 			= 3;
		$newsec->Deadline			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+3, date('Y')));
		$newsec->ExpectedPages		= 64;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateSectionsRequest.class.php';
		$newsections = new AdmCreateSectionsRequest();
		$newsections->Ticket 		= $ticket;
		$newsections->RequestModes 	= array();
		$newsections->PublicationId = $pubs[0]->Id;
		$newsections->IssueId		= $issues[0]->Id;
		$newsections->Sections 		= array($newsec);
		$return = null;
		$return = $admintest->createSections( $newsections, 'Create Sections' );
		
		// Get new created section
		$sectionIds = array();
		$sections = $return->Sections;
		foreach( $sections as $sec ) {
			$sectionIds[] = $sec->Id;
		}
		
		//	Get all sections
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetSectionsRequest.class.php';
		$section = new AdmGetSectionsRequest();
		$section->Ticket 		= $ticket;
		$section->RequestModes 	= array();
		$section->PublicationId = $pubs[0]->Id;
		$section->IssueId 		= $issues[0]->Id;
		$admintest->getSections( $section, 'Get All Sections' );
		
		//	Get specific sections
		$section->SectionIds 	= $sectionIds;
		$admintest->getSections( $section, 'Get Specific Sections' );
		
		//	Modify sections
		$modsec = new AdmSection();
		$modsec->Id						= $sections[0]->Id;
		$modsec->Name					= 'Business_' . date('dmy_his');
		$modsec->Description 			= 'Business Section';
		$modsec->SortOrder 				= 2;
		$modsec->Deadline				= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+6, date('Y')));

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifySectionsRequest.class.php';
		$modifysections = new AdmModifySectionsRequest();
		$modifysections->Ticket 		= $ticket;
		$modifysections->PublicationId 	= $pubs[0]->Id;
		$modifysections->IssueId		= $issues[0]->Id;
		$modifysections->Sections 		= array($modsec);
		$admintest->modifySections( $modifysections, 'Modify Sections' );
		
		//	Create new edition
		$newedi = new AdmEdition();
		$newedi->Name 				= 'South_' . date('dmy_his');
		$newedi->Description 		= 'South Edition';
		$newedi->SortOrder 			= 2;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateEditionsRequest.class.php';
		$neweditions = new AdmCreateEditionsRequest();
		$neweditions->Ticket 		= $ticket;
		$neweditions->PublicationId = $pubs[0]->Id;
		$neweditions->PubChannelId	= $newpubchannels[0]->Id;
		$neweditions->IssueId		= $issues[0]->Id;
		$neweditions->Editions 		= array($newedi);
		$return = null;
		$return = $admintest->createEditions( $neweditions, 'Create Editions' );
		
		// Get new created editions
		$editionIds = array();
		$editions = $return->Editions;
		foreach( $editions as $edi ) {
			$editionIds[] = $edi->Id;
		}
	
		//	Get all editions
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetEditionsRequest.class.php';
		$edition = new AdmGetEditionsRequest();
		$edition->Ticket 			= $ticket;
		$edition->PublicationId 	= $pubs[0]->Id;
		$edition->PubChannelId		= $newpubchannels[0]->Id;
		$edition->IssueId			= $issues[0]->Id;
		$admintest->getEditions( $edition, 'Get All Editions' );
		
		//	Get specific editions
		$edition->EditionIds		= $editionIds;
		$admintest->getEditions( $edition, 'Get Specific Editions' );
		
		//	Modify edition with empty name
		$modedi = new AdmEdition();
		$modedi->Id						= $editions[0]->Id;
		$modedi->Name 					= 'Central_' . date('dmy_his');
		$modedi->Description 			= 'Central Edition';
		$modedi->SortOrder 				= 2;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyEditionsRequest.class.php';
		$modifyeditions = new AdmModifyEditionsRequest();
		$modifyeditions->Ticket 		= $ticket;
		$modifyeditions->PublicationId 	= $pubs[0]->Id;
		$modifyeditions->PubChannelId 	= $newpubchannels[0]->Id;
		$modifyeditions->IssueId		= $issues[0]->Id;
		$modifyeditions->Editions 		= array($modedi);
		$admintest->modifyEditions( $modifyeditions, 'Modify Editions' );

        // Delete test publications
        require_once BASEDIR.'/server/interfaces/services/adm/AdmDeletePublicationsRequest.class.php';
        $deletePublications = new AdmDeletePublicationsRequest();
        $deletePublications->Ticket = $ticket;
        $deletePublications->PublicationIds = $pubIds;
        $admintest->deletePublications( $deletePublications );

        // Delete test user
        require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteUsersRequest.class.php';
        $deleteUsers = new AdmDeleteUsersRequest();
        $deleteUsers->Ticket = $ticket;
        $deleteUsers->UserIds = $userIds;
        $admintest->deleteUsers( $deleteUsers );

        // Delete test user group
        $admintest->deleteUserGroups( $usergroupIds );

		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffRequest.class.php';
		$logoff = new AdmLogOffRequest();
		$logoff->Ticket = $ticket;
		$admintest->logOff( $logoff );
	}
	
	static public function runBusinessTest( $watch, $user, $password )
	{
		$ticket = null;
		$return = null;
		
		$admintest = new AdminTestSoapProxy( $watch );
		
		//	LogOn with non exist user
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnRequest.class.php';
		$logon = new AdmLogOnRequest();
		$logon->AdminUser 		= 'woodwing_w1w2';
		$logon->Password 		= 'ww';
		$logon->ClientName 		= 'My machine IP';
		$logon->ClientAppName 	= 'Web';
		$logon->ClientAppVersion = 'v'.SERVERVERSION;
		
		$ticket = $admintest->logOn( $logon, 'LogOn with non exist user', '(S1053)' );
		
		//	LogOn with wrong password
		$logon->AdminUser		= $user;
		$logon->Password 		= '11';
		$ticket = $admintest->logOn( $logon, 'LogOn with wrong password', '(S1053)' );
		
		//	Correct User Name, Password
		$logon->Password		= $password;
		$admintest->logOn( $logon, 'Log On As Admin User' );
		
		//	Logon twice on the same user
		$ticket = $admintest->logOn( $logon, 'Logon twice on same user' );
		
		
		//	Create new usergroups with empty name
		$adminusergrp = new AdmUserGroup();
		$adminusergrp->Name 		= AdminTestDataFactory::buildErraticName('empty_name');
		$adminusergrp->Description 	= 'UserGroup Admin Soap Test';
		$adminusergrp->Admin 		= true;
		$adminusergrp->Routing 		= false;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateUserGroupsRequest.class.php';
		$newusergrps = new AdmCreateUserGroupsRequest();
		$newusergrps->Ticket 		= $ticket;
		$newusergrps->RequestModes 	= array();
		$newusergrps->UserGroups 	= array($adminusergrp);
		$admintest->createUserGroups( $newusergrps, 'Create new usergroups with empty name', '(S1032)' );
		
		//	Create new usergroups with long name
		$adminusergrp->Name 		= AdminTestDataFactory::buildErraticName('long_name', ADMTEST_USERGROUP_NAMELEN);
		$admintest->createUserGroups( $newusergrps, 'Create new usergroups with long name', '(S1026)' );
		
		//	Create Admin and normal usergroups
		$adminusergrp->Name 		= 'Group_' . date('dmy_his');
		$adminusergrp->Description 	= 'Admin User Group';
		$adminusergrp->Admin 		= true;
		$adminusergrp->Routing 		= false;
		$newusergrps->UserGroups[]	= $adminusergrp;
		$admintest->createUserGroups( $newusergrps, 'Create two usergroups with same name', '(S1010)' );
		
		$adminusergrp->Name 		= 'AdminGroup_' . date('dmy_his');

		$normalusergrp = new AdmUserGroup();
		$normalusergrp->Name		= 'NormalGroup_' . date('dmy_his');
		$normalusergrp->Description = 'Normal User Group';
		$normalusergrp->Admin 		= false;
		$normalusergrp->Routing 	= false;
		$newusergrps->UserGroups	= array( $adminusergrp, $normalusergrp );
		$return = $admintest->createUserGroups( $newusergrps, 'Create UserGroups' );
		
		$admintest->createUserGroups( $newusergrps, 'Create existing usergroups', '(S1010)' );
		
		// Get the new created usergroups
		$usergroupIds = array();
		$usergroups = $return->UserGroups;
		foreach( $usergroups as $usergroup ) {
			$usergroupIds[] = $usergroup->Id;
		}
		
		//	Create new user with nil name
		$newuser = new AdmUser();
		$newuser->Deactivated 		= true;
		$newuser->FixedPassword 	= true;
		$newuser->EmailUser			= true;
		$newuser->EmailGroup		= true;
		$newuser->Password			= 'test';

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateUserGroupsRequest.class.php';
		$newusers = new AdmCreateUserGroupsRequest();
		$newusers->Ticket 			= $ticket;
		$newusers->RequestModes 	= array();
		$newusers->Users 			= array($newuser);
		$admintest->createUsers( $newusers, 'Create new user with nil name', '(S1032)' );
		
		//	Create new user with empty name
		$newuser->Name 				= AdminTestDataFactory::buildErraticName('empty_name');
		$admintest->createUsers( $newusers, 'Create new user with empty name', '(S1032)' );
		
		//	Create new user with long name
		$newuser->Name 				= AdminTestDataFactory::buildErraticName('long_name', ADMTEST_USER_SHORTNAMELEN);
		$admintest->createUsers( $newusers, 'Create new user with long name', '(S1026)' );

		//	Create new user with long name
		$newuser->Name				= 'User_'. date('dmy_his');
		$newuser->FullName 			= AdminTestDataFactory::buildErraticName('long_name', ADMTEST_USER_FULLNAMELEN);
		$admintest->createUsers( $newusers, 'Create new user with long name', '(S1026)' );
		$newuser->FullName			= null; // restore
		
		//	Create new user with nil password
		$newuser->Name				= 'User_'. date('dmy_his');
		$newuser->Password			= null;
		$admintest->createUsers( $newusers, 'Create new user with nil password', '(S1033)' );
		$newuser->Password			= 'test'; // restore
	
		//	Create new user with empty password
		$newuser->Name				= 'User_'. date('dmy_his');
		$newuser->Password			= AdminTestDataFactory::buildErraticName('empty_name');
		$admintest->createUsers( $newusers, 'Create new user with nil password', '(S1033)' );
		$newuser->Password			= 'test'; // restore
		
		//	Create new user with bad email
		$newuser->EmailAddress		= AdminTestDataFactory::buildDifficultName('bad_email');
		$admintest->createUsers( $newusers, 'Create new user with invalid email', '(S1018)' );
		
		//	Create new user with wrong date range
		$newuser->EmailAddress		= 'user@woodwing.com';
		$newuser->ValidFrom			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+3, date('Y')));
		$newuser->ValidTill			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d'), date('Y')));
		$admintest->createUsers( $newusers, 'Create new user with wrong date range', BizResources::localize('INVALID_DATE') ); // TODO: introduce S-code!
		
		//	Create new user with wrong trackchangescolor
		$newuser->ValidFrom			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d') , date('Y')));
		$newuser->ValidTill			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+90, date('Y')));
		$newuser->TrackChangesColor	= 'p0o9i8';
		$admintest->createUsers( $newusers, 'Create new user with invalid trackchangescolor', '(S1056)' );
		
		//	Create new user with all attribute set
		$newuser->TrackChangesColor	= '00FFFF'; // cyan
		$newuser->PasswordExpired	= 90;
		$newuser->Language			= 'nlNL';
		
		$newusers->Users[]			= $newuser;			
		$admintest->createUsers( $newusers, 'Create users with same name', '(S1010)' );
		
		//	Added second new user with single quote name and no attribute set
		$newuser->Name				= 'AdminUser_'. date('dmy_his');
		$newuser1 = new AdmUser();
		$newuser1->Name				= "User's_". date('dmy_his');
		$newuser1->Password			= 'test';
		$newusers->Users			= array($newuser, $newuser1);
		$return = $admintest->createUsers( $newusers, 'Create Users' );
		
		// Get the new created users
		$userIds = array();
		$users = $return->Users;
		foreach( $users as $usr ) {
			$userIds[] = $usr->Id;
		}

		// Add Users To Group
		require_once BASEDIR.'/server/interfaces/services/adm/AdmAddUsersToGroupRequest.class.php';
		$adduserstogroup = new AdmAddUsersToGroupRequest();
		$adduserstogroup->Ticket	= $ticket;
		$adduserstogroup->UserIds 	= array($users[0]->Id);
		$adduserstogroup->GroupId 	= $usergroups[0]->Id;
		$admintest->addUsersToGroup( $adduserstogroup );
		
		//	Add Groups To User
		require_once BASEDIR.'/server/interfaces/services/adm/AdmAddGroupsToUserRequest.class.php';
		$addgroupstouser = new AdmAddGroupsToUserRequest();
		$addgroupstouser->Ticket	= $ticket;
		$addgroupstouser->GroupIds 	= array($usergroups[1]->Id);
		$addgroupstouser->UserId 	= $users[1]->Id;
		$admintest->addGroupsToUser( $addgroupstouser );
		
		//	Remove Users From Group
		require_once BASEDIR.'/server/interfaces/services/adm/AdmRemoveUsersFromGroupRequest.class.php';
		$removeusersfromgroup = new AdmRemoveUsersFromGroupRequest();
		$removeusersfromgroup->Ticket	= $ticket;
		$removeusersfromgroup->UserIds 	= array($users[0]->Id);
		$removeusersfromgroup->GroupId 	= $usergroups[0]->Id;
		$admintest->removeUsersFromGroup( $removeusersfromgroup );
		
		//	Remove Groups From User
		require_once BASEDIR.'/server/interfaces/services/adm/AdmRemoveGroupsFromUserRequest.class.php';
		$removegroupsfromuser = new AdmRemoveGroupsFromUserRequest();
		$removegroupsfromuser->Ticket	= $ticket;
		$removegroupsfromuser->GroupIds	= array($usergroups[1]->Id);
		$removegroupsfromuser->UserId 	= $users[1]->Id;
		$admintest->removeGroupsFromUser( $removegroupsfromuser );
		
		// Add Users To Group
		require_once BASEDIR.'/server/interfaces/services/adm/AdmAddUsersToGroupRequest.class.php';
		$adduserstogroup = new AdmAddUsersToGroupRequest();
		$adduserstogroup->Ticket	= $ticket;
		$adduserstogroup->UserIds 	= array($users[0]->Id);
		$adduserstogroup->GroupId 	= $usergroups[0]->Id;
		$admintest->addUsersToGroup( $adduserstogroup );
		
		//	Add Groups To User
		require_once BASEDIR.'/server/interfaces/services/adm/AdmAddGroupsToUserRequest.class.php';
		$addgroupstouser = new AdmAddGroupsToUserRequest();
		$addgroupstouser->Ticket	= $ticket;
		$addgroupstouser->GroupIds 	= array($usergroups[1]->Id);
		$addgroupstouser->UserId 	= $users[1]->Id;
		$admintest->addGroupsToUser( $addgroupstouser );

		//	Get all usergroups
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetUserGroupsRequest.class.php';
		$usergroup = new AdmGetUserGroupsRequest();
		$usergroup->Ticket 		= $ticket;
		$usergroup->RequestModes = array('GetUsers');
		$admintest->getUserGroups( $usergroup, 'Get All User Groups' );

		//	Get usergroups on nonexist user
		$usergroup->UserId 		= 0;
		$admintest->getUserGroups( $usergroup, 'Get UserGroups of non exist User' );
		
		//	Get all usergroups on specific user
		$usergroup->UserId 		= $users[0]->Id;
		$admintest->getUserGroups( $usergroup, 'Get All UserGroups on Specific User' );

		//	Get specific nonexist usergroups
		$usergroup->UserId 		= null;
		$usergroup->GroupIds 	= array(0);
		$admintest->getUserGroups( $usergroup, 'Get Specific Non Exist UserGroups', '(S1056)' );
		
		//	Get specific usergroups
		$usergroup->UserId 		= null;
		$usergroup->GroupIds 	= $usergroupIds;
		$admintest->getUserGroups( $usergroup, 'Get Specific UserGroups' );
		
		//	Get all users
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetUsersRequest.class.php';
		$user = new AdmGetUsersRequest();
		$user->Ticket 			= $ticket;
		$user->RequestModes 	= array('GetUserGroups');
		$admintest->getUsers( $user, 'Get All Users' );
		
		//	Get all users within specific group
		$user->GroupId 		= $usergroups[0]->Id;
		$admintest->getUsers( $user, 'Get All Users on Specific UserGroups' );
		
		//	Get specific nonexist users
		$user->GroupId 		= null;
		$user->UserIds		= array(0);
		$admintest->getUsers( $user, 'Get Specific Non Exist Users', '(S1056)' );
		
		//	Get specific users
		$user->GroupId 		= null;
		$user->UserIds		= $userIds;
		$admintest->getUsers( $user, 'Get Specific Users' );
	
		//	Modify user with nil name
		$moduser = new AdmUser();
		$moduser->Id				= $users[1]->Id;
		$moduser->Deactivated 		= false;
		$moduser->FixedPassword 	= true;
		$moduser->Password			= 'test';

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyUsersRequest.class.php';
		$modusers = new AdmModifyUsersRequest();
		$modusers->Ticket 			= $ticket;
		$modusers->RequestModes 	= array();
		$modusers->Users 			= array($moduser);
		$admintest->modifyUsers( $modusers, 'Modify user with nil name', '(S1032)' );
		
		//	Modify user with empty name
		$moduser->Name				= AdminTestDataFactory::buildErraticName('empty_name');
		$admintest->modifyUsers( $modusers, 'Modify user with empty name', '(S1032)' );

		//	Modify user with long name
		$moduser->Name 				= AdminTestDataFactory::buildErraticName('long_name', ADMTEST_USER_SHORTNAMELEN);
		$admintest->modifyUsers( $modusers, 'Modify new user with long name', '(S1026)' );

		//	Modify user with long name
		$moduser->Name				= 'User_Modified_' . date('dmy_his');
		$moduser->FullName			= AdminTestDataFactory::buildErraticName('long_name', ADMTEST_USER_FULLNAMELEN);
		$admintest->modifyUsers( $modusers, 'Modify new user with long name', '(S1026)' );
		$moduser->FullName			= null; // restore
		
		//	Modify user with nil password
		$moduser->Name				= 'User_Modified_' . date('dmy_his');
		$moduser->Password			= null;
		$admintest->modifyUsers( $modusers, 'Modify user with nil password' ); // no check for (S1033) => nil allowed!
		$moduser->Password			= 'test'; // restore
		
		//	Modify user with empty password
		$moduser->Password			= AdminTestDataFactory::buildErraticName('empty_name'); 
		$admintest->modifyUsers( $modusers, 'Modify user with empty password', '(S1033)' );
		$moduser->Password			= 'test'; // restore
		
		//	Modify user with bad email
		$moduser->EmailAddress		= AdminTestDataFactory::buildDifficultName('bad_email');
		$admintest->modifyUsers( $modusers, 'Modify user with invalid email', '(S1018)' );
		
		//	Modify user with wrong date range
		$moduser->EmailAddress		= 'user@woodwing.com';
		$moduser->ValidFrom			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+3, date('Y')));
		$moduser->ValidTill			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d'), date('Y')));
		$admintest->modifyUsers( $modusers, 'Modify user with wrong date range', BizResources::localize('INVALID_DATE') ); // TODO: introduce S-code!
		
		//	Modify user with wrong trackchangescolor
		$moduser->ValidFrom			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d') , date('Y')));
		$moduser->ValidTill			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+30, date('Y')));
		$moduser->TrackChangesColor	= 'p0o9i8';
		$admintest->modifyUsers( $modusers, 'Modify user with wrong trackchangescolor', '(S1056)' );
		
		//	Modify user with all attribute set
		$moduser->TrackChangesColor	= '000087'; // dark blue
		$moduser->PasswordExpired	= 90;
		$admintest->modifyUsers( $modusers, 'Modify Users' );
		
		// Modify usergroups with nil Id
		$modusergroup = new AdmUserGroup();
		$modusergroup->Name				= 'UserGroup_M_' . date('dmy_his');
		$modusergroup->Description 		= 'UserGroup Being Modified';
		$modusergroup->Admin 			= true;
		$modusergroup->Routing 			= false;
		
		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyUserGroupsRequest.class.php';
		$modusergroups = new AdmModifyUserGroupsRequest();
		$modusergroups->Ticket 			= $ticket;
		$modusergroups->RequestModes 	= array();
		$modusergroups->UserGroups 		= array( $modusergroup );
		$admintest->modifyUserGroups( $modusergroups, 'Modify usergroups with nil Id' );
		
		//	Modify usergroups with empty name
		$modusergroup->Id				= $usergroups[0]->Id;
		$modusergroup->Name				= AdminTestDataFactory::buildErraticName('empty_name');
		$admintest->modifyUserGroups( $modusergroups, 'Modify usergroups with empty name', '(S1032)' );
		
		//	Modify usergroups with long name
		$modusergroup->Name				= AdminTestDataFactory::buildErraticName('long_name', ADMTEST_USERGROUP_NAMELEN);
		$admintest->modifyUserGroups( $modusergroups, 'Modify usergroups with long name', '(S1026)' );
		
		//	Modify usergroups with same name
		$modusergroup->Name				= 'AdminGroup_M_' . date('dmy_his');
		$modusergroup1 = new AdmUserGroup();
		$modusergroup1->Id				= $usergroups[1]->Id;
		$modusergroup1->Name			= 'AdminGroup_M_' . date('dmy_his');
		$modusergroups->UserGroups[]	= $modusergroup1;
		$admintest->modifyUserGroups( $modusergroups, 'Modify usergroups with same name', '(S1011)' );
		
		//	Modify UserGroups with valid data
		$modusergroup1->Name			= 'NormalGroup_M_' . date('dmy_his');
		$admintest->modifyUserGroups( $modusergroups, 'Modify UserGroups' );
		
		//	Create publication with empty name
		$newpub = new AdmPublication();
		$newpub->Name 			= AdminTestDataFactory::buildErraticName('empty_name');
		$newpub->Description 	= 'New Publication for Admin SOAP';
		$newpub->EmailNotify 	= false;
		$newpub->SortOrder 		= 1;
		$newpub->ReversedRead 	= false;
		$newpub->AutoPurge		= 1;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreatePublicationsRequest.class.php';
		$newpubs = new AdmCreatePublicationsRequest();
		$newpubs->Ticket 		= $ticket;
		$newpubs->RequestModes 	= array();
		$newpubs->Publications 	= array($newpub);
		$admintest->createPublications( $newpubs, 'Create publication with empty name', '(S1032)' );
		
		//	Create publication with long name
		$newpub->Name			= AdminTestDataFactory::buildErraticName('long_name', ADMTEST_PUBLICATION_NAMELEN);
		$admintest->createPublications( $newpubs, 'Create publication with long name', '(S1026)' );
		
		//	Create publication with valid data
		$newpub->Name			= "New Pub's_". date('dmy_his');
		$ch_pub = new AdmPublication();
		$ch_pub->Name			= AdminTestDataFactory::buildDifficultName('ch_uni_name');
		$kr_pub = new AdmPublication();
		$kr_pub->Name			= AdminTestDataFactory::buildDifficultName('kr_uni_name');
		$ja_pub = new AdmPublication();
		$ja_pub->Name			= AdminTestDataFactory::buildDifficultName('ja_uni_name');
		$ru_pub = new AdmPublication();
		$ru_pub->Name			= AdminTestDataFactory::buildDifficultName('ru_uni_name');
		$newpubs->Publications	= array($newpub, $ch_pub, $kr_pub, $ja_pub, $ru_pub);
		$return = $admintest->createPublications( $newpubs, 'Create Publications with Unicode' );
		
		$admintest->createPublications( $newpubs, 'Create publications with existing name', '(S1010)' );
		
		// Get the new created publications
		$pubIds	= array();
		$pubs = $return->Publications;
		foreach( $pubs as $pub ) {
			$pubIds[] = $pub->Id;
		}
		
		//	Get all publications
		$publications = new stdClass();
		$publications->Ticket 		=  $ticket;
		$publications->RequestModes = array('GetIssues', 'GetUsers');
		$admintest->getPublications( $publications, 'Get All Publications' );
		
		//	Get specific publications
		$publications->PublicationIds 	= $pubIds;
		$admintest->getPublications( $publications, 'Get Specific Publications' );
		
		//	Modify publication with nil id
		$modpub = new AdmPublication();
		$modpub->Name 			= "WW's Pub";
		$modpub->Description 	= 'Publication description modified';
		$modpub->EmailNotify 	= true;
		$modpub->SortOrder 		= 1;
		$modpub->ReversedRead 	= true;
		$modpub->AutoPurge		= 7;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyPublicationsRequest.class.php';
		$modifypubs = new AdmModifyPublicationsRequest();
		$modifypubs->Ticket 		= $ticket;
		$modifypubs->RequestModes 	= array();
		$modifypubs->Publications 	= array($modpub);
		$admintest->modifyPublications( $modifypubs, 'Modify publication with nil id', '(S1019)' );
		
		//	Modify publication with empty name
		$modpub->Id 			= $pubs[0]->Id;
		$modpub->Name 			= AdminTestDataFactory::buildErraticName('empty_name');
		$admintest->modifyPublications( $modifypubs, 'Modify publication with empty name', '(S1032)' );
		
		//	Modify publication with long name
		$modpub->Name 			= AdminTestDataFactory::buildErraticName('long_name', ADMTEST_PUBLICATION_NAMELEN);
		$admintest->modifyPublications( $modifypubs, 'Modify publication with long name', '(S1026)' );
		
		//	Modify publication with valid data
		$modpub->Name			= "WW Pub's_M_" . date('dmy_his');
		$admintest->modifyPublications( $modifypubs, "Modify Publications" );

		// 	Create new pubchannel
		$newpubchannel = new AdmPubChannel();
		$newpubchannel->Type			= 'print';
		$newpubchannel->Description		= 'Publication Channel';

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreatePubChannelsRequest.class.php';
		$newpubchannels = new AdmCreatePubChannelsRequest();
		$newpubchannels->Ticket 		= $ticket;
		$newpubchannels->RequestModes 	= array();
		$newpubchannels->PublicationId 	= $pubs[0]->Id;
		$newpubchannels->PubChannels 	= array($newpubchannel);
		$admintest->createPubChannels( $newpubchannels, 'Create PubChannel with nil name', '(S1032)' );
		
		// Create new pubchannel with empty name
		$newpubchannel->Name			=  AdminTestDataFactory::buildErraticName('empty_name');
		$admintest->createPubChannels( $newpubchannels, 'Create PubChannel with empty name', '(S1032)' );
		
		// Create new pubchannel with long name
		$newpubchannel->Name			=  AdminTestDataFactory::buildErraticName('long_name',ADMTEST_PUBCHANNEL_NAMELEN);
		$admintest->createPubChannels( $newpubchannels, 'Create PubChannel with long name', '(S1026)' );
		
		// Create new pubchannel with long name
		$newpubchannel->Name			=  'PubChannel_' . date('dmy_his');
		$return = null;
		$return = $admintest->createPubChannels( $newpubchannels, 'Create PubChannel' );

		$admintest->createPubChannels( $newpubchannels, 'Create PubChannel with existing name', '(S1010)' );

		// Get the new created pubchannels
		$newpubchannelIds	= array();
		$newpubchannels 	= $return->PubChannels;
		foreach( $newpubchannels as $pubchannel ) {
			$newpubchannelIds[] = $pubchannel->Id;
		}
		
		//	Get all pubchannels
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPubChannelsRequest.class.php';
		$pubchannels = new AdmGetPubChannelsRequest();
		$pubchannels->Ticket 		=  $ticket;
		$pubchannels->RequestModes 	= array('GetIssues', 'GetEditions');
		$pubchannels->PublicationId	= $pubs[0]->Id;
		$admintest->getPubChannels( $pubchannels, 'Get All PubChannels' );
		
		//	Get specific pubchannels
		$pubchannels->PubChannelIds = $newpubchannelIds;
		$admintest->getPubChannels( $pubchannels, 'Get Specific PubChannels' );

		//	Modify pubchannel
		$modpubchannel = new AdmPubChannel();
		$modpubchannel->Id			=  $newpubchannels[0]->Id;
		$modpubchannel->Description = 'Publication Channel description modified';

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyPubChannelsRequest.class.php';
		$modifypubchannels = new AdmModifyPubChannelsRequest();
		$modifypubchannels->Ticket 			= $ticket;
		$modifypubchannels->RequestModes 	= array('GetIssues', 'GetEditions');
		$modifypubchannels->PublicationId	= $pubs[0]->Id;
		$modifypubchannels->PubChannels 	= array($modpubchannel);
		$admintest->modifyPubChannels( $modifypubchannels, 'Modify PubChannel with nil name', '(S1032)' );
		
		// Create new pubchannel with empty name
		$modpubchannel->Name			=  AdminTestDataFactory::buildErraticName('empty_name');
		$admintest->modifyPubChannels( $modifypubchannels, 'Modify PubChannel with empty name', '(S1032)' );
		
		// Create new pubchannel with long name
		$modpubchannel->Name			=  AdminTestDataFactory::buildErraticName('long_name',ADMTEST_PUBCHANNEL_NAMELEN);
		$admintest->modifyPubChannels( $modifypubchannels, 'Modify PubChannel with long name', '(S1026)' );
		
		// Create new pubchannel
		$modpubchannel->Name			=  'PubChannel_Modified_' . date('dmy_his');
		$admintest->modifyPubChannels( $modifypubchannels, 'Modify PubChannel' );

		// 	Create new issue with nil name
		$newiss = new AdmIssue();
		$newiss->Description 			= 'New Admin Issue';
		$newiss->SortOrder				= 2;
		$newiss->EmailNotify			= true;
		$newiss->ReversedRead			= true;
		$newiss->OverrulePublication	= false;
		$newiss->Deadline				= AdminTestDataFactory::buildDifficultDate('wrong_date');
		$newiss->ExpectedPages			= 32;
		$newiss->Subject				= 'World Cup';
		$newiss->Activated				= true;
		$newiss->PublicationDate		= AdminTestDataFactory::buildDifficultDate('wrong_date');

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateIssuesRequest.class.php';
		$newissues = new AdmCreateIssuesRequest();
		$newissues->Ticket 				= $ticket;
		$newissues->RequestModes 		= array();
		$newissues->PublicationId 		= $pubs[0]->Id;
		$newissues->PubChannelId 		= $newpubchannels[0]->Id;
		$newissues->Issues 				= array($newiss);
		$admintest->createIssues( $newissues, 'Create new issue with nil name', '(S1032)' );
		
		//	Create new issue with empty name
		$newiss->Name				= AdminTestDataFactory::buildErraticName('empty_name');
		$admintest->createIssues( $newissues, 'Create new issue with empty name', '(S1032)' );
		
		//	Create new issue with long name
		$newiss->Name				= AdminTestDataFactory::buildErraticName('long_name',ADMTEST_ISSUE_NAMELEN);
		$admintest->createIssues( $newissues, 'Create new issue with long name', '(S1026)' );
		
		//	Create new issue with wrong deadline
		$newiss->Name				= 'Issue_' . date('dmy_his');
		$admintest->createIssues( $newissues, 'Create new issue with wrong deadline', BizResources::localize('INVALID_DATE') ); // TODO: introduce S-code!
		
		//	Create new issue with wrong PublicationDate
		$newiss->Deadline			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+3, date('Y')));
		$admintest->createIssues( $newissues, 'Create new issue with wrong publication date', BizResources::localize('INVALID_DATE') ); // TODO: introduce S-code!
		
		//	Create new issue with valid data
		$return = null;
		$newiss->PublicationDate	= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d'), date('Y')));
		$return = $admintest->createIssues( $newissues, 'Create Issues' );
		
		$admintest->createIssues( $newissues, 'Create Issues with existing name', '(S1010)' );
		
		// Get new created issues
		$issueIds = array();
		$issues = $return->Issues;
		foreach( $issues as $iss ) {
			$issueIds[] = $iss->Id;
		}
		
		//	Get all issues
		$issue = new AdmIssue();
		$issue->Ticket 			= $ticket;
		$issue->RequestModes 	= array( 'GetSections', 'GetEditions');
		$issue->PublicationId 	= $pubs[0]->Id;
		$issue->PubChannelId 	= $newpubchannels[0]->Id;
		$admintest->getIssues( $issue, 'Get All Issues' );
		
		//	Get specific issues
		$issue->IssueIds 		= $issueIds;
		$admintest->getIssues( $issue, 'Get Specific Issues' );
		
		// 	Modify issue with nil name
		$modiss = new AdmIssue();
		$modiss->Id						= $issues[0]->Id;
		$modiss->Description 			= 'New Issue 1 for Admin SOAP';
		$modiss->SortOrder				= 2;
		$modiss->EmailNotify			= true;
		$modiss->ReversedRead			= true;
		$modiss->OverrulePublication	= false;
		$modiss->Deadline				= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+3, date('Y')));
		$modiss->ExpectedPages			= 32;
		$modiss->Subject				= 'World Cup';
		$modiss->Activated				= true;
		$modiss->PublicationDate		= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d'), date('Y')));

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyIssuesRequest.class.php';
		$modissues = new AdmModifyIssuesRequest();
		$modissues->Ticket 				= $ticket;
		$modissues->RequestModes 		= array();
		$modissues->PublicationId 		= $pubs[0]->Id;
		$modissues->PubChannelId 		= $newpubchannels[0]->Id;
		$modissues->Issues 				= array($modiss);
		$admintest->modifyIssues( $modissues, 'Modify issue with nil name', '(S1032)' );
		
		//	Modify issue with empty name
		$modiss->Name				= AdminTestDataFactory::buildErraticName('empty_name');
		$admintest->modifyIssues( $modissues, 'Modify issue with empty name', '(S1032)' );
		
		//	Modify issue with long name
		$modiss->Name				= AdminTestDataFactory::buildErraticName('long_name',ADMTEST_ISSUE_NAMELEN);
		$admintest->modifyIssues( $modissues, 'Modify issue with long name', '(S1026)' );
		
		//	Modify issue with wrong deadline
		$modiss->Deadline			= AdminTestDataFactory::buildDifficultDate('wrong_date');
		$modiss->Name				= 'Issue_' . date('dmy_his');
		$admintest->modifyIssues( $modissues, 'Modify issue with wrong deadline', BizResources::localize('INVALID_DATE') ); // TODO: introduce S-code!
		
		//	Modify issue with wrong PublicationDate
		$modiss->Deadline			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+3, date('Y')));
		$modiss->PublicationDate	= AdminTestDataFactory::buildDifficultDate('wrong_date');
		$admintest->modifyIssues( $modissues, 'Modify issue with wrong publication date', BizResources::localize('INVALID_DATE') ); // TODO: introduce S-code!
		
		//	Modify issue with valid data
		$modiss->PublicationDate	= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d'), date('Y')));
		$admintest->modifyIssues( $modissues, 'Modify Issues' );
		
		//	Create new sections with nil name
		$newsec = new AdmSection();
		$newsec->Description 		= 'New Admin SOAP Section';
		$newsec->SortOrder 			= 3;
		$newsec->Deadline			= AdminTestDataFactory::buildDifficultDate('wrong_date');
		$newsec->ExpectedPages		= 64;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateSectionsRequest.class.php';
		$newsections = new AdmCreateSectionsRequest();
		$newsections->Ticket 		= $ticket;
		$newsections->RequestModes 	= array();
		$newsections->PublicationId = $pubs[0]->Id;
		$newsections->IssueId		= 0; // $issues[0]->Id; // not an Overrule Issue, so pass zero
		$newsections->Sections 		= array($newsec);
		$admintest->createSections( $newsections, 'Create new sections with nil name', '(S1032)' );
		
		//	Create new section with empty name
		$newsec->Name				= AdminTestDataFactory::buildErraticName('empty_name');
		$admintest->createSections( $newsections, 'Create new sections with empty name', '(S1032)' );
		

		//	Create new section with long name
		$newsec->Name				= AdminTestDataFactory::buildErraticName('long_name',ADMTEST_SECTION_NAMELEN);
		$admintest->createSections( $newsections, 'Create new sections with long name', '(S1026)' );
		
		//	Create new section with wrong deadline
		$newsec->Name				= 'Sport_' . date('dmy_his');
		$admintest->createSections( $newsections, 'Create new sections with wrong deadline', BizResources::localize('INVALID_DATE') ); // TODO: introduce S-code!
		
		//	Create new sections with same name
		$return = null;
		$newsec->Deadline			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+3, date('Y')));
		$newsec1 = new AdmSection();
		$newsec1->Name				= $newsec->Name;
		$newsec1->Description		= 'New Admin SOAP Section';
		$newsections->Sections[]	= $newsec1;
		$admintest->createSections( $newsections, 'Create new sections with same name', '(S1010)' );
		
		//	Create new section with valid data
		$newsec->Name				= 'ICT_' . date('dmy_his');
		$newsec1->Name				= 'Business_' . date('dmy_his');
		
		$return = $admintest->createSections( $newsections, 'Create Sections' );
		
		// Get new created section
		$sectionIds = array();
		$sections = $return->Sections;
		foreach( $sections as $sec ) {
			$sectionIds[] = $sec->Id;
		}
		
		//	Get all sections
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetSectionsRequest.class.php';
		$section = new AdmGetSectionsRequest();
		$section->Ticket 		= $ticket;
		$section->RequestModes 	= array();
		$section->PublicationId = $pubs[0]->Id;
		$section->IssueId 		= 0; // $issues[0]->Id; // not an Overrule Issue, so pass zero
		$admintest->getSections( $section, 'Get All Sections' );
		
		//	Get specific sections
		$section->SectionIds 	= $sectionIds;
		$admintest->getSections( $section, 'Get Specific Sections' );
		
		$modsec = $sections[0];
		$modsec1 = $sections[1];
		$modsec->Description 			= 'Section being modified';
		$modsec->SortOrder 				= 2;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifySectionsRequest.class.php';
		$modifysections = new AdmModifySectionsRequest();
		$modifysections->Ticket 		= $ticket;
		$modifysections->PublicationId 	= $pubs[0]->Id;
		$modifysections->IssueId		= 0; // $issues[0]->Id; // not an Overrule Issue, so pass zero
		$modifysections->Sections 		= array($modsec);

		//	Modify sections with nil name
		$modsec->Name 					= null;
		$admintest->modifySections( $modifysections, 'Modify sections with nil name', '(S1032)' );
		
		//	Modify sections with empty name
		$modsec->Name 					= AdminTestDataFactory::buildErraticName('empty_name');
		$admintest->modifySections( $modifysections, 'Modify sections with empty name', '(S1032)' );
		
		//	Modify sections with long name
		$modsec->Name 					= AdminTestDataFactory::buildErraticName('long_name',ADMTEST_SECTION_NAMELEN);
		$admintest->modifySections( $modifysections, 'Modify sections with long name', '(S1026)' );
		
		//	Modify sections with wrong deadline
		$modsec->Name				= 'Sport_' . date('dmy_his');
		$modsec->Deadline			= AdminTestDataFactory::buildDifficultDate('wrong_date');
		$admintest->modifySections( $modifysections, 'Modify sections with wrong deadline', BizResources::localize('INVALID_DATE') ); // TODO: introduce S-code!
		
		//	Modify sections with same name
		$modsec->Deadline			= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+3, date('Y')));
		$modsec1->Name				= $modsec->Name;
		$modifysections->Sections[]	= $modsec1;
		$admintest->modifySections( $modifysections, 'Modify sections with same name', '(S1010)' );
		
		//	Modify sections with valid data
		$modsec->Name				= 'Finance_' . date('dmy_his');
		$modsec1->Name				= 'Politic_' . date('dmy_his');
		$admintest->modifySections( $modifysections, 'Modify Sections' );
		
		//	Create new edition with empty name
		$newedi = new AdmEdition();
		$newedi->Name 				= AdminTestDataFactory::buildErraticName('empty_name');
		$newedi->Description 		= 'South Admin SOAP Edition';
		$newedi->SortOrder 			= 2;
		$newedi1 = new AdmEdition();
		$newedi1->Name 				= AdminTestDataFactory::buildErraticName('empty_name');
		$newedi1->Description 		= 'North Admin SOAP Edition';
		$newedi1->SortOrder 		= 3;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateEditionsRequest.class.php';
		$neweditions = new AdmCreateEditionsRequest();
		$neweditions->Ticket 		= $ticket;
		$neweditions->PublicationId = $pubs[0]->Id;
		$neweditions->PubChannelId 	= $newpubchannels[0]->Id;
		$neweditions->IssueId		= 0; // $issues[0]->Id; // not an Overrule Issue, so pass zero
		$neweditions->Editions 		= array($newedi);
		$admintest->createEditions( $neweditions, 'Create new edition with empty name', '(S1032)' );
		
		//	Create new edition with long name
		$newedi->Name				= AdminTestDataFactory::buildErraticName('long_name',ADMTEST_EDITION_NAMELEN);
		$admintest->createEditions( $neweditions, 'Create new edition with long name', '(S1026)' );
		
		//	Create new editions with same name
		$newedi->Name				= 'South_' . date('dmy_his');
		$newedi1->Name				= $newedi->Name;
		$neweditions->Editions[]	= $newedi1;
		$admintest->createEditions( $neweditions, 'Create Editions with same name', '(S1010)' );
		
		//	Create new editions
		$newedi->Name				= 'Central_' . date('dmy_his');
		$newedi1->Name				= 'North_' . date('dmy_his');
		$return = $admintest->createEditions( $neweditions, 'Create Editions' );
		
		// Get new created editions
		$editionIds = array();
		$editions = $return->Editions;
		foreach( $editions as $edi ) {
			$editionIds[] = $edi->Id;
		}
	
		//	Get all editions
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetEditionsRequest.class.php';
		$edition = new AdmGetEditionsRequest();
		$edition->Ticket 			= $ticket;
		$edition->PublicationId 	= $pubs[0]->Id;
		$edition->PubChannelId 		= $newpubchannels[0]->Id;
		$edition->IssueId			= 0; // $issues[0]->Id; // not an Overrule Issue, so pass zero
		$admintest->getEditions( $edition, 'Get All Editions' );
		
		//	Get specific editions
		$edition->EditionIds		= $editionIds;
		$admintest->getEditions( $edition, 'Get Specific Editions' );
		
		//	Modify edition with empty name
		$modedi = new AdmEdition();
		$modedi->Id						= $editions[0]->Id;
		$modedi->Name 					= AdminTestDataFactory::buildErraticName('empty_name');
		$modedi->Description 			= 'Edition being modified';
		$modedi->SortOrder 				= 2;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyEditionsRequest.class.php';
		$modifyeditions = new AdmModifyEditionsRequest();
		$modifyeditions->Ticket 		= $ticket;
		$modifyeditions->PublicationId 	= $pubs[0]->Id;
		$modifyeditions->PubChannelId 	= $newpubchannels[0]->Id;
		$modifyeditions->IssueId		= 0; // $issues[0]->Id; // not an Overrule Issue, so pass zero
		$modifyeditions->Editions 		= array($modedi);
		$admintest->modifyEditions( $modifyeditions, 'Modify edition with empty name', '(S1032)' );
		
		//	Modify edition with long name
		$modedi->Name				= AdminTestDataFactory::buildErraticName('long_name',ADMTEST_EDITION_NAMELEN);
		$admintest->modifyEditions( $modifyeditions, 'Modify edition with long name', '(S1026)' );
		
		//	Modify editions with same name
		$modedi->Name				= 'Central_' . date('dmy_his');
		//	Add second modified edition
		$modedi1 = new AdmEdition();
		$modedi1->Id				= $editions[1]->Id;
		$modedi1->Name				= $modedi->Name;
		$modifyeditions->Editions[]	= $modedi1;
		$admintest->modifyEditions( $modifyeditions, 'Modify editions with same name', '(S1010)' );
		
		//	Modify edition with valid data
		$modedi->Name				= 'East_' . date('dmy_his');
		$modedi1->Name				= 'West_' . date('dmy_his');
		$admintest->modifyEditions( $modifyeditions, 'Modify Editions' );
		
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffRequest.class.php';
		$logoff = new AdmLogOffRequest();
		$logoff->Ticket = $ticket;
		$admintest->logOff( $logoff );
		
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnRequest.class.php';
		$logon = new AdmLogOnRequest();
		$logon->AdminUser 		= $newuser1->Name;
		$logon->Password 		= $newuser1->Password;
		$logon->ClientName 		= 'My machine IP';
		$logon->ClientAppName 	= 'Web';
		$logon->ClientAppVersion = 'v'.SERVERVERSION;
		
		$ticket = $admintest->logOn( $logon, 'Logon as Normal User' );
		$newusergrps->Ticket 		= $ticket;
		$newusers->Ticket 			= $ticket;
		$adduserstogroup->Ticket 	= $ticket;
		$addgroupstouser->Ticket 	= $ticket;
		$usergroup->Ticket 			= $ticket;
		$user->Ticket 				= $ticket;
		$modusers->Ticket 			= $ticket;
		$modusergroups->Ticket 		= $ticket;
		$newpubs->Ticket 			= $ticket;
		$publications->Ticket 		= $ticket;
		$modifypubs->Ticket 		= $ticket;
		$newissues->Ticket 			= $ticket;
		$issue->Ticket 				= $ticket;
		$modissues->Ticket 			= $ticket;
		$newsections->Ticket 		= $ticket;
		$section->Ticket 			= $ticket;
		$modifysections->Ticket 	= $ticket;
		$neweditions->Ticket 		= $ticket;
		$edition->Ticket 			= $ticket;
		$modifyeditions->Ticket 	= $ticket;
		$admintest->createUserGroups( $newusergrps, 'Create UserGroups', '(S1002)' );
		$admintest->createUsers( $newusers, 'Create Users', '(S1002)' );
		$admintest->addUsersToGroup( $adduserstogroup, '(S1002)' );
		$admintest->addGroupsToUser( $addgroupstouser, '(S1002)' );
		$admintest->getUserGroups( $usergroup, 'Get UserGroups', '(S1002)' );
		$admintest->getUsers( $user, 'Get Users', '(S1002)' );
		$admintest->modifyUsers( $modusers, 'Modify Users', '(S1002)' );	
		$admintest->modifyUserGroups( $modusergroups, 'Modify UserGroups', '(S1002)' );
		$admintest->createPublications( $newpubs, 'Create Publications', '(S1002)' );
		$admintest->getPublications( $publications, 'Get Publications', '(S1002)' );
		$admintest->modifyPublications( $modifypubs, 'Modify Publications', '(S1002)' );
		$admintest->createIssues( $newissues, 'Create Issues', '(S1002)' );
		$admintest->getIssues( $issue, 'Get Issues', '(S1002)' );
		$admintest->modifyIssues( $modissues, 'Modify Issues', '(S1002)' );
		$admintest->createSections( $newsections, 'Create Sections', '(S1002)' );
		$admintest->getSections( $section, 'Get Sections', '(S1002)' );
		$admintest->modifySections( $modifysections, 'Modify Sections', '(S1002)' );
		$admintest->createEditions( $neweditions, 'Create Editions', '(S1002)' );
		$admintest->getEditions( $edition, 'Get Editions', '(S1002)' );
		$admintest->modifyEditions( $modifyeditions, 'Modify Editions', '(S1002)' );
		
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffRequest.class.php';
		$logoff = new AdmLogOffRequest();
		$logoff->Ticket = $ticket;
		$admintest->logOff( $logoff );
		
		$logon->AdminUser 	= $newuser->Name;
		$logon->Password	= $newuser->Password;
		$admintest->logOn( $logon, 'Logon as deactivated user', '(S1051)' );
	}
}

class AdminTestSoapProxy
{
	private $watch = null;
	private $client = null;
	
	public function __construct( $watch = null )
	{
		require_once BASEDIR.'/server/protocols/soap/AdmClient.php';
		$this->client = new WW_SOAP_AdmClient();
		$this->watch = $watch;	
	}

	public function __destruct()
	{
		$this->watch = null;	
		$this->client = null;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// internal helper functions that do logging at screen
	
	private function showOperation( $operation )
	{
		echo '<hr/><font color="blue"><b>Operation: </b>'.$operation.'</font><br/>';
	}
	
	private function showDuration( $duration )
	{
		echo '<b>Duration: </b>'.$duration.' sec<br/>';
	}

	private function showError( $error )
	{
		echo '<font color="red"><b>ERROR: </b>'.$error.'</font><br/>';
	}

	private function showSuccess( $duration, $expErr )
	{
		$this->showDuration( $duration );
		if( !is_null($expErr) ) {// error expected?
			self::showError( 'No error occurred, while error "'.$expErr.'" was expected!' );
		}
	}

	private function showException( $e, $duration, $expErr )
	{
		$this->showDuration( $duration );
		$errMsg = $e->getMessage();
		if( is_null($expErr) || // no error expected?
			stripos( $errMsg, $expErr ) === false ) { // unexpected error?
			self::showError( $errMsg );
		}
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// LogOn - Log On
	public function logOn( $logon, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'LogOn - '.$criteria );
			$this->watch->Start();
			$return = $this->client->LogOn( $logon );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return->Ticket;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// LogOff - Log Off
	public function logOff( $logoff, $expErr = null )
	{
		try {
			self::showOperation( 'LogOff' );
			$this->watch->Start();
			$return = $this->client->LogOff( $logoff );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// GetPublications - Get Publications
	public function getPublications( $pubs, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'GetPublications - '.$criteria );
			$this->watch->Start();
			$return = $this->client->GetPublications( $pubs );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// CreatePublications - Create Publications
	public function createPublications( $pubs, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'CreatePublications - '.$criteria );
			$this->watch->Start();
			$return = $this->client->CreatePublications( $pubs );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// ModifyPublications - Modify Publications
	public function modifyPublications( $pubs, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'ModifyPublications - '.$criteria );
			$this->watch->Start();
			$return = $this->client->ModifyPublications( $pubs );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// GetPubChannels - Get PubChannels
	public function getPubChannels( $pubchannels, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'GetPubChannels - '.$criteria );
			$this->watch->Start();
			$return = $this->client->getPubChannels( $pubchannels );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// CreatePubChannels - Create PubChannels
	public function createPubChannels( $pubchannels, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'CreatePubChannels - '.$criteria );
			$this->watch->Start();
			$return = $this->client->CreatePubChannels( $pubchannels );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// ModifyPubChannels - Modify PubChannels
	public function modifyPubChannels( $pubchannels, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'ModifyPubChannels - '.$criteria );
			$this->watch->Start();
			$return = $this->client->ModifyPubChannels( $pubchannels );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// GetIssues - Get Issues
	public function getIssues( $issues, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'GetIssues - '.$criteria );
			$this->watch->Start();
			$return = $this->client->GetIssues( $issues );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// CreateIssues - Create Issues
	public function createIssues( $issues, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'CreateIssues - '.$criteria );
			$this->watch->Start();
			$return = $this->client->CreateIssues( $issues );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// ModifyIssues - Modify Issues
	public function modifyIssues( $issues, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'ModifyIssues - '.$criteria );
			$this->watch->Start();
			$return = $this->client->ModifyIssues( $issues);

			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// GetSections - Get Sections
	public function getSections( $sections, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'GetSections - '.$criteria );
			$this->watch->Start();
			$return = $this->client->GetSections( $sections );

			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// CreateSections - Create Sections
	public function createSections( $sections, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'CreateSections - '.$criteria );
			$this->watch->Start();
			$return = $this->client->CreateSections( $sections );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// ModifySections - Modify Sections
	public function modifySections( $sections, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'ModifySections - '.$criteria );
			$this->watch->Start();
			$return = $this->client->ModifySections( $sections );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// GetEditions - Get Editions
	public function getEditions( $editions, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'GetEditions - '.$criteria );
			$this->watch->Start();
			$return = $this->client->GetEditions( $editions );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// CreateEditions - Create Editions
	public function createEditions( $editions, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'CreateEditions - '.$criteria );
			$this->watch->Start();
			$return = $this->client->CreateEditions( $editions );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// ModifyEditions - Modify Editions
	public function modifyEditions( $editions, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'ModifyEditions - '.$criteria );
			$this->watch->Start();
			$return = $this->client->ModifyEditions( $editions );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// GetUsers - Get Users
	public function getUsers( $users, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'GetUsers - '.$criteria );
			$this->watch->Start();
			$return = $this->client->GetUsers( $users );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// CreateUsers - Create Users
	public function createUsers( $users, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'CreateUsers - '.$criteria );
			$this->watch->Start();
			$return = $this->client->CreateUsers( $users );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// ModifyUsers - Modify Users
	public function modifyUsers( $users, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'ModifyUsers - '.$criteria );
			$this->watch->Start();
			$return = $this->client->ModifyUsers( $users );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// GetUserGroups - Get UserGroups
	public function getUserGroups( $usergroups, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'GetUserGroups - '.$criteria );
			$this->watch->Start();
			$return = $this->client->GetUserGroups( $usergroups );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// CreateUserGroups - Create UserGroups
	public function createUserGroups( $usergroups, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'CreateUserGroups - '.$criteria );
			$this->watch->Start();
			$return = $this->client->CreateUserGroups( $usergroups );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// ModifyUserGroups - Modify UserGroups
	public function modifyUserGroups( $usergroups, $criteria, $expErr = null )
	{
		try {
			self::showOperation( 'ModifyUserGroups - '.$criteria );
			$this->watch->Start();
			$return = $this->client->ModifyUserGroups( $usergroups );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// AddUsersToGroup - Add Users To Group
	public function addUsersToGroup( $adduserstogroup, $expErr = null )
	{
		try {
			self::showOperation( 'AddUsersToGroup - Add Users To Group' );
			$this->watch->Start();
			$return = $this->client->AddUsersToGroup( $adduserstogroup );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// AddGroupsToUser - Add Groups To User
	public function addGroupsToUser( $addgroupstouser, $expErr = null )
	{
		try {
			self::showOperation( 'AddGroupsToUser - Add Groups To User' );
			$this->watch->Start();
			$return = $this->client->AddGroupsToUser( $addgroupstouser );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// RemoveUsersFromGroup - Remove Users From Group
	public function removeUsersFromGroup( $removeusersfromgroup, $expErr = null )
	{
		try {
			self::showOperation( 'RemoveUsersFromGroup - Remove Users From Group' );
			$this->watch->Start();
			$return = $this->client->RemoveUsersFromGroup( $removeusersfromgroup );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// RemoveGroupsFromUser - Remove Groups From User
	public function removeGroupsFromUser( $removegroupsfromuser, $expErr = null )
	{
		try {
			self::showOperation( 'RemoveGroupsFromUser - Remove Groups From User' );
			$this->watch->Start();
			$return = $this->client->RemoveGroupsFromUser( $removegroupsfromuser );
			self::showSuccess( $this->watch->Fetch(), $expErr );
		} catch( SoapFault $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		} catch( BizException $e ){
			self::showException( $e, $this->watch->Fetch(), $expErr );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // DeletePublications - Delete Publications
    public function deletePublications( $deletePublications, $expErr = null )
    {
        try {
            self::showOperation( 'DeletePublications - Delete Publications' );
            $this->watch->Start();
            $return = $this->client->DeletePublications( $deletePublications );
            self::showSuccess( $this->watch->Fetch(), $expErr );
        } catch( SoapFault $e ){
            self::showException( $e, $this->watch->Fetch(), $expErr );
        } catch( BizException $e ){
            self::showException( $e, $this->watch->Fetch(), $expErr );
        }
        $error = !isset($return) || is_soap_fault($return);
        return $error ? null : $return;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // DeleteUsers - Delete Users
    public function deleteUsers( $deleteUsers, $expErr = null )
    {
        try {
            self::showOperation( 'DeleteUsers - Delete Users' );
            $this->watch->Start();
            $return = $this->client->DeleteUsers( $deleteUsers );
            self::showSuccess( $this->watch->Fetch(), $expErr );
        } catch( SoapFault $e ){
            self::showException( $e, $this->watch->Fetch(), $expErr );
        } catch( BizException $e ){
            self::showException( $e, $this->watch->Fetch(), $expErr );
        }
        $error = !isset($return) || is_soap_fault($return);
        return $error ? null : $return;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // DeleteUserGroups - Delete UserGroups
    public function deleteUserGroups( $userGroupIds, $expErr = null )
    {
        self::showOperation( 'DeleteUserGroups - Delete UserGroups' );
        $this->watch->Start();
        require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
        foreach( $userGroupIds as $userGroupId ) {
            BizAdmUser::deleteUserGroup( $userGroupId ); // TODO - Should call DeleteUserGroupsService, in the future
        }
        self::showSuccess( $this->watch->Fetch(), $expErr );
    }
}

class AdminTestDataFactory
{
	static public function buildDifficultName( $difficulty )
	{
		switch( $difficulty ) {
			// name in max length
			case 'max_name':
				$name = 'abcdefghijklmnopqrstuvwxyz0';
				break;
			// chinese unicode name
			case 'ch_uni_name':
				$name = chr(0xE6).chr(0x98).chr(0x9F).chr(0xE6).chr(0xB4).chr(0xB2).chr(0xE6).chr(0x97).chr(0xA5).chr(0xE5).chr(0xA0).chr(0xB1) . ' ' . date('dmy_his');
				break;
			// korean unicode name
			case 'kr_uni_name':
				$name = chr(0xED).chr(0x95).chr(0x9C).chr(0xEA).chr(0xB5).chr(0xAD).chr(0xEC).chr(0x9D).chr(0xBC).chr(0xEB).chr(0xB3).chr(0xB4) . ' ' .  date('dmy_his');
				break;
			// japanese unicode name
			case 'ja_uni_name':
				$name = chr(0xE8).chr(0xAA).chr(0xAD).chr(0xE5).chr(0xA3).chr(0xB2).chr(0xE6).chr(0x96).chr(0xB0).chr(0xE8).chr(0x81).chr(0x9E) . ' ' .  date('dmy_his');
				break;
			// russian unicode name
			case 'ru_uni_name':
				$name = chr(0xD0).chr(0x92).chr(0xD0).chr(0xBB).chr(0xD0).chr(0xB0).chr(0xD0).chr(0xB4).chr(0xD0).chr(0xB8).chr(0xD0).chr(0xB2).chr(0xD0).chr(0xBE).chr(0xD1).chr(0x81).chr(0xD1).chr(0x82).chr(0xD0).chr(0xBE).chr(0xD0).chr(0xBA) . ' ' .  date('dmy_his');
				break;
			// wrong email
			case 'bad_email':
				$name = 'user@woodwing';
				break;
			// dangerous character name
		}
		return  $name;
	}

	static public function buildErraticName( $error, $maxlen=0 )
	{
		switch( $error ) {
			case 'empty_name':
				$name = '';
				break;
			case 'long_name':
				$name = str_repeat('x',$maxlen+1); // one extra to exceed max limit
				break;
			case 'common_name':
				$name = 'WoodWing';
				break;
		}
		return  $name;
	}

	static public function buildDifficultDate( $difficulty )
	{
		switch( $difficulty ) {
			case 'wrong_date':
				$date = date("Y-m-d\\TH:i ", mktime( 0, 0, 0, date("m"), date("d"), date("Y")));
				break;
		}
		return $date;
	}
	//	public function buildErraticDate( $error ) {  }
}
?>