<?php

/**
 * @package Enterprise
 * @subpackage Admin Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR.'/server/protocols/soap/SOAP_Attachment.class.php';

class AdmIdName
{
	public $Id;
	public $Name;

	/**
	 * @param integer              $Id                   
	 * @param string               $Name                 
	 */
	public function __construct( $Id=null, $Name=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.dataclasses.AdmIdName'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Id)){ $this->Id = null; }
	}
}
class AdmUser
{
	public $Id;
	public $Name;
	public $FullName;
	public $Deactivated;
	public $Password;
	public $FixedPassword;
	public $EmailAddress;
	public $EmailUser;
	public $EmailGroup;
	public $PasswordExpired;
	public $ValidFrom;
	public $ValidTill;
	public $Language;
	public $TrackChangesColor;
	public $Organization;
	public $Location;
	public $EncryptedPassword;
	public $UserGroups;
	public $ImportOnLogon;

	/**
	 * @param integer              $Id                   Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $FullName             Nullable.
	 * @param boolean              $Deactivated          Nullable.
	 * @param string               $Password             Nullable.
	 * @param boolean              $FixedPassword        Nullable.
	 * @param string               $EmailAddress         Nullable.
	 * @param boolean              $EmailUser            Nullable.
	 * @param boolean              $EmailGroup           Nullable.
	 * @param int                  $PasswordExpired      Nullable.
	 * @param string               $ValidFrom            Nullable.
	 * @param string               $ValidTill            Nullable.
	 * @param string               $Language             Nullable.
	 * @param string               $TrackChangesColor    Nullable.
	 * @param string               $Organization         Nullable.
	 * @param string               $Location             Nullable.
	 * @param string               $EncryptedPassword    Nullable.
	 * @param AdmUserGroup[]       $UserGroups           Nullable.
	 * @param boolean              $ImportOnLogon        Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $FullName=null, $Deactivated=null, $Password=null, $FixedPassword=null, $EmailAddress=null, $EmailUser=null, $EmailGroup=null, $PasswordExpired=null, $ValidFrom=null, $ValidTill=null, $Language=null, $TrackChangesColor=null, $Organization=null, $Location=null, $EncryptedPassword=null, $UserGroups=null, $ImportOnLogon=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->FullName             = $FullName;
		$this->Deactivated          = $Deactivated;
		$this->Password             = $Password;
		$this->FixedPassword        = $FixedPassword;
		$this->EmailAddress         = $EmailAddress;
		$this->EmailUser            = $EmailUser;
		$this->EmailGroup           = $EmailGroup;
		$this->PasswordExpired      = $PasswordExpired;
		$this->ValidFrom            = $ValidFrom;
		$this->ValidTill            = $ValidTill;
		$this->Language             = $Language;
		$this->TrackChangesColor    = $TrackChangesColor;
		$this->Organization         = $Organization;
		$this->Location             = $Location;
		$this->EncryptedPassword    = $EncryptedPassword;
		$this->UserGroups           = $UserGroups;
		$this->ImportOnLogon        = $ImportOnLogon;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.dataclasses.AdmUser'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Id)){ $this->Id = null; }
		if (is_nan($this->PasswordExpired)){ $this->PasswordExpired = null; }
		if (!is_null($this->Deactivated)){ $this->Deactivated = ('true' == $this->Deactivated) ? true : false; }
		if (!is_null($this->FixedPassword)){ $this->FixedPassword = ('true' == $this->FixedPassword) ? true : false; }
		if (!is_null($this->EmailUser)){ $this->EmailUser = ('true' == $this->EmailUser) ? true : false; }
		if (!is_null($this->EmailGroup)){ $this->EmailGroup = ('true' == $this->EmailGroup) ? true : false; }
		if (!is_null($this->ImportOnLogon)){ $this->ImportOnLogon = ('true' == $this->ImportOnLogon) ? true : false; }
		if (0 < count($this->UserGroups)){
			if (is_object($this->UserGroups[0])){
				foreach ($this->UserGroups as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class AdmUserGroup
{
	public $Id;
	public $Name;
	public $Description;
	public $Admin;
	public $Routing;
	public $ExternalId;
	public $Users;

	/**
	 * @param integer              $Id                   Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $Description          Nullable.
	 * @param boolean              $Admin                Nullable.
	 * @param boolean              $Routing              Nullable.
	 * @param string               $ExternalId           Nullable.
	 * @param AdmUser[]            $Users                Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $Description=null, $Admin=null, $Routing=null, $ExternalId=null, $Users=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Description          = $Description;
		$this->Admin                = $Admin;
		$this->Routing              = $Routing;
		$this->ExternalId           = $ExternalId;
		$this->Users                = $Users;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.dataclasses.AdmUserGroup'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Id)){ $this->Id = null; }
		if (!is_null($this->Admin)){ $this->Admin = ('true' == $this->Admin) ? true : false; }
		if (!is_null($this->Routing)){ $this->Routing = ('true' == $this->Routing) ? true : false; }
		if (0 < count($this->Users)){
			if (is_object($this->Users[0])){
				foreach ($this->Users as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class AdmPublication
{
	public $Id;
	public $Name;
	public $Description;
	public $SortOrder;
	public $EmailNotify;
	public $ReversedRead;
	public $AutoPurge;
	public $DefaultChannelId;
	public $ExtraMetaData;
	public $PubChannels;
	public $Issues;
	public $Editions;
	public $Sections;
	public $Statuses;
	public $UserGroups;
	public $AdminGroups;
	public $Workflows;
	public $Routings;
	public $CalculateDeadlines;

	/**
	 * @param integer              $Id                   Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $Description          Nullable.
	 * @param integer              $SortOrder            Nullable.
	 * @param boolean              $EmailNotify          Nullable.
	 * @param boolean              $ReversedRead         Nullable.
	 * @param integer              $AutoPurge            Nullable.
	 * @param integer              $DefaultChannelId     Nullable.
	 * @param AdmExtraMetaData[]   $ExtraMetaData        Nullable.
	 * @param AdmIdName[]          $PubChannels          Nullable.
	 * @param AdmIdName[]          $Issues               Nullable.
	 * @param AdmIdName[]          $Editions             Nullable.
	 * @param AdmIdName[]          $Sections             Nullable.
	 * @param AdmIdName[]          $Statuses             Nullable.
	 * @param AdmIdName[]          $UserGroups           Nullable.
	 * @param AdmIdName[]          $AdminGroups          Nullable.
	 * @param AdmIdName[]          $Workflows            Nullable.
	 * @param AdmIdName[]          $Routings             Nullable.
	 * @param boolean              $CalculateDeadlines   Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $Description=null, $SortOrder=null, $EmailNotify=null, $ReversedRead=null, $AutoPurge=null, $DefaultChannelId=null, $ExtraMetaData=null, $PubChannels=null, $Issues=null, $Editions=null, $Sections=null, $Statuses=null, $UserGroups=null, $AdminGroups=null, $Workflows=null, $Routings=null, $CalculateDeadlines=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Description          = $Description;
		$this->SortOrder            = $SortOrder;
		$this->EmailNotify          = $EmailNotify;
		$this->ReversedRead         = $ReversedRead;
		$this->AutoPurge            = $AutoPurge;
		$this->DefaultChannelId     = $DefaultChannelId;
		$this->ExtraMetaData        = $ExtraMetaData;
		$this->PubChannels          = $PubChannels;
		$this->Issues               = $Issues;
		$this->Editions             = $Editions;
		$this->Sections             = $Sections;
		$this->Statuses             = $Statuses;
		$this->UserGroups           = $UserGroups;
		$this->AdminGroups          = $AdminGroups;
		$this->Workflows            = $Workflows;
		$this->Routings             = $Routings;
		$this->CalculateDeadlines   = $CalculateDeadlines;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.dataclasses.AdmPublication'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Id)){ $this->Id = null; }
		if (is_nan($this->SortOrder)){ $this->SortOrder = null; }
		if (is_nan($this->AutoPurge)){ $this->AutoPurge = null; }
		if (is_nan($this->DefaultChannelId)){ $this->DefaultChannelId = null; }
		if (!is_null($this->EmailNotify)){ $this->EmailNotify = ('true' == $this->EmailNotify) ? true : false; }
		if (!is_null($this->ReversedRead)){ $this->ReversedRead = ('true' == $this->ReversedRead) ? true : false; }
		if (!is_null($this->CalculateDeadlines)){ $this->CalculateDeadlines = ('true' == $this->CalculateDeadlines) ? true : false; }
		if (0 < count($this->ExtraMetaData)){
			if (is_object($this->ExtraMetaData[0])){
				foreach ($this->ExtraMetaData as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->PubChannels)){
			if (is_object($this->PubChannels[0])){
				foreach ($this->PubChannels as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Issues)){
			if (is_object($this->Issues[0])){
				foreach ($this->Issues as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Editions)){
			if (is_object($this->Editions[0])){
				foreach ($this->Editions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Sections)){
			if (is_object($this->Sections[0])){
				foreach ($this->Sections as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Statuses)){
			if (is_object($this->Statuses[0])){
				foreach ($this->Statuses as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->UserGroups)){
			if (is_object($this->UserGroups[0])){
				foreach ($this->UserGroups as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->AdminGroups)){
			if (is_object($this->AdminGroups[0])){
				foreach ($this->AdminGroups as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Workflows)){
			if (is_object($this->Workflows[0])){
				foreach ($this->Workflows as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Routings)){
			if (is_object($this->Routings[0])){
				foreach ($this->Routings as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class AdmPubChannel
{
	public $Id;
	public $Name;
	public $Type;
	public $Description;
	public $PublishSystem;
	public $PublishSystemId;
	public $CurrentIssueId;
	public $SuggestionProvider;
	public $ExtraMetaData;
	public $DirectPublish;
	public $SupportsForms;
	public $Issues;
	public $Editions;
	public $SupportsCropping;

	/**
	 * @param integer              $Id                   Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $Type                 Nullable.
	 * @param string               $Description          Nullable.
	 * @param string               $PublishSystem        Nullable.
	 * @param string               $PublishSystemId      Nullable.
	 * @param integer              $CurrentIssueId       Nullable.
	 * @param string               $SuggestionProvider   Nullable.
	 * @param AdmExtraMetaData[]   $ExtraMetaData        Nullable.
	 * @param boolean              $DirectPublish        Nullable.
	 * @param boolean              $SupportsForms        Nullable.
	 * @param AdmIdName[]          $Issues               Nullable.
	 * @param AdmIdName[]          $Editions             Nullable.
	 * @param boolean              $SupportsCropping     Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $Type=null, $Description=null, $PublishSystem=null, $PublishSystemId=null, $CurrentIssueId=null, $SuggestionProvider=null, $ExtraMetaData=null, $DirectPublish=null, $SupportsForms=null, $Issues=null, $Editions=null, $SupportsCropping=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Type                 = $Type;
		$this->Description          = $Description;
		$this->PublishSystem        = $PublishSystem;
		$this->PublishSystemId      = $PublishSystemId;
		$this->CurrentIssueId       = $CurrentIssueId;
		$this->SuggestionProvider   = $SuggestionProvider;
		$this->ExtraMetaData        = $ExtraMetaData;
		$this->DirectPublish        = $DirectPublish;
		$this->SupportsForms        = $SupportsForms;
		$this->Issues               = $Issues;
		$this->Editions             = $Editions;
		$this->SupportsCropping     = $SupportsCropping;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.dataclasses.AdmPubChannel'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Id)){ $this->Id = null; }
		if (is_nan($this->CurrentIssueId)){ $this->CurrentIssueId = null; }
		if (!is_null($this->DirectPublish)){ $this->DirectPublish = ('true' == $this->DirectPublish) ? true : false; }
		if (!is_null($this->SupportsForms)){ $this->SupportsForms = ('true' == $this->SupportsForms) ? true : false; }
		if (!is_null($this->SupportsCropping)){ $this->SupportsCropping = ('true' == $this->SupportsCropping) ? true : false; }
		if (0 < count($this->ExtraMetaData)){
			if (is_object($this->ExtraMetaData[0])){
				foreach ($this->ExtraMetaData as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Issues)){
			if (is_object($this->Issues[0])){
				foreach ($this->Issues as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Editions)){
			if (is_object($this->Editions[0])){
				foreach ($this->Editions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class AdmIssue
{
	public $Id;
	public $Name;
	public $Description;
	public $SortOrder;
	public $EmailNotify;
	public $ReversedRead;
	public $OverrulePublication;
	public $Deadline;
	public $ExpectedPages;
	public $Subject;
	public $Activated;
	public $PublicationDate;
	public $ExtraMetaData;
	public $Editions;
	public $Sections;
	public $Statuses;
	public $UserGroups;
	public $Workflows;
	public $Routings;
	public $CalculateDeadlines;

	/**
	 * @param integer              $Id                   Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $Description          Nullable.
	 * @param integer              $SortOrder            Nullable.
	 * @param boolean              $EmailNotify          Nullable.
	 * @param boolean              $ReversedRead         Nullable.
	 * @param boolean              $OverrulePublication  Nullable.
	 * @param string               $Deadline             Nullable.
	 * @param integer              $ExpectedPages        Nullable.
	 * @param string               $Subject              Nullable.
	 * @param boolean              $Activated            Nullable.
	 * @param string               $PublicationDate      Nullable.
	 * @param AdmExtraMetaData[]   $ExtraMetaData        Nullable.
	 * @param AdmIdName[]          $Editions             Nullable.
	 * @param AdmIdName[]          $Sections             Nullable.
	 * @param AdmIdName[]          $Statuses             Nullable.
	 * @param AdmIdName[]          $UserGroups           Nullable.
	 * @param AdmIdName[]          $Workflows            Nullable.
	 * @param AdmIdName[]          $Routings             Nullable.
	 * @param boolean              $CalculateDeadlines   Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $Description=null, $SortOrder=null, $EmailNotify=null, $ReversedRead=null, $OverrulePublication=null, $Deadline=null, $ExpectedPages=null, $Subject=null, $Activated=null, $PublicationDate=null, $ExtraMetaData=null, $Editions=null, $Sections=null, $Statuses=null, $UserGroups=null, $Workflows=null, $Routings=null, $CalculateDeadlines=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Description          = $Description;
		$this->SortOrder            = $SortOrder;
		$this->EmailNotify          = $EmailNotify;
		$this->ReversedRead         = $ReversedRead;
		$this->OverrulePublication  = $OverrulePublication;
		$this->Deadline             = $Deadline;
		$this->ExpectedPages        = $ExpectedPages;
		$this->Subject              = $Subject;
		$this->Activated            = $Activated;
		$this->PublicationDate      = $PublicationDate;
		$this->ExtraMetaData        = $ExtraMetaData;
		$this->Editions             = $Editions;
		$this->Sections             = $Sections;
		$this->Statuses             = $Statuses;
		$this->UserGroups           = $UserGroups;
		$this->Workflows            = $Workflows;
		$this->Routings             = $Routings;
		$this->CalculateDeadlines   = $CalculateDeadlines;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.dataclasses.AdmIssue'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Id)){ $this->Id = null; }
		if (is_nan($this->SortOrder)){ $this->SortOrder = null; }
		if (is_nan($this->ExpectedPages)){ $this->ExpectedPages = null; }
		if (!is_null($this->EmailNotify)){ $this->EmailNotify = ('true' == $this->EmailNotify) ? true : false; }
		if (!is_null($this->ReversedRead)){ $this->ReversedRead = ('true' == $this->ReversedRead) ? true : false; }
		if (!is_null($this->OverrulePublication)){ $this->OverrulePublication = ('true' == $this->OverrulePublication) ? true : false; }
		if (!is_null($this->Activated)){ $this->Activated = ('true' == $this->Activated) ? true : false; }
		if (!is_null($this->CalculateDeadlines)){ $this->CalculateDeadlines = ('true' == $this->CalculateDeadlines) ? true : false; }
		if (0 < count($this->ExtraMetaData)){
			if (is_object($this->ExtraMetaData[0])){
				foreach ($this->ExtraMetaData as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Editions)){
			if (is_object($this->Editions[0])){
				foreach ($this->Editions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Sections)){
			if (is_object($this->Sections[0])){
				foreach ($this->Sections as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Statuses)){
			if (is_object($this->Statuses[0])){
				foreach ($this->Statuses as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->UserGroups)){
			if (is_object($this->UserGroups[0])){
				foreach ($this->UserGroups as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Workflows)){
			if (is_object($this->Workflows[0])){
				foreach ($this->Workflows as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->Routings)){
			if (is_object($this->Routings[0])){
				foreach ($this->Routings as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class AdmExtraMetaData
{
	public $Property;
	public $Values;

	/**
	 * @param string               $Property             
	 * @param string[]             $Values               
	 */
	public function __construct( $Property=null, $Values=null)
	{
		$this->Property             = $Property;
		$this->Values               = $Values;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.dataclasses.AdmExtraMetaData'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->Values)){
			if (is_object($this->Values[0])){
				foreach ($this->Values as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class AdmEdition
{
	public $Id;
	public $Name;
	public $Description;
	public $SortOrder;
	public $DeadlineRelative;

	/**
	 * @param integer              $Id                   Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $Description          Nullable.
	 * @param integer              $SortOrder            Nullable.
	 * @param int                  $DeadlineRelative     Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $Description=null, $SortOrder=null, $DeadlineRelative=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Description          = $Description;
		$this->SortOrder            = $SortOrder;
		$this->DeadlineRelative     = $DeadlineRelative;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.dataclasses.AdmEdition'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Id)){ $this->Id = null; }
		if (is_nan($this->SortOrder)){ $this->SortOrder = null; }
		if (is_nan($this->DeadlineRelative)){ $this->DeadlineRelative = null; }
	}
}
class AdmSection
{
	public $Id;
	public $Name;
	public $Description;
	public $SortOrder;
	public $Deadline;
	public $ExpectedPages;
	public $Statuses;

	/**
	 * @param integer              $Id                   Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $Description          Nullable.
	 * @param integer              $SortOrder            Nullable.
	 * @param string               $Deadline             Nullable.
	 * @param integer              $ExpectedPages        Nullable.
	 * @param AdmIdName[]          $Statuses             Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $Description=null, $SortOrder=null, $Deadline=null, $ExpectedPages=null, $Statuses=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Description          = $Description;
		$this->SortOrder            = $SortOrder;
		$this->Deadline             = $Deadline;
		$this->ExpectedPages        = $ExpectedPages;
		$this->Statuses             = $Statuses;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.dataclasses.AdmSection'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Id)){ $this->Id = null; }
		if (is_nan($this->SortOrder)){ $this->SortOrder = null; }
		if (is_nan($this->ExpectedPages)){ $this->ExpectedPages = null; }
		if (0 < count($this->Statuses)){
			if (is_object($this->Statuses[0])){
				foreach ($this->Statuses as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}
}
class AdmStatus
{
	public $Id;
	public $Name;
	public $SortOrder;
	public $Type;
	public $Produce;
	public $Color;
	public $DefaultRouteTo;
	public $CreatePermanentVersion;
	public $RemoveIntermediateVersions;
	public $AutomaticallySendToNext;
	public $ReadyForPublishing;
	public $Phase;
	public $SkipIdsa;

	/**
	 * @param integer              $Id                   Nullable.
	 * @param string               $Name                 Nullable.
	 * @param integer              $SortOrder            Nullable.
	 * @param string               $Type                 Nullable.
	 * @param boolean              $Produce              Nullable.
	 * @param string               $Color                Nullable.
	 * @param AdmIdName            $DefaultRouteTo       Nullable.
	 * @param boolean              $CreatePermanentVersion Nullable.
	 * @param boolean              $RemoveIntermediateVersions Nullable.
	 * @param boolean              $AutomaticallySendToNext Nullable.
	 * @param boolean              $ReadyForPublishing   Nullable.
	 * @param string               $Phase                Nullable.
	 * @param boolean              $SkipIdsa             Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $SortOrder=null, $Type=null, $Produce=null, $Color=null, $DefaultRouteTo=null, $CreatePermanentVersion=null, $RemoveIntermediateVersions=null, $AutomaticallySendToNext=null, $ReadyForPublishing=null, $Phase=null, $SkipIdsa=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->SortOrder            = $SortOrder;
		$this->Type                 = $Type;
		$this->Produce              = $Produce;
		$this->Color                = $Color;
		$this->DefaultRouteTo       = $DefaultRouteTo;
		$this->CreatePermanentVersion = $CreatePermanentVersion;
		$this->RemoveIntermediateVersions = $RemoveIntermediateVersions;
		$this->AutomaticallySendToNext = $AutomaticallySendToNext;
		$this->ReadyForPublishing   = $ReadyForPublishing;
		$this->Phase                = $Phase;
		$this->SkipIdsa             = $SkipIdsa;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.dataclasses.AdmStatus'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Id)){ $this->Id = null; }
		if (is_nan($this->SortOrder)){ $this->SortOrder = null; }
		if (!is_null($this->Produce)){ $this->Produce = ('true' == $this->Produce) ? true : false; }
		if (!is_null($this->CreatePermanentVersion)){ $this->CreatePermanentVersion = ('true' == $this->CreatePermanentVersion) ? true : false; }
		if (!is_null($this->RemoveIntermediateVersions)){ $this->RemoveIntermediateVersions = ('true' == $this->RemoveIntermediateVersions) ? true : false; }
		if (!is_null($this->AutomaticallySendToNext)){ $this->AutomaticallySendToNext = ('true' == $this->AutomaticallySendToNext) ? true : false; }
		if (!is_null($this->ReadyForPublishing)){ $this->ReadyForPublishing = ('true' == $this->ReadyForPublishing) ? true : false; }
		if (!is_null($this->SkipIdsa)){ $this->SkipIdsa = ('true' == $this->SkipIdsa) ? true : false; }
	}
}
class AdmTermEntity
{
	public $Id;
	public $Name;
	public $AutocompleteProvider;
	public $PublishSystemId;

	/**
	 * @param integer              $Id                   Nullable.
	 * @param string               $Name                 Nullable.
	 * @param string               $AutocompleteProvider Nullable.
	 * @param string               $PublishSystemId      Nullable.
	 */
	public function __construct( $Id=null, $Name=null, $AutocompleteProvider=null, $PublishSystemId=null)
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->AutocompleteProvider = $AutocompleteProvider;
		$this->PublishSystemId      = $PublishSystemId;
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.dataclasses.AdmTermEntity'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->Id)){ $this->Id = null; }
	}
}
