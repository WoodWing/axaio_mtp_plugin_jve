<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';

print '<html><body><h1>Show object types</h1>This test tool shows the structured contents of the "types" field of smart_objects for a specified object. In this field, renditions and formats are tracked of the object files that are stored in the filestore.<br/><br/>';
if( !isset($_REQUEST['ID']) || empty($_REQUEST['ID']) ) { 
	print 'ERROR: Please specify object ID at URL. For example: showobjectypes.php?ID=123';
} else {
	$objProps = BizQuery::queryObjectRow($_REQUEST['ID'] );
	print 'Types: <code>'.print_r( unserialize($objProps['Types']), true ).'</code>';
}
print '</body></html>';