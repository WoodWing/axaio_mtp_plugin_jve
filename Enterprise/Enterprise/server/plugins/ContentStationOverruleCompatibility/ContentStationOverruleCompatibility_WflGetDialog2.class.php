<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * See OverruleCompatibility.class.php for more info
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflGetDialog2_EnterpriseConnector.class.php';

class ContentStationOverruleCompatibility_WflGetDialog2 extends WflGetDialog2_EnterpriseConnector
{
	private $overrulePub;
	private $reqPubId=null;

	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_BEFOREAFTER; }

	/**
	 * Converts requested fake pub id to Server publication and overrule issue
	 *
	 * @param WflGetDialog2Request $req
	 */
	final public function runBefore (WflGetDialog2Request &$req)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		$this->reqPubId = $req->MetaData['Publication']->PropertyValues[0]->Value;

		if( OverruleCompatibility::isOverrulePub( $this->reqPubId ) ) { // Can only be true for Content  Station
			LogHandler::Log('ContentStationOverruleCompatibility','DEBUG','Intercepting GetDialog overrule pub - before');
			$this->overrulePub = $this->reqPubId; // remember for runAfter
			$req->MetaData['Publication']->PropertyValues[0]->Value = OverruleCompatibility::getPublication( $this->overrulePub );

			$overruleIssue = OverruleCompatibility::getIssue( $this->overrulePub );
			if( isset( $req->MetaData['Issue']->PropertyValues[0] )) {
				$req->MetaData['Issue']->PropertyValues[0]->Value = $overruleIssue;
			} else if( !isset( $req->MetaData['Issue'] ))  {
				$propValue = new PropertyValue();
				$propValue->Value = $overruleIssue;

				$mdValue = new MetaDataValue();
				$mdValue->Property = 'Issue';
				$mdValue->PropertyValues = array( $propValue );
				$req->MetaData['Issue'] = $mdValue;
			}
		} else {
			if( OverruleCompatibility::isContentStation( $req->Ticket ) ) {
				require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
				// BZ#24178: version#1 step 6
				// When GetDialog is called for the first time for SetProperties action,
				// regardless whether this object has normal or overrule iss, this plugin is going to do nothing on its issue as the
				// properties of this object will be retrieved from the DB as it is. i.e: Adjusting the issue on the getDialog req will not make any
				// difference as it will be retrieved from DB by the server anyway.
				// Only when user starts changing the Brand, which will be indicated by $req->MetaData['Publication']->PropertyValues[0]->Value (Pub is not null anymore),
				// this plugin will set the issue to be Null when issue is found to be overrule issue.
				// This is needed to avoid confusion on the server side where server gets
				// normal publication(selected by user) and overrule publication(overrule issue - retrieved from DB) at the same time.

				// Asides from 'SetProperties', other actions like 'Create' and 'CopyTo', it is taken care of at the server Biz layer, so no extra
				// attention needed by this plugin.
				if( $req->MetaData['Publication']->PropertyValues[0]->Value ) { // In CS, when Pub is not sent, it indicates the first draw of CopyTo or SetProperties;
					if( isset($req->MetaData['Issue']->PropertyValues[0]->Value) ) {
						// BZ#24178 (See above)
						require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
						$reqIss = DBIssue::getIssue( $req->MetaData['Issue']->PropertyValues[0]->Value );
						if( $reqIss['overrulepub'] ){ // BZ#24178
							$req->MetaData['Issue']->PropertyValues[0]->Value = null; // When issue sent by CS is an overrulepub, set it to Null as this will not be the intended issue. (it should be normal issue)
						}
					}

					// It could be the case that the user has selected a normal* brand at a CS workflow dialog.
					//   * Which has no combined id with an Overrule Issue.
					// By simply passing that brand id to the GetDialog service without specifying an issue,
					// it picks the current/first issue of the default/first channel.
					// But, that issue could turn out tobe an Overrule Issue...! By pre-selecting such issue, the
					// user would not be able to not select a normal issue anymore... so he/she got stuck with the dialog.
					// So, we need to avoid this situation by telling the GetDialog service to take other issue
					// that is not overrrule. This trick is only needed for CS (BZ#17312).
					BizWorkflow::setAvoidOverrulePreSelection();
				}
			}
		}
	}

	/**
	 * Converts publication and overrule issue back to Content station and ensures the response widgets only contain
	 * valid entries for the overrule issue.
	 *
	 * @param WflGetDialog2Request $req
	 * @param WflGetDialog2Response $resp
	 */
	final public function runAfter (WflGetDialog2Request $req, WflGetDialog2Response &$resp)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';

		if( !OverruleCompatibility::isContentStation( $req->Ticket ) ) {
			return; // Bail out when it's not CS, since nothing to do.
		}

		// For existing objects, the Publication parameter will not be set (for initial dialog draws).
		// Here the pub id is 'patched' when object is target to an overrule issue, in context of CS only (BZ#16956).
		if( !$this->reqPubId ) {
			if( count($resp->Targets) == 1 && isset($resp->Targets[0]->Issue->Id) ) {
				if( $resp->Targets[0]->Issue->OverrulePublication ) {
					$respPubId = $resp->Dialog->MetaData['Publication']->PropertyValues[0]->Value;
					$this->overrulePub = OverruleCompatibility::createPubId( $respPubId, $resp->Targets[0]->Issue->Id );
					//$req->MetaData['Publication']->PropertyValues[0]->Value = $respPubId; // Is this needed?
				}
			}
		} elseif ( count($resp->Targets) == 0 && count($resp->RelatedTargets) > 0 ) {
			// BZ#24883 - It might be the case, that object didn't have target, but rely on related parent dossier object target
			require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
			$objType = '';
			if( $req->MetaData ) foreach( $req->MetaData as $metaData ) {
				if( $metaData->Property == 'Type' ) {
					if( $metaData->PropertyValues ) {
						$objType = $metaData->PropertyValues[0]->Value; // Object Type can be only one type.
					}
				}
			}
			if( BizWorkflow::canBeIssuelessObject($objType) ) {
				foreach( $resp->RelatedTargets as $relatedTarget ) {
					$iss = '';
					if( $relatedTarget->BasicMetaData->Type == 'Dossier' ) {
						foreach( $resp->Dialog->MetaData as $metaDataValue ) {
							if( $metaDataValue->Property == 'Issues' ) {
								$iss = '';
								if ( !is_null( $metaDataValue->Values ) ) {
									$iss = count($metaDataValue->Values) > 0 ? $metaDataValue->Values[0] : $iss ;
								} elseif ( isset( $metaDataValue->PropertyValues[0] ) ) {
									$iss = $metaDataValue->PropertyValues[0]->Value;
								}
								break;
							}
						}
						if( $iss ) {
							foreach( $relatedTarget->Targets as $target ) {
								if( $target->Issue->Id == $iss and $target->Issue->OverrulePublication ) {
									$respPubId = $relatedTarget->BasicMetaData->Publication->Id;
									$this->overrulePub = OverruleCompatibility::createPubId( $respPubId, $target->Issue->Id );
									//$req->MetaData['Publication']->PropertyValues[0]->Value = $respPubId; // Is this needed?
									break 2;
								}
							}
						}
					}
				}
			}
		}

		if ( $resp->RelatedTargets) foreach ( $resp->RelatedTargets as $relatedTarget ) {
			if ( $relatedTarget->Targets ) foreach ( $relatedTarget->Targets as $target ) {
				if ( $target->Issue->OverrulePublication === true ) {
					$relatedTarget->BasicMetaData->Publication->Id = OverruleCompatibility::createPubId(
						$relatedTarget->BasicMetaData->Publication->Id, $target->Issue->Id );
					$relatedTarget->BasicMetaData->Publication->Name = $target->Issue->Name;
				}
				break;
			}
		}

		// Populate the overrule issue(s) into dialog-widget['Publication']
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		require_once( BASEDIR . '/server/bizclasses/BizSession.class.php' );
		$user = BizSession::getShortUserName();
		if( $resp->Dialog->Tabs )foreach( $resp->Dialog->Tabs as $tab ){
			if( $tab->Widgets ) foreach( $tab->Widgets as $dialogWidget ){
				if( $dialogWidget->PropertyInfo->Name == 'Publication' ){
					$propValues = $dialogWidget->PropertyInfo->PropertyValues;
					if( $propValues )foreach($propValues as $propValue ){
						$issues = BizPublication::getIssues( $user, $propValue->Value, 'flat', null, true/*onlyOverruleIss*/);
						if($issues) foreach( $issues as $issue ){
							if($issue->OverrulePublication) { // just to be sure.
								$propValueForOverrule = new PropertyValue();
								$propValueForOverrule->Value = OverruleCompatibility::createPubId( $propValue->Value, $issue->Id );
								$propValueForOverrule->Display = $propValue->Display . ' ' . $issue->Name;
								$dialogWidget->PropertyInfo->PropertyValues[] = $propValueForOverrule;
							}
						}
					}
				}
			}
		}

		// If the above did not resolve $this->overrulePub (e.g. the object has no targets or multi-set properties), then
		// resolve here. Assume that for an overrule issue, the $resp->Dialog->MetaData['Issue'] is resolved by core.
		if( is_null( $this->overrulePub ) && !$req->MultipleObjects && isset($resp->Dialog->MetaData['Publication']) ) {
			$respPubId = $resp->Dialog->MetaData['Publication']->PropertyValues[0]->Value;
			if( $respPubId ) {
				if( isset( $resp->Dialog->MetaData['Issue']->PropertyValues[0]->Value ) ) {
					$respIssId = $resp->Dialog->MetaData['Issue']->PropertyValues[0]->Value;
					if( $respIssId ) {
						require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
						if( DBIssue::isOverruleIssue( $respIssId ) ) {
							$this->overrulePub = OverruleCompatibility::createPubId( $respPubId, $respIssId );
						}
					}
				}
			}
		}

		// We can see via our member is an overrule pub was passed into request:
		if( OverruleCompatibility::isOverrulePub($this->overrulePub) ) {
			LogHandler::Log('ContentStationOverruleCompatibility','DEBUG','Intercepting GetDialog overrule pub - after');
			// Note: we don't fix $resp->Publications because it's not used. We don't remove it either
			// to prevent error/warnings from core server.

			$issueId = OverruleCompatibility::getIssue( $this->overrulePub );
			$ourIssue = null;

			// Remove all issues from the PubChannels sections, except the one we need:
			// Walk thru pub channels looking for overrule issues:
			$pubChannels = $resp->PubChannels;
			if( !empty($pubChannels) && count($pubChannels) >0 ) { // double check just to be sure in case we get wrong input
				$pubChannelCount = count( $pubChannels );
				for( $pc=$pubChannelCount-1; $pc>=0; $pc-- ) {
					$issues = $pubChannels[$pc]->Issues;
					if( !empty($issues) && count( $issues) > 0 ) { // double check just to be sure in case we get wrong input
						$issueCount = count( $issues );
						for( $is=$issueCount-1; $is>=0; $is-- ) {
							if( $issues[$is]->Id != $issueId )  {
								unset( $pubChannels[$pc]->Issues[$is] );
							} else {
								// remember name:
								$ourIssue = clone $pubChannels[$pc]->Issues[$is];
								// Remove Status, Section and Editons at Issue level to prevent duplicates in dialog
								$pubChannels[$pc]->Issues[$is]->States = null;
								$pubChannels[$pc]->Issues[$is]->Sections = null;
							}
						}
					}
				}
			}


			// If the above did not resolve $ourIssue (e.g. the object has no targets/channels), then resolve here.
			// Typically happens for multiple selected objects.
			if( !$ourIssue ) {
				global $globAuth;
				if( !isset($globAuth) ) {
					require_once BASEDIR.'/server/authorizationmodule.php';
					$globAuth = new authorizationmodule( );
				}
				$globAuth->getrights( BizSession::getShortUserName() );
				require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
				$issueRow = DBIssue::getIssue( $issueId );
				$ourIssue = BizPublication::getIssueInfo( $globAuth->getCachedRights(), $issueRow );
			}

			// Fix pub name and id:
			$respPubId	 = $resp->Dialog->MetaData['Publication']->PropertyValues[0]->Value;
			$respPubName = $resp->Dialog->MetaData['Publication']->PropertyValues[0]->Display;
			$overrulePubId	=	OverruleCompatibility::createPubId( $respPubId, $ourIssue->Id );
			$overrulePubName =  OverruleCompatibility::createPubName( $respPubName, $ourIssue->Name );

			// Set the Overrule Publication Id and Name back to MetaData['Publication']
			$resp->Dialog->MetaData['Publication']->PropertyValues[0]->Value = $overrulePubId;
			$resp->Dialog->MetaData['Publication']->PropertyValues[0]->Display = $overrulePubName;

			// Get categories (from sections) for publication level:
			foreach( $resp->Dialog->Tabs as $tab ) {
				foreach( $tab->Widgets as $dialogWidget ) {
					if( $dialogWidget->PropertyInfo->Name == 'Category' ){
						$dialogWidget->PropertyInfo->PropertyValues = OverruleCompatibility::createPropertyValues( $ourIssue->Sections );
					}
				}
			}
		} else {
			// Remove all the "overrule brand issue" from issues, when it is a normal brand, only for Content Station
			$pubChannels = $resp->PubChannels;
			if( $pubChannels) foreach( $pubChannels as $pubChannelInfo ) {
				foreach( $pubChannelInfo->Issues as $key => $issueInfo ) {
					if( $issueInfo->OverrulePublication ) {
						unset($pubChannelInfo->Issues[$key]);
					}
				}
			}
		}

		// If the Dialog MetaData is updated, also update the response MetaData with the same values.
		if (isset($resp->MetaData) && isset($resp->MetaData->BasicMetaData) && isset($resp->Dialog->MetaData['Publication'])) {
			$resp->MetaData->BasicMetaData->Publication->Id = $resp->Dialog->MetaData['Publication']->PropertyValues[0]->Value;
			$resp->MetaData->BasicMetaData->Publication->Name = $resp->Dialog->MetaData['Publication']->PropertyValues[0]->Display;
		}
	}

	/**
	 * @param WflGetDialog2Request $req
	 */
	final public function runOverruled (WflGetDialog2Request $req) // Not called because we're just doing run before and after
	{
		$req = $req; // keep analyzer happy
	}
}
