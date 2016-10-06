var tip = "Please check the INDESIGNSERV_APPSERVER which should match with ServerInfo name of WWSettings.xml or APPLICATION_SERVERS setting.";

// When this user has already being logged in, SC will cache it and no longer login again.
// Therefor we logout first, to make sure that we always gain a new ticket from Ent Server
// in the succeeding steps below.
try {
	if( app.entSession.activeUser != '' ) {
		app.entSession.logout();
	}
} catch( e ) {
	app.consoleerr( "Error (" + e.number + "):" + e.name + " - " + e.message );
}

// Logon to Enterprise Server.
try {
	// retrieve script params
	var appServer = app.scriptArgs.get("appServer");
	var user = app.scriptArgs.get("Username");
	var password = app.scriptArgs.get("Password");

	app.consoleout('Application server: [' + appServer + ']');
	app.entSession.login( user, password, appServer );

	var srvr = app.entSession.activeServer;
	var srvrURL = app.entSession.activeUrl;
	var ticket = app.entSession.activeTicket;
	var imagePreviewOption = app.serverSettings.imagePreview ? 'ImagePreviewIsSet' : 'ImagePreviewOptionNotSet' ;

	app.consoleout('active server: [' + srvr + ']');
	app.consoleout('active server URL: [' + srvrURL + ']');
	
	if( srvr == null || typeof( srvr ) == 'undefined' || srvr == '' ) {
		throw( "Could not log in to the configured application server: [" + appServer + "]. " + tip );
	}
	
	// If we'd logout here, the ticket gets removed from the DB and so the caller can not validate
	// whether or not the ticket belongs to its own DB and conclude both share the same DB.
	// Therefore we leave the session untouched.
	//app.entSession.logout();

} catch( e ) {
	app.consoleerr( "Error (" + e.number + "):" + e.name + " - " + e.message );
	if( e.number == 362755 ) { // the app server is not configured
		tip += ' Also check if the WWSettings.xml file has no duplicate entries in the \'Servers\' entry.';
		throw( "Could not find the configured application server: [" + appServer + "]. " + tip );
	}
	if( e.number == 362781 ) { // bad url configured for app server
		throw( "Could not log in to the configured application server: [" + appServer + "]. " +
			"The INDESIGNSERV_APPSERVER matches with ServerInfo name of WWSettings.xml or APPLICATION_SERVERS, " + 
			"but the url attribute configured for that ServerInfo entry may not be correct. " + 
			"It causes the following error: \"" + e.message + "\"." );
	}
	// throw as is
	throw( e );
}

// Return the ticket/URL to Enterprise Server to validate the origin. The imagePreviewOption is needed to make sure
// that the -previews flag will be set.
var response = ticket + " " + srvrURL + " " + imagePreviewOption;
response;