<?php
// TODO Richard: since the multi language feature even screens without parameters may not be cached by browser,
// as they may be in a different language now from last time.
// send no cache header, most likely in other screens as well
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar() / inputvar()
require_once BASEDIR."/server/secure.php";
require_once BASEDIR."/server/apps/functions.php";
require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/dbclasses/DBUser.class.php';

// When password expired, we allow user to change password
$userPwdExpir = array_key_exists( 'userPwdExpir', $_REQUEST ) ? $_REQUEST['userPwdExpir'] : null; 
$ticket = checkSecure( null, $userPwdExpir );

webauthorization( BizAccessFeatureProfiles::ACCESS_MYPROFILE );

$tpl = HtmlDocument::loadTemplate( 'password.htm' );

if( empty($userPwdExpir) ) {
	require_once( BASEDIR . '/server/dbclasses/DBTicket.class.php' );
	$user = DBTicket::checkTicket( $ticket );
} else {
	$user = $userPwdExpir;
}
// Get user type for this user, if no access then access is denied
$user_info = DBUser::getUser( $user );
$sUserLanguage = $user_info['language'];

$submit       = isset($_POST['submit'])      ? $_POST['submit'] : '';
$oldPassword  = isset($_POST['oldPassword']) ? $_POST['oldPassword'] : '';
$newPass_1    = isset($_POST['newPass_1'])   ? $_POST['newPass_1'] : '';
$newPass_2    = isset($_POST['newPass_2'])   ? $_POST['newPass_2'] : '';
$oldPassword  = trim ($oldPassword);
$sNewLanguage = isset($_POST['language'])    ? $_POST['language'] : '';

$newPass_1 = trim ($newPass_1);
$newPass_2 = trim ($newPass_2);

$aLanguageCodes = BizResources::getLanguageCodes();
$error = '';

if ($submit) {
	$sUserLanguage = $sNewLanguage; // show valid selected language on screen, even in psw error situation
	if (!empty($oldPassword) || !empty($newPass_1) || !empty($newPass_2))  // any password info given
	{
		if (!empty($oldPassword) && !empty($newPass_1) && !empty($newPass_2)) // then all must be given
		{
			// == -> Exact match
			if (strcmp($newPass_1 ,$newPass_2) != 0) {
				$error = BizResources::localize("ERR_MISMATCHPASS");
			}
		} else {
			$error = BizResources::localize("ERR_MANDATORYFIELDS");
		}

		if( !$error ) // Everything went correct
		{
			try {
				require_once BASEDIR.'/server/services/wfl/WflChangePasswordService.class.php';
				$service = new WflChangePasswordService();
				if( empty($userPwdExpir) ) {
					$req = new WflChangePasswordRequest( $ticket, $oldPassword, $newPass_1, null );
				} else {
					$req = new WflChangePasswordRequest( null, $oldPassword, $newPass_1, $userPwdExpir );
				}
				$service->execute( $req );
			} catch( BizException $e ) {
				$error = $e->getMessage();
			}
		}
	}

	// change language
	if (!$error && empty($userPwdExpir)) // only allow change password when expired (else errors on ticket)
	{
		if (array_key_exists($sNewLanguage, $aLanguageCodes)) // non existent langauge selected, HTML hack or input corrupted
		{
			require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
			$res = BizUser::changeLanguage( $user, $sNewLanguage );
			if ($res)
			{
				$error = $res->message;
			}
			else // successfully stored language in db
			{
				setLogCookie("language", $sNewLanguage); // remember for next requests and next login
			}
		}
		else
		{
			$sUserLanguage = $user_info['language']; // fallback to last known valid language for user
			$error = BizResources::localize("ERR_WRONGLANG");
		}
	}

	// Change password for expired password successfull; let user relogin (else show error later on)
	if (!$error && !empty($userPwdExpir) ){
		header('Location: login.php?message='.urlencode(BizResources::localize("PSW_SUCCESS_UPDATE")));
		exit();
	}

	if (!$error){
		if(strlen($sNewLanguage) > 0){
			$error .= "<font color='#00cc00'>";
			$error .=	BizResources::localize("LAN_SUCCESS_UPDATE");
			$error .=	"</font><br/>";
		}
		if(strlen($newPass_1) > 0){
			$error .= "<font color='#00cc00'>";
			$error .=	BizResources::localize("PSW_SUCCESS_UPDATE");
			$error .=	"</font>";
		}
	}
}
if( !empty($userPwdExpir) ) {
	if( !empty($error) ) { $error .= '<br/>'; }
	$error .= BizResources::localize( 'WARN_PASSWORD_EXPIRED' );
}


$tpl = str_replace ("<!--ERROR-->", $error, $tpl);

// fill dropdown, with language selected
$aLanguageOptions = array();
$selected = false;
foreach($aLanguageCodes as $sLanguageCode){
	$sLanguageName = BizResources::localize("LAN_NAME_$sLanguageCode");
	if ($sLanguageCode == $sUserLanguage){
		$selected = true;
		$aLanguageOptions[] = '<option value="'.$sLanguageCode.'" selected="selected">'.formvar($sLanguageName).'</option>';
	}else{
		$aLanguageOptions[] = '<option value="'.$sLanguageCode.'">'.formvar($sLanguageName).'</option>';
	}
}
if($selected === false){
	$aLanguageOptions = array();
	$langcode = BizSettings::getFeatureValue('CompanyLanguage');
	foreach($aLanguageCodes as $sLanguageCode){
		$sLanguageName = BizResources::localize("LAN_NAME_$sLanguageCode");
		if ($sLanguageCode == $langcode){
			$aLanguageOptions[] = '<option value="'.$sLanguageCode.'" selected="selected">'.formvar($sLanguageName).'</option>';
		}else{
			$aLanguageOptions[] = '<option value="'.$sLanguageCode.'">'.formvar($sLanguageName).'</option>';
		}
	}
}
$sLanguageOptions = implode("", $aLanguageOptions);

$tpl = str_replace ("<%LANGUAGES%>", "$sLanguageOptions", $tpl);

if( !empty($userPwdExpir) ) {
	$formPar = inputvar( 'userPwdExpir', $userPwdExpir, 'hidden' );
	$tpl = str_replace ("<!--HIDDEN_FORM_PARAMS-->", $formPar, $tpl);
}

$tpl .= "<script language='javascript'>document.forms[0].oldPassword.focus();</script>";

print HtmlDocument::buildDocument($tpl);
?>