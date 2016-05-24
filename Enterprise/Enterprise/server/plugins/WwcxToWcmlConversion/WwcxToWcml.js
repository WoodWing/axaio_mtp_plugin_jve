var tmStart = new Date();
try {
	alert( ">>> WwcxToWcml.js" );
	// retrieve script params
	var wwcxPath = app.scriptArgs.get("wwcxPath");
	var wcmlPath = app.scriptArgs.get("wcmlPath");

	app.convertFile( new File( wwcxPath ), new File( wcmlPath ) );
} catch( e ) {
	app.consoleerr( "Error (" + e.number + "):" + e.name + " - " + e.message );
	if( e.number == 362755 ) {
		// One of the causes is a non existing appServer
		throw( "Could not find the configured application server: [" + appServer + 
			"]. Please check the INDESIGNSERV_APPSERVER which should match with ServerInfo name of WWSettings.xml or APPLICATION_SERVERS setting." +
		    ' Also check if the WWSettings.xml file has no duplicate entries in the \'Servers\' entry.' );
	}
	throw( e ); // throw as is
} finally {
	var tmStop = new Date();
	var tmDiff = tmStop - tmStart;
	alert( "<<< WwcxToWcml.js took ["+ tmDiff.valueOf() +"] ms" );
}