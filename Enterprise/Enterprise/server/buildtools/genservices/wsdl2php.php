<?php

require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/buildtools/genservices/ServicesClassGenerator.php';

ini_set( 'display_errors', true );

$interfaces = array( 
	'-all-', 
	'Workflow', 
	'Admin', 
	'Planning', 
	'DataSource', 
	'Admin DataSource', 
	'Publishing'
);

$actions = array( 
	'-all-', 
	'Services classes', 
	'Service classes',
	'Server and client classes',
	'Request and response classes', 
	'Service interface classes', 
	'Data classes',
	'Readable document',
	//'Client proxy classes', // PEAR not used anymore
	//'Interface validator classes', // PEAR not used anymore
);

$selAction = $_REQUEST['act'];
$selInterface = $_REQUEST['inf'];
	
echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	
<html xmlns="http://www.w3.org/1999/xhtml">
	
	<head>
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<meta http-equiv="PRAGMA" content="NO-CACHE" />
		<meta http-equiv="Expires" content="-1" />
		<meta name="generator" content="Enterprise Server" />
		<title>Enterprise Server</title>
	
		<link href="../../../config/templates/woodwingmain.css" rel="stylesheet" type="text/css" media="all" />
		<link rel="icon" href="../../../config/images/favicon.ico" type="image/x-icon" />
		<link rel="shortcut icon" href="../../../config/images/favicon.ico" type="image/x-icon" />
	</head>
	<body>
		<h1>Enterprise Build tool</h1>
		<h2>WSDL -> PHP class generator</h2>
		<form name="thisform" action="wsdl2php.php" method="post">
			<select name="inf">
';

				foreach( $interfaces as $infKey => $infDisplay ) {
					echo '<option name="'.$infKey.'" ';
					if( $selInterface == $infDisplay ) {
						echo 'selected="selected"';
					}
					echo '>'.$infDisplay.'</option>';
				}

			
echo '
			</select>
			<select name="act">
';

				foreach( $actions as $actKey => $actDisplay ) {
					echo '<option name="'.$actKey.'" ';
					if( $selAction == $actDisplay ) {
						echo 'selected="selected"';
					}
					echo '>'.$actDisplay.'</option>';
				}

			
echo '
			</select>
			<input type="submit" name="submit" value="Run generator"/>
			<br/>
			<br/>
		</form>
	</body>
</html>
';

if( $selAction && $selInterface ) {
	// Collect required generators
	$gens = array();
	switch( $selInterface )
	{
		case '-all-':
		case 'Workflow':
			$gens['Workflow'] = new WorkflowServicesClassGenerator();
			if( $selInterface !== '-all-' ) break;
		case 'Admin':
			$gens['Admin'] = new AdminServicesClassGenerator();
			if( $selInterface !== '-all-' ) break;
		case 'SysAdmin':
			$gens['SysAdmin'] = new SysAdminServicesClassGenerator();
			if( $selInterface !== '-all-' ) break;
		case 'Planning':
			$gens['Planning'] = new PlanningServicesClassGenerator();
			if( $selInterface !== '-all-' ) break;
		case 'DataSource':
			$gens['DataSource'] = new DataSourceServicesClassGenerator();
			if( $selInterface !== '-all-' ) break;
		case 'Admin DataSource':
			$gens['Admin DataSource'] = new AdmDatSrcServicesClassGenerator();
			if( $selInterface !== '-all-' ) break;
		case 'Publishing':
			$gens['Publishing'] = new PublishingServicesClassGenerator();
			if( $selInterface !== '-all-' ) break;
	}
	// Collect required generator functions
	$funcs = array();
	switch( $selAction )
	{
		case '-all-':
		case 'Services classes':
			$funcs['Services classes'] = 'generateServicesClasses';
			if( $selAction !== '-all-' ) break;
		case 'Service classes':
			$funcs['Service classes'] = 'generateServiceClasses';
			if( $selAction !== '-all-' ) break;
		case 'Server and client classes':
			$funcs['SOAP server+client classes'] = 'generateSoapServerClientClasses';
			$funcs['JSON client classes'] = 'generateJsonClientClasses';
			if( $selAction !== '-all-' ) break;
		case 'Request and response classes':
			$funcs['Request and response classes for PHP'] = 'generateRequestResponseClasses4Php';
			$funcs['Request and response classes for Flex'] = 'generateRequestResponseClasses4Flex';
			if( $selAction !== '-all-' ) break;
		case 'Service interface classes':
			$funcs['Service interface classes'] = 'generateServiceInterfaces';
			if( $selAction !== '-all-' ) break;
		case 'Data classes':
			$funcs['Data classes for PHP'] = 'generateDataClasses4Php';
			$funcs['Data classes for Flex'] = 'generateDataClasses4Flex';
			$funcs['Data validation classes'] = 'generateDataValidationClasses';
			if( $selAction !== '-all-' ) break;
		case 'Readable document':
			$funcs['Readable document'] = 'generateReadableDocument';
			if( $selAction !== '-all-' ) break;
		/* Commented out: PEAR no longer used
		case 'Client proxy classes':
			$funcs['Client proxy classes'] = 'generateClientProxyClasses';
			if( $selAction !== '-all-' ) break;*/
	}
	// For all generators, run generator functions
	foreach( $gens as $genTitle => $gen ) {
		// Run generator functions
		foreach( $funcs as $funcTitle => $func ) {
			echo 'Running '.$genTitle.' -> '.$funcTitle.'<br/>';
			$gen->$func();
			// Show results
			if( count($gen->SuccessFiles) > 0 ) {
				echo '<br/><font color="green">Generated files:</font><ul>';
				foreach( $gen->SuccessFiles as $fileName ) {
					echo '<li><font color="green">'.$fileName.'</font></li>';
				}
				echo '</ul>';
			}
			if( count($gen->ErrorFiles) > 0 ) {
				echo '<br/><font color="red">Failed write to files:</font><ul>';
				foreach( $gen->ErrorFiles as $fileName ) {
					echo '<li><font color="red">'.$fileName.'</font></li>';
				}
				echo '</ul>';
			}
			if( count($gen->Warnings) > 0 ) {
				echo '<br/><font color="orange">Warnings:</font><ul>';
				foreach( $gen->Warnings as $warning ) {
					echo '<li><font color="orange">'.$warning.'</font></li>';
				}
				echo '</ul>';
			}
			if( count($gen->FatalErrors) > 0 ) {
				echo '<br/><b><font color="purple">Fatal errors:</font></b><ul>';
				foreach( $gen->FatalErrors as $error ) {
					echo '<li><b><font color="purple">'.$error.'</font></b></li>';
				}
				echo '</ul>';
			}
			if( count($gen->SkippedFiles) > 0 ) {
				echo '<br/><font color="blue">Skipped files:</font><ul>';
				foreach( $gen->SkippedFiles as $fileName ) {
					echo '<li><font color="blue">'.$fileName.'</font></li>';
				}
				echo '</ul>';
			}
		}
	}
	echo '<br/><br/>Generator run finished.';
} else {
	echo '<br/>Make your choice and press the button.<br/>';
}