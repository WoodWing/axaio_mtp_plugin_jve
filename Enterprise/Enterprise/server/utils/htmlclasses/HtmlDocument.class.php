<?php
/**
 * Utility class to build HTML documenta based on HTML master templates.
 * This is especially used by web/admin applications.
 *
 * @package Enterprise
 * @subpackage Utils
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/admin/global_inc.php'; // formvar, inputvar

class HtmlDocument
{
	public static function loadTemplate( $fileName )
	{
		// determine absolute filepath template
		if( file_exists( $fileName ) ) {
			$filePath = $fileName; // full path given
		} else {
			$filePath = BASEDIR.'/config/templates/'.$fileName;
		}
		// abort execution when master not found
		if( !file_exists( $filePath ) )
		{
			$err = BizResources::localize( 'ERR_TPL' );
			die( $err.basename( $filePath ) );
		}
		
		// return master to caller as HTML stream
		return implode( '', file( $filePath ));
	}

	/**
	 * Replaces all resource keys with localized resource strings in given text contents.
	 *
	 * The resource keys must have "<!--RES:[KEY]-->" pattern wherein [KEY] is the key name.
	 * Each key is looked up in the config/configlang.php file and in the config/resources files.
	 *
	 * @param string $txt Contents that may contain resource keys.
	 * @return string Contents with localized resource strings.
	 */
	public static function replaceConfigKeys( $txt )
	{
		// show configurable UI terms (pub/iss/sec/status/edition) by replacing CONFIG keys
		$docKeys = array();
		if( preg_match_all( '<!--RES:([a-zA-Z0-9_\.]*)-->', $txt, $docKeys ) ) {
			$uiTerms = BizResources::getConfigTerms();
			$docKeys = array_unique( $docKeys[1] );
			foreach( $docKeys as $docKey ) {
				if( array_key_exists( $docKey, $uiTerms ) ) {
					$localized = $uiTerms[$docKey];
				} else {
					$localized = BizResources::localize( $docKey );
				}
				$txt = str_replace('<!--RES:'.$docKey.'-->', $localized, $txt );
			}
		}
		return $txt;
	}

	public static function buildDocument( $txt, $template = true, $body = null, $flexwidth=false, $fromlicensedir = false,
	                                      $wwtest=false, $cssUrls=null, $jsUrls=null )
	{
		global $isadmin;
		global $ispubladmin;
		global $globUser;
	
		if ($template) {
			require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
			if( $wwtest ) {
				$layouttemplate = 'wwtest.htm';
			} else {
				$layouttemplate = 'apps.htm';
			}

			// get rights
			$wr = array();
			if( BizSession::isStarted() ) {
				try {
					require_once BASEDIR.'/server/secure.php'; // getauthorizations()
					$dbDriver = DBDriverFactory::gen();
					$sth = getauthorizations( $dbDriver, $globUser );
					while( ($row = $dbDriver->fetch($sth) ) ) {
						$wr[$row["feature"]] = $row;
					}
				} catch( BizException $e ) { // ignore errors; could be no DB installed!
				}
			}
				
			$txt = str_replace("<!--CONTENT-->", $txt, HtmlDocument::loadTemplate($layouttemplate) );
			$txt = str_replace("<!--PAGEWIDTH-->", $flexwidth ? '100%' : '800', $txt);
			$txt = str_replace("<!--INETROOT-->", INETROOT, $txt);

			// Add Stylesheet(CSS) Includes
			$webappLinks = '';
			if( $cssUrls ) foreach( $cssUrls as $cssUrl ) {
				$webappLinks .= '<link href="'.$cssUrl.'" rel="stylesheet" type="text/css" media="all" />' . "\r\n";

			}
			$txt = str_replace("<!--WEBAPP_LINKS-->", $webappLinks, $txt );

			// Add JavaScripts Includes
			$webappScripts = '';
			if( $jsUrls ) foreach( $jsUrls as $jsUrl ) {
				$webappScripts .= '<script language="javascript" type="text/javascript" src="'.$jsUrl.'" ></script>' . "\r\n";
			}
			$txt = str_replace("<!--WEBAPP_SCRIPTS-->", $webappScripts, $txt );


			$imagedir = '../../config/images/';
			$appsdir = '../../server/apps/';
			$admindir = '../../server/admin/';

			if ($fromlicensedir) {
				$imagedir = '../' . $imagedir;	
				$appsdir = '../' . $appsdir;
				$admindir = '../' . $admindir;
			}
	
			// build Applications menu
			$menu = '';
			$appIcons = array();
			if( count($wr) > 0 && defined('SHOW_DEPRECATED_WEBAPPS') && SHOW_DEPRECATED_WEBAPPS == 'ON' ) {
				$menu .= '<p><a class="menutitle" href="'.$appsdir.'index.php">'.BizResources::localize('MNU_APPLICATIONS').'</a></p>';
				$menu .= '<p>';
				if ($wr[BizAccessFeatureProfiles::ACCESS_QUERY_BROWSE]) {
					$menu .= '<a id="bullet" class="menu" href="'.$appsdir.'browse.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_QUERY_BROWSE').'</a><br/>';
					$appIcons[] = '<a href="'.$appsdir.'browse.php"><img src="'.$imagedir.'web_32.gif" border="0" width="32" height="32"><br/>'.BizResources::localize('MNU_QUERY_BROWSE').'</a>';
				}
				if ($wr[BizAccessFeatureProfiles::ACCESS_REPORTING]) {
					$menu .= '<a id="bullet" class="menu" href="'.$appsdir.'report.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_REPORTING').'</a><br/>';
					$appIcons[] = '<a href="'.$appsdir.'report.php"><img src="'.$imagedir.'stats_32.gif" border="0" width="32" height="32"><br/>'.BizResources::localize('MNU_REPORTING').'</a>';
				}
				//if ($wr[BizAccessFeatureProfiles::ACCESS_EXPORT]) {
					$menu .= '<a id="bullet" class="menu" href="'.$appsdir.'export.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_EXPORT').'</a><br/>';
					$appIcons[] = '<a href="'.$appsdir.'export.php"><img src="'.$imagedir.'exp_32.gif" border="0" width="32" height="32"><br/>'.BizResources::localize('MNU_EXPORT').'</a>';
				//}
				if ($wr[BizAccessFeatureProfiles::ACCESS_MYPROFILE]) {
					$menu .= '<a id="bullet" class="menu" href="'.$appsdir.'password.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MY_PROFILE').'</a><br/>';
					$appIcons[] = '<a href="'.$appsdir.'password.php"><img src="'.$imagedir.'users.gif" border="0" width="32" height="32"><br/>'.BizResources::localize('MY_PROFILE').'</a>';
				}
				$menu .= '</p>';
			}			

			// build Maintenance menu
			$adminIcons = array();
			if( $isadmin || $ispubladmin ) {
				$menu .= '<p><a class="menutitle" href="'.$admindir.'index.php">'.BizResources::localize('ACT_MAINTENANCE').'</a></p>';
				$menu .= '<p>';
				if( $isadmin || $ispubladmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'publications.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('PUBLICATIONS').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'publications.php"><img src="'.$imagedir.'pub.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('PUBLICATIONS').'</a>';
				}
				if( $isadmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'profiles.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_PROFILES').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'profiles.php"><img src="'.$imagedir.'profile_32.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('MNU_PROFILES').'</a>';
				}
				if( $isadmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'groups.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_GROUP').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'groups.php"><img src="'.$imagedir.'groups.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('GRP_GROUP').'</a>';
				}
				if( $isadmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'users.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_USER').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'users.php"><img src="'.$imagedir.'users.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('USR_USER').'</a>';
				}
				if( $isadmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'properties.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_META_DATA').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'properties.php"><img src="'.$imagedir.'metadata.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('MNU_META_DATA').'</a>';
				}
				if( $isadmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'actionproperties.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_DIALOG_SETUP').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'actionproperties.php"><img src="'.$imagedir.'dialogs.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('MNU_DIALOG_SETUP').'</a>';
				}
				if( $isadmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'actionpropertiesquery.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('QRY_SETUP').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'actionpropertiesquery.php"><img src="'.$imagedir.'dialogs.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('QRY_SETUP').'</a>';
				}
				if( $isadmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'namedqueries.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_NAMED_QUERY').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'namedqueries.php"><img src="'.$imagedir.'namedqueries.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('MNU_NAMED_QUERY').'</a>';
				}
				if( $isadmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'userqueries.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_USER_QUERY').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'userqueries.php"><img src="'.$imagedir.'userqueries.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('MNU_USER_QUERY').'</a>';
				}
				if( $isadmin || $ispubladmin ) { // since v6.1: moved from web apps to admin, since web apps are taken over by Content Station, except for this Export web app
					$menu .= '<a id="bullet" class="menu" href="'.$appsdir.'export.php"><img src="' . $imagedir . 'transparent.gif"/>'.BizResources::localize("MNU_EXPORT").'</a><br/>';
					$adminIcons[] = "<a href='" . $appsdir . "export.php'><img src='" . $imagedir . "exp_32.gif' border='0' width='32' height='32'><br>".BizResources::localize("MNU_EXPORT")."</a>";
				}
				//if( $isadmin ) { // to do: access profile
				//	$menu .= '<a id="bullet" class="menu" href="'.$admindir.'searchindexing.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('ACT_SEARCH_SERVER').'</a><br/>';
				//	$adminIcons[] = '<a href="'.$admindir.'searchindexing.php"><img src="'.$imagedir.'searchsvr_32.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('ACT_SEARCH_SERVER').'</a>';
				//}
				//if( $isadmin ) { // to do: access profile
				//	$menu .= '<a id="bullet" class="menu" href="'.$admindir.'log.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_LOG').'</a><br/>';
				//	$adminIcons[] = '<a href="'.$admindir.'log.php"><img src="'.$imagedir.'log.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('MNU_LOG').'</a>';
				//}
				//if( $isadmin ) { // to do: access profile
				//	$menu .= '<a id="bullet" class="menu" href="'.$admindir.'online.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_ONLINE').'</a><br/>';
				//	$adminIcons[] = '<a href="'.$admindir.'online.php"><img src="'.$imagedir.'go_32.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('MNU_ONLINE').'</a>';
				//}
				if( $isadmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'serverplugins.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('PLN_SERVERPLUGINS').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'serverplugins.php"><img src="'.$imagedir.'plugin_admin_32.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('PLN_SERVERPLUGINS').'</a>';
				}
				if( $isadmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'outputdevices.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('OUTPUT_DEVICES').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'outputdevices.php"><img src="'.$imagedir.'outputdevice.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('OUTPUT_DEVICES').'</a>';
				}
				//if( $isadmin || $ispubladmin ) { // to do: access profile
				//	$menu .= '<a id="bullet" class="menu" href="'.$admindir.'datasources.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('DS_MENU_ITEM').'</a><br/>';
				//	$adminIcons[] = '<a href="'.$admindir.'datasources.php"><img src="'.$imagedir.'datasource_32.png" border="0" width="32" height="32"/><br/>'.BizResources::localize('DS_DATASOURCE').'</a>';
				//}
				//if( $isadmin ) { // to do: access profile
				//	$menu .= '<a id="bullet" class="menu" href="'.$admindir.'servers.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('SVR_SERVERS').'</a><br/>';
				//	$adminIcons[] = '<a href="'.$admindir.'servers.php"><img src="'.$imagedir.'server_32.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('SVR_SERVERS').'</a>';
				//}
				//if( $isadmin ) { // to do: access profile
				//	$menu .= '<a id="bullet" class="menu" href="'.$admindir.'serverjobs.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('SVR_SERVER_JOBS').'</a><br/>';
				//	$adminIcons[] = '<a href="'.$admindir.'serverjobs.php"><img src="'.$imagedir.'jobqueue_32.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('SVR_SERVER_JOBS').'</a>';
				//}
				//if( $isadmin ) { // to do: access profile
				//	$menu .= '<a id="bullet" class="menu" href="'.$admindir.'serverjobconfigs.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('SVR_SERVERJOB_CONFIGS').'</a><br/>';
				//	$adminIcons[] = '<a href="'.$admindir.'serverjobconfigs.php"><img src="'.$imagedir.'jobconfig_32.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('SVR_SERVERJOB_CONFIGS').'</a>';
				//}
				//if( $isadmin ) { // to do: access profile
				//	$menu .= '<a id="bullet" class="menu" href="'.$admindir.'indesignservers.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('IDS_INDSERVERS').'</a><br/>';
				//	$adminIcons[] = '<a href="'.$admindir.'indesignservers.php"><img src="'.$imagedir.'ids_overview_32.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('IDS_INDSERVERS').'</a>';
				//}
				//if( $isadmin ) { // to do: access profile
				//	$menu .= '<a id="bullet" class="menu" href="'.$admindir.'indesignserverjobs.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('IDS_SERVER_JOBS').'</a><br/>';
				//	$adminIcons[] = '<a href="'.$admindir.'indesignserverjobs.php"><img src="'.$imagedir.'ids_jobs_32.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('IDS_SERVER_JOBS').'</a>';
				//}
				//if( $isadmin || $ispubladmin ) { // to do: access profile
				//	$menu .= '<a id="bullet" class="menu" href="'.$admindir.'mtpsetup.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_MTP').'</a><br/>';
				//	$adminIcons[] = '<a href="'.$admindir.'mtpsetup.php"><img src="'.$imagedir.'prtpv_32.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('MTP_SETUP').'</a>';
				//}
				if( $isadmin || $ispubladmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'serverapps.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_INTEGRATIONS').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'serverapps.php"><img src="'.$imagedir.'server_32.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('MNU_INTEGRATIONS').'</a>';
				}
				if( $isadmin || $ispubladmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'advanced.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('MNU_ADVANCED').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'advanced.php"><img src="'.$imagedir.'advanced_32.png" border="0" width="32" height="32"/><br/>'.BizResources::localize('MNU_ADVANCED').'</a>';
				}
				if( $isadmin ) { // to do: access profile
					$menu .= '<a id="bullet" class="menu" href="'.$admindir.'license/index.php"><img src="'.$imagedir.'transparent.gif"/>'.BizResources::localize('LIC_LICENSING').'</a><br/>';
					$adminIcons[] = '<a href="'.$admindir.'license/index.php"><img src="'.$imagedir.'woodwing95.gif" border="0" width="32" height="32"/><br/>'.BizResources::localize('LIC_LICENSING').'</a>';
				}
				$menu .= '</p>';
			}
			$txt = str_replace("<!--MENU-->", $menu, $txt);

			// build icon index page (Applications or Maintenance)
			if( strpos( $txt, '<!--WEBAPP_ICONS-->' ) !== false ) {
				$icons = $appIcons;
				$iconAnchor = '<!--WEBAPP_ICONS-->';
			} else if( strpos( $txt, '<!--ADMIN_ICONS-->' ) !== false ) {
				$icons = $adminIcons;
				$iconAnchor = '<!--ADMIN_ICONS-->';
			} else { // not the icon index page
				$icons = array();
				$iconAnchor = '';
			}
			if( count( $icons ) > 0 && !empty($iconAnchor) ) { // icon index page?
				$icontxt = '';
				$nr = 0;
				for( $row = 0; $row < ceil(count($icons)/4); $row++ ) {
					$icontxt .= '<tr class="menuicons">';
					for( $col = 1; $col <= 4; $col++ ) {
						$icon = isset( $icons[$nr] ) ? $icons[$nr] : '';
						$icontxt .= '<td valign="top" align="center" width="25%">'.$icon.'</td>';
						$nr++;
					}
					$icontxt .= "</tr>";
				}
				$txt = str_replace( $iconAnchor, $icontxt, $txt );
			}
		}
		// include javascript files that needs localization
		$includes = array();
		if( preg_match_all( '<!--INC:([a-zA-Z0-9_]*\.js)-->', $txt, $includes ) ) {
			$includes = array_unique( $includes[1] );
			foreach( $includes as $include ) {
				$incFile = BASEDIR.'/server/utils/javascript/'.$include;
				if( file_exists( $incFile ) ) {
					$incContent = file_get_contents( $incFile );
					$txt = str_replace('<!--INC:'.$include.'-->', $incContent, $txt );
				}
			}
		}
	
		$txt = self::replaceConfigKeys( $txt );
	
		// handle alternative body
		if ($body){
			$txt = str_replace("<body", "<body $body ", $txt);
		}
		
		// show user (logged in) and server version
		$user = '';
		try {
			if( BizSession::isStarted() ) {
				$user = BizSession::getUserInfo( 'fullname' );
			}
		} catch( BizException $e ) { // ignore errors; could be no DB installed!
		}
		$txt = str_replace("<!--USER-->",formvar($user) ,$txt);

		$versionInfo = trim( SERVERVERSION . ' ' . SERVERVERSION_EXTRAINFO );
		$txt = str_replace("<!--VERSIONINFO-->", $versionInfo ,$txt);
		return $txt;
	}
}