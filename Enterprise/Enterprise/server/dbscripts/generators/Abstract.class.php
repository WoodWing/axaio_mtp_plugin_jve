<?php

/**
 * Abstract class for SQL script generators.
 *
 * Since 10.2 the class named 'GenericGenerator' was renamed and moved from dbmodel.php into here.
 *
 * Generators are called for each element of WW_DbModel_Definition and return code by their txt() method.
 *
 * @package Enterprise
 * @subpackage DbScripts/Generators
 * @since 10.2.0
 **/

abstract class WW_DbScripts_Generators_Abstract
{
	/** @var string */
	protected $txt;

	/**
	 * returns generated SQL
	 *
	 * @return string
	 */
	public function txt()
	{
		return $this->txt;
	}

	/**
	 * add txt
	 *
	 * @param string text
	 */
	public function addTxt($txt)
	{
		$this->txt .= $txt;
	}

	/**
	 * materialize in a file
	 *
	 * @param string $file filename
	 * @return bool TRUE when there was content to write. FALSE when empty.
	 */
	public function materialize($file)
	{
		$wroteContent = false;
		if ($this->txt) {
			$fp = @fopen($file, "w+");
			if (!$fp) exit ("Cannot open file for writing: $file");
			fputs($fp, $this->txt);
			fclose($fp);
			$wroteContent = true;
		}
		return $wroteContent;
	}

	/**
	 * empties generated txt
	 */
	public function clean()
	{
		$this->txt ='';
	}

	/**
	 * function to generate start of create table text
	 *
	 * @param array $table DB table info.
	 */
	abstract public function tablePre( $table );

	/**
	 * function to generate end of create table text plus additional stuff (sequences etc)
	 *
	 * @param array $table table info.
	 */
	abstract public function tablePost( $table );

	/**
	 * Function to generate seperate database fields in create statement
	 *
	 * @param array $table table info.
	 * @param array $field field info.
	 * @param bool $comma Add comma to the (add column) statement.
	 * @param bool $doDefault Add default value to the field definition.
	 */
	abstract public function field( $table, $field, $comma, $doDefault=true );

	/**
	 * Method to generate indexes. The index field can contain a length limitation
	 * (e.g. documentid(200). Length limitation on index fields is only applicable
	 * in case of Mysql. For Oracle and Mssql the (200) is removed before the
	 * index field is quoted.
	 *
	 * @param array $index DB index info.
	 * @param array $table DB table info.
	 */
	abstract public function index( $index, $table );

	/**
	 * Function to drop indexes
	 *
	 * @param array $index DB index info
	 * @param array $table DB table info.
	 */
	abstract public function dropIndex( $index, $table );

	/**
	 * function to generate insert statement
	 *
	 * @param string $line
	 * @param array $table DB table info.
	 * @param bool $auto true if auto-match on number of fields needs to be done (beware of , in values!!)
	 */
	abstract public function insert( $line, $table, $auto = true );

	/**
	 * function to generate statements after all insert statements
	 *
	 * @param array $table DB table info.
	 */
	public function insertPost( $table )
	{
	}

	/**
	 * function to stamp database of spec version
	 *
	 * @param string $version Version to stamp.
	 */
	abstract public function setDbModelVersionForCoreServer( $version );

	/**
	 * function to change an existing field definition
	 *
	 * @param array $table DB table info.
	 * @param array $oldfld DB field info as in the old/source database.
	 * @param array $newfld DB field info as in the new/target database.
	 */
	abstract public function alterField( $table, $oldfld, $newfld );

	/**
	 * function to drop an existing field definition
	 *
	 * @param array $table DB table info.
	 * @param array $fld DB field info as in the new/target database.
	 */
	abstract public function dropField( $table, $fld );

	/**
	 * function to change an existing index definition
	 *
	 * @param array $table DB table info.
	 * @param array $oldIndex DB index info as in the old/source database.
	 * @param array $newIndex DB index info as in the new/target database.
	 */
	abstract public function alterIndex( $table, $oldIndex, $newIndex );

	/**
	 * function called before running upgrade script
	 */
	public function upgradePre()
	{
	}

	/**
	 * function called after running upgrade script
	 */
	public function upgradePost()
	{
	}

	/**
	 * Tells whether or not the field name is allowed for the target DB engine.
	 *
	 * @param string $fieldName
	 * @return boolean TRUE when allowed, else FALSE.
	 */
	abstract public function isReservedFieldName( $fieldName );
}
