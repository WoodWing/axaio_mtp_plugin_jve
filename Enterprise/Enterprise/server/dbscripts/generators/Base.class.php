<?php

/**
 * Base class for SQL script generators.
 *
 * Since 10.2 the class named 'StdSqlGenerator' was renamed and moved from dbmodel.php into here.
 *
 * @since 10.2.0
 */

require_once BASEDIR.'/server/dbscripts/generators/Abstract.class.php';

abstract class WW_DbScripts_Generators_Base extends WW_DbScripts_Generators_Abstract
{
	protected $thistable;
	protected $thistablefields;
	protected $droptables;
	protected $errors = array();
	protected $myname;

	/**
	 * constructor
	 *
	 * @param $drop boolean	true means drop statements will be generated
	 */
	public function __construct($drop)
	{
		$this->droptables = $drop;
	}

	public function getDBName()
	{
		return $this->myname;
	}

	/**
	 * {@inheritDoc}
	 */
	public function tablePre($table)
	{
		$this->txt .= "\r\n";
//		if ($this->droptables) $this->txt .= $this->dropTable($table["name"]).$this->closeline()."\r\n";
		if ($this->droptables) $this->txt .= $this->dropTable($table["name"]);
		$this->txt .= "CREATE TABLE ".$this->quotefields($table["name"])." (\r\n";
		$this->thistable = $table['name'];
		$this->thistablefields = array();
	}

	/**
	 * function to generate alter table text
	 *
	 * @param array $table DB table info.
	 */
	public function alterPre($table)
	{
		$this->txt .= "ALTER TABLE " . $this->quotefields($table["name"]) . "\r\n";
	}

	/**
	 * function to generate alter table text ending
	 *
	 */
	public function alterPost()
	{
		$this->txt = substr($this->txt, 0, -3);		// skip last ,\r\n
		$this->txt .= $this->closeline()."\r\n";
	}

	/**
	 * Function to generate alter table add field.
	 *
	 * @param array $table DB table info.
	 * @param array $field DB field info.
	 */
	public function addField( $table, $field )
	{
		$this->txt .= "ADD ";
		$this->field( $table, $field, true );
	}

	/**
	 * {@inheritDoc}
	 */
	public function tablePost($table)
	{
		$primarykey = 'id';

		// Primary keys (if any).
		if (isset($table['indexes'])) foreach ($table['indexes'] as $ix) {
			if (isset($ix["primary"])) $primarykey = $ix['fields'];
		}

		// Primary key
		$keys = $this->quotefields($primarykey);
		$this->txt .= "  PRIMARY KEY ($keys)\r\n)".$this->closeline()."\r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function field( $table, $field, $comma, $doDefault=true )
	{
		// name, type, not null
		$this->txt .= "  ".$this->quotefields($field["name"])." ".$this->normtype($field["type"]);
		if ((isset($field["nullable"]) && $field["nullable"] == false) || !isset($field["nullable"]) ) {
			$this->txt .= $this->notnull();
		}
		else {
			$this->txt .= $this->addnull();
		}

		// check mandatory field properties
		if( !isset($field['v']) || !isset($field['name']) || !isset($field['type']) ||
			(!isset($field['default']) && !isset($field['autoincrement']) ) ) { // either default or autoincrement
			exit( 'In table '.$table['name'].', incomplete field definition found: '.print_r( $field, true ) );
		}

		if( $this->isReservedFieldName( $field['name'] ) ) {
			$this->errors[] = 'In table "'.$table['name'].'" the field "'.$field['name'].'" is defined, but that is a reserved word.';
		}

		$this->thistablefields[] = $field['name'];

		// default or autoincrement
		if( $doDefault === true ) {
			if( isset($field["autoincrement"]) ) {
				$this->txt .= " ".$this->autoincrement();
			} else {
				$this->txt .= $this->setDefault($field);
			}
		}
		if($comma) {
			$this->txt .= ",\r\n";
		}
	}

	/**
	 * Based on the 'default' property a default value is returned.
	 *
	 * @param array $field field properties as defined in dbmodel.
	 * @return mixed The default value.
	 */
	protected function setDefault( $field )
	{
		$default = '';

		if( isset($field['default'])) {
			if ( is_string( $field['default'])) {
				if (strlen($field['default']) > 0 ) {
					$default = ' default '.$field['default'];
				}
				// default => "" are skipped.
			} else {
				$default = ' default '.$field['default'];
			}
		}

		return $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function index($index, $table)
	{
//		if (@$index['primary'] || @$index['unique']) return;
		if (isset($index['primary']) && $index['primary'] == true ) {
			return;
		}

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
				if ($this->myname == 'mssql' || $this->myname == 'ora') {
					$indexfield = $column;
				}
			}
		}
		$index['fields'] = implode(',', $indexfields);

		$this->txt .= "CREATE $unique INDEX ".$this->quotefields($name)." ON ".$this->quotefields($table["name"])."(";
		$this->txt .= $this->quotefields($index['fields']);
		$this->txt .= ") $type".$this->closeline()."\r\n";
	}

	/**
	 * Validates mandatory properties that must be set in a field definition.
	 * Also checks if all properties used in the field definition are known / supported.
	 *
	 * @param array $table DB table info.
	 * @param array $field DB field info.
	 */
	private function validateField( $table, $field )
	{
		$mandatories = array( 'v', 'name', 'type' );
		foreach( $mandatories as $prop ) {
			if( !isset($field[$prop]) ) {
				$this->errors[] = 'The "'.$prop.'" property is missing for "'.
					$table['name'].'::'.$field['name'].'" field. ';
			}
			if( empty($field[$prop]) ) {
				$this->errors[] = 'The "'.$prop.'" property value is empty for "'.
					$table['name'].'::'.$field['name'].'" field. ';
			}
		}
		if( !isset($field['default']) && !isset($field['autoincrement']) ) { // either default or autoincrement
			$this->errors[] = 'The "default" and "autoincrement" and  property are both missing for "'.
				$table['name'].'::'.$field['name'].'" field. ';
		}

		$supported = array_flip( array( 'v', 'name', 'type', 'default', 'nullable', 'comment', 'alters', 'autoincrement', 'patched' ) );
		foreach( array_keys($field) as $prop ) {
			if( !isset($supported[$prop]) ) {
				$this->errors[] = 'The "'.$prop.'" property for "'.$table['name'].'::'.$field['name'].
					'" field is not supported. ';
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function alterField( $table, $oldfld, $newfld )
	{
		$this->validateField( $table, $oldfld );
		$this->validateField( $table, $newfld );
		if( !$this->isAutoIncrementEqual( $oldfld, $newfld ) ) {
			$this->errors[] = 'Attempt to alter the "autoincrement" value for the "'.$table['name'].'::'.$newfld['name'].'" field, '.
				'but this is not supported by the DB generator classes. Original value is "'.$oldfld['autoincrement'].'" and the '.
				'new value is "'.$newfld['autoincrement'].'". Field definition cannot be changed if the identity is different. '.
				'In that case table has to rebuild by running a seperate script to update the identity.';
		}
		$this->doAlterField( $table, $oldfld, $newfld );
	}

	/**
	 * function to change an existing field definition
	 *
	 * @param array $table DB table info.
	 * @param array $oldfld field info as in the old/source database.
	 * @param array $newfld field info as in the new/target database.
	 */
	abstract protected function doAlterField( $table, $oldfld, $newfld );

	/**
	 * {@inheritDoc}
	 */
	public function dropField($table, $fld)
	{
		$this->txt .= 'ALTER TABLE ' . $this->quotefields($table['name']) . ' DROP ' .
			$this->quotefields($fld['name']) .$this->closeline()."\r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function dropIndex( $index, $table )
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert($insert, $table, $auto = true)
	{
		$this->txt .= "INSERT INTO ".$this->quotefields($table['name']);
		if ($auto) {
			$cnt = count($this->thistablefields);
			$insertarr = explode(",", $insert, $cnt+1);
			if (count($insertarr) > $cnt) {
				array_pop($insertarr);
			}
			$insert = implode(",", $insertarr);
		}
		$this->txt .= " (".$this->quotefields(join(", ",$this->thistablefields)).") VALUES (";
		$this->txt .= $insert;
		$this->txt .= ")".$this->closeline()."\r\n";
	}

	/**
	 * Add an UPDATE statement to let the generated script save the DB model version after installation.
	 * The model is provided by a the core server.
	 *
	 * @param string $version DB model version in 'major.minor' notation
	 */
	public function setDbModelVersionForCoreServer( $version )
	{
		$this->txt .= "UPDATE ".$this->quotefields('smart_config').
			" set ".$this->quotefields('value')." = '$version' where ".
			$this->quotefields('name')." = 'version'".$this->closeline()."\r\n";
	}

	/**
	 * Add an UPDATE statement to let the generated script save the DB model version after installation.
	 * The model is provided by a server plugin.
	 *
	 * @param string $pluginName Internal name of the server plugin
	 * @param string $version DB model version in 'major.minor' notation
	 */
	public function setDbModelVersionForServerPlugin( $pluginName, $version )
	{
		$this->txt .= "UPDATE ".$this->quotefields('smart_serverplugins').
			" SET ".$this->quotefields('dbversion')." = '$version' WHERE ".
			$this->quotefields('uniquename')." = '$pluginName'".$this->closeline()."\r\n";
	}

	/**
	 * Add an UPDATE statement to let the generated script save the DB model version after installation.
	 * The model is provided by a server plugin.
	 *
	 * @param string $pluginName Internal name of the server plugin
	 * @param string $tablePrefix
	 */
	public function setDbTablePrefixForServerPlugin( $pluginName, $tablePrefix )
	{
		$this->txt .= "UPDATE ".$this->quotefields('smart_serverplugins').
			" SET ".$this->quotefields('dbprefix')." = '$tablePrefix' WHERE ".
			$this->quotefields('uniquename')." = '$pluginName'".$this->closeline()."\r\n";
	}

	/**
	 * Under certain conditions a table must not be added to the 'update identity'
	 * script. This function checks if a table must added depending on the version
	 * which is updated.
	 *
	 * @param string $oldversion Version for which the identity script is generated.
	 * 							 E.g. '4.1' or '4.2'
	 * @param array $table DB table info.
	 * @return boolean Whether or not the table must be added to the 'update identity' script
	 */
	public function addToIdentScript( $oldversion, $table )
	{
		return false;
	}

	/**
	 * Return auto_increment text (or empty string).
	 *
	 * @return string	SQL fragment
	 */
	protected function autoincrement()
	{
		return "auto_increment";
	}

	/**
	 * Return NOT NULL text (or empty string).
	 *
	 * @return string SQL fragment
	 */
	protected function notnull()
	{
		return " NOT NULL ";
	}

	/**
	 * Return NULL text (or empty string).
	 *
	 * @return string SQL fragment
	 */
	protected function addnull()
	{
		return '';
	}

	/**
	 * Quote a field, database dependently.
	 *
	 * @param string $fld field or list of fields (comma separated). The field can contain a length limitation
	 * in case an index field is passed (e.g. documentid(200). This must be quoted like `documentid`(200).
	 * Length limitation on index fields is only applicable in case of Mysql.
	 * @return string	quoted field(s)
	 */
	public function quotefields($fld)
	{
		$ret = '';
		$arr = explode(',', $fld);
		$komma = '';
		foreach ($arr as $ix) {
			$posBracket = strpos($ix, '(', 1);
			if (is_integer($posBracket)) { // bracket found
				$column = substr($ix, 0, ($posBracket));
				$betweenBrackets = substr($ix, $posBracket);
				$ret .= $komma.'`'.trim($column).'`'.$betweenBrackets;
			}
			else { //no bracket
				$ret .= $komma.'`'.trim($ix).'`';
			}
			$komma = ', ';
		}
		return $ret;
	}

	/**
	 * Function to convert database types dependent on database
	 *
	 * @param  string $type	type before conversion
	 * @return string Type (after conversion)
	 */
	protected function normtype($type)
	{
		return $type;
	}

	/**
	 * Function to generate text for unique key construct.
	 *
	 * @param string $name name of unique key
	 * @param string $fields field or list of fields (comma separated).
	 * @return string SQL fragment for defining a unique key.
	 */
	protected function uniquekey($name, $fields)
	{
		return " UNIQUE KEY ".$this->quotefields($name)." (".$this->quotefields($fields)."),\r\n";
	}

	/**
	 * function to generate text for closing sql lines
	 *
	 * @return string	SQL fragment
	 */
	protected function closeline()
	{
		return ";";
	}

	/**
	 * function to generate text for drop if exists constructs
	 *
	 * @param string $name Name of table.
	 * @return string	SQL fragment
	 */
	public function dropTable($name)
	{
		return "DROP TABLE IF EXISTS ".$this->quotefields($name) . $this->closeline() . "\r\n";
	}

	/**
	 * Tells if the field's name remains the same (not changed).
	 *
	 * @param string $oldName
	 * @param string $newName
	 * @return bool
	 */
	protected function isNameEqual( $oldName, $newName )
	{
		return strcasecmp( $oldName, $newName ) == 0;
	}

	/**
	 * Tells if the field's type remains the same (not changed).
	 *
	 * @param string $oldType
	 * @param string $newType
	 * @return bool
	 */
	protected function isTypeEqual( $oldType, $newType )
	{
		return strcasecmp( $this->normtype( $oldType ), $this->normtype( $newType ) ) == 0;
	}

	/**
	 * Tells if the field's default remains the same (not changed).
	 *
	 * @param array $oldField
	 * @param array $newField
	 * @return bool
	 */
	protected function isDefaultEqual( array $oldField, array $newField )
	{
		$oldNullable = isset($oldField['default']) ? $oldField['default'] : null;
		$newNullable = isset($newField['default']) ? $newField['default'] : null;
		return $oldNullable === $newNullable;
	}

	/**
	 * Tells if the field's nullable remains the same (not changed).
	 *
	 * @param array $oldField
	 * @param array $newField
	 * @return bool
	 */
	protected function isNullableEqual( array $oldField, array $newField )
	{
		$oldNullable = isset($oldField['nullable']) ? $oldField['nullable'] : null;
		$newNullable = isset($newField['nullable']) ? $newField['nullable'] : null;
		return $oldNullable === $newNullable;
	}

	/**
	 * Tells if the field's autoincrement remains the same (not changed).
	 *
	 * @param array $oldField
	 * @param array $newField
	 * @return bool
	 */
	protected function isAutoIncrementEqual( array $oldField, array $newField )
	{
		$oldAutoIncrement = isset($oldField['autoincrement']) ? $oldField['autoincrement'] : null;
		$newAutoIncrement = isset($newField['autoincrement']) ? $newField['autoincrement'] : null;
		return $oldAutoIncrement == $newAutoIncrement;
	}

	/**
	 * Provides the errors that occur while generating the DB.
	 *
	 * @return string[] List of error messages.
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Updates the smart_config table with the name of an installed patch.
	 *
	 * @param string $patchName The name of the patch
	 */
	public function insertPatchInfo( $patchName )
	{
		$this->txt .= 'INSERT INTO '.$this->quotefields('smart_config').' ('.$this->quotefields('name').', '.
			$this->quotefields('value') .') '.'VALUES ('."'$patchName'".', '."'yes'".')'.
			$this->closeline()."\r\n";
	}
}
