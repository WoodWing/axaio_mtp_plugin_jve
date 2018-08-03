<?php
/**
 * Module that compares the installed DB schema with the defined DB schema.
 * It reports errors when there are differences.
 *
 * Status: Experimental. Only MySQL supported.
 *
 * @since 		v9.7
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/../../config/config.php';

ini_set('display_errors', 1);

// Move to WW_DbScripts_Factory:
switch ( DBTYPE ) {
	case 'mysql':
		require_once BASEDIR.'/server/dbscripts/generators/Mysql.class.php';
		class SelectedDbGenerator extends WW_DbScripts_Generators_Mysql {}
		break;
	case 'mssql':
		exit( 'MSSQL not supported.' ); // TODO
}
class AppliedDbGenerator extends SelectedDbGenerator
{
	public function nomalizeFieldType( $type )
	{
		return $this->normtype( $type );
	}
}

// TODO: Move these classes to dataclasses folder:
class WW_DbSchema_Definition
{
	public $Tables = array();
}

class WW_DbSchema_Table_Definition
{
	public $Name;
	public $Fields;
	public $Indexes;
}

class WW_DbSchema_Field_Definition
{
	public $Name;
	public $Type;
	public $isNullable;
	public $Default;
}

class WW_DbSchema_Index_Definition
{
	public $Name;
	public $Fields;
	public $isPrimary;
}

// TODO: Move this function to the WW_DbScripts_Factory class:
function composeDbSchemaFromDefinition()
{
	require_once BASEDIR.'/server/dbmodel/Reader.class.php';
	require_once BASEDIR.'/server/dbmodel/Factory.class.php';

	$model = new WW_DbSchema_Definition();
	$model->Tables = array();
	$definitions = WW_DbModel_Factory::createModels();
	foreach( $definitions as $definition ) {
		$reader = new WW_DbModel_Reader( $definition );
		$tableStructs = $reader->listTables();
		foreach( $tableStructs as $tableStruct ) {
			if( isset( $tableStruct['fields'] ) && count( $tableStruct['fields'] ) ) {
				$table = new WW_DbSchema_Table_Definition();
				$table->Name = $tableStruct['name'];
				$table->Fields = array();
				$table->Indexes = array();

				foreach( $tableStruct['fields'] as $fieldStruct ) {
					if( !isset( $fieldStruct['drops'] ) ) {
						$field = new WW_DbSchema_Field_Definition();
						$field->Name = $fieldStruct['name'];
						$field->Type = normalizeFieldType( $fieldStruct['type'] );
						$field->isAutoIncrement = isset( $fieldStruct['autoincrement'] ) && $fieldStruct['autoincrement'];
						$field->isNullable = isset( $fieldStruct['nullable'] ) && $fieldStruct['nullable'];
						$field->Default = isset( $fieldStruct['default'] ) ? normalizeDefaultValue( $field->Type, $fieldStruct['default'] ) : null;
						$table->Fields[ $field->Name ] = $field;
						if( $field->Name == 'id' && $field->isAutoIncrement ) {
							$index = new WW_DbSchema_Index_Definition();
							$index->Name = 'primary';
							$index->Fields = array( 'id' );
							$table->Indexes[ $index->Name ] = $index;
							$index->isPrimary = true;
						}
					}
				}

				foreach( $tableStruct['indexes'] as $indexStruct ) {
					if( isset( $indexStruct['name'] ) ) { // there is also 'oraname'
						$index = new WW_DbSchema_Index_Definition();
						$index->Name = $indexStruct['name'];
						$index->Fields = array_map( 'trim', explode( ',', $indexStruct['fields'] ) );
						$index->isPrimary = isset( $indexStruct['primary'] ) ? $indexStruct['primary'] == true : false;
						$table->Indexes[ $index->Name ] = $index;
					}
				}

				ksort( $table->Fields );
				ksort( $table->Indexes );
				$model->Tables[ $table->Name ] = $table;
			}
		}
	}

	ksort( $model->Tables );
	return $model;
}

function normalizeFieldType( $fieldType )
{
	static $generator = null;
	if( is_null($generator) ) {
		$generator = new AppliedDbGenerator( false );
	}
	return $generator->nomalizeFieldType( $fieldType );
}

function normalizeDefaultValue( $fieldType, $default ) 
{
	if( !is_null($default) ) {
		$default = trim( $default );
		if( strlen( $default ) == 0 ) {
			if( $fieldType == 'timestamp' ) {
				$default = 'CURRENT_TIMESTAMP'; // TODO: make DB indep
			} else {
				$default = null;
			}
		} else {
			$default = trim( $default, '\'' );

			if( stripos( $fieldType, 'blob' ) !== false ) {
				if( strlen( $default ) == 0 ) {
					$default = null;
				}
			}
		}
	}
	return $default;
}

function composeDbSchemaFromInstallation()
{
   	$dbDriver = DBDriverFactory::gen();
	$tableNames = array();
	
	// TODO: create function in all DB drivers:
	if( DBTYPE == 'mysql' ) {
		$sth = $dbDriver->query( "SHOW TABLES" );
		while( ($row = $dbDriver->fetch($sth)) ) {
			$tableName = reset($row);
			if( stripos( $tableName, DBPREFIX ) === 0 ) {
				$tableNames[] = $tableName;
			}
		}
	}
	
	$model = new WW_DbSchema_Definition();
	$model->Tables = array();
	foreach( $tableNames as $tableName ) {
		$table = new WW_DbSchema_Table_Definition();
		$table->Name = $tableName;

		$table->Fields = array();
		// >>> TODO: create function in all DB drivers:
		$sth = $dbDriver->query( "SHOW COLUMNS FROM `" . $tableName . "`" );
		while( ($row = $dbDriver->fetch($sth)) ) {
			if( stripos( $row['Field'], 'C_' ) !== 0 ) { // skip custom props
				$field = new WW_DbSchema_Field_Definition();
				$field->Name = $row['Field'];
				$field->Type = $row['Type'];
				$field->isAutoIncrement = $row['Extra'] == 'auto_increment';
				$field->isNullable = $row['Null'] == 'YES';
				$field->Default = $row['Default'];
				$table->Fields[$field->Name] = $field;
			}
		} // <<<
		ksort( $table->Fields );

		$table->Indexes = array();
		// >>> TODO: create function in all DB drivers:
		$sth = $dbDriver->query( "SHOW INDEX FROM `" . $tableName . "`" );
		while( ($row = $dbDriver->fetch($sth)) ) {
			$indexName = strtolower( $row['Key_name'] );
			if( isset($table->Indexes[$indexName]) ) {
				$index = $table->Indexes[$indexName];
				$index->Fields[$row['Seq_in_index']] = $row['Column_name'];
			} else {
				$index = new WW_DbSchema_Index_Definition();
				$index->Name = $indexName;
				$index->Fields = array( $row['Seq_in_index'] => $row['Column_name'] );
				$index->isPrimary = $indexName == 'primary';
			}
			ksort( $index->Fields );
			$index->Fields = array_values( $index->Fields );
			$table->Indexes[$indexName] = $index;
		} // <<<
		ksort( $table->Indexes );
		
		$model->Tables[$table->Name] = $table;
	}
	ksort( $model->Tables );
	return $model;
}

function compareDbSchemas( WW_DbSchema_Definition $definedSchema, WW_DbSchema_Definition $installedSchema )
{
	$results = array();
	$definedTableNames = array_keys( $definedSchema->Tables );
	$installedTableNames = array_keys( $installedSchema->Tables );
	$combinedTableNames = array_unique( array_merge( $definedTableNames, $installedTableNames ) );
	foreach( $combinedTableNames as $tableName ) {
		if( !isset( $definedSchema->Tables[$tableName] ) ) {
			$results[$tableName][] = "Table is installed but not defined. Please drop table. ";
		} elseif( !isset( $installedSchema->Tables[$tableName] ) ) {
			$results[$tableName][] = "Table is defined but not installed. Please create table. ";
		} else { // both present
			$definedFieldNames = array_keys( $definedSchema->Tables[$tableName]->Fields );
			$installedFieldNames = array_keys( $installedSchema->Tables[$tableName]->Fields );
			$combinedFieldNames = array_unique( array_merge( $definedFieldNames, $installedFieldNames ) );
			foreach( $combinedFieldNames as $fieldName ) {
				if( !isset( $definedSchema->Tables[$tableName]->Fields[$fieldName] ) ) {
					$results[$tableName][] = "Field '$fieldName' is installed but not defined. Please drop field. ";
				} elseif( !isset( $installedSchema->Tables[$tableName]->Fields[$fieldName] ) ) {
					$results[$tableName][] = "Field '$fieldName' is defined but not installed. Please add field. ";
				} else {
					$definedFields = $definedSchema->Tables[$tableName]->Fields[$fieldName];
					$installedFields = $installedSchema->Tables[$tableName]->Fields[$fieldName];
					foreach( array_keys( get_class_vars( 'WW_DbSchema_Field_Definition' ) ) as $fieldProp ) {
						if( $definedFields->$fieldProp !== $installedFields->$fieldProp ) {
							$results[$tableName][] = "The '$fieldProp' property of field '$fieldName' definition differs from installation. Please update field. ".
								"Defined value is '{$definedFields->$fieldProp}' (".gettype($definedFields->$fieldProp).") ".
								"and installed value is '{$installedFields->$fieldProp}' (".gettype($installedFields->$fieldProp)."). ";
						}
					}
				}
			}
			$definedIndexNames = array_keys( $definedSchema->Tables[$tableName]->Indexes );
			$installedIndexNames = array_keys( $installedSchema->Tables[$tableName]->Indexes );
			$combinedIndexNames = array_unique( array_merge( $definedIndexNames, $installedIndexNames ) );
			foreach( $combinedIndexNames as $indexName ) {
				if( !isset( $definedSchema->Tables[$tableName]->Indexes[$indexName] ) ) {
					$results[$tableName][] = "Index '$indexName' is installed but not defined. Please drop index. ";
				} elseif( !isset( $installedSchema->Tables[$tableName]->Indexes[$indexName] ) ) {
					$results[$tableName][] = "Index '$indexName' is defined but not installed. Please add index. ";
				} else {
					$definedIndexes = $definedSchema->Tables[$tableName]->Indexes[$indexName];
					$installedIndexes = $installedSchema->Tables[$tableName]->Indexes[$indexName];
					foreach( array_keys( get_class_vars( 'WW_DbSchema_Index_Definition' ) ) as $indexProp ) {
						$definedIndexProp = $definedIndexes->$indexProp;
						if( gettype( $definedIndexProp ) == 'array' ) {
							$definedIndexProp = implode( ',', $definedIndexProp );
						}
						$installedIndexProp = $installedIndexes->$indexProp;
						if( gettype( $installedIndexProp ) == 'array' ) {
							$installedIndexProp = implode( ',', $installedIndexProp );
						}
						if( $definedIndexProp !== $installedIndexProp ) {
							$results[$tableName][] = "The '$indexProp' property of index '$indexName' definition differs from installation. Please update index. ".
								"Defined value is '$definedIndexProp' (".gettype($definedIndexProp).") ".
								"and installed value is '$installedIndexProp' (".gettype($installedIndexProp)."). ";
						}
					}
				}
			}
		}
	}
	return $results;
}

$definedSchema = composeDbSchemaFromDefinition();
$installedSchema = composeDbSchemaFromInstallation();

// FOR DEBUG ONLY:
/*
header('Content-Type: text/plain');
header('Content-Disposition: inline');
var_dump( $definedSchema );
var_dump( $installedSchema );
exit();
*/

$differences = compareDbSchemas( $definedSchema, $installedSchema );
if( $differences ) {
	foreach( $differences as $tableName => $tableDifferences ) {
		print "<b>$tableName:</b><br/>";
		print '<ul><li>'.implode( '</li><li>', $tableDifferences ).'</li></ul>';
	}
} else {
	print 'DB installation OK';
}
