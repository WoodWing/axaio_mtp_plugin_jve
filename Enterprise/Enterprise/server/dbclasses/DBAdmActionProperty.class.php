<?php
/**
 * Implements DB querying of PropertyUsage objects from smart_actionproperties table.
 * Customizations made in Dialog Setup maintenance pages are reflected through PropertyUsage objects.
 * 
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v6.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';

// TODO: Add to admin WSDL and generate this class, for now added manually.
class AdmPropertyUsage extends PropertyUsage
{
	public $Id;
	public $PublicationId;
	public $SortOrder;
	public $Action;
	public $ObjectType;
	public $RefreshOnChange;
	public $ParentFieldId;
	public $DocumentId;

	/**
	 * @param int                  $Id
	 * @param int                  $PublicationId
	 * @param int                  $SortOrder
	 * @param string               $Name
	 * @param boolean              $Editable
	 * @param boolean              $Mandatory
	 * @param string               $Action
	 * @param string               $ObjectType
	 * @param boolean              $Restricted
	 * @param boolean              $RefreshOnChange
	 * @param int                  $ParentFieldId
	 * @param string               $DocumentId
	 * @param int                  $InitialHeight
	 */
	public function __construct( $Id=null, $PublicationId=null, $SortOrder=null, $Name=null, $Editable=null,
	                             $Mandatory=null, $Action=null, $ObjectType=null, $Restricted=null, $RefreshOnChange=null,
	                             $ParentFieldId=null, $DocumentId=null, $InitialHeight=null)
	{
		parent::__construct( $Name, $Editable, $Mandatory, $Restricted, $RefreshOnChange, $InitialHeight );
		$this->Id                   = $Id;
		$this->PublicationId        = $PublicationId;
		$this->SortOrder            = $SortOrder;
		$this->Action               = $Action;
		$this->ObjectType           = $ObjectType;
		$this->ParentFieldId        = $ParentFieldId;
		$this->DocumentId           = $DocumentId;
	}
}

class DBAdmActionProperty extends DBBase
{
	const TABLENAME = 'actionproperties';
	
	/**
	 * Creates a new record at smart_actionproperties table based on given AdmPropertyUsage object.
	 * Given object gets updated automatically with information from database after creation. This
	 * is typically useful to get the new Id, but also assures that all info is correctly round-tripped.
	 *
	 * @param AdmPropertyUsage $obj 
	 * @throws BizException on fatal DB error.
	 */
	static public function insertAdmPropertyUsage( AdmPropertyUsage &$obj )
	{
		self::clearError();
		$id = self::insertRow( self::TABLENAME, self::objToRow( $obj ) );
		if( $id ) {
			$tmp = self::getAdmPropertyUsage( $id );
			if( !is_null($tmp) ) {
				$obj = $tmp;
			}
		}
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Updates an existing record at smart_actionproperties table based on given AdmPropertyUsage object.
	 * Given object gets updated automatically with information from database after creation. This is typically
	 * useful to enrich object with info from DB, but also assures that all info is correctly round-tripped.
	 *
	 * @param AdmPropertyUsage $obj 
	 * @throws BizException on fatal DB error.
	 */
	static public function updateAdmPropertyUsage( AdmPropertyUsage &$obj )
	{
		self::clearError();
		if( self::updateRow( self::TABLENAME, self::objToRow( $obj ), "`id` = ?", array( intval($obj->Id) ) ) ) {
			$tmp = self::getAdmPropertyUsage( $obj->Id );
			if( !is_null($tmp) ) {
				$obj = $tmp;
			}
		}
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Deletes an AdmPropertyUsage.
	 *
	 * @static
	 * @param AdmPropertyUsage $obj
	 * @return bool Whether or not the operation was succesful.
	 * @throws BizException Throws an Exception if the object could not be deleted.
	 */
	public static function deleteAdmPropertyUsage( AdmPropertyUsage $obj )
	{
		self::clearError();
		$where = '`id` = ? ';
		$params = array( $obj->Id);
		$result = (bool)self::deleteRows( self::TABLENAME, $where, $params );

		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $result;
	}

	/**
	 * Deletes all AdmPropertyUsages for a certain Action and DocumentId.
	 *
	 * @static
	 * @param string $action The action for which to execute this operation.
	 * @param string $documentId The DocumentId for which to execute this operation.
	 * @return bool Whether or not the operation was succesful.
	 * @throws BizException Throws an Exception if the objects could not be deleted.
	 */
	public static function deleteAdmPropertyUsageByActionAndDocumentId( $action, $documentId )
	{
		self::clearError();
		$where = '`action` = ? and `documentid` = ? ';
		$params = array( $action, $documentId );
		$result = (bool)self::deleteRows( self::TABLENAME, $where, $params );

		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $result;
	}

	/**
	 * Retrieve an existing record from smart_actionproperties table as an AdmPropertyUsage object.
	 *
	 * @param int $id Record id at DB.
	 * @return AdmPropertyUsage object
	 * @throws BizException on fatal DB error.
	 */
	static public function getAdmPropertyUsage( $id )
	{
		self::clearError();
		$row = self::getRow( self::TABLENAME, "`id` = ?", '*', array( intval($id) ) ); 
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		if( !is_null($row) ) {
			return self::rowToObj( $row );
		}
		return null; // should never happen
	}

	/**
	 * Retrieve existing records from smart_actionproperties table as AdmPropertyUsage objects.
	 *
	 * @param null|string $action The action to retrieve the records for, null for all.
	 * @param null|string $documentId The documentId for which to retrieve the records, leave null for all.
	 * @return AdmPropertyUsage[] $ret Returns an array of AdmPropertyUsage objects that were found in the DB.
	 * @throws BizException on fatal DB error.
	 */
	static public function getAdmPropertyUsages( $action=null, $documentId=null )
	{
		self::clearError();

		$where = '';
		$params = array();

		if (!is_null($action)) {
			$where .= '`action` = ? ';
			$params[] = $action;
		}

		if (!is_null( $documentId )) {
			if ($where != '') {
				$where .= 'and ';
			}
			$where .= '`documentid` = ? ';
			$params[] = $documentId;
		}

		$rows = self::listRows( self::TABLENAME, 'id', '', $where, '*', $params );

		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$ret = array();

		if ($rows) foreach ($rows as $row) {
			$ret[] = self::rowToObj($row);
		}

		return $ret;
	}

	/**
     * Converts a smart_actionproperty DB row to a AdmPropertyUsage object.
     * Currently, there is no AdmPropertyUsage yet, so we temporary use stdClass.
     *
     * @param array $row DB row (with key values)
     * @return object AdmPropertyUsage
     */
	static protected function rowToObj( $row )
	{
		$obj = new AdmPropertyUsage();
		$obj->Id				= $row['id'];
		$obj->PublicationId		= $row['publication'];
		$obj->SortOrder			= $row['orderid'];
		$obj->Name				= $row['property'];
		$obj->Editable			= ($row['edit'] == 'on' ? true : false);
		$obj->Mandatory			= ($row['mandatory'] == 'on' ? true : false);
		$obj->Action			= $row['action'];
		$obj->ObjectType		= $row['type'];
		$obj->Restricted		= ($row['restricted'] == 'on' ? true : false);
		$obj->RefreshOnChange   = ($row['refreshonchange'] == 'on' ? true : false);
		$obj->ParentFieldId     = $row['parentfieldid'];
		$obj->DocumentId        = $row['documentid'];
		$obj->InitialHeight     = $row['initialheight'];
		return $obj;
	}
	
	/**
     * Converts a AdmPropertyUsage object into a smart_actionproperty DB row.
     * Currently, there is no AdmPropertyUsage yet, so we temporary use stdClass.
     *
     * @param AdmPropertyUsage $obj AdmPropertyUsage object
     * @return array DB row (with key values)
     */
	static protected function objToRow( AdmPropertyUsage $obj )
	{	
		$row = array();
		if(!is_null($obj->Id)){
			$row['id'] 			= intval($obj->Id);
		}
		if(!is_null($obj->PublicationId)){
			$row['publication'] = intval($obj->PublicationId);
		}
		if(!is_null($obj->SortOrder)){
			$row['orderid'] 	= intval($obj->SortOrder);
		}
		if(!is_null($obj->Name)){
			$row['property'] 	= $obj->Name;
		}
		if(!is_null($obj->Editable)){
			$row['edit']		= ($obj->Editable == true ? 'on' : '');
		}
		if(!is_null($obj->Mandatory)){
			$row['mandatory']	= ($obj->Mandatory == true ? 'on' : '');
		}
		if(!is_null($obj->Action)){
			$row['action'] 		= $obj->Action;
		}
		if(!is_null($obj->ObjectType)){
			$row['type'] 		= $obj->ObjectType;
		}
		if(!is_null($obj->Restricted)){
			$row['restricted']	= ($obj->Restricted == true ? 'on' : '');
		}
		if(!is_null($obj->RefreshOnChange)){
			$row['refreshonchange']	= ($obj->RefreshOnChange == true ? 'on' : '');
		}
		if(!is_null($obj->ParentFieldId)){
			$row['parentfieldid'] = $obj->ParentFieldId;
		}
		if(!is_null($obj->DocumentId)){
			$row['documentid'] = $obj->DocumentId;
		}
		if(!is_null($obj->InitialHeight)){
			$row['initialheight'] = (is_numeric($obj->InitialHeight)) ? $obj->InitialHeight : 0;
		}
		return $row;
	}
}