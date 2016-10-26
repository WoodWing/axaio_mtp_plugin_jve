/**
 InDesign Server integration to support three web editing features for articles:
 - Write-To-Fit (per text frame, also called Hyphenate & Justify / H&J / Compose)
 - Preview (per page or spread, only pages the article is placed on)
 - PDF generation (all pages, only pages the article is placed on)

 This IDPreview.js module is compatible with:
 - SC8 plug-ins for InDesign Server CS6
 - SC10 plug-ins for InDesign Server CC 2014

 Note that technically this script should work for SC9 plug-ins for InDesign Server CC as well,
 but CC is no longer supported by Enterprise Server 9.5+.

 Note that the scripting interface has changed since SC10 to improve preview performance.
 Those improvements are NOT backported to SC8, so SC10 has better performance than SC8.
*/

///////////////////////////////////////////////////////////////////////////////////////////////

//
// Constants
//

const CONSOLE = "cons";
const INFO = "info";
const WARNING = "warn";
const ERROR = "error";

const LOG_FILE = 1;
const LOG_CONSOLE = 2;
const DEBUGLEVEL = LOG_FILE;

const PREVIEW_RESOLUTION = 150.0;
const PREVIEW_QUALITY = JPEGOptionsQuality.HIGH;

app.serverSettings.imagePreview = true;


///////////////////////////////////////////////////////////////////////////////////////////////

//
// StopWatch
//

function StopWatch()
{
	this.lastStart = 0;
	this.duration = 0;
}
StopWatch.prototype.start = function()
{
	this.lastStart = new Date();
}
StopWatch.prototype.stop = function()
{
	now = new Date();
	this.duration += ( now.getTime() - this.lastStart.getTime() );
}
StopWatch.prototype.getDuration = function()
{
	return this.duration / 1000;
}

///////////////////////////////////////////////////////////////////////////////////////////////

//
// Functions
//

function getOverset(story)
{
	wwlog(INFO, "      Action: Get Overset");

	// overset is easy: if last frame has overset:
	if( story.textContainers.length > 0 &&
			story.textContainers[story.textContainers.length-1].overflows )
	{
		wwlog(INFO, "      Info: Story has overset");

		// Overset, calculate number of lines by checking #overset chars
		// and number dividing chars/lines of non-overset
		lastFrame = story.textContainers[story.textContainers.length-1];

		// we get characters via texts to be Smart Layout compatible:		
		if( lastFrame.texts.length > 0 )
		{
			if (  lastFrame.texts[0].characters.length > 0 )
			{
				// Last frame contains some text, any character that is beyond the last character of this frame is overset text.
				wwlog(INFO, "      Info: Last frame contains some text");
				lastText = lastFrame.texts[lastFrame.texts.length-1]
				lastChar = lastText.characters[lastText.characters.length-1];
				numOversetChars = story.characters.length - lastChar.index-1;

				lines = getTotalLines( story );

				linesPerChar = lines/lastChar.index;
				oversetLines = Math.round( numOversetChars * linesPerChar + 0.5);
			}
			else
			{
				//               
				// Last frame contains no text which means the frame is resized to a point where all text content is 
				// pushed outside this last frame. 
				//
				wwlog(INFO, "      Info: Last frame is empty");
				var frameCount = story.textContainers.length;
				if ( frameCount > 1 )
				{
					// There are previous threaded frames before the last frame (that contains no text). Any character that
					// is beyond that previous frame (index of frameCount -2) is overset text.
					lastFrame = story.textContainers[ frameCount - 2];
					lastText = lastFrame.texts[lastFrame.texts.length-1]
					lastChar = lastText.characters[lastText.characters.length-1];
					numOversetChars = story.characters.length - lastChar.index-1;
				}
				else
				{
					// The story contains only one frame so this the last frame. All text in the story are overset.
					numOversetChars = story.characters.length;
				}

				// Overset line count is undefined for last frame with no content.
				// As a negative value would imply that this is underset we just show a large number of overset lines.
				oversetLines = 9999;
			}

			return [ 'overset', oversetLines, numOversetChars ];
		}
	}
	else
	{
		wwlog(INFO, "      Info: Story has underset or fits");

		if( story.itemLink )
		{
			if (story.itemLink.status == LinkStatus.linkMissing)
			{
				wwlog(ERROR, "      Error: link missing " + story.itemLink.name);
				//	basedocpath = myDoc.filePath;
				//	newpath = basedocpath + ":__" + story.itemLink.name;
				//	wwlog("Fix link to: " + newpath);
				//	story.itemLink.relink(newpath);
				//	story.itemLink.update();
			}
		}
		actualLines = getTotalLines( story );
		try {
			// Record the index of the last character + 1. This will 
			// be the index of the first character to remove
			var endBefore = story.length;

			if( story.texts.length > 0 )
			{
				// Always insert dummy paragraph after the last paragraph.
				var nLastParagraph = (story.texts[0].paragraphs.length - 1);
				if( nLastParagraph >= 0 ) { // Bugfix: Avoid reference error for BZ#11869
					// Add a new paragraph with a space (' ') so last inline object
					// does not get removed.
					story.texts[0].paragraphs[nLastParagraph].insertionPoints[-1].contents += '\r ';
				}
			}

			// Fill the story with dummy text
			fillWithDummyText( story );

			// Determine the number of lines of underset
			maxLines = getTotalLines( story );
			underset = maxLines - actualLines;

			// Remove the text added by the fill. Don't remove the end of story character!
			var endAfter = story.length-1;
			var charsToRemove = story.characters.itemByRange(endBefore, endAfter);
			charsToRemove.remove();
		}
		catch (e)
		{
			wwlog( ERROR, "      Error #3: " + e.name + " - " + e.message + " Source: IDPreview.js#" + e.line );
			return [ 'error', "" + e, 0 ];
		}

		if(underset == 0){
			return [ 'copyfit', 0, 0 ];
		}
		return [ 'underset', underset, 0 ];

	}

	// Ending up here is unexpected, return an error
	return [ 'error', "", 0 ];

}

/**
 * Fills a textframe with dummy text until it overflows (overset).
 */
function fillWithDummyText( story )
{
	wwlog(INFO, "      Action: Fill with dummy text");
	wwlog(INFO, "      DEBUG: textContainers.length = " + story.textContainers.length);
	wwlog(INFO, "      DEBUG: paragraphs.length = " + story.paragraphs.length);

	var dummyText = "\rAndipsus";

	// Construct and apply the Basic ParagraphStyle to apply at the end of all content
	// so we can fill the frame with the last style applyed and with cleared overrides.
	var lastParagraphStyle = story.insertionPoints[-1].appliedParagraphStyle;

    // Fix for EN-86852 'Preview for InDesign Server shows wrong linebreak/overset calculation with specific pragraph style settings'
    // Option 'Keep with Previous' disturbs underset calculation. Switch it off for dummy text.
    if ( lastParagraphStyle.keepWithPrevious == true )
    {
        lastParagraphStyle.keepWithPrevious = false;
    }

	story.insertionPoints[-1].applyParagraphStyle( lastParagraphStyle, true );

	// Add the dummyText until we encounter a text overset. Even with a 1000 lines of overset
	// the Lines.length of the Story will still be the copfit amount.
	while( story.textContainers.length > 0 && !story.textContainers[story.textContainers.length-1].overflows )
	{
		// Apply the pragraphstyle with cleared overrides to the end of the content.
		story.insertionPoints[-1].applyParagraphStyle( lastParagraphStyle, true );

		var target = story;

		if( story.paragraphs.length > 0 )
		{
			target = story.paragraphs.lastItem();
		}

		target.contents += dummyText;
	}

	wwlog(INFO, "	   Action: End filling");
}


function getTotalLines( story )
{
	// story.lines does not give actual lines, but lines up till what has been composed.
	// this typically included a piece of overset, so get actual lines different way:
	lines = 0;
	wwlog(INFO, "      Action: Get total lines");
	try {
		for( var tf=0; tf < story.textContainers.length; ++tf ) {
			fram = story.textContainers[tf];
			if ( fram.texts.length > 0 ) {
				lines += fram.texts[0].lines.length;
			}
		}
	}
	catch (e) {
		wwlog( ERROR, "      Error #4: " + e.name + " - " + e.message + " Source: IDPreview.js#" + e.line );
	}
	wwlog(INFO, "      Info: Total line count: " + lines);
	return lines;
}

// checks if item is in array ( like PHP )
function in_array (arr, el)
{
	for (var i = 0; i < arr.length; i++) {
		if (arr[i] == el) {
			return true;
		}
	}
	return false;
};

// used for sorting array numeric
function sortNumber(a,b)
{
	return a - b;
}

function pageSide2Text( nSide )
{
	var sSide = "";

	switch( nSide )
	{
		case PageSideOptions.leftHand:
			sSide = "left";
			break;
		case PageSideOptions.rightHand:
			sSide = "right";
			break;
		case PageSideOptions.singleSided:
		default:
			sSide = "left";
			break;
	}
	return sSide;
}

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

	return year.substr(-4) + '-' + month.substr(-2) + '-' + day.substr(-2) + ' ' + h.substr(-2) + ':' + m.substr(-2) + ':' + s.substr(-2) + '.' + ms.substr(-3);
}

function initlog(logfile)
{
	if ( typeof(logfile) != "undefined" ) {
		try {
			var oLogFile = new File( logfile );
			oLogFile.remove();
		}
		catch(err) {
			app.consoleerr(	"Error: " + err.name + " - " + err.message + " Source: IDPreview.js#" + err.Line);
		}
	}
}

function wwlogtofile ( strLogText )
{
	if ( typeof(logfile) != "undefined" ) {
		try {

			var oLogFile = new File( logfile );

			if ( oLogFile.open( "a" ))
			{
				oLogFile.writeln( "[" + getDateShort() + "] " + strLogText );
				oLogFile.close();
			}

			app.wwlog( "WebEdit", LogLevelOptions.INFO, strLogText );
		}
		catch(err) { // could not write loglines..., not so serious
			app.consoleerr(	"Error: " + err.name + " - " + err.message + " Source: IDPreview.js#" + err.Line);
		}
	}
}

function wwlog( logmode, strLogText )
{
	if ( logmode == CONSOLE || logmode == ERROR || DEBUGLEVEL >= LOG_CONSOLE ) {
		try {
			if ( logmode != 'ERROR' ) {
				app.consoleout(strLogText);
			} else {
				app.consoleerr(strLogText);
			}
		}
		catch(err) { // for debugging with InDesign Client
			$.writeln( '[' + logmode + ']' + strLogText );
		}
	}
	if ( DEBUGLEVEL >= LOG_FILE ) {
		wwlogtofile ( '[' + logmode + '] ' + strLogText );
	}
}

function wwlogError( strLogText )
{
	try {
		app.consoleerr(strLogText);
	}
	catch(err) { // for debugging with InDesign Client
		$.writeln( strLogText );
	}
	wwlogtofile ( strLogText );
}

function logSystemInfo ()
{
	if ( typeof(logfile) != "undefined" ) {
		wwlog( INFO, 'InDesign Server version=[v' + app.version + ']' );
		var oProducts = app.products;

		// walk through all installed products
		for( var i=0; i<oProducts.length; i++ )
		{
			with( oProducts.item(i) ) // expose props: name, version and activationState
			{
				var sState = "";
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
				wwlog( INFO, 'Installed plugin: [' + name + '] version=[' + version + '] state=[' + sState + ']' );
			}
		}
	}
}

function getPageName( oPage )
{
	if( oPage.appliedSection ) {
		return oPage.appliedSection.name + oPage.name;
	} else {
		return oPage.name;
	}
}

/**
 * Searches through the installed products in IDS for an active Smart Connection installation.
 *
 * @returns object|null The found Smart Connection product, else null.
 */
function getActiveSmartConnectionProduct()
{
	// walk through all installed products looking for the Smart Connection product key.
	var foundProduct = null;
	for( var i=0; i < app.products.length; i++ )
	{
		var thisProduct = app.products.item(i);
		var prodName = thisProduct.name;
		if( prodName.length > 4 &&
				prodName.substring( 0, 4 ) == "SCID" && !isNaN( prodName.substring( 4 ) ) ) // e.g. "SCID900"
		{
			// Anything but none means activated, this includes a demo.
			if( thisProduct.activationState != ActivationStateOptions.none ) {
				foundProduct = thisProduct;
			}
			break;
		}
	}
	return foundProduct;
}

/**
 * Replaces characters ('&', '<', '>', '"', ''') with a special meaning to XML_markup with their entities.
 *
 * @param str Character string.
 * @returns Character string without special characters.
 */
function htmlEntities( str )
{
	return str.replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' ).replace( /'/g,'&apos' );
}

///////////////////////////////////////////////////////////////////////////////////////////////

//
// MAIN
//

var isSC10plus = ("enableGeneratingPreview" in app); // introduced in SC10
if( "serverSettings" in app && "imagePreview" in app.serverSettings ) {
	app.serverSettings.imagePreview = true;
}

var xmlArgs = app.scriptArgs.get("XMLParams");
var xmlObj = new XML(xmlArgs);

var editionId = String( xmlObj.editionId );
var editionName = String( xmlObj.editionName );
var previewfile = String( xmlObj.previewfile );
var templatefile = String( xmlObj.template );
var previewType = String( xmlObj.previewType ); // v7.6 feature: 'page' or 'spread'
var getRelations = String( xmlObj.getRelations );

var dumpfile = String( xmlObj.dumpfile );
var exportType = String( xmlObj.exportType );
var logfile = String( xmlObj.logfile );

var layoutID = String( xmlObj.layoutID );
var layoutVersion = String( xmlObj.layoutVersion );
var layoutPath = String( xmlObj.layoutPath );

var articleIDS = String( xmlObj.articleIDS).split(',');
var articlePaths = String( xmlObj.articlePaths).split(',');
var guidsOfChangedStoriesCsv = String( xmlObj.guidsOfChangedStoriesCsv );

var ticketID = String( xmlObj.ticketID );
var userId = String( xmlObj.userId );
var appServer = String( xmlObj.appServer );

function main()
{
	loginWatch = new StopWatch();
	oversetWatch = new StopWatch();
	openfileWatch = new StopWatch();
	genPagesWatch = new StopWatch();
	frameIterWatch = new StopWatch();
	cleanupWatch = new StopWatch();
	totalsWatch = new StopWatch();

	try {
		totalsWatch.start();
		wwlog( CONSOLE, ">>> Start IDPreview.js");

		// If no logfile parameter supplied, no logging takes place.
		initlog(logfile);
		logSystemInfo();

		wwlog( INFO, "Param: editionId: [" + editionId + "]");
		wwlog( INFO, "Param: editionName: [" + encodeURIComponent(editionName) + "]"); // BZ#7341 (encode edition)
		wwlog( INFO, "Param: previewfile: [" + previewfile + "]");
		wwlog( INFO, "Param: templatefile: [" + templatefile + "]");
		wwlog( INFO, "Param: previewType: [" + previewType + "]");
		wwlog( INFO, "Param: getRelations: [" + getRelations + "]");

		wwlog( INFO, "Param: dumpfile: [" + dumpfile + "]");
		wwlog( INFO, "Param: exportType: [" + exportType + "]");
		wwlog( INFO, "Param: logfile: [" + logfile + "]");

		wwlog( INFO, "Param: layoutID: [" + layoutID + "]");
		wwlog( INFO, "Param: layoutVersion: [" + layoutVersion + "]");
		wwlog( INFO, "Param: layoutPath: [" + layoutPath + "]");

		for( var i = 0; i < articleIDS.length; i++ ){
			wwlog( INFO, "Param: articleID: [" + articleIDS[i] + "]");
			wwlog( INFO, "Param: articlePath: [" + articlePaths[i] + "] for articleID:" + articleIDS[i] );
		}
		wwlog( INFO, "Param: guidsOfChangedStoriesCsv: [" + guidsOfChangedStoriesCsv + "]");

		wwlog( INFO, "Param: ticketID: [" + ticketID + "]");
		wwlog( INFO, "Param: userId: [" + userId + "]");
		wwlog( INFO, "Param: appServer: [" + appServer + "]");

		var workspacePath = File(dumpfile).parent;
		wwlog( INFO, "Workspace folder: [" + workspacePath + "]");

		// An activated Smart Connection installation is a must have.
		var scProd = getActiveSmartConnectionProduct();
		if( scProd ) {
			wwlog( INFO, "Smart Connection " + scProd.name + " is activated." );
		} else {
			wwlog( ERROR, "Smart Connection has not been activated. Processing stopped." );
			throw( "Smart Connection has not been activated." );
		}

		// Tell SCE that we are in the preview mode.
		if( isSC10plus ) {
			app.enableGeneratingPreview( workspacePath );
		} else { // SC8 (and SC9)
			app.generatingPreview = true;
		}
	}
	catch( e ) {
		wwlog( ERROR, "Error #0: " + e.name + " - " + e.message + " Source: IDPreview.js#" + e.line );
		throw( e );
	}

	// Login to the app server. Use the quick login to prevent the retrieval
	// of the publication structure, etc.
	try
	{
		var myDoc = null;

		loginWatch.start();
		// Optimization. If the user currently logged in is the same as the one that logged in
		// previously, then we can skip logging in. 
		if( app.entSession.activeServer != '' && "activeUser" in app.entSession && app.entSession.activeUser != '' ) {
			// Get the name of the currently logged in user
			var lastServer = app.entSession.activeServer;
			var lastUserId = app.entSession.activeUser;
			wwlog( INFO, "Last server: [" + lastServer + "]");
			wwlog( INFO, "Last user: [" + lastUserId + "]");
			if( lastUserId != userId || lastServer != appServer ) {
				// Different user, do a log out and fork the login
				wwlog( INFO, "Different user or server, perform log out and log in");
				app.entSession.logout();
				var requestInfo = [ "ServerInfo", "Publications->FeatureAccessList", "FeatureProfiles" ];
				app.entSession.forkLogin( userId, ticketID, appServer, true, requestInfo ); // true = fast logon
			} else {
				wwlog( INFO, "Same user and server, reusing session");
			}
		} else {
			// Either the session does not support the activeUser property (before 7.4), or
			// there was no logged in user
			wwlog( INFO, "New instance, log in");
			var requestInfo = [ "ServerInfo", "Publications->FeatureAccessList", "FeatureProfiles" ];
			app.entSession.forkLogin( userId, ticketID, appServer, true, requestInfo ); // true = fast logon
		}
		loginWatch.stop();

		// Set the paths of the objects that should not be retrieved from the server
		if( isSC10plus ) {
			var artArr = new Array();
			for( var k = 0; k < articleIDS.length; k++ ){
				artArr[artArr.length] = [ articleIDS[k], articlePaths[k] ];
			}
			app.setArticleFileArray( artArr );
		} else { // SC8 (and SC9)
			for( var k = 0; k < articleIDS.length; k++ ){
				if( articleIDS[k] != "" && articlePaths[k] != "" ){
					app.insertLabel( articleIDS[k], articlePaths[k] );
				}
			}
		}

		// For SC8 (and SC9), indicate where to find the local layout file
		// that was already retrieved by Enterprise Server from filestore.
		if( !isSC10plus && layoutID != "" && layoutPath != "" ) {
			app.insertLabel( layoutID, layoutPath );
		}

		// Open
		openfileWatch.start();
		try
		{
			var pageRange = '';
			var layoutVersionUpdated = false;

			// Open file
			if( layoutID ) {
				wwlog( CONSOLE, "#Open documents: " + app.documents.length );
				wwlog( CONSOLE, "Action: Opening InDesign object [" + layoutID + "]");

				// [SCE 6.0] Opening a layout the 'SCE 6.0' way.
				myDoc = app.openObject( layoutID, false, false, "Layout" ); // false = readonly

				if (myDoc == null) {
					throw( "Cannot open InDesign object [" + layoutID + "]");
				}

				// When layout version mismatches, the app.openObject() has opened a newer version
				// of the layout than used for previous preview operation. In that case, we need to
				// re-compose ALL stories, to make sure write-to-fit and preview info is accurate.
				if( myDoc.entMetaData.get("Version") != layoutVersion ) {
					wwlog( CONSOLE, "The previous preview operation was done on layout version [" + layoutVersion + "] " +
							"but now requested to preview different layout version [" + myDoc.entMetaData.get("Version") + "]. " +
							"Therefore clearing the guidsOfChangedStoriesCsv parameter to make sure ALL stories re-composed. "
					);
					guidsOfChangedStoriesCsv = '';
					layoutVersionUpdated = true;
				}
			} else if( templatefile ) {
				wwlog( CONSOLE, "Action: Opening article template file: " + templatefile + "");
				myDoc = app.openArticle( File(templatefile), articleIDS[0] );

				if (myDoc == null) {
					throw( "Cannot open article template file: " + templatefile );
				}
			} else {
				throw( "No file specified." );
			}
		}
		catch( e )
		{
			wwlog( ERROR, "Error #1: " + e.name + " - " + e.message + " Source: IDPreview.js#" + e.line );

			// No opened document, bail out entirely
			throw(e);
		}
		openfileWatch.stop();


		// Compose
		try
		{
			var guidsOfChangedStories = guidsOfChangedStoriesCsv.split(",");

			// return width / height properties in 'points' unit
			myDoc.viewPreferences.horizontalMeasurementUnits = MeasurementUnits.points;
			myDoc.viewPreferences.verticalMeasurementUnits = MeasurementUnits.points;
			if ( editionName ){ // edition name is used to activate edition
				wwlog(INFO, "Current edition: [" + encodeURIComponent(myDoc.activeEdition) +"]"); // BZ#7341 (encode edition)	
				if ( myDoc.activeEdition != editionName && typeof(myDoc.activeEdition) != "undefined" ) {
					wwlog(INFO, "Activate edition: [" + encodeURIComponent(editionName) + "]"); // BZ#7341 (encode edition)
					myDoc.activeEdition = editionName;
				}
			}
			wwlog( CONSOLE, "Action: Get Compose Data...");
			var arrPages = new Array();
			var arrPageSequences = new Array();
			var arrUnlockedStories = new Array(); // collect stories that are unlocked by us (and so require restore lock)
			// prepare pagesequence array
			for(var p=0;p<myDoc.pages.length;p++){
				arrPageSequences[myDoc.pages[p].id] = p+1;
			}
			dump = new File(dumpfile);
			dump.open("w");
			dump.encoding = 'UTF-8';
			dump.write( '<?xml version="1.0" encoding="UTF-8"?>\n' ); // EN-86609
			dump.write( '<textcompose>\n' );
			dump.write( '\t<context ' +
					'editionid="' + editionId + '" ' +
					'exporttype="' + exportType + '" ' +
					'previewtype="' + previewType + '">\n'
			);
			if( layoutID ) {
				dump.write( '\t\t<layout ' +
						'id="' + layoutID + '" ' +
						'name="' + htmlEntities( myDoc.entMetaData.get( "Core_Name" )) + '" ' +
						'version="' + myDoc.entMetaData.get("Version") + '" />\n'
				);
			}
			dump.write( '\t</context>\n' );

			dump.write( '\t<stories>\n' );
			frameIterWatch.start();
			try
			{
				// Walk through the list of articles in the opened document to find (based on the ID) 
				// the article we're editing in WebEditor. 
				var mas = myDoc.managedArticles;
				wwlog(INFO, "#articles=" + mas.length );
				for( var artIdx = 0; artIdx < mas.length; ++artIdx )
				{
					wwlog(INFO, "  article #" + artIdx );
					var ma = mas.item(artIdx);
					var md = ma.entMetaData;
					if( !md.has( "Core_ID" ) )
						continue;

					var curId = md.get( "Core_ID" );
					if( in_array( articleIDS, curId ) )
					{
						wwlog(INFO, "  Found article (curId="+curId+").");
						// Got the article. Now walk through the components to find the pages. The
						// list of components consists of stories and pageitems
						var comps = ma.components;
						wwlog(INFO, "  Article #comps=" + comps.length );
						for( var j = 0; j < comps.length; ++j )
						{
							var comp = comps[j];
							wwlog(INFO, "    -- Component " + j + " --" );
							// Now build a list of pages the components of the article run through.
							// comp can be a PageItem derived class or a Story. Check here for story otherwise
							// it's a PageItem derived item.
							wwlog(INFO, "    Type=" + comp + " (" + j + ")" );
							if( comp instanceof Story )
							{
								if( guidsOfChangedStoriesCsv.length > 0 && // empty means: update all
										!in_array( guidsOfChangedStories, comp.guid ) ) {
									wwlog(INFO, "    Skipping unchanged story: " + comp.guid );
								}
								else
								{
									if( guidsOfChangedStoriesCsv.length == 0 ) {
										wwlog(INFO, "    Processing story: " + comp.guid );
									} else {
										wwlog(INFO, "    Processing changed story: " + comp.guid );
									}
									var tfs = comp.textContainers;
									if( tfs == undefined )
										continue;

									wwlog(INFO, "    # text frames:   "+ tfs.length);

									// Unlock the story, which is required for getOverset() function which fills frames with
									// placeholder text to do proper underset/overset calculations.
									// >>> BZ#13734: Took out unlocking/locking stories from getOverset() function.
									// For 1 article with 100 stories (single frames) it took 14 seconds to set/unset the locks!
									// This is because it did loop through all 100 stories (comps), removes lock and sets back.
									// At IDS plugins, it loops through the 100 stories too (!!) to reflect this single change to all.
									// So basically, it did 100x100 mutations, which took time. Now we remember which we changed and
									// we do NOT set back immediately. In the scenario, the script changes the first story, the plugins
									// reflect that into the 100 stories of the article. The second frame is then already unlocked
									// implicitly and so it won't trigger the script since it checks the lock first.
									var bOldLock = comp.textLock;
									wwlog( INFO, "    Info: Story locked: " + bOldLock );
									if( bOldLock ) {
										comp.textLock = false;
										arrUnlockedStories.push(comp);
									}
									// <<<
									oversetWatch.start();
									var fitInfo = getOverset(comp);
									oversetWatch.stop();

									dump.write( '\t\t<story guid="' +  comp.guid + '" label="' + escape(comp.storyTitle) +
											'" words="' + comp.words.length + '" chars="' + comp.characters.length +
											'" lines="' + comp.lines.length + '" paras="' + comp.paragraphs.length +
											'" type="' + fitInfo[0] + '" value="' + fitInfo[1] + '" length="' + fitInfo[2] + '">\n'); // escape(): BZ#27285/BZ#26670

									var arrStoryPages = new Array();
									var arrPageNames = new Array();
									dump.write( '\t\t\t<textframes>\n');
									try
									{
										for( var tfIdx = 0; tfIdx < tfs.length; ++tfIdx )
										{
											wwlog( INFO, "      Info: text frame #" + tfIdx );
											var oPage = tfs[tfIdx].parentPage;
											if(oPage) {
												/*	The document's ZeroPoint has to be set to the [0,0] ZeroPoint the WebEditor uses.
												 Otherwise article/image/etc. frames will be positioned outside the preview view while
												 the article itself is positioned right.
												 After calculation the original ZeroPoint is restored.
												 */
												var uZeroPoint = myDoc.zeroPoint;	// Store the current document's ZeroPoint.
												myDoc.zeroPoint = [0,0];					// Set ZeroPoint to WebEditor's ZeroPoint.

												var frameData = tfs[tfIdx].frameData; // Since SC CS4 v7.3.4 build 295/SC CS5 v7.3.4 build 293 and above
												var idArticleIdsCsv = "";
												if( typeof( tfs[tfIdx].allIndesignArticleIds ) != "undefined" ) { // Since SC 10.2
													idArticleIdsCsv = tfs[tfIdx].allIndesignArticleIds.join(',');
												}
												var frameHasTiles = false;
												var frameDataObj  = eval("(" + frameData + ")");
												for( var frameIdx = 0; frameIdx < frameDataObj.length; frameIdx++ ) {
													dump.write( '\t\t\t\t<textframe guid="' + frameDataObj[frameIdx].ElementID + '" frameid="' + tfs[tfIdx].id + '" ' +
															'frameorder="' + frameDataObj[frameIdx].FrameOrder + '" pagesequence="' + frameDataObj[frameIdx].PageSequence + '" ' +
															'pagenr="' + escape(frameDataObj[frameIdx].PageNumber) + '" layer="' + escape(frameDataObj[frameIdx].Layer) + '" ' +
															'ypos="' + frameDataObj[frameIdx].Top + '" xpos="' + frameDataObj[frameIdx].Left + '" ' +
															'width="' + frameDataObj[frameIdx].Width + '" height="' + frameDataObj[frameIdx].Height + '" ' +
															'idarticleids="' + idArticleIdsCsv + '" ' +
															'splineid="' + tfs[tfIdx].id + '" frametype="text" ' );
													// Enterprise 7.6 adds support for placement tiles which break up a placement into multiple tiles that lie
													// across page boundaries. This enables the Server and in the end Content Station to display articles that
													// lie across pages (spread support). A tile is bound to a page via a pagesequence. Smart Connection adds
													// the tiles to the Placement object (see WSDL) and thus also to the frameData JSON. The Tiles property
													// is an optional parameter: it only exists when Smart Connection 7.6+ is used and when the placement
													// crosses the page boundaries.
													if( "Tiles" in frameDataObj[frameIdx] ) {
														frameHasTiles = true;
														dump.write( '>\n\t\t\t\t\t<tiles>\n');
														for( var tileIdx = 0; tileIdx < frameDataObj[frameIdx].Tiles.length; ++tileIdx ) {
															dump.write( '\t\t\t\t\t\t<tile pagesequence="' + frameDataObj[frameIdx].Tiles[tileIdx].PageSequence +
																	'" ypos="' + frameDataObj[frameIdx].Tiles[tileIdx].Top + '" xpos="' + frameDataObj[frameIdx].Tiles[tileIdx].Left +
																	'" width="' + frameDataObj[frameIdx].Tiles[tileIdx].Width + '" height="' + frameDataObj[frameIdx].Tiles[tileIdx].Height + '"/>\n');
														}
														dump.write( '\t\t\t\t\t</tiles>\n\t\t\t\t</textframe>\n');
													} else {
														// No tiles, just close the textframe element
														dump.write( '/>\n');
													}
												} // escape(): BZ#27285/BZ#26670
												// Note: 'pagesequence' and 'guid' attributes are redundant, but still there to serve Web Editor.

												myDoc.zeroPoint = uZeroPoint;	// Restore the document's ZeroPoint.

												if( !in_array( arrStoryPages, oPage ) ) {
													arrPageNames.push( getPageName(oPage) );
													arrStoryPages.push(oPage);
												}

												if( !in_array( arrPages, oPage ) ) {
													arrPages.push(oPage);
												}

												// v7.6 feature: In Spread Preview mode, when the article
												// text frame is placed on left page (of the spread),
												// also include the right page (and vice versa).
												// For Single Page preview mode, when there are tiles, the
												// sibling spread page needs to be included as well.
												// For example a 'head' stretched over the whole spread.
												if( previewType == 'spread' || frameHasTiles ) {
													if( oPage.side == PageSideOptions.leftHand ||
															oPage.side == PageSideOptions.rightHand ) {
														if( typeof( oPage.parent ) != "undefined" &&
																oPage.parent instanceof Spread ) {
															var oSpreadPages = oPage.parent.pages;
															for( var iSpreadPage = 0; iSpreadPage < oSpreadPages.count(); ++iSpreadPage ) {
																var oSpreadPage = oSpreadPages.item( iSpreadPage );
																if( oSpreadPage.id != oPage.id ) {
																	wwlog(INFO, "    Including other page (#" + getPageName( oSpreadPage ) + ") of the spread " +
																			"(than the article textframe is placed on) to support spread preview mode." );
																	if( !in_array( arrStoryPages, oSpreadPage ) ) {
																		arrPageNames.push( getPageName( oSpreadPage ) );
																		arrStoryPages.push( oSpreadPage );
																	}

																	if( !in_array( arrPages, oSpreadPage ) ) {
																		arrPages.push( oSpreadPage );
																	}
																}
															}
														}
													}
												}
											}
										}

										wwlog(INFO, "    Story placed on page(s) : " + arrPageNames.sort(sortNumber).join(','));
									}
									catch (e)
									{
										wwlog( ERROR, "    Error #5: " + e.name + " - " + e.message + " Source: IDPreview.js#" + e.line );
									}

									dump.write( '\t\t\t</textframes>\n');
									dump.write( '\t\t</story>\n' );
								}
							}
							else // e.g. graphic frame
							{
								var arrStoryPages = new Array();
								var arrPageNames = new Array();
								var oPage = comp.parentPage;
								if( oPage ) {
									if( !in_array( arrPages, oPage ) ) {
										if( guidsOfChangedStoriesCsv.length == 0 ) {
											wwlog(INFO, "    Including page for non-story page item because of full refresh. Page " + getPageName( oPage ) );
											arrPages.push(oPage);
											// Store the current page into arrStoryPages & arrPageNames
											if( !in_array( arrStoryPages, oPage ) ) {
												arrPageNames.push( getPageName(oPage) );
												arrStoryPages.push(oPage);
											}
										} else { // client already got preview in a previous call
											wwlog(INFO, "    Skipping page for non-story page item because of optimised refresh. Page " + getPageName( oPage ) );
										}
									} else { // page was already collected in this call
										wwlog(INFO, "    Ignoring page for non-story page item because page is already included. Page " + getPageName( oPage ) );
									}
								} else {
									wwlog(INFO, "    Skipping non-story page item because it is placed on pasteboard. " );
								}
								// Fix issue EN-86050: Incomplete preview is shown for graphic article placed as spread on a Layout
								wwlog(INFO, "    Check if graphic item is across pages to include other pages" );
								// Do we have Tiles
								var frameHasTiles = false;
								var frameData = comp.frameData;
								var frameDataObj  = eval("(" + frameData + ")");
								for( var frameIdx = 0; frameIdx < frameDataObj.length; frameIdx++ ) {
									if( "Tiles" in frameDataObj[frameIdx] ) {
										wwlog(INFO, "    We have Tiles " );
										frameHasTiles = true;
										break;
									}
								}
								// On spread view or if we have tiles collect related pages
								if( previewType == 'spread' || frameHasTiles ) {
									if( oPage.side == PageSideOptions.leftHand ||
										oPage.side == PageSideOptions.rightHand ) {
										if( typeof( oPage.parent ) != "undefined" &&
												oPage.parent instanceof Spread ) {
											var oSpreadPages = oPage.parent.pages;
											for( var iSpreadPage = 0; iSpreadPage < oSpreadPages.count(); ++iSpreadPage ) {
												var oSpreadPage = oSpreadPages.item( iSpreadPage );
												if( oSpreadPage.id != oPage.id ) {
													wwlog(INFO, "    Including other page (#" + getPageName( oSpreadPage ) + ") of the spread " +
															"(than the article graphic frame is placed on) to support spread preview mode." );
													if( !in_array( arrStoryPages, oSpreadPage ) ) {
														arrPageNames.push( getPageName( oSpreadPage ) );
														arrStoryPages.push( oSpreadPage );
													}

													if( !in_array( arrPages, oSpreadPage ) ) {
														arrPages.push( oSpreadPage );
													}
												}
											}
										}
									}
									wwlog(INFO, "    Graphic frame placed on page(s) : " + arrPageNames.sort(sortNumber).join(','));
								}
							}
						}
					}
				}
			}
			catch( e )
			{
				wwlog( ERROR, "Error #2.1: '" + e.name + " - " + e.message + "' Source: IDPreview.js line#" + e.line );
			}
			// >>> BZ#13734: Restore the text locks. This is needed or else the document won't close
			// raising the warning that there are documents still checked out. See above for more comments.
			for( var l=0; l<arrUnlockedStories.length; l++ ) {
				arrUnlockedStories[l].textLock = true;
			} // <<<
			dump.write( '\t</stories>\n' );
			frameIterWatch.stop();


			// Export file						
			genPagesWatch.start();
			var arrSortPages = new Array();
			var arrSortPageObjects = new Array();

			dump.write( '\t<pages>\n' );
			try
			{
				// sort all pages of found array by walking through pages ( must be pagesequence )
				for(var pgIdx=0;pgIdx<myDoc.pages.length;pgIdx++)
				{
					oPage = myDoc.pages[pgIdx];
					var finalPageName;
					if ( in_array(arrPages, oPage) )
					{
						var pageSectionName = oPage.appliedSection ? oPage.appliedSection.name : '';
						if ( oPage.name.substr(0,pageSectionName.length) == pageSectionName )
						{
							// section name is allready in pagename ( sections/numbering -> tickbox 'Include prefix when numbering pages' )
							arrSortPages.push(oPage.name);
							arrSortPageObjects.push( { key:pgIdx, value:oPage } );
							finalPageName = oPage.name;
						}
						else
						{
							arrSortPages.push( getPageName(oPage) );
							arrSortPageObjects.push( { key:pgIdx, value:oPage } );
							finalPageName = oPage.name;
						}

						var pageWidth = myDoc.documentPreferences.pageWidth;
						var pageHeight = myDoc.documentPreferences.pageHeight;
						var pageOrder = oPage.appliedSection.pageNumberStart + pgIdx;

						dump.write( '\t\t<page side="' + pageSide2Text(oPage.side) +
								'" name="' + escape(finalPageName) + '" sequence="' + (pgIdx+1) +
								'" width="' + pageWidth + '" height="' + pageHeight +
								'" order= "' + pageOrder  + '"/>\n' );
					}
				}
			}
			catch( e )
			{
				wwlog( ERROR, "Error #2.2: '" + e.name + " - " + e.message + "' Source: IDPreview.js line#" + e.line );
			}

			dump.write( '\t</pages>\n' );
			
			// Since 9.7 request SC for the Object->Relations, Relation->Placements and the Object->Placements
			// because those may change due to ObjectOperation processing. Note that those may NOT be 
			// stored in the DB yet, but reside in the layout in the editor workspace only.
			if( getRelations == 'true' && typeof(myDoc.entWorkflow.relations) != "undefined" ) {			
				dump.write( '\t<layout>' + htmlEntities( myDoc.entWorkflow.relations ) + '</layout>\n' );
			}
			
			dump.write( '</textcompose>\n' );
			dump.close();

			var strPageRange = arrSortPages.join(',');
			wwlog( CONSOLE, "Action: Generating [" + exportType + "] for pages [" + strPageRange + "]");

			exportfile = File(previewfile);
			if(exportType == 'PDF'){
				if ( arrSortPages.length > 0  ) {
					app.pdfExportPreferences.pageRange = strPageRange;
					myDoc.exportFile(ExportFormat.pdfType, exportfile);
				}
				// no pages found, layout does not know anything about this article... should be saved first
				// do not generate pdf in that case
			}
			if(exportType == 'JPEG'){  // multiple pages => multiple jpegs with increasing number in filename before '.jpeg'
				if ( arrSortPages.length > 0 ) {
					// CS5 7.4 has a special method for generating a preview that does not force
					// the download of placed images.
					for( var i = 0; i < arrSortPageObjects.length; ++i ) {
						var pgIdx = arrSortPageObjects[i].key;
						var pg = arrSortPageObjects[i].value;
						// Export is per page. Figure out the file name to use for subsequent
						// exports to match the way exportFile creates names.
						var jpgPath = exportfile;
						// Grab the filename
						var jpgFilename = jpgPath.name;
						// Find the dot of the extension
						var lastDot = jpgFilename.lastIndexOf( '.' );
						// Initialize based on the absence of a dot
						var jpgBasename = jpgFilename;
						var jpgExt = "";
						if( lastDot >= 0 ) {
							// Slice the filename to get the basename and extension
							jpgBasename = jpgFilename.slice( 0, lastDot );
							jpgExt = jpgFilename.slice( lastDot );
						}
						// Add the index to the basename
						jpgBasename += pgIdx+1;
						// Turn the path string into a file object
						jpgPath = new File( jpgPath.parent + '/' + jpgBasename + jpgExt );
						wwlog( INFO, "  Export page #" + pgIdx + " to file: [" + jpgPath + "]");
						pg.exportJPEGPreview( jpgPath, PREVIEW_QUALITY, PREVIEW_RESOLUTION );
					}
				}
				// no pages found, layout does not know anything about this article... should be saved first
				// do not generate jpegs in that case
			}
			genPagesWatch.stop();
		}
		catch( e )
		{
			wwlog( ERROR, "Error #2: " + e.name + " - " + e.message + " Source: IDPreview.js#" + e.line );
		}
	}
	catch( e )
	{
		throw( e );
	}
	finally
	{
		cleanupWatch.start();
		// Clean up
		wwlog( CONSOLE, "Action: Clean-up");

		try
		{
			// Save to reflect updated WCML articles in the local layout, to speedup next previews.
			// Changed stories are then saved and don't need updates over and over again for succeeding previews.
			// This should not be done for articles that are not placed on a layout.
			if( isSC10plus && !templatefile ) { // SC10+ && article is placed
				myDoc.save( myDoc.fullName );
			}

			// Close all documents without saving.
			while( app.documents.length > 0 ) {
				app.documents.item(0).close( SaveOptions.NO );
			}
		}
		catch(e) {}

		try
		{
			// Clear the globally set labels again.
			if( !isSC10plus ) { // SC8 (and SC9)
				for( var s = 0; s < articleIDS.length; s++ ){
					if( articleIDS[s] != "" ){
						app.insertLabel( articleIDS[s], "" );
					}
				}
				if( layoutID != "" ) {
					app.insertLabel( layoutID, "" );
				}
			}

			// When login optimization was not possible, a log out is needed.
			if( !("activeUser" in app.entSession) && app.entSession.activeServer != '' ) {
				app.entSession.logout();
			}
		}
		catch(err)
		{}

		if( isSC10plus ) {
			app.disableGeneratingPreview(); // this also clears app.setArticleFileArray()
		} else { // SC8 (and SC9)
			app.generatingPreview = false;
		}

		cleanupWatch.stop();
		totalsWatch.stop();
		wwlog( CONSOLE, "<<< Ready, IDPreview took [" + totalsWatch.getDuration() + "] sec");

		wwlog(INFO, "Performance: Duration login: [" + loginWatch.getDuration() + "] sec");
		wwlog(INFO, "Performance: Duration opening document: [" + openfileWatch.getDuration() + "] sec");
		wwlog(INFO, "Performance: Duration text frame iteration: [" + frameIterWatch.getDuration() + "] sec, which includes overset calculation: [" + oversetWatch.getDuration() + "] sec");
		wwlog(INFO, "Performance: Duration PDF/preview generation: [" + genPagesWatch.getDuration() + "] sec");
		wwlog(INFO, "Performance: Duration cleanup and logout: [" + cleanupWatch.getDuration() + "] sec");
		wwlog(INFO, "Performance: Total execution time: [" + totalsWatch.getDuration() + "] sec");
		wwlog(INFO, "Performance: 1: [" + tmp1.getDuration() + "] sec");
		wwlog(INFO, "Performance: 2: [" + tmp2.getDuration() + "] sec");
		wwlog(INFO, "Performance: 3: [" + tmp3.getDuration() + "] sec");
	}
}

app.doScript( main, ScriptLanguage.JAVASCRIPT, null, UndoModes.FAST_ENTIRE_SCRIPT, "Generate Preview" );
//main();

// just in case
exit(0);
