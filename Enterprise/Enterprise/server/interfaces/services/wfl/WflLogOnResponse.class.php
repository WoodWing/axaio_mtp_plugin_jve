<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflLogOnResponse
{
	public $Ticket;
	public $Publications;
	public $NamedQueries;
	public $FeatureSet;
	public $LimitationSet;
	public $ServerInfo;
	public $Settings;
	public $Users;
	public $UserGroups;
	public $Membership;
	public $ObjectTypeProperties;
	public $ActionProperties;
	public $Terms;
	public $FeatureProfiles;
	public $Messages;
	public $TrackChangesColor;
	public $Dictionaries;
	public $MessageList;
	public $CurrentUser;
	public $MessageQueueConnections;
	public $MessageQueue;

	/**
	 * @param string               $Ticket                    
	 * @param PublicationInfo[]    $Publications              Nullable.
	 * @param NamedQueryType[]     $NamedQueries              Nullable.
	 * @param Feature[]            $FeatureSet                Nullable.
	 * @param string[]             $LimitationSet             Nullable.
	 * @param ServerInfo           $ServerInfo                Nullable.
	 * @param Setting[]            $Settings                  Nullable.
	 * @param User[]               $Users                     Nullable.
	 * @param UserGroup[]          $UserGroups                Nullable.
	 * @param UserGroup[]          $Membership                Nullable.
	 * @param ObjectTypeProperty[] $ObjectTypeProperties      Nullable.
	 * @param ActionProperty[]     $ActionProperties          Nullable.
	 * @param Term[]               $Terms                     Nullable.
	 * @param FeatureProfile[]     $FeatureProfiles           Nullable.
	 * @param Message[]            $Messages                  Nullable.
	 * @param Color                $TrackChangesColor         Nullable.
	 * @param Dictionary[]         $Dictionaries              Nullable.
	 * @param MessageList          $MessageList               Nullable.
	 * @param User                 $CurrentUser               Nullable.
	 * @param MessageQueueConnection[] $MessageQueueConnections   Nullable.
	 * @param string               $MessageQueue              Nullable.
	 */
	public function __construct( $Ticket=null, $Publications=null, $NamedQueries=null, $FeatureSet=null, $LimitationSet=null, $ServerInfo=null, $Settings=null, $Users=null, $UserGroups=null, $Membership=null, $ObjectTypeProperties=null, $ActionProperties=null, $Terms=null, $FeatureProfiles=null, $Messages=null, $TrackChangesColor=null, $Dictionaries=null, $MessageList=null, $CurrentUser=null, $MessageQueueConnections=null, $MessageQueue=null )
	{
		$this->Ticket               = $Ticket;
		$this->Publications         = $Publications;
		$this->NamedQueries         = $NamedQueries;
		$this->FeatureSet           = $FeatureSet;
		$this->LimitationSet        = $LimitationSet;
		$this->ServerInfo           = $ServerInfo;
		$this->Settings             = $Settings;
		$this->Users                = $Users;
		$this->UserGroups           = $UserGroups;
		$this->Membership           = $Membership;
		$this->ObjectTypeProperties = $ObjectTypeProperties;
		$this->ActionProperties     = $ActionProperties;
		$this->Terms                = $Terms;
		$this->FeatureProfiles      = $FeatureProfiles;
		$this->Messages             = $Messages;
		$this->TrackChangesColor    = $TrackChangesColor;
		$this->Dictionaries         = $Dictionaries;
		$this->MessageList          = $MessageList;
		$this->CurrentUser          = $CurrentUser;
		$this->MessageQueueConnections = $MessageQueueConnections;
		$this->MessageQueue         = $MessageQueue;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'LogOnResponse' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Publications' ) ) {
			$validator->enterPath( 'Publications' );
			if( !is_null( $datObj->Publications ) ) {
				$validator->checkType( $datObj->Publications, 'array' );
				if( !empty($datObj->Publications) ) foreach( $datObj->Publications as $listItem ) {
					$validator->enterPath( 'PublicationInfo' );
					$validator->checkType( $listItem, 'PublicationInfo' );
					WflPublicationInfoValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'NamedQueries' ) ) {
			$validator->enterPath( 'NamedQueries' );
			if( !is_null( $datObj->NamedQueries ) ) {
				$validator->checkType( $datObj->NamedQueries, 'array' );
				if( !empty($datObj->NamedQueries) ) foreach( $datObj->NamedQueries as $listItem ) {
					$validator->enterPath( 'NamedQueryType' );
					$validator->checkType( $listItem, 'NamedQueryType' );
					WflNamedQueryTypeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FeatureSet' ) ) {
			$validator->enterPath( 'FeatureSet' );
			if( !is_null( $datObj->FeatureSet ) ) {
				$validator->checkType( $datObj->FeatureSet, 'array' );
				if( !empty($datObj->FeatureSet) ) foreach( $datObj->FeatureSet as $listItem ) {
					$validator->enterPath( 'Feature' );
					$validator->checkType( $listItem, 'Feature' );
					WflFeatureValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'LimitationSet' ) ) {
			$validator->enterPath( 'LimitationSet' );
			if( !is_null( $datObj->LimitationSet ) ) {
				$validator->checkType( $datObj->LimitationSet, 'array' );
				if( !empty($datObj->LimitationSet) ) foreach( $datObj->LimitationSet as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ServerInfo' ) ) {
			$validator->enterPath( 'ServerInfo' );
			if( !is_null( $datObj->ServerInfo ) ) {
				$validator->checkType( $datObj->ServerInfo, 'ServerInfo' );
				WflServerInfoValidator::validate( $validator, $datObj->ServerInfo );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Settings' ) ) {
			$validator->enterPath( 'Settings' );
			if( !is_null( $datObj->Settings ) ) {
				$validator->checkType( $datObj->Settings, 'array' );
				if( !empty($datObj->Settings) ) foreach( $datObj->Settings as $listItem ) {
					$validator->enterPath( 'Setting' );
					$validator->checkType( $listItem, 'Setting' );
					WflSettingValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Users' ) ) {
			$validator->enterPath( 'Users' );
			if( !is_null( $datObj->Users ) ) {
				$validator->checkType( $datObj->Users, 'array' );
				if( !empty($datObj->Users) ) foreach( $datObj->Users as $listItem ) {
					$validator->enterPath( 'User' );
					$validator->checkType( $listItem, 'User' );
					WflUserValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UserGroups' ) ) {
			$validator->enterPath( 'UserGroups' );
			if( !is_null( $datObj->UserGroups ) ) {
				$validator->checkType( $datObj->UserGroups, 'array' );
				if( !empty($datObj->UserGroups) ) foreach( $datObj->UserGroups as $listItem ) {
					$validator->enterPath( 'UserGroup' );
					$validator->checkType( $listItem, 'UserGroup' );
					WflUserGroupValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Membership' ) ) {
			$validator->enterPath( 'Membership' );
			if( !is_null( $datObj->Membership ) ) {
				$validator->checkType( $datObj->Membership, 'array' );
				if( !empty($datObj->Membership) ) foreach( $datObj->Membership as $listItem ) {
					$validator->enterPath( 'UserGroup' );
					$validator->checkType( $listItem, 'UserGroup' );
					WflUserGroupValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ObjectTypeProperties' ) ) {
			$validator->enterPath( 'ObjectTypeProperties' );
			if( !is_null( $datObj->ObjectTypeProperties ) ) {
				$validator->checkType( $datObj->ObjectTypeProperties, 'array' );
				if( !empty($datObj->ObjectTypeProperties) ) foreach( $datObj->ObjectTypeProperties as $listItem ) {
					$validator->enterPath( 'ObjectTypeProperty' );
					$validator->checkType( $listItem, 'ObjectTypeProperty' );
					WflObjectTypePropertyValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ActionProperties' ) ) {
			$validator->enterPath( 'ActionProperties' );
			if( !is_null( $datObj->ActionProperties ) ) {
				$validator->checkType( $datObj->ActionProperties, 'array' );
				if( !empty($datObj->ActionProperties) ) foreach( $datObj->ActionProperties as $listItem ) {
					$validator->enterPath( 'ActionProperty' );
					$validator->checkType( $listItem, 'ActionProperty' );
					WflActionPropertyValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Terms' ) ) {
			$validator->enterPath( 'Terms' );
			if( !is_null( $datObj->Terms ) ) {
				$validator->checkType( $datObj->Terms, 'array' );
				if( !empty($datObj->Terms) ) foreach( $datObj->Terms as $listItem ) {
					$validator->enterPath( 'Term' );
					$validator->checkType( $listItem, 'Term' );
					WflTermValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'FeatureProfiles' ) ) {
			$validator->enterPath( 'FeatureProfiles' );
			if( !is_null( $datObj->FeatureProfiles ) ) {
				$validator->checkType( $datObj->FeatureProfiles, 'array' );
				if( !empty($datObj->FeatureProfiles) ) foreach( $datObj->FeatureProfiles as $listItem ) {
					$validator->enterPath( 'FeatureProfile' );
					$validator->checkType( $listItem, 'FeatureProfile' );
					WflFeatureProfileValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Messages' ) ) {
			$validator->enterPath( 'Messages' );
			if( !is_null( $datObj->Messages ) ) {
				$validator->checkType( $datObj->Messages, 'array' );
				if( !empty($datObj->Messages) ) foreach( $datObj->Messages as $listItem ) {
					$validator->enterPath( 'Message' );
					$validator->checkType( $listItem, 'Message' );
					WflMessageValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'TrackChangesColor' ) ) {
			$validator->enterPath( 'TrackChangesColor' );
			if( !is_null( $datObj->TrackChangesColor ) ) {
				$validator->checkType( $datObj->TrackChangesColor, 'Color' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Dictionaries' ) ) {
			$validator->enterPath( 'Dictionaries' );
			if( !is_null( $datObj->Dictionaries ) ) {
				$validator->checkType( $datObj->Dictionaries, 'array' );
				if( !empty($datObj->Dictionaries) ) foreach( $datObj->Dictionaries as $listItem ) {
					$validator->enterPath( 'Dictionary' );
					$validator->checkType( $listItem, 'Dictionary' );
					WflDictionaryValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageList' ) ) {
			$validator->enterPath( 'MessageList' );
			if( !is_null( $datObj->MessageList ) ) {
				$validator->checkType( $datObj->MessageList, 'MessageList' );
				WflMessageListValidator::validate( $validator, $datObj->MessageList );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'CurrentUser' ) ) {
			$validator->enterPath( 'CurrentUser' );
			if( !is_null( $datObj->CurrentUser ) ) {
				$validator->checkType( $datObj->CurrentUser, 'User' );
				WflUserValidator::validate( $validator, $datObj->CurrentUser );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageQueueConnections' ) ) {
			$validator->enterPath( 'MessageQueueConnections' );
			if( !is_null( $datObj->MessageQueueConnections ) ) {
				$validator->checkType( $datObj->MessageQueueConnections, 'array' );
				if( !empty($datObj->MessageQueueConnections) ) foreach( $datObj->MessageQueueConnections as $listItem ) {
					$validator->enterPath( 'MessageQueueConnection' );
					$validator->checkType( $listItem, 'MessageQueueConnection' );
					WflMessageQueueConnectionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'MessageQueue' ) ) {
			$validator->enterPath( 'MessageQueue' );
			if( !is_null( $datObj->MessageQueue ) ) {
				$validator->checkType( $datObj->MessageQueue, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflLogOnResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

