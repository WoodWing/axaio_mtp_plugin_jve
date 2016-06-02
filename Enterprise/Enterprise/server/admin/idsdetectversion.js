try {
	// retrieve script params
	var sRespFile = app.scriptArgs.get("respfile");
	app.consoleout('producs XML file: [' + sRespFile + ']');

	// get product info and write it into xml format to return caller
	oRespFile = new File(sRespFile);
	if( !oRespFile.open("w") ) {
		var e = new Error( "Could not create file for writing: " + sRespFile );
		e.name = "File Access";
		throw( e );
	}
	oRespFile.write( app.version );
	oRespFile.close();
} catch( e ) {
	app.consoleerr( "Error: " + e.name + " - " + e.message );
	throw( e );
}
