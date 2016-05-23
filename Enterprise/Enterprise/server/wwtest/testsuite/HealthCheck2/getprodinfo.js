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
	//oRespFile.write( '<?xml version="1.0" encoding="utf-8" ?>\r\n' );
	oRespFile.write( '<products>\r\n' );
	
	// walk through all installed products
	for( i=0; i < app.products.length; i++ )
	{
		with( app.products.item(i) ) // expose props: name, version and activationState
		{
			sState = "";
			switch( activationState )
			{
				case ActivationStateOptions.none:
					sState = "none";
					break;
				case ActivationStateOptions.demo:
					sState = "demo";
					break;
				case ActivationStateOptions.serial:
					sState = "serial";
					break;
				case ActivationStateOptions.limitedSerial:
					sState = "limited serial";
					break;
				case ActivationStateOptions.server:
					sState = "server";
					break;
				case ActivationStateOptions.limitedServer:
					sState = "limited server";
					break;
			}
			oRespFile.write( '<prodinfo name="' + name + '" version="' + version + '" state="' + sState + '" />\r\n' );
		}
	}
	
	// InDesign Server product itself:
	oRespFile.write( '<prodinfo name="InDesign Server" version="v' + app.version + '" state="serial" />\r\n' );
	
	oRespFile.write( '</products>' );
	oRespFile.close();
} catch( e ) {
	app.consoleerr( "Error: " + e.name + " - " + e.message );
	throw( e );
}