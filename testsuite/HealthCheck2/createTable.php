<html><body><?php
if( file_exists('../../../../../config/config.php') ) {
	require_once '../../../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
	require_once '../../../../../Enterprise/config/config.php';
}
require_once BASEDIR . '/server/utils/FolderUtils.class.php';
require_once BASEDIR . '/server/utils/TestSuite.php';

$version = [];
preg_match("/(\d+)\.(\d+).*/", SCENT_DBVERSION, $version);

$major = $version[1];
$minor = $version[2];

$files = [ "mtptable_{$major}-{$minor}_" . DBTYPE . ".sql"
         , "mtptable_{$major}_"          . DBTYPE . ".sql"
         , "mtptable_"                   . DBTYPE . ".sql"
         ];

$done = false;
foreach($files as $cur_file) {

    if(is_readable(dirname(__FILE__).'/'.$cur_file))
    {
            print '<font>Running '.$cur_file.'...</font><br><br>';
            $dbDriver = DBDriverFactory::gen();

            $check_tables = array('axaio_mtp_trigger', 'axaio_mtp_sentobjects', 'axaio_mtp_process_options'); // names without prefix 
            $tableCheck = false;

            foreach($check_tables as $value)
            {
                    if( $dbDriver->tableExists( $value ))
                    {
                            print '<font color="green">Table <em>'.DBPREFIX.$value.'</em> already created</font><br>';
                            $tableCheck = true;
                    }
            }

            if( $tableCheck == false )
            {
                    $runSqlScript = runSqlScript( $dbDriver, $cur_file );
                    foreach($check_tables as $value)
                    {
                            if( $dbDriver->tableExists( $value ))
                            {
                                    print '<font color="green">Table <em>'.DBPREFIX.$value.'</em> successfully created</font><br>';
                                    
                                    if(DBTYPE == "mysql") {
                                        //try to copy values from old table (without prefix) to new one
                                        $sql = 'INSERT INTO '.DBPREFIX.$value.'
                                                SELECT *
                                                FROM '.$value.';';
                                        $res = $dbDriver->query($sql);
                                        if($res) {
                                            print '<font color="green" style="padding-left:1em"> - Found values in existing table <em>'.$value.'</em>, moved them to new table</font><br>';
                                        }

                                        $sql = 'DROP TABLE '.$value.';'; //DROP OLD TABLE
                                        $res = $dbDriver->query($sql);
                                    }
                            }
                            else
                            {
                                    print '<font color="red">Couldn\'t create Table <em>'.DBPREFIX.$value.'</em></font><br>';
                            }
                    }

            }
        $done = true;
    }
}

if(!$done) {
    print "<font color='red'>None of the possible SQL files could be found in ".dirname(__FILE__)."<br><ul><li>"
         . join('<li>', $files)
         ."</ul></font>";

}

/**
 * Runs an SQL script on the database as a part of the installation.
 *
 * @param object $dbDriver
 * @param string $sqlScript SQL script to run.
 */
function runSqlScript( $dbDriver, $sqlScript )
{
	$sqlTxt = file_get_contents( $sqlScript );
        $sqlTxt = str_replace('{DBPREFIX}', DBPREFIX, $sqlTxt);
        
	$sqlStatements = explode( ';', $sqlTxt );
	array_pop( $sqlStatements ); // remove the last empty element ( after the ; )

	if( $sqlStatements ) foreach( $sqlStatements as $sqlStatement ) {
		$sth = $dbDriver->query( $sqlStatement );
		if( !$sth ) {
			$message = $dbDriver->error().' ('.$dbDriver->errorcode().')';
			if (LogHandler::debugMode())
			{
				LogHandler::Log( 'createTable.php', 'ERROR', print_r('$message', true) );
				LogHandler::Log( 'createTable.php', 'ERROR', print_r($message, true) );
			}
/*
			$this->report->add( 'DbInstaller', 'FATAL', 'ERROR',
								$message, 'SQL: '.$sqlStatement, '',
								array( 'phase' => $this->phase ) );
*/
		}
	}
}

?></body></html>
