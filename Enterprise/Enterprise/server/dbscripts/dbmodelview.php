<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/dbscripts/dbmodel.php';

ini_set('display_errors', 1);

// where to generate files
$scriptsDir = BASEDIR."/server/dbscripts";
$dbStruct = new DBStruct();
$allVersions = $dbStruct->getVersions();

// use one version when explicitly requested
if( isset($_REQUEST['ver']) ) {
	if( in_array( $_REQUEST['ver'], $allVersions ) ) {	
		$dbVersions = array( $_REQUEST['ver'] ); 
	} else {
		die( 'Version '.displayVer($_REQUEST['ver']).' does not exist.' );
	}
} else {
	// take last version if not requested for all versions
	if( !isset($_REQUEST['all']) ) {
		$dbVersions = array( end($allVersions) ); 
	}
	// else use all versions
}

// initiate databasestruct
$dbTables = $dbStruct->listTables();
$docChapters = $dbStruct->getCategorizedTableNames();

foreach( $dbVersions as $dbVersion ) {

	$tables = 1;
	$title = 'Enterprise Database model '.displayVer($dbVersion);
	$html = '
<html>
<head>
	<title>'.$title.'</title>
	<style>
		body { font-family: helvetica, arial, sans-serif; font-size: 11pt; }
		td { font-family: helvetica, arial, sans-serif; font-size: 11pt; vertical-align:top; }
		div.section { margin: 1em; padding: 1em; border:2px solid #CCCCCC; background-color:#FFEECC; width: 800px; }
		td.caption { vertical-align:middle; padding-left: 1em; padding-top: 1em; }
		table.section { padding: 1em; background-color:#FFDD88; width:100%; }
		td.hpc { width:180px; font-style: italic; font-weight: bold; } /* head-property-column */ /* WW orange: #FFA500 */
		td.htc { width:220px; font-style: italic; font-weight: bold; } /* head-type-column */
		td.hcc { width:350px; font-style: italic; font-weight: bold; } /* head-comment-column */
		td.bpc {} /* body-property-column */
		td.btc {} /* body-type-column */
		td.bcc {} /* body-comment-column */
		td.fpc {} /* foot-property-column */
		td.ftc { font-style: italic; font-size: 9pt; text-align: right; } /* foot-type-column */
		td.fcc {} /* foot-comment-column */
	</style>
</head>
<body>
	<table>
		<tr><td><img title="" src="../../config/images/woodwing95.gif"/></td><td class="caption"><h1>'.$title.'</h1></td></tr>
	</table>';

	foreach( $dbTables as $dbTable ) {
		$found = false;
		foreach( $docChapters as $docChapter => $docTables ) {
			foreach( $docTables as $docTable ) {
				if( $docTable == $dbTable['name'] ) {
					$found = true;
					break 2;
				}
			}
		}
		if( $found === false ) {
			$docChapters['Miscelaneous'][] = $dbTable['name']; // fallback: catch unstructured tables
		}
	}
	
	foreach( $docChapters as $docChapter => $docTables ) {
		$html .= '<br/><h2>'.$docChapter.'</h2>'.PHP_EOL;
		foreach( $docTables as $docTable ) {
			$table = $dbStruct->getTable( $docTable );
			
			// Table header (with anchor used for references)
			$html .= '<div class="section"><h3><a name="'.$table['name'].'"/>Table['.$tables.']: '.$table['name'].' ('.displayVer($dbVersion).')</h3>'.PHP_EOL;
			if( !empty($table['comment']) ) {
				$html .= $table['comment'].'<br/><br/>';
			}
			if( isset($table['fields']) && count($table['fields']) ) {
				$html .= '<table class="section"><tr><td width="150"><b>Field</b></td><td width="100"><b>Type</b></td><td><b>Since</b></td>';
				$html .= '<td><b>Null</b></td><td><b>Default</b></td><td width="150"><b>Reference</b></td><td><b>Comment</b></td></tr>'.PHP_EOL;
				$markedRed = false;
				$markedGrey = false;
				foreach( $table['fields'] as $field ) {
		
					$field = skipTooNew( $field, $dbVersion );
					$newIntroduced = !isset($field['alters']) && $field['v'] == $dbVersion;
					$rowColor = $newIntroduced ? '#FF0000' : ($field['v'] > $dbVersion ? '#888888' : '#000000');
					$lastFieldDef = isset($field['alters']) ? $field['alters'] : $field;
					$markedGrey = ($rowColor == '#888888' || $markedGrey);

					// Name column					
					$cellColor = ($rowColor == '#000000') ? ($field['name'] != $lastFieldDef['name'] && $field['v'] == $dbVersion ? '#FF0000' : '#000000') : $rowColor;
					$font = '<font color="'.$cellColor.'">';
					$html .= '<tr><td>'.$font.$field['name'].'</font></td>';
					$markedRed = ($cellColor == '#FF0000') || $markedRed;

					// Type column					
					$cellColor = ($rowColor == '#000000') ? ($field['type'] != $lastFieldDef['type'] && $field['v'] == $dbVersion ? '#FF0000' : '#000000') : $rowColor;
					$font = '<font color="'.$cellColor.'">';
					$html .= '<td>'.$font.$field['type'].'</font></td>';
					$markedRed = ($cellColor == '#FF0000') || $markedRed;

					// Since column					
					$oldestVer = getOldestVer( $field );
					$font = '<font color="'.$rowColor.'">';
					$html .= '<td>'.$font.displayVer($oldestVer).'</font></td>';
					$markedRed = ($rowColor == '#FF0000') || $markedRed;
			
					// Null column
					$nullable = @$field['nullable'] ? 'Yes' : 'No';
					$html .= '<td>'.$font.$nullable.'</font></td>';
					
					// Default column
					$default = isset($field['default']) ? strval(trim($field['default'],"' ")) : '';
					$html .= '<td>'.$font.$default.'</font></td>';
			
					// Reference column
					$ref = '';
					foreach( $table['lookups'] as $lookup ) {
						if( $lookup['field'] == $field['name'] ) {
							$ref = '<a href="#'.$lookup['reftable'].'">'.$lookup['reftable'].' ('.$lookup['reffield'].')</a>';
							break;
						}
					}
					if( empty( $ref ) ) {
						$html .= '<td/>';
					} else {
						$html .= '<td>'.$font.$ref.'</font></td>';
					}
					
					// Comment column
					$comment = isset($field['comment']) ? trim($field['comment']) : '';
					if( empty( $comment ) ) {
						$html .= '<td/>';
					} else {
						$html .= '<td>'.$font.$comment.'</font></td>';
					}
					$html .= '</tr>'.PHP_EOL;
				}
				$html .= '</table>'.PHP_EOL;
				
				// Show legenda
				if( $markedRed ) {
					$html .= '<table width="100%"><tr><td class="ftc"><font color="#F0000">'.
									'red = Introduced or changed since '.displayVer($dbVersion).
									'</font></td></tr></table>'.PHP_EOL;
				}
				if( $markedGrey ) {
					$html .= '<table width="100%"><tr><td class="ftc"><font color="#888888">'.
									'grey = Not available '.displayVer($dbVersion).'(introduced later).'.
									'</font></td></tr></table>'.PHP_EOL;
				}
			}
			$html .= '</div>'.PHP_EOL;
			$tables++;
		}
	}
	$html .= '<div class="section"><p style="font-size: 9pt;" align="right">
Confidential, (c) 1998-'.date('Y').' WoodWing Software bv. All rights reserved.</p></div>
<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
</body></html>';

	$fp = fopen( $scriptsDir.'/dbmodel_'.$dbVersion.'.html', 'w' );
	fwrite( $fp, $html );
	fclose( $fp ); 
	echo $html;
}

/**
 * Converts an internal version format (e.g. 610) to human readable format (e.g. v6.1).
 *
 * @param string $ver Internal version
 * @return string Human readable version
 */
function displayVer( $ver )
{
	if( substr($ver,-2,1) == 9 && substr($ver,-1,1) == 9 ) { // 5.9.9 should become 6.0.0
		$ver = (intval(substr($ver,0,1))+1).'.0';
	} else {
		$ver = substr($ver,0,1).'.'.substr($ver,-2,1); //.'.'.substr($ver,-1,1);
	}
	return 'v'.$ver;
}

/**
 * Finds out the oldest field definition and returns its version.
 * It searches through the 'alters' field (using recursion).
 * When the field definition is never changed, it returns the current version.
 * In other terms, it returns the version when the field was introduced.
 *
 * @param array $field Field definition from dbmodel.php
 * @return string 3-digit version string, such as 610
 */
function getOldestVer( $field )
{
	if( isset($field['alters']) ) {
		return getOldestVer( $field['alters'] );
	} else {
		return $field['v'];
	}
}

/**
 * Returns the field defintion that is older than requested, if there are any alters defined.
 * When none found, the closest one is returned.
 *
 * @param array $field Field definition from dbmodel.php
 * @param string $dbVersion 3-digit version string, such as 610
 * @return array
 */
function skipTooNew( $field, $dbVersion )
{
	if( $dbVersion < $field['v'] ) {
		if( isset($field['alters']) ) {
			return skipTooNew( $field['alters'], $dbVersion );
		}
	}
	return $field;
}
