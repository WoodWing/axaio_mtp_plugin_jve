// ---------------------------------------------------------
// Constants
// ---------------------------------------------------------

const CONSOLE = "cons";
const INFO = "info";
const WARNING = "warn";
const ERROR = "error";

const LOG_FILE = 1;
const LOG_CONSOLE = 2;
const DEBUGLEVEL = LOG_FILE;

const THIS_FILE_NAME = "server/plugins/IdsAutomation/indesignserverjob.jsx";

$.gc();

// ---------------------------------------------------------
// Get parameters 
// ---------------------------------------------------------

var pServer     = app.scriptArgs.get("server");
var pTicket     = app.scriptArgs.get("ticket");
var pLayout     = app.scriptArgs.get("layout");
var logfile     = app.scriptArgs.get("logfile"); // variable needs to be called 'logfile'
var pDelay      = app.scriptArgs.get("delay"); // wait for n seconds between opening and saving layout

app.serverSettings.imagePreview = true;

initlog(logfile);
logSystemInfo();

wwlog( CONSOLE, '----------------' );
wwlog( CONSOLE , 'before login: activeServer = [' + app.entSession.activeServer  + '] activeUser = [' + app.entSession.activeUser + ']' );

// To not have a conflict with MCE previews and logged in accounts
// we always re-login (with a performance price)
if( app.entSession.activeTicket ) {
	wwlog( CONSOLE , 'Found activeTicket, so logout active session.');
	app.entSession.logout();
}

wwlog( CONSOLE, 'Login to [' + pServer + '] server with ticket [' + pTicket + ']' );
app.entSession.forkLogin( '', pTicket, pServer );
wwlog( CONSOLE , 'after login: activeServer = [' + app.entSession.activeServer  + '] activeUser = [' + app.entSession.activeUser + ']' );

var myDoc;
var myErr;
try {
	wwlog( CONSOLE , 'Opening layout [' + pLayout + ']' );
	myDoc = app.openObject( pLayout, true );
	if( !myDoc ) { // actually, SC should have thrown error, but tested version 10.0.3 does not, so we do it
		throw new Error( 'Could not open layout [' + pLayout + '] for editing.' );
	}
	
	// For debugging purposes, sleep for a while. This make it possible to test race conditions.
	if( pDelay > 0 ) {
		wwlog( CONSOLE , 'Taking a nap for ' + pDelay + ' seconds ... (debug feature)' );
		sleep( pDelay*1000 ); // ms wait
	}

	wwlog( CONSOLE , 'Check-in ['+myDoc.entMetaData.get('Core_Name')+'] (Generating previews and PDF.)');
	try {
		myDoc.entWorkflow.checkIn();
		wwlog( CONSOLE , '-- Check-in done --');
	} 
	catch( err ) {
		myErr = err;
		wwlog( ERROR , 'Error when checking in, unlocking document without creating preview' );
		myDoc.entWorkflow.abortCheckOut();  
		wwlog( CONSOLE , '-- Layout unlocked --');
	}   
}
catch( err ) {
	myErr = err;
}

// logout after a job, since server will not find new issues without relogin
wwlog( CONSOLE , 'Logout [' + pServer + ']' );
app.entSession.logout();

// Mark IDS job as failed in case something went wrong above.
if( typeof myErr != 'undefined' ) {
	wwlog( ERROR, "Error: " + myErr.message + " Source: " + THIS_FILE_NAME + "#" + myErr.line );
	throw( myErr );
}

wwlog( CONSOLE , 'Job Completed' );
wwlog( CONSOLE , '----------------' );


// ---------------------------------------------------------
// Log functions
// ---------------------------------------------------------

function getDateShort()
{
	var today   = new Date();
	var year    = today.getFullYear().toString();
	var month   = "0" + (today.getMonth()+1).toString();
	var day     = "0" + today.getDate().toString();
	
	var h = "0" + today.getHours();
	var m = "0" + today.getMinutes();
	var s = "0" + today.getSeconds();
	var ms = "00" + today.getMilliseconds();
	
	return year.substr(-4) + '-' + month.substr(-2) + '-' + day.substr(-2) + ' ' + 
			h.substr(-2) + ':' + m.substr(-2) + ':' + s.substr(-2) + '.' + ms.substr(-3);
}

function initlog( logfile )
{
	if( typeof( logfile ) != "undefined" ) {	
		try {
			var oLogFile = new File( logfile );
			oLogFile.remove();
		}
		catch( err ) { 
			app.consoleerr( "Error: " + err.message + " Source: " + THIS_FILE_NAME + "#" + err.line );
		}	
	}
    
}

function wwlogtofile( strLogText )
{
	if( typeof(logfile) != "undefined" ) {	
		try {
			var oLogFile = new File( logfile );
			if( oLogFile.open( "a" ) ) {
				oLogFile.writeln( "[" + getDateShort() + "] " + strLogText );
				oLogFile.close();
			}
			
			app.wwlog( "IdsAutomation", LogLevelOptions.INFO, strLogText );
		}
		catch( err ) { // could not write loglines..., not so serious
			app.consoleerr( "Error: " + err.message + " Source: " + THIS_FILE_NAME + "#" + err.line );
		}
	}
}

function wwlog( logmode, strLogText )
{
	strLogText = ' [IdsAutomation] ' + strLogText;
	if( logmode == CONSOLE || logmode == ERROR || DEBUGLEVEL >= LOG_CONSOLE ) {
		try {
			if ( logmode != 'ERROR' ) {
				app.consoleout(strLogText);
			} else {
				app.consoleerr(strLogText);				
			}
		}
		catch( err ) { // for debugging with InDesign Client
			$.writeln( '[' + logmode + ']' + strLogText );
		}
	}
	if( DEBUGLEVEL >= LOG_FILE ) {
		wwlogtofile ( '[' + logmode + '] ' + strLogText );
	}
}

function logSystemInfo()
{
	if( typeof(logfile) != "undefined" ) {	
		wwlog( INFO, 'InDesign Server version=[v' + app.version + ']' );
		var oProducts = app.products;
	
		// walk through all installed products
		for( var i=0; i<oProducts.length; i++ ) {
			with( oProducts.item(i) ) { // expose props: name, version and activationState
				var sState = "";
				switch( activationState ) {
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
				wwlog( INFO, 'Installed plugin: [' + name + '] version=[' + version + '] state=[' + sState + ']' );
			}
		}
	}
}

function sleep( milliseconds )
{
	var start = new Date().getTime();
	for( var i = 0; i < 1e7; i++ ) {
		if( (new Date().getTime() - start) > milliseconds ){
			break;
		}
	}
}