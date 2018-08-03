<?php

/**
 * SQL script generator for MySQL.
 *
 * Since 10.2 the class named 'MysqlGenerator' was renamed and moved from dbmodel.php into here.
 *
 * @since 10.2.0
 */

require_once BASEDIR.'/server/dbscripts/generators/Base.class.php';

class WW_DbScripts_Generators_Mysql extends WW_DbScripts_Generators_Base
{
	/**
	 * function to generate end of create table text plus additional stuff (sequences etc)
	 *
	 * @param $drop boolean	true means drop statements will be generated
	 */
	public function __construct($drop)
	{
		parent::__construct( $drop );
		$this->myname = "mysql";
	}

	/**
	 * {@inheritDoc}
	 */
	public function tablePost($table)
	{
		$primarykey = 'id';

		// unique keys (if any)
		if( isset( $table['indexes'] ) ) foreach( $table['indexes'] as $ix ) {
			if( isset( $ix["primary"] ) && $ix["primary"] == true ) {
				$primarykey = $ix['fields'];
			}
		}

		// primary key
		$keys = $this->quotefields( $primarykey );
		$this->txt .= "  PRIMARY KEY ($keys)\r\n) DEFAULT CHARSET=utf8".$this->closeline()."\r\n";
	}

	/**
	 * function to change an existing field definition
	 *
	 * @param array $table table info.
	 * @param array $oldfld field info as in the old/source database.
	 * @param array $newfld field info as in the new/target database.
	 */
	protected function doAlterField( $table, $oldfld, $newfld )
	{
		//$newfldname = $this->quotefields( $newfld['name'] );
		$oldfldname = $this->quotefields( empty($oldfld['name']) ? $newfld['name'] : $oldfld['name'] );
		$tablename = $this->quotefields($table['name']);

		$this->txt .= "ALTER TABLE $tablename CHANGE $oldfldname ";
		$this->field( $table, $newfld, false );
		$this->txt .= ";\r\n";
	}

	/**
	 * function to change an existing index definition. If primary key is changed
	 * it is expected that the new key will also be the new primary key.
	 * The change of the index is done in one step. The reason is that if it is
	 * done in two steps (first the drop of the index and then adding the new)
	 * this can result in errors if the fields of the index are used in foreign keys
	 * or the primary key is referred to.
	 *
	 * @param array $table DB table info.
	 * @param array $oldIndex DB index info as in the old/source database
	 * @param array $newIndex DB index info as in the new/target database
	 */
	public function alterIndex($table, $oldIndex, $newIndex)
	{
		$this->txt .= "ALTER TABLE " . $this->quotefields($table['name']) . ' ';
		if (array_key_exists('primary', $oldIndex) && $oldIndex['primary'] == true) {

			if( $table['fields'] ) foreach( $table['fields'] as $fields ) {
				if( $oldIndex['name'] == 'primary' ) { // When primary key index is not user-defined, it is by default set to 'id', the auto-increment field (which for index, by default becomes 'primary').
					if( $fields['name'] == 'id' ) { // Found the primary DB field definition.
						if( array_key_exists( 'drops', $fields )) { // Is the primary DB field going to be dropped?

							// When the primary DB field is going to be dropped, certain adjustment need to be done for
							// the primary index:
							// When a new field is added and set as primary key, Mysql will do the following:
							// 1) The new field will be added.
							// 2) An index (a primary key) is added for this field.
							// And so, when we drop the field that is set as primary key, the following two will happen:
							// 1) The field is dropped.
							// 2) The primary key is dropped from the index.
							// In our DB script, we have to make sure that the sql script can drop the field and also
							// the primary index, otherwise we will get into sql error.
							// In other words, we cannot first drop the field, then tries to drop the index or rename the
							// index in two separate sql, nor can we drop the index or rename the index first then only
							// drop the field (mysql will complain that the primary index is missing!)
							// In order to remove or rename the field smoothly, we first need to unset the to-be-dropped
							// field as a non-primary key field, change the old index primary to the new one, then only
							// drop the field (which is by then no longer a primary-key field).

							// Unset the primary field to-be-dropped as not primary field.
							$this->txt .= 'CHANGE '. $this->quotefields( 'id' ) . ' '.
								$this->quotefields( 'id' ) . ' '.$fields['drops']['type'].' NOT NULL, '; // Primary key is always 'NOT NULL'
						}
					}
				}


			}
			// Then only change the primary index.
			$this->txt .= 'DROP PRIMARY KEY, ADD PRIMARY KEY (' .
				$this->quotefields($newIndex['fields']) . ')';
		} else {
			$this->txt .= 'DROP INDEX '  . $this->quotefields($oldIndex['name']) . ', ADD ';
			if (array_key_exists('unique', $newIndex) && $newIndex['unique'] == true) {
				$this->txt .= 'UNIQUE ';
			}
			$this->txt .= 'INDEX ' . $this->quotefields($newIndex['name']) . ' (' .
				$this->quotefields($newIndex['fields']) . ') ';
		}

		$this->txt .= $this->closeline(). "\r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function dropIndex( $index, $table )
	{
		$this->txt .= 'DROP INDEX ' . $this->quotefields($index['name']) . ' ON ' . $this->quotefields($table['name']) . ' ';
		$this->txt .= $this->closeline(). "\r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function isReservedFieldName( $fieldName )
	{
		return false; // all names are allowed as long as they are quoted
	}
}
