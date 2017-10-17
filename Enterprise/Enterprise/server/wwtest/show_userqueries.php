<?php

require_once dirname(__FILE__).'/../../config/config.php';

ini_set('display_errors', '1');

// ------------------------------------------

function startElementFormat( $parser, $name, $attrs ) 
{
	if( $name == 'QUERYPARAM' ) {
		print "<li><font color='#000ff'>"; 
	} else if( $name == 'PROPERTY' ) {
		print ""; 
	} else if( $name == 'OPERATION' ) {
		print ""; 
	} else if( $name == 'TYPE' ) {
		print '<'; // hide
	}
}

function endElementFormat( $parser, $name ) 
{
	if( $name == 'QUERYPARAM' ) { 
		print "</li>"; 
	} else if( $name == 'PROPERTY' ) {
		print "&nbsp;</font>"; 
	} else if( $name == 'OPERATION' ) {
		print "&nbsp;"; 
	} else if( $name == 'TYPE' ) {
		print '/>'; // unhide
	}
}

function characterDataFormat( $parser, $data ) 
{
    print $data;
}

// ------------------------------------------

$indent = 0;
function startElementXML( $parser, $name, $attrs ) 
{
	global $indent;
	$indent++;
	for ( $i=0; $i < $indent; $i++ ) {
		print "&nbsp;";
	}
	print "&lt;<font color='#000ff'>$name</font>&gt;";
}

function endElementXML( $parser, $name ) 
{
	print "&lt;/<font color='#000ff'>$name</font>&gt;<br>";
	global $indent;
	$indent--;
}

function characterDataXML( $parser, $data ) 
{
	print $data;
}

// ------------------------------------------

// Setup DB layer:
$dbDriver = DBDriverFactory::gen();		

// error checking
if (!$dbDriver->dbh) {
	print "<font color='#ff0000'>".$dbDriver->error()." (".$dbDriver->errorcode().")</font><br/>";
	echo '<font color="#ff0000">Could not connect to database</font>';
	exit;
}

$dbs = $dbDriver->tablename("settings");
$sql = "SELECT * FROM ".$dbs." s order by `user`";
$sth = $dbDriver->query($sql);
echo '<html><head><meta http-equiv="content-type" content="text/html;charset=utf-8" /></head><body>';
while( ($row = $dbDriver->fetch($sth)) ) {
	// Examples of user settings: UserQuery_... or UserQuery2_... or QueryPanels
	// Filter out the UserQuery settings with following pattern: UserQuery[<ver>]_<name>
	// In other terms, we ignore other settings, such as QueryPanels.
	$underscore = strpos( $row['setting'], '_' ); // position of underscore in UserQuery[<ver>]_<name>
	if( $underscore !== FALSE && substr( $row['setting'], 0, strlen('UserQuery') ) == 'UserQuery' ) {
		$userQuery = substr( $row['setting'],$underscore+1 ); // get name of user query

		// Create XML parser (inside while loop -> for each XML tree!)
		$xml_parser = xml_parser_create();
		// use case-folding so we are sure to find the tag in $map_array
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
		if( isset( $_REQUEST['xml'] ) ) {
			xml_set_element_handler($xml_parser, "startElementXML", "endElementXML");
			xml_set_character_data_handler($xml_parser, "characterDataXML");
		} else {
			xml_set_element_handler($xml_parser, "startElementFormat", "endElementFormat");
			xml_set_character_data_handler($xml_parser, "characterDataFormat");
		}

		// Show user query
		echo "<hr>user: [<b>".$row['user']."</b>]<br/>";
		echo "application: [<b>".$row['appname']."</b>]<br/>";
		echo "setting: [<b>".$userQuery."</b>]<br/>";
		echo "query:<br><ul>";
	    if (!xml_parse($xml_parser, $row['value'], true)) {
	        die(sprintf("XML error: %s at line %d",
	                    xml_error_string(xml_get_error_code($xml_parser)),
	                    xml_get_current_line_number($xml_parser)));
	    }
	    echo "</ul>";
		xml_parser_free($xml_parser);
	}
}
echo "</body></html>";