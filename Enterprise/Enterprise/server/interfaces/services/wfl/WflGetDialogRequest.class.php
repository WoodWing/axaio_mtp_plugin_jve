<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflGetDialogRequest
{
	public $Ticket;
	public $ID;
	public $Publication;
	public $Issue;
	public $Section;
	public $State;
	public $Type;
	public $Action;
	public $RequestDialog;
	public $RequestPublication;
	public $RequestMetaData;
	public $RequestStates;
	public $RequestTargets;
	public $DefaultDossier;
	public $Parent;
	public $Template;
	public $Areas;

	/**
	 * @param string               $Ticket                    
	 * @param string               $ID                        Nullable.
	 * @param string               $Publication               Nullable.
	 * @param string               $Issue                     Nullable.
	 * @param string               $Section                   Nullable.
	 * @param string               $State                     Nullable.
	 * @param string               $Type                      Nullable.
	 * @param string               $Action                    Nullable.
	 * @param boolean              $RequestDialog             
	 * @param boolean              $RequestPublication        
	 * @param boolean              $RequestMetaData           
	 * @param boolean              $RequestStates             
	 * @param boolean              $RequestTargets            
	 * @param string               $DefaultDossier            Nullable.
	 * @param string               $Parent                    Nullable.
	 * @param string               $Template                  Nullable.
	 * @param string[]             $Areas                     Nullable.
	 */
	public function __construct( $Ticket=null, $ID=null, $Publication=null, $Issue=null, $Section=null, $State=null, $Type=null, $Action=null, $RequestDialog=null, $RequestPublication=null, $RequestMetaData=null, $RequestStates=null, $RequestTargets=null, $DefaultDossier=null, $Parent=null, $Template=null, $Areas=null )
	{
		$this->Ticket               = $Ticket;
		$this->ID                   = $ID;
		$this->Publication          = $Publication;
		$this->Issue                = $Issue;
		$this->Section              = $Section;
		$this->State                = $State;
		$this->Type                 = $Type;
		$this->Action               = $Action;
		$this->RequestDialog        = $RequestDialog;
		$this->RequestPublication   = $RequestPublication;
		$this->RequestMetaData      = $RequestMetaData;
		$this->RequestStates        = $RequestStates;
		$this->RequestTargets       = $RequestTargets;
		$this->DefaultDossier       = $DefaultDossier;
		$this->Parent               = $Parent;
		$this->Template             = $Template;
		$this->Areas                = $Areas;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetDialogRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ID' ) ) {
			$validator->enterPath( 'ID' );
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Publication' ) ) {
			$validator->enterPath( 'Publication' );
			if( !is_null( $datObj->Publication ) ) {
				$validator->checkType( $datObj->Publication, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Issue' ) ) {
			$validator->enterPath( 'Issue' );
			if( !is_null( $datObj->Issue ) ) {
				$validator->checkType( $datObj->Issue, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Section' ) ) {
			$validator->enterPath( 'Section' );
			if( !is_null( $datObj->Section ) ) {
				$validator->checkType( $datObj->Section, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'State' ) ) {
			$validator->enterPath( 'State' );
			if( !is_null( $datObj->State ) ) {
				$validator->checkType( $datObj->State, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Type' ) ) {
			$validator->enterPath( 'Type' );
			if( !is_null( $datObj->Type ) ) {
				$validator->checkType( $datObj->Type, 'string' );
				WflObjectTypeValidator::validate( $validator, $datObj->Type );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Action' ) ) {
			$validator->enterPath( 'Action' );
			if( !is_null( $datObj->Action ) ) {
				$validator->checkType( $datObj->Action, 'string' );
				WflActionValidator::validate( $validator, $datObj->Action );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestDialog' ) ) {
			$validator->enterPath( 'RequestDialog' );
			$validator->checkNull( $datObj->RequestDialog );
			if( !is_null( $datObj->RequestDialog ) ) {
				$validator->checkType( $datObj->RequestDialog, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestPublication' ) ) {
			$validator->enterPath( 'RequestPublication' );
			$validator->checkNull( $datObj->RequestPublication );
			if( !is_null( $datObj->RequestPublication ) ) {
				$validator->checkType( $datObj->RequestPublication, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestMetaData' ) ) {
			$validator->enterPath( 'RequestMetaData' );
			$validator->checkNull( $datObj->RequestMetaData );
			if( !is_null( $datObj->RequestMetaData ) ) {
				$validator->checkType( $datObj->RequestMetaData, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestStates' ) ) {
			$validator->enterPath( 'RequestStates' );
			$validator->checkNull( $datObj->RequestStates );
			if( !is_null( $datObj->RequestStates ) ) {
				$validator->checkType( $datObj->RequestStates, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestTargets' ) ) {
			$validator->enterPath( 'RequestTargets' );
			$validator->checkNull( $datObj->RequestTargets );
			if( !is_null( $datObj->RequestTargets ) ) {
				$validator->checkType( $datObj->RequestTargets, 'boolean' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DefaultDossier' ) ) {
			$validator->enterPath( 'DefaultDossier' );
			if( !is_null( $datObj->DefaultDossier ) ) {
				$validator->checkType( $datObj->DefaultDossier, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Parent' ) ) {
			$validator->enterPath( 'Parent' );
			if( !is_null( $datObj->Parent ) ) {
				$validator->checkType( $datObj->Parent, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Template' ) ) {
			$validator->enterPath( 'Template' );
			if( !is_null( $datObj->Template ) ) {
				$validator->checkType( $datObj->Template, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Areas' ) ) {
			$validator->enterPath( 'Areas' );
			if( !is_null( $datObj->Areas ) ) {
				$validator->checkType( $datObj->Areas, 'array' );
				if( !empty($datObj->Areas) ) foreach( $datObj->Areas as $listItem ) {
					$validator->enterPath( 'AreaType' );
					$validator->checkType( $listItem, 'string' );
					WflAreaTypeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetDialogRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (!is_null($this->RequestDialog)){ $this->RequestDialog = ('true' == $this->RequestDialog) ? true : false; }
		if (!is_null($this->RequestPublication)){ $this->RequestPublication = ('true' == $this->RequestPublication) ? true : false; }
		if (!is_null($this->RequestMetaData)){ $this->RequestMetaData = ('true' == $this->RequestMetaData) ? true : false; }
		if (!is_null($this->RequestStates)){ $this->RequestStates = ('true' == $this->RequestStates) ? true : false; }
		if (!is_null($this->RequestTargets)){ $this->RequestTargets = ('true' == $this->RequestTargets) ? true : false; }
		if (0 < count($this->Areas)){
			if (is_object($this->Areas[0])){
				foreach ($this->Areas as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

