<?php
/**
 * @package     Enterprise
 * @subpackage  BuildTools
 * @since       10.2.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * CLI script that generates PHP classes from WSDLs.
 *
 * Those classes implement the web services for the core Enterprise Server (=provider).
 * Can also be used to generate the classes for a specific server plugin (=provider) instead.
 * For either of the two providers, it generates classes for all interfaces defined by the provider.
 */
require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/buildtools/genservices/WebServiceClassesGeneratorFactory.class.php';

$args = parseCliArguments();
$provider = $args["plugin"] ? $args["plugin"] : 'Enterprise Server';
$factory = new WW_BuildTools_GenServices_WebServiceClassesGeneratorFactory( $args["plugin"], $args["version"], $args["dir"] );
if( !$factory->validate() ) {
	$errorMsg = $factory->getErrorMessage();
	echo $errorMsg.PHP_EOL;
	exit( 1 );
}

$webInterfaces = $factory->getWebInterfaces();
if( $webInterfaces ) foreach( $webInterfaces as $webInterface ) {
	$generator = $factory->createGeneratorForInterface( $webInterface );
	if( $generator ) {
		echo "Created a class generator for the $webInterface interface of $provider...".PHP_EOL;
		$exitStatus = 0;
		$steps = $generator->getProcessingSteps();
		if( $steps ) foreach( $steps as $stepInfo => $functionName ) {
			echo "\tGenerating $stepInfo...".PHP_EOL;
			$generator->$functionName();
			// Show results
			if( count( $generator->FatalErrors ) > 0 ) {
				echo 'FATAL ERRORS:'.PHP_EOL;
				foreach( $generator->FatalErrors as $errors ) {
					echo '-'.$errors.PHP_EOL;
					$exitStatus = 1;
				}
			}
			if( count( $generator->ErrorFiles ) > 0 ) {
				foreach( $generator->ErrorFiles as $fileName ) {
					echo "ERROR: No write access to file: $fileName".PHP_EOL;
					$exitStatus = 1;
				}
			}
			if( $exitStatus ) {
				echo '=> Process aborted!'.PHP_EOL;
				exit( $exitStatus );
			}
		}
		echo "\tGenerator run finished.".PHP_EOL;
	}
}

echo "Successfully generated web service files.".PHP_EOL;
exit( 0 );

/**
 * Reads the command line arguments.
 *
 * @return string Name of the server plugin to generate web service classes for.
 *    Empty when web service classes for the core Enterprise Server should be generated instead.
 */
function parseCliArguments()
{
	$opts = new Zend\Console\Getopt( array(
		'plugin|p=s' => 'Optional. Name of the server plugin to generate the service classes for. '.
			'When not provided, the web service classes for the core Enterprise Server are generated.',
		'version|v=s' => 'Optional. Plugin version to use for the server plugin to generate the service classes for.'.
			'When not provided, the Enterprise Server version is used.',
		'dir|d=s' => 'Optional. Location of the plugin directory that the plugin resides in.',
		'help|h' => 'Show this information.'
	) );
	try {
		$arguments = $opts->getArguments();
	} catch( Exception $e ) {
		echo $opts->getUsageMessage();
		exit( 0 );
	}
	$plugin = isset( $arguments['plugin'] ) ? strval( $arguments['plugin'] ) : ''; // optional
	$version = isset( $arguments['plugin'] ) && isset( $arguments['version'] ) ? strval( $arguments['version'] ) : ''; // optional and depending on 'plugin'
	$dir = isset( $arguments['plugin'] ) && isset( $arguments['dir'] ) ? strval( $arguments['dir'] ) : ''; // optional and depending on 'plugin'
	if( isset( $arguments['help'] ) ) {
		echo $opts->getUsageMessage();
		exit( 0 );
	}
	return array(
		'plugin' => $plugin,
		'version' => $version,
		'dir' => $dir,
	);
}