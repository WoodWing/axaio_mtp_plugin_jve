/**
 * Application Session manager. Allows to logon and logoff to Enterprise server <br/>
 * through application services based on SOAP. The ticket is stored a cookie and can be retrieved. <br/>
 * Although this session management is new since v5.x, tickets are still shared with v4.x web applications. <br/>
 * 
 * @package 	SCEnterprise
 * @subpackage 	WebApps
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * Constructor
 */

function AppSession() 
{
}

AppSession.prototype.resTable = null;
AppSession.prototype.prodCode = "ContentStationPro700";
AppSession.prototype.version  = null;
AppSession.prototype.maxElements = 20; // Default Max Elements

/**
 * Logs on to Enterprise server through web app services <br/>
 *
 * @param url string           The HTTP URL of the web app service.
 * @param callbackFn function  The function that must be called when logon is completed.
 * @param userName string      The typed user name to be validated for logon.
 * @param password string      The typed password to be validated for logon.
 * @param ticket string        Used for silent logon
 */
AppSession.prototype.logOn = function( callbackApp, userName, password, ticket )
{
	/* // does only return localhost instead of client's external IP
	if (navigator.javaEnabled() && (navigator.appName != "Microsoft Internet Explorer")) {
		var tool = java.awt.Toolkit.getDefaultToolkit();
		var addr = java.net.InetAddress.getLocalHost();
		var host = addr.getHostName();
		var ip = addr.getHostAddress();
		alert("Your host name is '" + host + "'\nYour IP address is " + ip);
	}*/

	var params = new SOAPClientParameters();
	params.add( "User", userName );
	params.add( "Password", password );
	params.add( "Ticket", ticket );
	params.add( "Server", "Enterprise Server" );
	params.add( "ClientName", "" ); // TODO: get IP address
	params.add( "Domain", null );
	params.add( "ClientAppName", "WebEditor" );
	params.add( "ClientAppVersion", "v7.0" );
	params.add( "ClientAppSerial", "SCEnt_ResolveServerSide" );
	params.add( "ClientAppProductKey", AppSession.prototype.prodCode );
	params.add( "RequestTicket", "true" );
	
	var url = AppSession.prototype.getServiceURL();
	if( url && url.length > 0 ) {
		SOAPClient.invoke( url, "AppSession", "LogOnRequest", params, false, callbackHere, null );
	} else {
		var err = new Error( "Could not determine server URL" ); // should never happen
		callbackHere( err, null );
	}

	/**
	 * Private function called by SOAPClient library when the request is completed. <br/>
	 *
	 * @param r object             Response object or SOAP fault.
	 * @param soapResponse object  Raw soap response (XML DOM tree).
	 */
	function callbackHere( r, soap )
	{
		if( r && r.name == "Error" ) { // failed...
			// Forget about previous ticket
			CookieManager.prototype.deleteCookie( "webedit_ticket_" + AppSession.prototype.prodCode );
			// Tell caller logon failed
			if( callbackApp ) {
				callbackApp( r, soap );
			} else {
				alertError( r );
			}
				
		} else { // successfull...
			// Set expiration to 1 day
			var expDate = new Date();
			expDate.setDate( expDate.getDate() + 1 );
			// Save ticket as done for v4.x
			CookieManager.prototype.setCookie( "webedit_ticket_" + AppSession.prototype.prodCode, r.Ticket, expDate, "/" );
			// Tell caller logon is completed successfully
			if( callbackApp ) callbackApp( r, soap );
		}
		/* // DEBUG:
		if(soapResponse.xml)    // IE
		    alert(soapResponse.xml);
		else                    // MOZ
		    alert((new XMLSerializer()).serializeToString(soapResponse));
		*/
	}
}

/**
 * Logs off current user using application services <br/>
 * Should be used after calling {@link logOn()} <br/>
 *
 * @param callbackFn function  The function that must be called when logoff is completed. Might be null.
 */
AppSession.prototype.logOff = function( callbackApp )
{
	var params = new SOAPClientParameters();
	params.add( "Ticket", AppSession.prototype.getTicket() );
	params.add( "SaveSettings", false );
	params.add( "Settings", null );
	params.add( "ReadMessageIDs", null );
	var url = AppSession.prototype.getServiceURL();
	if( url && url.length > 0 ) {
		SOAPClient.invoke( url, "AppSession", "LogOffRequest", params, false, callbackHere, null );
	}

	/**
	 * Private function called by SOAPClient library when the request is completed. <br/>
	 *
	 * @param r object             Response object or SOAP fault.
	 * @param soapResponse object  Raw soap response (XML DOM tree).
	 */
	function callbackHere( r, soapResponse )
	{
		if( r && r.name == "Error" ) { // failed...
			if( callbackApp )
				callbackApp( r );
			else {
					if( r.message.indexOf("(S1043)") >= 0 ) {
					}	else	{
				alertError( r );
					}
			}
		} else { // successfull...
			if( callbackApp )
				callbackApp( null );
		}
	}
}

/**
 * Retrieve Web Editor ticket from cookie. <br/>
 *
 * @return string Ticket that was retrieved by logon. Could be of previous session.
 * @param soapResponse object  Raw soap response (XML DOM tree).
 */
AppSession.prototype.getTicket = function()
{
	return CookieManager.prototype.getCookie( "webedit_ticket_" + AppSession.prototype.prodCode );
}

/**
 * Returns the current user language which is stored in the browser's cookie. <br/>
 *
 * @return Language code using Adobe's abbreviation, such as enUS, deDE, etc
 */
AppSession.prototype.getLanguage = function()
{
	var usrLang = CookieManager.prototype.getCookie( 'language' );
	return usrLang ? usrLang : 'enUS';
}

/**
 * Converts given language code from Adobe to TinyMCE. <br/>
 *
 * @param adobeLang string Language code using Adobe's abbreviation, such as enUS, deDE, nlNL etc
 * @return Language code using TinyMCE's abbreviation, such as en, de, nl, etc
 */
AppSession.prototype.adobeLangToTinyLang = function( adobeLang )
{
	// Supported languages:
	switch( adobeLang )
	{
		// SCE v4.0:
		case 'enUS': return 'en';       // English US
		case 'deDE': return 'de';       // German
		case 'esES': return 'es';       // Spanish
		case 'frFR': return 'fr';       // French
		case 'itIT': return 'it';       // Italian
		case 'jaJP': return 'ja';       // Japanese
		case 'nlNL': return 'nl';       // Dutch
		case 'ptBR': return 'pt';       // Portuguese Brazilian
		// SCE v4.2:
		case 'zhCN': return 'zh';       // Simplified Chinese [EKL@v4.1.7b171]
		case 'ruRU': return 'ru';       // Russian [EKL@v4.1.7b171]
		case 'koKR': return 'ko';       // Korean [v4.2]
		case 'zhTW': return 'tw';       // Traditional Chinese
		case 'plPL': return 'pl';       // Polish [v4.2]
		// Future:
		case 'fiFI': return 'fi';       // Finnish
		case 'csCZ': return 'cs';       // Czech
		case 'noNO': return 'no';       // Norwegian
		case 'huHU': return 'hu';       // Hungarian
		case 'enGB': return 'en';       // English
		case 'daDK': return 'da';       // Danish
		case 'svSV': return 'sv';       // Swedish
	}
	return 'en'; // default english
}

/**
 * Converts given language code from Adobe to TinyMCE SpellChecker . <br/>
 *
 * @param adobeLang string Language code using Adobe's abbreviation, such as enUS, deDE, nlNL etc
 * @return Language code using TinyMCE's dictionary names, such as English, German, Dutch, etc
 */
AppSession.prototype.adobeLangToTinySpeller = function( adobeLang )
{
	// Supported languages:
	switch( adobeLang )
	{
		// SCE v4.0:
		case 'enUS': return 'English';
		case 'deDE': return 'German';
		case 'esES': return 'Spanish';
		case 'frFR': return 'French';
		case 'itIT': return 'Italian';
		//case 'jaJP': return 'Japanese';   // unsupported by TinyMCE
		case 'nlNL': return 'Dutch';
		//case 'ptBR': return 'Portuguese'; // unsupported by TinyMCE
		// SCE v4.2:
		//case 'zhCN': return 'Chinese';    // Simplified Chinese -> unsupported by TinyMCE
		//case 'ruRU': return 'Russian';    // unsupported by TinyMCE
		//case 'koKR': return 'Korean';     // unsupported by TinyMCE
		//case 'zhTW': return 'Chinese';    // Traditional Chinese -> unsupported by TinyMCE
		case 'plPL': return 'Polish';
		// Future:
		//case 'fiFI': return 'Finnish';    // unsupported by TinyMCE
		//case 'csCZ': return 'Czech';      // unsupported by TinyMCE
		//case 'noNO': return 'Norwegian';  // unsupported by TinyMCE
		//case 'huHU': return 'Hungarian';  // unsupported by TinyMCE
		case 'enGB': return 'English';
		case 'daDK': return 'Danish';
		case 'svSV': return 'Swedish';
	}
	return 'English'; // default english
}

/**
 * Returns the full URL path of the AppSession service. <br/>
 * This URL is used as entry point to fire SOAP requests for session management. <br/>
 *
 * @return string    The URL which starts with http:// and ends with /appservices.php.
 */
AppSession.prototype.getServiceURL = function()
{
	var base = getBaseDir();
	return base ? base + "appservices.php" : null;
}

/**
 * Returns the full URL path of the workflow service. <br/>
 * This URL is used as entry point to fire SOAP requests for session management. <br/>
 *
 * @return string    The URL which starts with http:// and ends with /index.php.
 */
AppSession.prototype.getWorkflowURL = function()
{
	var base = getBaseDir();
	return base ? base + "index.php" : null;
}

/**
  * Calls SOAP request and does re-logon when ticket is expired
  * See SOAPClient.invoke for details about params.
  *
  * @param callbackApp Function called when operation completed. Its boolean param indicates succes of entire operation.
  */
AppSession.prototype.soapRequest = function( url, service, method, params, flag, callbackApp, ns )
{
	// Perform the actual SOAP request
	if( url ) { // real request?
		SOAPClient.invoke( url, service, method, params, flag, callbackHere, ns );
	} // else, silent logon (and real logon on failure)
	
	function callbackHere( r, soap ) // returned from original request (or silent logon failure)
	{
		if( r && r.name == "Error" ) { // failed... (r=null for empty, but valid response)
			if( r.detail == "SCEntError_InvalidTicket" ) {
				var webAppTicket = CookieManager.prototype.getCookie( 'ticket' );
				if( webAppTicket && webAppTicket.length > 0 ) {
					// found web app ticket, let's try silent logon first
					AppSession.prototype.logOn( callbackSilent, "", "", webAppTicket );
				} else {
					// no web app ticket, show logon dialog
					LogonDialog.prototype.show( callbackRelogon );
				}
				// the callback (above) do take over from this point!
			} else {
				if( r.message.indexOf("(S1021)") >= 0 || r.message == 'FORCED_RESTORE') { // S1021 = object already locked || FORCED_RESTORE = new version found in DB
					// suppress error; handled by caller
				} else {
					alertError( r );
				}
				if( callbackApp ) callbackApp( false, r, soap );
			}
		} else {
			if( callbackApp ) callbackApp( true, r, soap );
		}
	}

	function callbackSilent( r, soap ) // returned from silent logon
	{
		if( r && r.name == "Error" ) { // failed... (r=null for empty, but valid response)
			if( r.detail == "SCEntError_InvalidTicket" ) {
				// silent logon failed as well, now show logon dialog
				LogonDialog.prototype.show( callbackRelogon ); 
			} else {
				// silent logon failed on something else, now handle error here
				callbackHere( r, soap ); 
			}
		} else {
			// silent logon ok, now handle original request
			callbackRelogon( r.Ticket ); 
		}
	}
	
	function callbackRelogon( ticket ) // returned from logon dialog
	{
		if( ticket ) {
			// re-try original request
			params.add( "Ticket", ticket );	// this overwrites old value
			SOAPClient.invoke( url, service, method, params, flag, callbackHere, ns ); // use original callback
		} else {
			if( callbackApp ) callbackApp( false, null, null );
		}
	}
}
