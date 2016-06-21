<script language="JavaScript">
	function submitForm( action )
	{
		var f = document.forms.brandfilestorage;	
		if( action == 'clearIssId' )
		{
			f.issid.value = ''; // clear the issue id when Brand is re-selected
		}
		f.submit();
	}
</script>
<?php

require_once '../../config/config.php';
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/bizclasses/BizStorage.php'; // FileStorage
require_once BASEDIR.'/server/secure.php';
	
// Check if acting user has system admin rights.
$ticket = checkSecure();
checkSecure('admin');
/*$user =*/ DBTicket::checkTicket( $ticket );

// check publication rights
$pubId = array_key_exists( 'pubid', $_REQUEST ) ? intval( $_REQUEST['pubid'] ) : 0;
$issId = array_key_exists( 'issid', $_REQUEST ) ? intval( $_REQUEST['issid'] ) : 0;
$calculateDiskspace = array_key_exists( 'calDiskSpace', $_REQUEST ) ? $_REQUEST['calDiskSpace'] : null;
if( $calculateDiskspace ) { // Either calculateDiskspace or copyFiles
	$copyFiles = null;
	$fileDestination = null;
} else {
	$copyFiles = array_key_exists( 'copyFiles', $_REQUEST ) ? $_REQUEST['copyFiles'] : null;
	$fileDestination = array_key_exists( 'fileDest', $_REQUEST ) ? $_REQUEST['fileDest'] : null;
	if( $copyFiles && !$fileDestination ) {
		echo '<br/><font color="red">ERROR:</font> Please fill in the file directory where the files should be copied to: ';
	}
}

//if( $pubId ) {
//	checkPublAdmin( $pubId );
//}

// Validate brand id, which is mandatory at URL param.
echo '<html><head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>Calculate diskspace for selected publication</title>
	</head><body>';
if( !$pubId ) {
	echo 'Please specify the brand/publication you want to calculate the diskspace for. <br /><br /><br />';
	$pubId=0;
}
$where = "";
$params = array();
$fields = array( 'id', 'publication' );
$rows = DBBase::listRows( 'publications', 'id', null, $where, $fields, $params );
echo '<form id=brandfilestorage action="copyFileStore.php" method="get">';
if( $rows ) {
	if( $issId ) {
		echo '<select name="pubid" OnChange = "submitForm( \'clearIssId\')">';
	} else {
		echo '<select name="pubid" OnChange = "submitForm( \'\')">';
	}
	echo '<option value="">Brands</option>';
	foreach( $rows as $row ) {
		//if( !checkPublAdmin( $row['id'], false) ) {
		//	continue; // hide brands that are not owned by the acting user
		//}
		echo '<option value="'.$row['id'].'"';
		if( $pubId == $row['id'] ) {
			echo ' selected="selected"';
		}
		echo '>'.$row['publication'].'</option>';
	}
	echo '</select>';
}

if( $pubId ) {
	require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
	require_once BASEDIR.'/server/admin/global_inc.php';
	$rows = DBIssue::listPublicationIssues( $pubId );
	$selected = ( $issId == -1 ) ? 'selected' : '';
	echo '<select name="issid">'.
	     '<option value="">Issues</option>'.
		 '<option value="-1" '.$selected.'>All</option>';
	foreach( $rows as $issueId => $issInfo ) {
		echo '<option value="'.$issueId.'"';
		if( $issId == $issueId ) {
			echo ' selected="selected"';		
		}
		echo '>'.$issInfo['name'].'</option>';
	}
	echo '<input type="submit" name="calDiskSpace" value="Calculate Diskspace"></br>';
	echo '<input type="submit" name="copyFiles" value="Copy Files into">';
	echo '<input type="text" name="fileDest" value="'.formvar($fileDestination).'">';
}
echo '</form>';	

// Helper class that does the actual calculations.
class DBBrandFileStorageSizeCalculator extends DBBase
{
	private $stats = array();
	
	public function __construct()
	{
		// Initialize statistics to be calculated.
		$this->stats['TotalFileCount'] = 0;
		$this->stats['TotalStorageSize'] = 0;
		$this->stats['ObjectsStorageSize'] = 0;
		$this->stats['PagesStorageSize'] = 0;
		$this->stats['VersionsStorageSize'] = 0;
		$this->stats['OutputStorageSize'] = 0;		
		$this->stats['DeletedTotalFileCount'] = 0;
		$this->stats['DeletedTotalStorageSize'] = 0;
		$this->stats['DeletedObjectsStorageSize'] = 0;
		$this->stats['DeletedPagesStorageSize'] = 0;
		$this->stats['DeletedVersionsStorageSize'] = 0;
		$this->stats['DeletedOutputStorageSize'] = 0;
		$this->stats['FilesNotFound'] = array();
		
		$this->filesToBeCopied = array();
	}
	
	public function calculate( $pubId, $issId, $ticket )
	{
		$objRows = $this->calculateObjects( $pubId, $issId, $ticket );
		$this->calculatePages( $objRows );
		$this->calculateVersions( $objRows );
		
		require_once BASEDIR.'/server/serverinfo.php'; // SERVERVERSION
		$serverVer = explode( '.', SERVERVERSION );
  		$serverVersion = $serverVer[0] .'.'. $serverVer[1];
		if( version_compare( $serverVersion, '7.5', '>=' ) ) {
			$this->calculateEditionRenditions( $objRows );
		}
		
	}

	private function queryObjects( $pubId, $issueId, $workflow, $ticket )
	{
		// Query DB for all dossiers that are assigned to the given issue.
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$minProps = array( 'ID', 'Type', 'Name' );
		$params = array(
			new QueryParam( 'PublicationId', '=', $pubId ),
			new QueryParam( 'IssueId', '=', $issueId )
		);

		$request = new WflQueryObjectsRequest();
		$request->Ticket = $ticket;
		$request->Params = $params;
		$request->FirstEntry = 1;
		$request->MaxEntries = 0;		
		$request->Hierarchical = false;
		$request->Order = null;
		$request->MinimalProps = $minProps;
		$request->RequestProps = null;
		$request->Areas = $workflow ? array( 'Workflow' ) : array( 'Trash' );
	
		$service = new WflQueryObjectsService();
		$response = $service->execute( $request );

		// Determine column indexes to work with.
		$indexes = array_combine( array_values($minProps), array_fill(1,count($minProps), -1) );
		foreach( array_keys($indexes) as $colName ) {
			foreach( $response->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found
				}
			}
		}
		
		// Collect the Object ids from search results.
		$objectIds = array();
		foreach( $response->Rows as $row ) {
			$objectIds[] = $row[$indexes['ID']];
		}
		
		return $objectIds;
	}
	
	/**
	 * Calculate the total file size at FileStore referenced from smart_objects and smart_deletedobjects tables.
	 */
	private function calculateObjects( $pubId, $issId, $ticket )
	{
		$objRows = array();
		$tables = array( 'objects' => true, 
						 'deletedobjects' => false );

		foreach( $tables as $table => $workflow ) {
			if( $issId == -1 ) {
				$where = '`publication` = ? ';
				$params = array( $pubId );
				$fields = array( 'id', 'type', 'name', 'types', 'publication', 'storename', 'majorversion', 'minorversion' );
				$rows = DBBase::listRows( $table, 'id', null, $where, $fields, $params );
				$objRows = $this->iterateObjRows( $rows, $table, $objRows );
			} else {
				$objIds = $this->queryObjects( $pubId, $issId, $workflow, $ticket );
				$index = 0;
				$numObjToRetrieve = 10;
				for( $ctr=1; $ctr<=count( $objIds ); $ctr+=$numObjToRetrieve ) {
					$partialObjIds = array_slice( $objIds, $index, $numObjToRetrieve );
					$index += $numObjToRetrieve;
					$where = '`publication` = ? ' .
							 ' AND `id` in ('.implode(',',$partialObjIds).')';
					
					$params = array( $pubId );
					$fields = array( 'id', 'type', 'name', 'types', 'publication', 'storename', 'majorversion', 'minorversion' );
					$rows = DBBase::listRows( $table, 'id', null, $where, $fields, $params );
					$objRows = $this->iterateObjRows( $rows, $table, $objRows );
				}
			}
		}
		return $objRows;
	}
	
	/**
	 * Iterate throus the rows of object to gather the file info at FileStore
	 * and count the total number of objects( $rows ).
	 */
	private function iterateObjRows( $rows, $table, $objRows )
	{	
		if( $rows ) foreach( $rows as $row ) {
			$version = '';
			$this->_joinMajorMinorVer( $version, $row, '' ); // do not use DBBase to be 7.0.x compatible
			$row['Version'] = $version;
			$row['Deleted'] = ($table == 'deletedobjects');
	
			$objRows[ $row['id'] ] = $row;
			$types = strlen( $row['types'] ) > 1 ? unserialize( $row['types'] ) : array();
			foreach( $types as $rendition => $format ) {
				$fs = new FileStorage( 
					$row['storename'], 
					$row['id'], 
					$rendition, 
					$format, 
					$row['Version'] );
				if( $fs->doesFileExist() ) {
					$fileName = $fs->getFilename();
					$parentDir = substr( $row['storename'], 0, strrpos( $row['storename'], '/' ) );
					$this->filesToBeCopied[] = array( $parentDir => $fileName );
					$fileSize = filesize( $fileName );
					if( $row['Deleted'] ) {
						$this->stats['DeletedTotalFileCount'] += 1;
						$this->stats['DeletedObjectsStorageSize'] += $fileSize;
						$this->stats['DeletedTotalStorageSize'] += $fileSize;
					} else {
						$this->stats['TotalFileCount'] += 1;
						$this->stats['ObjectsStorageSize'] += $fileSize;
						$this->stats['TotalStorageSize'] += $fileSize;
					}
				} else {
					$this->stats['FilesNotFound'][] = $fs->getFilename();
				}
			}
		}
		return $objRows;
	}

	/**
	 * Calculate the total file size at FileStore referenced from smart_pages table.
	 */
	private function calculatePages( $objRows )
	{
		$objIds = "'".implode( "', '", array_keys( $objRows ) )."'";
		$where = '`objid` IN ( '.$objIds.' )';
		$params = array();
		$fields = array( 'id', 'objid', 'pagenumber', 'types', 'edition' );
		$rows = DBBase::listRows( 'pages', 'id', null, $where, $fields, $params );
		if( $rows ) foreach( $rows as $row ) {
			$types = strlen( $row['types'] ) > 1 ? unserialize( $row['types'] ) : array();
			$objRow = $objRows[ $row['objid'] ];
			foreach( $types as $pageTypeItem ) {
				$pageNr = $pageTypeItem[0];
				//$pageRendition = $pageTypeItem[1];
				$format = $pageTypeItem[2];
				$pageNrVal = preg_replace('/[*"<>?\\\\|:]/i', '', $row["pagenumber"]);
				$fs = new FileStorage( 
					$objRow['storename'], 
					$objRow['id'], 
					'page', // rendition 
					$format, 
					$objRow['Version'],
					$pageNrVal.'-'.$pageNr,
					$row['edition'] );
				if( $fs->doesFileExist() ) {
					$fileName = $fs->getFilename();
					$parentDir = substr( $objRow['storename'], 0, strrpos( $objRow['storename'], '/' ) );
					$this->filesToBeCopied[] = array( $parentDir => $fileName );
					$fileSize = filesize( $fileName );
					if( $objRow['Deleted'] ) {
						$this->stats['DeletedTotalFileCount'] += 1;
						$this->stats['DeletedPagesStorageSize'] += $fileSize;
						$this->stats['DeletedTotalStorageSize'] += $fileSize;
					} else {
						$this->stats['TotalFileCount'] += 1;
						$this->stats['TotalStorageSize'] += $fileSize;
						$this->stats['PagesStorageSize'] += $fileSize;
					}
				} else {
					$this->stats['FilesNotFound'][] = $fs->getFilename();
				}
			}
		}
	}
	
	/**
	 * Calculate the total file size at FileStore referenced from smart_objectrenditions table.
	 */	
	private function calculateEditionRenditions( $objRows )
	{
		$objIds = "'".implode( "', '", array_keys( $objRows ) )."'";
		$where = '`objid` IN ( '.$objIds.' )';
		$params = array();		
		$fields = '*';
		$rows = DBBase::listRows( 'objectrenditions', 'id', null, $where, $fields, $params );
		if( $rows ) foreach( $rows as $row ) {
			$objRow = $objRows[ $row['objid'] ];
			$version = '';
			$this->_joinMajorMinorVer( $version, $row, '' ); // do not use DBBase to be 7.0.x compatible			
			$fs = new FileStorage( 
					$objRow['storename'], 
					$objRow['id'], 
					$row['rendition'],
					$row['format'],
					$version,
					null,// page
					$row['editionid'] );
			if( $fs->doesFileExist() ) {
				$fileName = $fs->getFilename();
				$parentDir = substr( $objRow['storename'], 0, strrpos( $objRow['storename'], '/' ) );
				$this->filesToBeCopied[] = array( $parentDir => $fileName );
				$fileSize = filesize( $fileName );
				if( $objRow['Deleted'] ) {
					$this->stats['DeletedTotalFileCount'] += 1;
					$this->stats['DeletedOutputStorageSize'] += $fileSize;
					$this->stats['DeletedTotalStorageSize'] += $fileSize;
				} else {
					$this->stats['TotalFileCount'] += 1;
					$this->stats['TotalStorageSize'] += $fileSize;
					$this->stats['OutputStorageSize'] += $fileSize;
				}
			} else {
				$this->stats['FilesNotFound'][] = $fs->getFilename();
			}	
		}
		
	}

	/**
	 * Calculate the total file size at FileStore referenced from smart_objectversions table.
	 */
	private function calculateVersions( $objRows )
	{
		$objIds = "'".implode( "', '", array_keys( $objRows ) )."'";
		$where = '`objid` IN ( '.$objIds.' )';
		$params = array();
		$fields = array( 'id', 'objid', 'types', 'majorversion', 'minorversion' );
		$rows = DBBase::listRows( 'objectversions', 'id', null, $where, $fields, $params );
		if( $rows ) foreach( $rows as $row ) {
			$types = strlen( $row['types'] ) > 1 ? unserialize( $row['types'] ) : array();
			$objRow = $objRows[ $row['objid'] ];
			if( !isset($objRow['PagesStorageSize']) ) {
				$objRow['PagesStorageSize'] = 0;
			}
			$version = '';
			$this->_joinMajorMinorVer( $version, $row, '' ); // do not use DBBase to be 7.0.x compatible
			foreach( $types as $rendition => $format ) {
				$fs = new FileStorage( 
					$objRow['storename'], 
					$objRow['id'], 
					$rendition, 
					$format, 
					$version );
				if( $fs->doesFileExist() ) {
					$fileName = $fs->getFilename();
					$parentDir = substr( $objRow['storename'], 0, strrpos( $objRow['storename'], '/' ) );
					$this->filesToBeCopied[] = array( $parentDir => $fileName );
					$fileSize = filesize( $fileName );
					if( $objRow['Deleted'] ) {
						$this->stats['DeletedTotalFileCount'] += 1;
						$this->stats['DeletedVersionsStorageSize'] += $fileSize;
						$this->stats['DeletedTotalStorageSize'] += $fileSize;
					} else {
						$this->stats['TotalFileCount'] += 1;
						$this->stats['TotalStorageSize'] += $fileSize;
						$this->stats['VersionsStorageSize'] += $fileSize;
					}
				} else {
					$this->stats['FilesNotFound'][] = $fs->getFilename();
				}
			}
		}
	}

	/**
	 * Write calculated statistics to HTML table.
	 *
	 * @return string HTML table.
	 */
	public function reportStatisticsAsHtmlTable()
	{
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$report = '<table><thead style="font-weight:bold">';
		$report .= '<tr><td>Storage area</td><td>Storage size</td></tr>';
		$report .= '</thead><tbody>';
		$report .= '<tr><td>Production has '.$this->stats['TotalFileCount'].' files:</td><td align="right">'.NumberUtils::getByteString( $this->stats['TotalStorageSize'] ).'</td></tr>';
		$report .= '<tr><td>- Objects:</td><td align="right">'.NumberUtils::getByteString( $this->stats['ObjectsStorageSize'] ).'</td></tr>';
		$report .= '<tr><td>- Pages:</td><td align="right">'.NumberUtils::getByteString( $this->stats['PagesStorageSize'] ).'</td></tr>';
		$report .= '<tr><td>- Versions:</td><td align="right">'.NumberUtils::getByteString( $this->stats['VersionsStorageSize'] ).'</td></tr>';
		$report .= '<tr><td>TrashCan has '.$this->stats['DeletedTotalFileCount'].' files:</td><td align="right">'.NumberUtils::getByteString( $this->stats['DeletedTotalStorageSize'] ).'</td></tr>';
		$report .= '<tr><td>- Objects:</td><td align="right">'.NumberUtils::getByteString( $this->stats['DeletedObjectsStorageSize'] ).'</td></tr>';
		$report .= '<tr><td>- Pages:</td><td align="right">'.NumberUtils::getByteString( $this->stats['DeletedPagesStorageSize'] ).'</td></tr>';
		$report .= '<tr><td>- Versions:</td><td align="right">'.NumberUtils::getByteString( $this->stats['DeletedVersionsStorageSize'] ).'</td></tr>';
		$totalCount = $this->stats['TotalFileCount'] + $this->stats['DeletedTotalFileCount'];
		$totalStore = $this->stats['TotalStorageSize'] + $this->stats['DeletedTotalStorageSize'];
		$report .= '<tr><td>FileStore has '.$totalCount.' files:</td><td align="right">'.NumberUtils::getByteString( $totalStore ).'</td></tr>';
		$report .= '</tbody></table>';
		
		if( count($this->stats['FilesNotFound']) > 0 ) {
			$report .= '<br/><font color="red">ERROR:</font> Could not find the following files in the FileStore: ';
			$report .= '<ul><li>'.implode( '</li><li>', $this->stats['FilesNotFound'] ).'</li></ul>';
		}
		return $report;
	}

	/**
	 * Formats major.minor version
	 * When NULL, no action is taken.
	 *
	 * @param string $versionProp Returns joined version property in major.minor notation
	 * @param array $row DB row containing "majorversion" and "minorversion" key-values
	 * @param string $fieldPrefix Prefix for "majorversion" and "minorversion" field names.
	 */
    private function _joinMajorMinorVer( &$versionProp, $row, $fieldPrefix )
    {
    	if( isset($row[$fieldPrefix.'majorversion']) && isset($row[$fieldPrefix.'minorversion']) ) {
    		$major = intval($row[$fieldPrefix.'majorversion']);
    		$minor = intval($row[$fieldPrefix.'minorversion']);
    		if( $major < 0 || $minor < 0 ) {
    			$major = 0;
    			$minor = 0;
    		}
    		$versionProp = $major.'.'.$minor;
    	}
    }
    
	public function writeCopyFileScript( $fileDestination )
    {
   		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
   		$copied = 0;
    	foreach( $this->filesToBeCopied as /*$objId =>*/ $objFileStoreInfo ) {
			foreach( $objFileStoreInfo as $parentDir => $fullPathFileName ) {
				$fileName = substr( $fullPathFileName, strrpos( $fullPathFileName, '/' )+1 );
				if( $parentDir ) {					
	    			$destinationDir = $fileDestination .'/'.$parentDir;
				} else {					
					$destinationDir = $fileDestination . '/';
				}

    			if( !is_dir( $destinationDir ) ) {
			  		FolderUtils::mkFullDir( $destinationDir );
			  	}	

				if( !copy( $fullPathFileName, $destinationDir . '/' . $fileName ) ) {
					echo 'Not Able to copy "' . $fullPathFileName . '" into "' . $destinationDir . '/' . $fileName. '"</br></br>' ;
				} else {
					$copied++;
				}
			}
    	}
		echo '</br>' . $copied . ' number of files copied into "'. $fileDestination.'".</br>';

    }
}

if( $pubId && $issId ) {
	// Calculate storage size selected brand.
	$calc = new DBBrandFileStorageSizeCalculator();
	$calc->calculate( $pubId, $issId, $ticket );
	
	// Report the statistics to admin user.
	echo $calc->reportStatisticsAsHtmlTable();

	if( $fileDestination && $copyFiles ) {
		$calc->writeCopyFileScript( $fileDestination );	
	}	

}
echo '</body></html>';
