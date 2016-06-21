<?php
require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/buildtools/genservices/ServicesClassGenerator.php';

global $argv;
$selInterface = @$argv[1];

// Determine generator
switch( $selInterface )
{
	case 'wfl':
		$gen = new WorkflowServicesClassGenerator();
		break;
	case 'adm':
		$gen = new AdminServicesClassGenerator();
		break;
	case 'sys':
		$gen = new SysAdminServicesClassGenerator();
		break;
	case 'pln':
		$gen = new PlanningServicesClassGenerator();
		break;
	case 'dat':
		$gen = new DataSourceServicesClassGenerator();
		break;
	case 'ads':
		$gen = new AdmDatSrcServicesClassGenerator();
		break;
	case 'pub':
		$gen = new PublishingServicesClassGenerator();
		break;
	default:
		echo 'Usage: php wsdl2phpcli.php <interface>'.PHP_EOL;
		echo 'Supported interfaces: adm, ads, dat, pln, pub, wfl'.PHP_EOL;
		exit(1);
}

// Collect required generator functions
$funcs = array();
$funcs['Services class'] = 'generateServicesClasses';
$funcs['Service classes'] = 'generateServiceClasses';
$funcs['SOAP server+client classes'] = 'generateSoapServerClientClasses';
$funcs['JSON client classes'] = 'generateJsonClientClasses';
$funcs['Request and response classes for PHP'] = 'generateRequestResponseClasses4Php';
$funcs['Request and response classes for Flex'] = 'generateRequestResponseClasses4Flex';
$funcs['Interfaces classes'] = 'generateServiceInterfaces';
$funcs['Data classes for PHP'] = 'generateDataClasses4Php';
$funcs['Data classes for Flex'] = 'generateDataClasses4Flex';
$funcs['Data validation classes'] = 'generateDataValidationClasses';
$funcs['Readable document'] = 'generateReadableDocument';

// Run generator functions
$exitStatus = 0;
foreach( $funcs as $funcTitle => $func ) {
	echo 'Generating '.$funcTitle.'...'.PHP_EOL;
	$gen->$func();
	// Show results
	if( count($gen->FatalErrors) > 0 ) {
		echo 'FATAL ERRORS:'.PHP_EOL;
		foreach( $gen->FatalErrors as $errors ) {
			echo '-'.$errors.PHP_EOL;
			$exitStatus = 1;
		}
	}
	if( count($gen->ErrorFiles) > 0 ) {
		foreach( $gen->ErrorFiles as $fileName ) {
			echo 'ERROR: No write access to file: '.$fileName.PHP_EOL;
			$exitStatus = 1;
		}
	}
}
echo 'Generator run finished.'.PHP_EOL;
exit( $exitStatus );
