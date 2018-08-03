<?php

/**
 * SQL script generator for MS SQL.
 *
 * Since 10.2 the class named 'MssqlGenerator' was renamed and moved from dbmodel.php into here.
 *
 * @since 10.2.0
 */

require_once BASEDIR.'/server/dbscripts/generators/Base.class.php';

class WW_DbScripts_Generators_Mssql extends WW_DbScripts_Generators_Base
{
	protected $thistableident;
	protected $thistableinserts;
	private $procInserted;

	public function __construct($drop)
	{
		parent::__construct( $drop );
		$this->myname = "mssql";
	}

	/**
	 * Qoutes table names and column names to the Mssql convention ( [<$fld>].
	 *
	 * @param string $fld
	 * @return string
	 */
	public function quotefields($fld)
	{
		$ret = '';
		$arr = explode(',', $fld);
		$komma = '';
		foreach ($arr as $ix) {
			$ret .= $komma.'['.trim($ix).']';
			$komma = ', ';
		}
		return $ret;
	}

	protected function normtype($line)
	{
		$line = preg_replace('/(medium)*int\([ 0-9]*\)/i', 'int', $line); // this will transform "mediumint(9) to int", "tinyint(1) to tinyint".
		$line = preg_replace('/timestamp\([ 0-9]*\)/i', 'timestamp', $line);
		$line = preg_replace('/unsigned/i', '', $line);
		$line = preg_replace('/longblob/i', "image", $line);
		$line = preg_replace('/(medium)*blob/i', "text", $line);
		$line = preg_replace('/double/i', 'real', $line);
		$line = preg_replace('/bigint\([0-9]*\)/i', 'bigint', $line);

		return $line;
	}

	protected function autoincrement()
	{
		$this->thistableident = true;
		return "IDENTITY(1,1)";
	}
	protected function uniquekey($name, $fields)
	{
		return " constraint ".$this->quotefields($name)." unique (".$this->quotefields($fields)."),\r\n";
	}

	protected function dropexists()
	{
		return "";
	}

	/**
	 * Based on the 'default' property a default value is returned.
	 *
	 * @param array $field DB field properties as defined in DB model.
	 * @return string SQL fragment.
	 */
	protected function setDefault( $field )
	{
		$default = '';

		if( isset($field['default'])) {
			if ( is_string( $field['default'])) {
				if (strlen($field['default']) > 0 ) {
					$default = ' default '.$field['default'];
				} elseif ( $field['type'] == 'blob' || $field['type'] == 'mediumblob' ) {
					$default = " default ''";
					// If no default is set mssql throws an error if an record is inserted
					// and the blob values is not specified.
				}
			} else {
				$default = ' default '.$field['default'];
			}
		}

		return $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function tablePost($table)
	{
		parent::tablePost($table);
		$this->thistableinserts = 0;
	}

	/**
	 * {@inheritDoc}
	 */
	public function insertpost($table)
	{
		if ($this->thistableident && $this->thistableinserts) {
			$this->txt .= "SET IDENTITY_INSERT ".$this->quotefields($table["name"])." OFF\r\n";
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert($line, $table, $auto = true)
	{
		if ($this->thistableident && !$this->thistableinserts) {
			$this->txt .= "SET IDENTITY_INSERT ".$this->quotefields($table["name"])." ON\r\n";
			$this->thistableinserts += 1;
		}

		$auto = true;
		// handle hex strings (blob in namedqueries)
		$r = array();
		while (preg_match("/0x([0-9a-f]*)/i", $line, $r) > 0) {
			$hex = pack("H*", $r[1]);
			$hex = str_replace("'", "''", $hex);
			$line = preg_replace("/0x".$r[1]."/i", "'$hex'", $line);
			$auto = false;
		}

		parent::insert($line, $table, $auto);
	}

	/**
	 * {@inheritDoc}
	 */
	public function tablePre($table)
	{
		$this->thistableident = false;
		parent::tablePre($table);
	}

	/**
	 * function to generate text for drop if exists constructs
	 *
	 * @param string $name DB table name.
	 * @return string	SQL fragment.
	 */
	public function dropTable($name)
	{
		return "if exists (select 1 from sysobjects where name = '$name') drop table ".$this->quotefields($name) . $this->closeline() . "\r\n";
	}
	/**
	 * function to generate alter table text
	 *
	 * @param array $table DB table info.
	 */
	public function alterPre($table)
	{
		$this->txt .= "ALTER TABLE ".$this->quotefields($table["name"])." ADD \r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function addField($table, $field)
	{
		$this->field( $table, $field, true );
	}

	/**
	 * function to change an existing field definition
	 *
	 * IMPORTANT: The rules of modifying existing columns in MS SQL Server are different from what either Oracle
	 * or DB2 would allow you to do. For example, you cannot change the data type of a column on which a constraint is
	 * based or give it a default value. You also are unable to alter columns with TEXT, NTEXT, IMAGE, or TIMESTAMP data
	 * types. Some more restrictions apply; see vendor's documentation for details.
	 *
	 * @param array $table DB table info.
	 * @param array $oldfld DB field info as in the old/source database.
	 * @param array $newfld DB field info as in the new/target database.
	 */
	protected function doAlterField( $table, $oldfld, $newfld )
	{
		$newfldname = $this->quotefields( $newfld['name'] );
		$oldfldname = $this->quotefields( $oldfld['name'] );
		$tablename = $table['name'];

		// Don't do anything if field's name, type, default and nullable have not been changed.
		// This can happen if the mssql types are the same but the mysql types not.
		if( $this->isNameEqual( $oldfld['name'], $newfld['name'] ) &&
			$this->isTypeEqual( $oldfld['type'], $newfld['type'] ) &&
			$this->isDefaultEqual( $oldfld, $newfld ) &&
			$this->isNullableEqual( $oldfld, $newfld ) ) {
			return;
		}

		// Drop constraint, or else you risk errors such as: ALTER TABLE ALTER COLUMN value failed because one or more objects access this column.(MSSQL: 4922)
		$this->dropFieldConstraint( $table['name'], $oldfld['name'] );

		// Alter column when field's type or nullable have changed. (The field's default is added later!)
		if( !$this->isTypeEqual( $oldfld['type'], $newfld['type'] ) ||
			!$this->isNullableEqual( $oldfld, $newfld ) ) {
			// if column allows NULL initially, need to set all column values having NULL to ''
			// before changing the column to NOT NULL
			if( !$this->isNullableEqual( $oldfld, $newfld ) ) {
				$this->txt .= "UPDATE $tablename SET $oldfldname = '' WHERE $oldfldname is null;";
				$this->txt .= "\r\n";
			}
			// check if the field is an index field
			$reAddIndexes = array();
			if( isset($table['indexes']) ){
				foreach( $table['indexes'] as $index ){
					if ( $this->fieldUsedInCurrentIndex( $index, $newfld)) {
						$reAddIndexes[] = $index; // keep track which index we drop now,we need to add it back later
						$this->dropIndex( $index, $table);
					}
				}
			}

			if( $this->isNameEqual( $oldfld['name'], $newfld['name'] ) ) {
				$this->txt .= "ALTER TABLE $tablename ALTER COLUMN ";
				$this->field( $table, $newfld, false, false ); // Note: alter column with default value is unsupported for MSSQL
				$this->txt .= ";\r\n";
			}

			if( isset($reAddIndexes) ) foreach( $reAddIndexes as $reAddIndex ){
				// re-adding back the index after setting field from NULL to Not NULL
				if ( isset($reAddIndex['primary']) && $reAddIndex['primary'] == true ) {
					$this->index( $reAddIndex, $table, true );
				} else {
					$this->index( $reAddIndex, $table );
				}
			}
		}

		// Rename column when field name has changed
		if( !$this->isNameEqual( $oldfld['name'], $newfld['name'] ) ) {
			$this->txt .= "EXECUTE sp_rename '" . "$tablename." . $oldfld['name'] . "', '" . $newfld['name'] . "', 'COLUMN';\r\n";
		}

		// Restore the constraint
		if( !isset($newfld['autoincrement']) ) { // Add default for non autoincrement field
			$default = !isset($newfld['default']) ? "''" : $newfld['default'];
			$this->txt .= 'ALTER TABLE '. $this->quotefields($tablename) .' ADD DEFAULT ('.$default.') FOR '.$newfldname.';'."\r\n";
		}
	}

	/**
	 * Checks if a field is used in the index. To check this the version of the index must be taken into account.
	 * If the field is used an index with an equal/higher version as the field itself the index is not taken into
	 * account. Suppose a field is added in version 800 and the index using that field is also added in version 800.
	 * In that case the field is not yet used as the index is not yet created (indexes are created after the field
	 * definitions are handled). In that case we check on the 'alters' attribute to see if there is maybe an older
	 * definition that also uses the field.
	 *
	 * @param array $index (including the older definitions)
	 * @param array $field (definition of the field)
	 * @return mixed null|array Null if field is not used else the index definition
	 */
	protected function fieldUsedInCurrentIndex( $index, $field )
	{
		$result = null;
		$currentIndex = null;

		if( version_compare( $index['v'], $field['v'], '<' ) ) {
			$currentIndex = $index;
		} elseif ( isset ( $index['alters'] )) {
			// Check if the field is used in the previous definition which is the current one as the new one is not yet
			// current. Indexes are handled after the fields.
			if( version_compare( $index['alters']['v'], $field['v'], '<' ) ) {
				$currentIndex = $index['alters'];
			}
		}

		if ( $currentIndex ) {
			$indexFields = isset($currentIndex['fields']) ? preg_split('/\,/', $currentIndex['fields']) : null;
			if( $indexFields ) foreach( $indexFields as $indexField ){
				if( trim($indexField) == $field['name'] ){ // if yes, we need to drop the index field before we can switch it to 'NOT NULL"
					$result = $currentIndex;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Checks if the given field ( parameter $fieldName) is a primary key index.
	 *
	 * @param array $index Index definitions.
	 * @param string $fieldName The field name to be checked if it is a primary key index.
	 * @return bool
	 */
	private static function isIndexPrimary( $index, $fieldName )
	{
		if( array_key_exists('fields',$index) ) {
			if( $index['fields'] == $fieldName ) {
				return array_key_exists('primary',$index) && $index['primary'];
			}
			if( array_key_exists('alters',$index) && $index['alters'] ) {
				return self::isIndexPrimary( $index['alters'], $fieldName );
			}
		}
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function dropField($table, $fld)
	{
		// When the index is an altered primary, we already have dropped the constraint in the alterIndex().
		// By calling dropFieldConstraint() again, would try to drop it again, which needs to be avoided here.
		// For example, this happened when altering the id field into jobid for the smart_serverjobs table.
		$isAlteredPrimaryIndex = false;
		foreach( $table['indexes'] as $index ) {
			if( array_key_exists('alters',$index) && $index['alters'] ) {
				if( $this->isIndexPrimary( $index['alters'], $fld['name'] ) ) {
					$isAlteredPrimaryIndex = true;
					break; // found
				}
			}
		}
		if( !$isAlteredPrimaryIndex ) { // When the primary index is changed, the 'drop constraint' is taken care of in alterIndex(), so don't include here.
			$this->dropFieldConstraint($table['name'], $fld['name']);
		}
		$this->txt .= 'ALTER TABLE ' . $this->quotefields($table['name']) . ' DROP COLUMN ' .
			$this->quotefields($fld['name']) .$this->closeline()."\r\n";
	}

	/**
	 * Function to change an existing index definition.
	 *
	 * @param array $table table info
	 * @param array $oldIndex index info as in the old/source database
	 * @param array $newIndex info as in the new/target database
	 */
	public function alterIndex($table, $oldIndex, $newIndex)
	{
		if ( (array_key_exists('primary', $oldIndex) && $oldIndex['primary'] == true) &&
			(array_key_exists('primary', $newIndex) && $newIndex['primary'] == true)) {
			$this->storeProcedure_ChangePrimaryIndex( $table['name'] );
		} else {
			$this->dropIndex($oldIndex, $table);
		}

		$this->index($newIndex, $table, true);

	}

	/**
	 * Function to drop indexes. If index is 'unique' the index is dropped either as a
	 * 'constrain' otherwise just a 'drop index' is done. This depends on the way the index
	 * was created. If created as constrain a 'drop constrain' is done, otherwise a
	 * 'drop index'. Which of the two is applicable is determined at runtime.
	 *
	 * @param array $index index info.
	 * @param array $table tabel info.
	 */
	public function dropIndex($index, $table)
	{
		if (array_key_exists('primary', $index) && $index['primary'] == true) {
			$this->storeProcedure_ChangePrimaryIndex( $table['name'] );
		} elseif (array_key_exists('unique', $index) && $index['unique'] == true) {
			$indexname = $index['name'];
			$this->txt .= "IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'" . $this->quotefields($table['name']) .
				"') AND is_unique_constraint = 1 AND name = N" . "'$indexname'" . ' ) ' .
				'ALTER TABLE '. $this->quotefields($table['name']) . ' DROP CONSTRAINT ' . $this->quotefields($index['name']) . ' ' ;
			$this->txt .= $this->closeline(). "\r\n";
			$this->txt .= "IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'" . $this->quotefields($table['name']) . "')
										AND is_unique_constraint = 0 AND name = N" . "'$indexname'" . ' ) ' .
				'DROP INDEX ' . $this->quotefields($table['name']) . '.' . $this->quotefields($index['name']) . ' ';
			$this->txt .= $this->closeline(). "\r\n";
		} else { //Non-unique
			$this->txt .= 'DROP INDEX ' . $this->quotefields($table['name']) .
				'.' . $this->quotefields($index['name']) . ' ';
			$this->txt .= $this->closeline(). "\r\n";
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function index( $index, $table, $alter = false )
	{
		// Handle adding primary indexes (alter).
		if ( isset($index['primary']) && $index['primary'] == true ) {
			if ( $alter ) {
				$this->txt .= 'ALTER TABLE ' . $this->quotefields( $table['name'] ) .
					' ADD PRIMARY KEY (' . $this->quotefields( $index['fields'] ) . ')' . $this->closeline() . "\r\n";
				return;
			} else { // Create
				return; // Primary indexes are already added by the create statement of the table.
			}
		}

		// Handle adding unique indexes (alter).
		if ( $alter && isset($index['unique']) && $index['unique'] == true ) {
			$this->txt .= 'ALTER TABLE ' . $this->quotefields($table['name']) . ' ADD CONSTRAINT ' .
				$this->quotefields($index['name']) . ' UNIQUE ' .
				' (' . $this->quotefields($index['fields']) . ') ';
			$this->txt .= $this->closeline(). "\r\n";
			return;
		}

		// Handle adding normal indexes (alter/create) and unique indexes (create).
		$unique = '';
		if (isset($index['unique']) && $index['unique'] == true ) {
			$unique = 'UNIQUE';
		}
		$name = '';
		if (isset($index["name"])) {
			$name = $index["name"];
		}

		if (!$name && isset($index[$this->myname."name"])) {
			$name = $index[$this->myname."name"];
		}

		if (!$name) return;

		$type = '';
		if (isset($index[$this->myname."type"])) {
			$type = $index[$this->myname."type"];
		}

		// Remove length constraint in case of Mssql. So 'documentid(200)' will be changed into 'documentid'.
		$indexfields = explode(',', $index['fields']);
		foreach ($indexfields as &$indexfield) {
			$posBracket = strpos($indexfield, '(', 1);
			if (is_integer($posBracket)) { // bracket found
				$column = substr($indexfield, 0, ($posBracket));
				$indexfield = $column;
			}
		}
		$index['fields'] = implode(',', $indexfields);

		$this->txt .= "CREATE $unique INDEX ".$this->quotefields($name)." ON ".$this->quotefields($table["name"])."(";
		$this->txt .= $this->quotefields($index['fields']);
		$this->txt .= ") $type".$this->closeline()."\r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function upgradePre()
	{
		$this->procInserted = false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function upgradePost()
	{
		if( $this->procInserted === true ) {
			$this->txt .= 'DROP PROCEDURE [dbo].[SCE_GetConstraintName];'."\r\n";
			$this->procInserted = false;
		}
	}

	/**
	 * This function executes a stored procedure to retrieve constrains on a
	 * table field. Next the constrain is dropped. If the procedure is not
	 * yet created, it is created first.
	 *
	 * @param string quoted tablename $tablename
	 * @param string quoted fieldname $fldname
	 */
	public function dropFieldConstraint($tablename, $fldname)
	{
		$this->storedProcedure_GetConstraintName(); // make sure our stored procedure exists
		$this->txt .= 'DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)'."\r\n";
		$this->txt .= 'EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = \''. $tablename . '\', @columnName = \'' . $fldname . '\', @constraintName = @constraintName OUTPUT'."\r\n";
		$this->txt .= 'SET @sql = \'ALTER TABLE '. $tablename . ' DROP CONSTRAINT \' + @constraintName' . "\r\n";
		$this->txt .= 'EXEC (@sql)' . $this->closeline() . "\r\n";
	}

	/**
	 * This function checks if the the value of autoincrement is the same of two
	 * table fields. Typical this function can be used to check if the autoincrement value
	 * has been changed between the old definition and the new definition of a
	 * field.
	 *
	 * @param array $field1 DB field info (as in the old/source database)
	 * @param array $field2 DB field info (as in the new/target database)
	 * @return bool autoincrement value is the same of two fields
	 */
	private function autoincrementIsSame($field1, $field2)
	{
		$result = true;
		$field1Autoincrement = false;
		$field2Autoincrement = false;

		if (array_key_exists('autoincrement', $field1)) {
			$field1Autoincrement = $field1['autoincrement'];
		}
		if (array_key_exists('autoincrement', $field2)) {
			$field2Autoincrement = $field2['autoincrement'];
		}
		if ($field2Autoincrement <> $field1Autoincrement) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Creates a stored procedure that can find the name of the constraint for any field any table.
	 *
	 * When we set a default value for a field, it also creates a DEFAULT-type constraint for that column.
	 * The constraint is named with 'DF__' as a prefix. In order to use DROP/ALTER COLUMN, the constraint bound to
	 * that column has to be dropped first with this syntax.
	 *   ALTER TABLE table_name DROP CONTRAINT constraint_name
	 * Since we need to supply the exact contraint name to the code above, we have to find constraint name in
	 * the sysobjects table. Unfortunately, we can't use EXEC sp_unbindefault table_name.column_name in order
	 * to drop the default constraint. Then you would need to query the sysobjects table to find the default constraint.
	 */
	private function storedProcedure_GetConstraintName()
	{
		// See for good solution: http://www.thescripts.com/forum/thread80097.html
		if( $this->procInserted === false ) {
			$this->txt .=
				'CREATE PROCEDURE [dbo].[SCE_GetConstraintName] ( @tablename sysname, @columnName sysname, @constraintName sysname OUTPUT ) AS'."\r\n".
				'SELECT @constraintName = o1.name FROM sysobjects o1'."\r\n".
				'INNER JOIN sysobjects o2 ON o1.parent_obj = o2.id'."\r\n".
				"INNER JOIN syscolumns c ON (o1.id = c.cdefault) OR (c.id = o2.id and c.cdefault = 0 and o1.xtype = 'PK')"."\r\n".
				'WHERE (o2.name = @tablename) AND (c.name = @columnName);'."\r\n";
			$this->procInserted = true;
		}
	}

	/**
	 * Creates a stored procedure that will drop the old primary key and adds the new one.
	 *
	 * To drop a primary key in MSSQL, the primary key constraint needs to be known;
	 * therefore a procedure is needed to retrieve the constraint name before dropping the index.
	 *
	 * The procedure returned by this function will do the following:
	 * - Gets the old primary index constraint name
	 * - Drops the old primary index constraint name retrieved. ( Dropping the primary key will be done later, so not
	 *     covered in this procedure. )
	 * - Adds the new primary index with the constraint name retrieved.
	 *
	 * @param string $tableName Table name of which the primary index is to be altered.
	 * @param string $oldPrimaryIndex The old primary key index to be dropped.
	 * @param string $newPrimaryIndex The new primary key index to be added.
	 */
	private function storedProcedure_DropPrimaryIndex( $tableName, $oldPrimaryIndex, $newPrimaryIndex )
	{
		$this->storedProcedure_GetConstraintName(); // make sure our stored procedure exists
		$this->txt .=
			'DECLARE @return_value int, @constraintName sysname, @sql1 nvarchar(1024), @sql2 nvarchar(1024)' . "\r\n" .
			'EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = \''.$tableName.'\', '.
			'@columnName = \''.$oldPrimaryIndex.'\', @constraintName = @constraintName OUTPUT' . "\r\n" .
			'SET @sql1 = \'ALTER TABLE '.$tableName.' DROP CONSTRAINT \' + @constraintName' . "\r\n" .
			'SET @sql2 = \'ALTER TABLE '.$tableName.' ADD CONSTRAINT \' + @constraintName + \' PRIMARY KEY ('.$newPrimaryIndex.')\'' . "\r\n".
			'EXEC (@sql1)' . "\r\n".
			'EXEC (@sql2);' . "\r\n";
	}

	/**
	 * Creates a procedure to change the primary index of a table.
	 *
	 * In MSSQL the primary index can only be dropped by knowing its constraint name. EN-84882
	 * This function takes care of the above.
	 *
	 * @since 9.4.9
	 * @param string $tableName The name of the table (including prefix).
	 */
	private function storeProcedure_ChangePrimaryIndex( $tableName )
	{
		$this->txt .=
			'DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = \'ALTER TABLE '.$tableName.' DROP CONSTRAINT |ConstraintName|\''. "\r\n" .
			'SET @SQL1 = REPLACE(@SQL1, \'|ConstraintName|\', ( SELECT name FROM sysobjects WHERE xtype = \'PK\' AND parent_obj = OBJECT_ID(\''.$tableName.'\')))'. "\r\n" .
			'EXEC (@SQL1);'. "\r\n";
	}

	/**
	 * Under certain conditions a table must not be added to the 'update identity'
	 * script. This function checks if a table must added depending on the version
	 * which is updated.
	 *
	 * @param string $oldversion Version for which the identity script is generated.
	 * 							 E.g. '4.1' or '4.2'
	 * @param array $table DB table info.
	 * @return bool Whether or not the table must be added to the 'update identity' script.
	 */
	public function addToIdentScript($oldversion, $table)
	{
		$result = false;

		switch ($oldversion) {
			case '4.1':
				// Prevent loss of custom fields
				if (!($table["name"]== "smart_objects") && !($table["name"] == "smart_deletedobjects")) {
					$result = true;
				}
				if( version_compare( $table['v'], $oldversion , '>' ) ) {
					$result = false; // From version 4.2 onwards the identity is set
				}
				break;
			case '4.2':
				// Only the identity of mtpsentobjects is changed.
				if ($table["name"]== "smart_mtpsentobjects") {
					$result = true;
				}
				break;
			default:
				break;
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function addnull()
	{
		return " null ";
	}

	/**
	 * {@inheritDoc}
	 */
	public function isReservedFieldName( $fieldName )
	{
		return false; // TODO
	}
}