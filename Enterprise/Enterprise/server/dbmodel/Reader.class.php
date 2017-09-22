<?php

/**
 * Class that can help reading a DB model definition.
 *
 * @package    Enterprise
 * @subpackage DbModel
 * @since      10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class WW_DbModel_Reader
{
	/** @var WW_DbModel_Provider */
	private $model;

	/**
	 * WW_DbModel_Reader constructor.
	 *
	 * @param WW_DbModel_Provider $dbModelDefinition
	 * @throws BizException when the prefix of the model definition is not valid.
	 */
	public function __construct( WW_DbModel_Provider $dbModelDefinition )
	{
		$this->model = $dbModelDefinition;
		$className = get_class( $dbModelDefinition );

		// Validate the provided prefix to be used for DB table names.
		$prefix = $this->model->getTablePrefix();
		$method = $className."::getTablePrefix()";
		if( strpos( $prefix, DBPREFIX ) !== 0 ) {
			$message = "The DB table prefix '{$prefix}' returned by {$method} should start with '".DBPREFIX."'. ".
				"Please correct and try again.";
			throw new BizException( '', 'Server', '', $message );
		}
		if( $className !== 'WW_DbModel_Definition' ) { // plugin?
			if( strlen( $prefix ) > 10 ) {
				$message = "The DB table prefix '{$prefix}' returned by {$method} should not be longer than 10 characters. ".
					"Please correct and try again.";
				throw new BizException( '', 'Server', '', $message );
			}
			if( $prefix == DBPREFIX ) {
				$message = "The DB table prefix returned by {$method} should not be '".DBPREFIX."'. ".
					"Please correct and try again.";
				throw new BizException( '', 'Server', '', $message );
			}
		}
		if( substr( $prefix, -1 ) !== '_' ) {
			$message = "The DB table prefix '{$prefix}' returned by {$method} should not end with an underscore (_). ".
				"Please correct and try again.";
			throw new BizException( '', 'Server', '', $message );
		}
		if( !preg_match( "/^[a-z0-9_]+$/", $prefix ) ) {
			$message = "The DB table prefix '{$prefix}' returned by {$method} should consist of lower case alpha numberic ".
				"characters (a-z), digits (0-9) or underscores (_) only. Please correct and try again. ";
			throw new BizException( '', 'Server', '', $message );
		}

		// Validate version information.
		$method = $className."::getVersions()";
		foreach( $this->model->getVersions() as $version ) {
			if( !$this->isMajorMinorVersion( $version ) ) {
				$message = "The version '".$version."' returned by {$method} should be in 'major.minor' notation. ".
					"Please correct and try again. ";
				throw new BizException( '', 'Server', '', $message );
			}
		}
		$method = $className."::getVersions()";
		$version = $this->model->getVersion();
		if( !$this->isMajorMinorVersion( $version ) ) {
			$message = "The version '".$version."' returned by {$method} should be in 'major.minor' notation. ".
				"Please correct and try again. ";
			throw new BizException( '', 'Server', '', $message );
		}
		if( $className === 'WW_DbModel_Definition' ) { // core?
			if( $version !== SCENT_DBVERSION ) {
				$message = "The version '".$version."' returned by {$method} should be same as SCENT_DBVERSION. ".
					"Please correct and try again. ";
				throw new BizException( '', 'Server', '', $message );
			}
		}
	}

	/**
	 * Returns tables from db model.
	 * Dropped tables are excluded.
	 *
	 * @return array with table info.
	 * @throws BizException when any of the table names in the model definition are not valid.
	 */
	public function listTables()
	{
		$allTables = $this->model->getStruct()['tables'];
		$catTables = $this->model->getCategorizedTableNames();
		$retTables = array();
		foreach( $allTables as $key => $table ) {
			if( !preg_match( "/^[a-z0-9_]+$/", $table['name'] ) ) {
				$message = "The DB table name '".$table['name']."' should consist of lower case alpha numberic ".
					"characters (a-z), digits (0-9) or underscores (_) only. Please correct and try again. ";
				throw new BizException( '', 'Server', '', $message );
			}
			$prefix = $this->model->getTablePrefix();
			if( strpos( $table['name'], $prefix ) !== 0 ) {
				$message = "The DB table name '".$table['name']."' should match the prefix '{$prefix}'. ".
					"Please correct and try again. ";
				throw new BizException( '', 'Server', '', $message );
			}
			if( strlen( $table['name'] ) > 32 ) {
				$message = "The DB table name '".$table['name']."' should not be longer than 32 characters. ".
					"Please correct and try again. ";
				throw new BizException( '', 'Server', '', $message );
			}
			if( !$this->isMajorMinorVersion( $table['v'] ) ) {
				$message = "The version '".$table['v']."' for DB table '".$table['name']."' should be in 'major.minor' notation. ".
					"Please correct and try again. ";
				throw new BizException( '', 'Server', '', $message );
			}
			if( isset($table['fields']) ) foreach( $table['fields'] as $field ) {
				if( strlen( $field['name'] ) > 32 ) {
					$message = "The field name '".$field['name']."' (of DB table '".$table['name']."') should not be longer than 32 characters. ".
						"Please correct and try again. ";
					throw new BizException( '', 'Server', '', $message );
				}
				if( !$this->isMajorMinorVersion( $field['v'] ) ) {
					$message = "The version '".$field['v']."' for field '".$field['name']."' (of DB table '".$table['name']."') ".
						"should be in 'major.minor' notation. Please correct and try again. ";
					throw new BizException( '', 'Server', '', $message );
				}
				if( !isset($field['drops']) && !isset($field['type']) ) {
					$message = "The field name '".$field['name']."' (of DB table '".$table['name']."') has not type property set. ".
						"Please correct and try again. ";
					throw new BizException( '', 'Server', '', $message );
				}
			}
			if( !$this->toBeDropped( $table, '0' ) ) {
				$found = false;
				foreach( $catTables as $tableNames ) {
					foreach( $tableNames as $tableName ) {
						if( $tableName == $table['name'] ) {
							$found = true;
							break 2;
						}
					}
				}
				if( !$found ) {
					$className = get_class( $this->getDbModelProvider() );
					$method = $className."::getCategorizedTableNames()";
					$message = "The table '".$table['name']."' is not categorized by the {$method} function. ".
						"Please correct and try again. ";
					throw new BizException( '', 'Server', '', $message );
				}
				$retTables[ $key ] = $table;
			}
		}
		return $retTables;
	}

	/**
	 * Returns the table info for the specified table name.
	 *
	 * @param string $tablename .
	 * @return null| array with table info.
	 */
	public function getTable( $tablename )
	{
		foreach( $this->model->getStruct()['tables'] as $table ) {
			if( $table['name'] == $tablename ) {
				return $table;
			}
		}
		return null;
	}

	/**
	 * Validate the format of a given version in 'major.minor' notation.
	 *
	 * @param string $version version in 'major.minor'
	 * @return bool Whether or not the format is correct.
	 */
	private function isMajorMinorVersion( $version )
	{
		$parts = explode( '.', $version );
		return count( $parts ) == 2 && ctype_digit( $parts[0] ) && ctype_digit( $parts[1] );
	}

	/**
	 * Checks if a field/index definition has been changed. Changed (alter) means if it is altered
	 * compared to a version prior to or equal to the 'from' version.
	 *
	 * @param string $fromversion From version.
	 * @param array $dbInfo field/index definition.
	 * @return array  $field/index definition of the last 'alter' version or null if the field is not changed.
	 */
	public function getAlteredDBInfo( $fromversion, $dbInfo )
	{
		$result = null;

		if( array_key_exists( 'alters', $dbInfo ) ) {
			if( is_array( $dbInfo['alters'] ) ) {
				if( version_compare( $dbInfo['alters']['v'], $fromversion, '<=' ) ) {
					$result = $dbInfo['alters'];
				} else {
					$dbInfo = $dbInfo['alters'];
					$result = $this->getAlteredDBInfo( $fromversion, $dbInfo );
				}
			}
		}

		return $result;
	}

	/**
	 * Checks if a table definition has become obsolete. Obsolete means that the table is already dropped in a version
	 * prior to the upgrading from version.
	 *
	 * @param array $table table definition.
	 * @param string $fromVersion The upgrade from version.
	 * @return bool is obsolete then true and else false.
	 */
	public function isObsolete( $table, $fromVersion )
	{
		return isset ( $table['drops'] ) && version_compare( $table['v'], $fromVersion, '<' );
	}

	/**
	 * Checks if a field is db info structure is patched in a certain version.
	 * Returns the original version of the patched structure.
	 *
	 * @param array $dbInfo (field) info structure to check.
	 * @param string $version Version on which the patch must be applied.
	 * @return string The original version or null if structure has not been patched.
	 */
	public function patchedDBInfo( $dbInfo, $version )
	{
		$result = null;

		if( array_key_exists( 'patched', $dbInfo ) ) {
			if( $dbInfo['patched'] ) foreach( $dbInfo['patched'] as $patchVersion => $name ) {
				if( $patchVersion == $version ) {
					$result = $dbInfo['v'];
					break;
				}
			}
		} elseif( array_key_exists( 'alters', $dbInfo ) ) {
			$dbInfo = $dbInfo['alters'];
			$result = $this->patchedDBInfo( $dbInfo, $version );
		}

		return $result;
	}

	/**
	 * Finds the first field definition of a field that has been changed (altered).
	 *
	 * @param array $field field info.
	 * @return array field info of the first definition of a changed field  or null if the field was never changed.
	 */
	public function getFirstDefinitonAlteredField( $field )
	{
		$result = null;
		if( isset( $field['alters'] ) && is_array( $field['alters'] ) ) {
			$result = $this->getFirstDefinitonAlteredField( $field['alters'] );
			if( is_null( $result ) ) {
				$result = $field['alters'];
			}
		}
		return $result;
	}

	/**
	 * Checks if the definition (of a table or field etc) contains
	 * a 'drops' entry. The 'drops' entry contains information about when
	 * the table or field has become unneeded and and also what is its history.
	 *
	 * @param array $dbInfo table or field info.
	 * @return array|null array with history in case a table/field definition is dropped else null.
	 */
	public function getDroppedInfo( $dbInfo )
	{
		$result = null;
		if( array_key_exists( 'drops', $dbInfo ) ) {
			if( is_array( $dbInfo['drops'] ) ) {
				$result = $dbInfo['drops'];
			}
		}
		return $result;
	}

	/**
	 * Checks if the definition of a table or field has become unneeded in some version after the passed $fromversion.
	 *
	 * @param array $dbInfo table or field info.
	 * @param string $fromversion version to compare against.
	 * @return boolean true if definition is dropped in a later version else false.
	 */
	public function toBeDropped( $dbInfo, $fromversion )
	{
		$result = false;
		if( ( version_compare( $dbInfo['v'], $fromversion, '>' ) ) ) {
			if( array_key_exists( 'drops', $dbInfo ) ) {
				$result = true;
			}
		}
		return $result;
	}

	/**
	 * Retrieve the DB model definition used by this reader.
	 *
	 * @return WW_DbModel_Provider
	 */
	public function getDbModelProvider()
	{
		return $this->model;
	}
}