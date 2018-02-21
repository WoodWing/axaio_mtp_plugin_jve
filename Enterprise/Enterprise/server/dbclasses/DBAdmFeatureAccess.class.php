<?php
/**
 * Keeps track of feature access objects introduced by server plugins.
 *
 * @package    Enterprise
 * @subpackage DBClasses
 * @since      10.2.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAdmFeatureAccess extends DBBase
{
	const TABLENAME = 'featureaccess';
	/**
	 * @var int $accessFeatureMin The minimum number of the Access Feature range.
	 */
	private $accessFeatureMin = null;
	/**
	 * @var int $accessFeatureMax The maximum number of the Access Feature range.
	 */
	private $accessFeatureMax = null;

	/**
	 * DBAdmFeatureAccess constructor.
	 *
	 * @param int $min
	 * @param int $max
	 */
	public function __construct( $min, $max )
	{
		$this->accessFeatureMin = $min;
		$this->accessFeatureMax = $max;
	}

	/**
	 * Retrieve all features from DB that are introduced by (and registered for) server plug-ins.
	 *
	 * @return ProfileFeatureAccess[] Features, indexed by their names.
	 * @throws BizException on fatal SQL errors
	 */
	public function listFeatures()
	{
		$features = array();
		$where = "`featureid` >= ? AND `featureid` <= ? ";
		$params = array( intval( $this->accessFeatureMin ), intval( $this->accessFeatureMax ));
		$rows = self::listRows( self::TABLENAME, null, null, $where, '*', $params );
		if( self::hasError() || is_null($rows) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		if( $rows ) foreach( $rows as $row ) {
			$feature = self::rowToObj( $row );
			$features[ $feature->Name ] = $feature;
		}
		return $features;
	}

	/**
	 * Stores a new feature in the DB. When Flag is set to '?' a unique flag will be generated.
	 *
	 * @param ProfileFeatureAccess $feature The feature to store.
	 * @return ProfileFeatureAccess The stored feature, providing a new Id and optionally a new Flag.
	 * @throws BizException on fatal SQL errors or when ids or flags have reached max allowed values.
	 */
	public function createFeature( ProfileFeatureAccess $feature )
	{
		$feature->Id = $this->determineNewFeatureId();
		if( $feature->Flag === '?' ) {
			$feature->Flag = self::determineNewFlag();
		}
		$row = self::objToRow( $feature );
		self::insertRow( self::TABLENAME, $row, false );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		return self::rowToObj( $row );
	}

	/**
	 * Resolves the flags defined for a give list of features.
	 *
	 * @param string[] $featureNames
	 * @return string[] The flags, indexed by feature names.
	 */
	public function getFeatureFlags( array $featureNames )
	{
		$flags = array();
		if( $featureNames ) {
			$featureNamesCsv = "'".implode( "', '", $featureNames )."'";
			$where = '`featurename` IN ( '.$featureNamesCsv.' )';
			$params = array();
			$fields = array( 'featurename', 'accessflag' );
			$rows = self::listRows( self::TABLENAME, null, null, $where, $fields, $params );
			if( $rows ) foreach( $rows as $row ) {
				$flags[ $row['featurename'] ] = self::convertAccessFlagIntToChar( $row['accessflag'] );
			}
		}
		return $flags;
	}

	/**
	 * Resolve the feature name for a given feature flag.
	 *
	 * @param string $flag
	 * @return string|null Feature name, or NULL when not found.
	 */
	public function getFeatureNameForFlag( $flag )
	{
		$fields = array( 'featurename' );
		$where = '`accessflag` = ?';
		$params = array( self::convertAccessFlagCharToInt( $flag ) );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params );
		return isset( $row['featurename'] ) ? $row['featurename'] : null;
	}

	/**
	 * Picks a new feature id in the reserved range of 5000-5999.
	 *
	 * @return int|null The new system wide unique feature id.
	 * @throws BizException when too many ids are in use already.
	 */
	private function determineNewFeatureId()
	{
		$maxId = $this->getMaxFeatureId();
		if( !$maxId ) {
			$featureId = $this->accessFeatureMin;
		} elseif( $maxId >= $this->accessFeatureMax ) {
			$detail = "Maximum value ({$maxId}) for the 'featureid' field reached in ".self::TABLENAME." table.";
			throw new BizException('ERR_DATABASE', 'Server', $detail );
		} else { // Within the supported feature access range $this->accessFeatureMin to $this->accessFeatureMax
			$featureId = $maxId + 1;
		}
		return $featureId;
	}

	/**
	 * Return the highest feature id present in the DB.
	 *
	 * @return int|null The maximum value, or null when no records found (empty table).
	 */
	private function getMaxFeatureId()
	{
		$dbDriver = DBDriverFactory::gen();
		$dbTable = $dbDriver->tablename( self::TABLENAME );
		$where = "`featureid` >= ? AND `featureid` <= ? ";
		$params =  array( intval( $this->accessFeatureMin ), intval( $this->accessFeatureMax ));
		$sql = "SELECT MAX(`featureid`) AS `maxid` FROM $dbTable WHERE $where";
		$sth = self::query( $sql, $params );
		$row = self::fetch( $sth );
		return isset( $row['maxid'] ) ? $row['maxid'] : null;
	}

	/**
	 * Picks are new access right flag (UTF-8 char) in the range of 192-600 (decimal)
	 *
	 * @return string The new system wide unique flag.
	 * @throws BizException when too many flags are in use already.
	 */
	static private function determineNewFlag()
	{
		$maxFlag = self::getMaxFlag();
		if( !$maxFlag ) {
			$newFlag = 192; // starting with this two-byte UTF-8 char: http://www.fileformat.info/info/unicode/char/00c0/index.htm
		} elseif( $maxFlag > 600 ) {
			// The 'rights' field in smart_authorization table is used to list all enabled features with a flag.
			// This field can store 1024 UTF-8 chars in MySQL but only 1024 bytes(!) in MSSQL.
			// By starting with 192, we use 2-byte characters for the flags stored by server plugins.
			// ES 10.2 core server also has around 20 flags, which are all single byte. This may grow in time a little.
			// So we can store 1024/2 = 512 flags for plugins, but we also need to reserve some space for the core.
			// By taking a maximum flag of 600, we have used 600-192=408 chars/flags, which takes 2x408=816 bytes.
			// That reserves 1024-816=208 chars/flags for the core server, which is plenty.
			$detail = "Maximum value ({$maxFlag}) for the 'accessflag' field reached in ".self::TABLENAME." table.";
			throw new BizException('ERR_DATABASE', 'Server', $detail );
		} else {
			$newFlag = $maxFlag + 1;
		}
		return self::convertAccessFlagIntToChar( $newFlag );
	}

	/**
	 * Return the highest flag present in the DB.
	 *
	 * @return int|null The maximum value, or null when no records found (empty table).
	 */
	static private function getMaxFlag()
	{
		$dbDriver = DBDriverFactory::gen();
		$dbTable = $dbDriver->tablename( self::TABLENAME );
		$sql = "SELECT MAX(`accessflag`) AS `maxflag` FROM $dbTable ";
		$sth = self::query( $sql );
		$row = self::fetch( $sth );
		return isset( $row['maxflag'] ) ? $row['maxflag'] : null;
	}

	/**
	 * Converts an internal feature access object to a database row.
	 *
	 * @param ProfileFeatureAccess $obj
	 * @return array DB row
	 * @throws BizException when data object is provided without a Name attribute.
	 */
	static private function objToRow( $obj )
	{
		$row = array();

		if( is_null( $obj->Name ) ) {
			$detail = __METHOD__." called with data object for which no Name attribute is provided.";
			throw new BizException( 'ERR_ARGUMENT', 'Server', $detail );
		}
		$row['featurename'] = strval( $obj->Name );

		if( !is_null( $obj->Id ) ) $row['featureid'] = intval( $obj->Id );
		if( !is_null( $obj->Flag ) ) $row['accessflag'] = self::convertAccessFlagCharToInt( $obj->Flag );

		return $row;
	}

	/**
	 * Converts a database row to an internal feature access object.
	 *
	 * @param array $row
	 * @return ProfileFeatureAccess
	 * @throws BizException when data record is provided without a 'featurename' field.
	 */
	static private function rowToObj( $row )
	{
		$obj = new ProfileFeatureAccess();

		if( !isset( $row['featurename'] ) ) {
			$detail = __METHOD__." called with data record for which no 'featurename' is queried.";
			throw new BizException( 'ERR_ARGUMENT', 'Server', $detail );
		}
		$obj->Name = strval($row['featurename']);

		$obj->Id = isset($row['featureid']) ? intval($row['featureid']) : null;
		$obj->Flag = isset($row['accessflag']) ? self::convertAccessFlagIntToChar(intval($row['accessflag'])) : null;

		return $obj;
	}

	/**
	 * Converts an integer to a UTF-8 character. Supported are 1-4 byte UTF-8 characters.
	 * Integer zeroes (0) will be converted to an empty string.
	 *
	 * @param int $int
	 * @return string
	 */
	static private function convertAccessFlagIntToChar( $int )
	{
		if( $int === 0 ) {
			$result = '';
		} else {
			$result = mb_convert_encoding( '&#'.intval($int).';', 'UTF-8', 'HTML-ENTITIES' );
		}
		return $result;
	}

	/**
	 * Converts a UTF-8 character into an integer. Supported are 1-4 byte UTF-8 characters.
	 * Empty strings will be converted to integer zeroes (0).
	 *
	 * @param string $char
	 * @return int
	 */
	static private function convertAccessFlagCharToInt( $char )
	{
		if( $char === '' ) {
			$result = 0;
		} else {
			$unpack = unpack('N', mb_convert_encoding( $char, 'UCS-4BE', 'UTF-8' ) );
			$result = intval( $unpack[1] );
		}
		return $result;
	}
}