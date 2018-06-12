<?php
/**
 * @package 	SCEnterprise
 * @subpackage 	wwtest
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 * 
 * DB Sanity Check - wwtest tool
 * 
 * Checks all references between all database tables. 
 * When any reference is broken it reports pending records at screen.
 * It removes the reported 'orphan' records when "?del=orphans" param is given.
 */

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/dbmodel/Reader.class.php';
require_once BASEDIR.'/server/dbmodel/Factory.class.php';
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dbclasses/DBCascadePub.class.php';

// Only admin user are allowed to run this tool
checkSecure('admin');

// Running in clean-up mode to remove orphan records?
$removeOrphanRecords = isset($_REQUEST['del']) ? $_REQUEST['del'] == 'orphans' : false;

// Initiate database
try {
	$dbTables = array();
	$catTables = array();
	$definitions = WW_DbModel_Factory::createModels();
	foreach( $definitions as $definition ) {
		$reader = new WW_DbModel_Reader( $definition );
		$dbTables = array_merge( $dbTables, $reader->listTables() );
		$catTables = array_merge( $catTables, $definition->getCategorizedTableNames() );
		$readers[] = $reader;
	}
} catch( BizException $e ) {
	exit( $e->getMessage().' '.$e->getDetail() );
} catch( Throwable $e ) {
	exit( $e->getMessage() );
}

$dbDriver = DBDriverFactory::gen();

// Show header
$html = '<h1>DB Sanity Check</h1>';
$extraLogToScreen = false; // for heavy debug only

// Walk through categorized bundles of tables
foreach( $catTables as $catName => $tableNames ) {
	if( $extraLogToScreen ) {
		$html .= '<h2>'.$catName.'</h2>'."\r\n";
	}
	foreach( $tableNames as $tableName ) {
		// Get table definition from DB model
		$table = null;
		foreach( $readers as $reader ) {
			$table = $reader->getTable( $tableName );
			if( $table ) {
			    break;
            }
		}
		if( !$table ) {
			echo '<font color="red">ERROR: Requested table '.$tableName.' does not exist in model.</font><br/>';
			die();
		}
		if( isset($table['fields']) ) foreach( $table['fields'] as $field ) {

			$ref = '';
			foreach( $table['lookups'] as $lookup ) {
				if( $lookup['field'] == $field['name'] && 
						$field['name'] != 'majorversion' && $field['name'] != 'minorversion' ) { // ignore those since versions are scoped and breaking does no harm

					// Some heavy debug logging to show what reference (table+column) is checked
					$ref = $table['name'] . '[' . $field['name'] . '] -> ' . $lookup['reftable'] . '[' . $lookup['reffield'] . ']';
					if( $extraLogToScreen ) {
						$html .= $ref . '<br/>';
					}

					// JOIN referenced table with source / this table and take the unresolvable references
					$select = "SELECT src.*, ref.`".$lookup['reffield']."` as '".$lookup['reftable'].'->'.$lookup['reffield']."' ";
					$from   = "FROM `".$table['name']."` src ";
					$joins  = "LEFT JOIN `".$lookup['reftable']."` ref ON (src.`".$field['name']."` = ref.`".$lookup['reffield']."`) ";

					if( stripos( $field['type'], 'char' ) === 0 || stripos( $field['type'], 'varchar' ) === 0 ) { // string reference
						$where  = "WHERE (ref.`".$lookup['reffield']."` IS NULL) AND (src.`".$field['name']."` <> '')";
					} else { // numeric reference
						$where  = "WHERE (ref.`".$lookup['reffield']."` IS NULL) AND (src.`".$field['name']."` > 0)";
					}
					$sth = $dbDriver->query( $select.$from.$joins.$where );
					if( is_null($sth) ) {
						$html .= '<font color="red">'.$dbDriver->error().'</font><br/>';
					}

					// When we have records, we have problems, which we fetch and show here
					$problemRows = DBBase::fetchResults( $sth );
					if( count($problemRows) ) { // problems found?
						// display the problems
						$html .= '<hr/><font color="red">ERROR: Detected broken references at <b>'.$table['name'].'</b> table.<br/>Reference: '.$ref.'</font><table>';
						$html .= '<tr>';
						foreach( array_keys($problemRows[0]) as $fieldName ) {
							$html .= '<th>'.$fieldName.'<th/>';
						}
						$html .= '</tr>';
						foreach( $problemRows as $problemRow ) {
							$html .= '<tr>';
							foreach( $problemRow as $fieldValue ) {
								$html .= '<td>'.$fieldValue.'<td/>';
							}
							$html .= '</tr>';
						}
						$html .= '</table>';
						
						if( $removeOrphanRecords ) { // remove the problems?
							$delete = "DELETE src.* ";
							$sth = $dbDriver->query( $delete.$from.$joins.$where );
							if( is_null($sth) ) {
								$html .= '<font color="red">'.$dbDriver->error().'</font><br/>';
							}
							$html .= 'Removed these records.<br/>';
						}
					}
					break;
				}
			}
		}
	}
}
$html .= '<br/><hr/>Check complete<br/>';

// Report as HTML web page

require_once BASEDIR . '/server/utils/VersionUtils.class.php';
$serverVersion = VersionUtils::getVersionDigits( SERVERVERSION, 4 );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta http-equiv="PRAGMA" content="NO-CACHE" />
    <meta http-equiv="Expires" content="-1" />
	<title>WoodWing Enterprise Server</title>
	<link href="../../config/templates/woodwingmain.css?v=<?php echo $serverVersion; ?>" rel="stylesheet" type="text/css" media="all" />
	<link rel="icon" href="../../config/images/favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="../../config/images/favicon.ico" type="image/x-icon" />
</head>

<body>
<?php
echo $html;
?>
</body>
</html>
