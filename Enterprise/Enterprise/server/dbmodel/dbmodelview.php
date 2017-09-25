<?php
require_once dirname(__FILE__).'/../../config/config.php';

ini_set('display_errors', 1);

try {
	showDiff();
} catch( BizException $e ) {
	exit( $e->getMessage().' '.$e->getDetail() );
} catch( Throwable $e ) {
	exit( $e->getMessage() );
}

function showDiff()
{
	require_once BASEDIR.'/server/dbmodel/Reader.class.php';
	require_once BASEDIR.'/server/dbmodel/Factory.class.php';

	$dbVersion = null;
	$definitions = WW_DbModel_Factory::createModels();
	$dbTables = array();
	$docChapters = array();
	$readers = array();
	foreach( $definitions as $definition ) {
		$reader = new WW_DbModel_Reader( $definition );
		if( is_null( $dbVersion ) ) { // only take version for core server (which is assumed to be the first one)
			$dbVersion = $reader->getDbModelProvider()->getVersion();
		}
		$dbTables = array_merge( $dbTables, $reader->listTables() );
		$docChapters = array_merge( $docChapters, $definition->getCategorizedTableNames() );
		$readers[] = $reader;
	}

	$tables = 1;
	$title = 'Enterprise Database model v'.$dbVersion;
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

			// Lookup table definition
			$table = null;
			foreach( $readers as $reader ) {
				$table = $reader->getTable( $docTable );
				if( $table ) {
					break;
				}
			}

			// Skip dropped tables
			if( isset($table['drops']) ) {
				continue;
			}

			// Output able header (with anchor used for references)
			$html .= '<div class="section"><h3><a name="'.$table['name'].'"/>Table['.$tables.']: '.$table['name'].'</h3>'.PHP_EOL;
			if( !empty($table['comment']) ) {
				$html .= $table['comment'].'<br/><br/>';
			}
			if( isset($table['fields']) && count($table['fields']) ) {
				$html .= '<table class="section"><tr><td width="150"><b>Field</b></td><td width="100"><b>Type</b></td><td><b>Since</b></td>';
				$html .= '<td><b>Null</b></td><td><b>Default</b></td><td width="150"><b>Reference</b></td><td><b>Comment</b></td></tr>'.PHP_EOL;
				foreach( $table['fields'] as $field ) {

					// Skip dropped fields
					if( isset($field['drops']) ) {
						continue;
					}

					// Name, Type and Version columns
					$html .= '<tr><td>'.$field['name'].'</td><td>'.$field['type'].'</td><td>v'.$field['v'].'</td>';

					// Null column
					$nullable = @$field['nullable'] ? 'Yes' : 'No';
					$html .= '<td>'.$nullable.'</td>';
					
					// Default column
					$default = isset($field['default']) ? strval(trim($field['default'],"' ")) : '';
					$html .= '<td>'.$default.'</td>';
			
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
						$html .= '<td>'.$ref.'</td>';
					}
					
					// Comment column
					$comment = isset($field['comment']) ? trim($field['comment']) : '';
					if( empty( $comment ) ) {
						$html .= '<td/>';
					} else {
						$html .= '<td>'.$comment.'</td>';
					}
					$html .= '</tr>'.PHP_EOL;
				}
				$html .= '</table>'.PHP_EOL;
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

	echo $html;
}