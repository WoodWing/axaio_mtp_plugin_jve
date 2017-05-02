/**
 InDesign Server integration to support three web editing features for articles:
 - Write-To-Fit (per text frame, also called Hyphenate & Justify / H&J / Compose)
 - Preview (per page or spread, only pages the article is placed on)
 - PDF generation (all pages, only pages the article is placed on)

 This IDPreview.js module is compatible with:
 - SC8 plug-ins for InDesign Server CS6
 - SC10 plug-ins for InDesign Server CC 2014
 - SC11 plug-ins for InDesign Server CC 2015

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
// const DEBUGLEVEL = 0;

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
/**
 * Taken from
 *   https://github.com/douglascrockford/JSON-js/blob/master/json2.js @ 031b1d9e6971bd4c433ca85e216cc853f5a867bd
 *
 * through
 *   http://www.json.org
 */


function installJSON() {
    app.consoleout('### Installing JSON');
    if (typeof JSON !== 'object') {
        app.consoleout('### Defining JSON');
        JSON = {};
    }

    if (typeof JSON.stringify !== 'function' || typeof JSON.parse !== 'function') {
        (function () {
            'use strict';

            var rx_one = /^[\],:{}\s]*$/;
            var rx_two = /\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g;
            var rx_three = /"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g;
            var rx_four = /(?:^|:|,)(?:\s*\[)+/g;
            var rx_escapable = /[\\"\u0000-\u001f\u007f-\u009f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g;
            var rx_dangerous = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g;

            function f(n) {
                // Format integers to have at least two digits.
                return n < 10
                    ? '0' + n
                    : n;
            }

            function this_value() {
                return this.valueOf();
            }

            if (typeof Date.prototype.toJSON !== 'function') {

                Date.prototype.toJSON = function () {

                    return isFinite(this.valueOf())
                        ? this.getUTCFullYear() + '-' +
                        f(this.getUTCMonth() + 1) + '-' +
                        f(this.getUTCDate()) + 'T' +
                        f(this.getUTCHours()) + ':' +
                        f(this.getUTCMinutes()) + ':' +
                        f(this.getUTCSeconds()) + 'Z'
                        : null;
                };

                Boolean.prototype.toJSON = this_value;
                Number.prototype.toJSON = this_value;
                String.prototype.toJSON = this_value;
            }

            var gap;
            var indent;
            var meta;
            var rep;


            function quote(string) {
                rx_escapable.lastIndex = 0;
                return rx_escapable.test(string) ? '"' + string.replace(rx_escapable, function (a) {
                        var c = meta[a];
                        return typeof c === 'string'
                            ? c
                            : '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
                    }) + '"' : '"' + string + '"';
            }

            function str(key, holder) {

                var i;          // The loop counter.
                var k;          // The member key.
                var v;          // The member value.
                var length;
                var mind = gap;
                var partial;
                var value = holder[key];

                if (value && typeof value === 'object' &&
                    typeof value.toJSON === 'function') {
                    value = value.toJSON(key);
                }

                if (typeof rep === 'function') {
                    value = rep.call(holder, key, value);
                }

                switch (typeof value) {
                    case 'string':
                        return quote(value);
                    case 'number':
                        return isFinite(value) ? String(value) : 'null';
                    case 'boolean':
                    case 'null':
                        return String(value);
                    case 'object':
                        if (!value) {
                            return 'null';
                        }
                        gap += indent;
                        partial = [];
                        if (Object.prototype.toString.apply(value) === '[object Array]') {
                            length = value.length;
                            for (i = 0; i < length; i += 1) {
                                partial[i] = str(i, value) || 'null';
                            }
                            v = partial.length === 0 ? '[]' : gap ? '[\n' + gap + partial.join(',\n' + gap) + '\n' + mind + ']' : '[' + partial.join(',') + ']';
                            gap = mind;
                            return v;
                        }

                        if (rep && typeof rep === 'object') {
                            length = rep.length;
                            for (i = 0; i < length; i += 1) {
                                if (typeof rep[i] === 'string') {
                                    k = rep[i];
                                    v = str(k, value);
                                    if (v) {
                                        partial.push(quote(k) + ( gap ? ': ' : ':' ) + v);
                                    }
                                }
                            }
                        } else {

                            for (k in value) {
                                if (Object.prototype.hasOwnProperty.call(value, k)) {
                                    v = str(k, value);
                                    if (v) {
                                        partial.push(quote(k) + ( gap ? ': ' : ':' ) + v);
                                    }
                                }
                            }
                        }

                        v = partial.length === 0
                            ? '{}'
                            : gap
                                ? '{\n' + gap + partial.join(',\n' + gap) + '\n' + mind + '}'
                                : '{' + partial.join(',') + '}';
                        gap = mind;
                        return v;
                }
            }

            if (typeof JSON.stringify !== 'function') {
                app.consoleout('### Defining JSON.stringify');
                meta = {    // table of character substitutions
                    '\b': '\\b',
                    '\t': '\\t',
                    '\n': '\\n',
                    '\f': '\\f',
                    '\r': '\\r',
                    '"': '\\"',
                    '\\': '\\\\'
                };
                JSON.stringify = function (value, replacer, space) {

                    var i;
                    gap = '';
                    indent = '';

                    if (typeof space === 'number') {
                        for (i = 0; i < space; i += 1) {
                            indent += ' ';
                        }
                    } else if (typeof space === 'string') {
                        indent = space;
                    }

                    rep = replacer;
                    if (replacer && typeof replacer !== 'function' &&
                        (typeof replacer !== 'object' ||
                        typeof replacer.length !== 'number')) {
                        throw new Error('JSON.stringify');
                    }

                    return str('', {'': value});
                };
            }

            if (typeof JSON.parse !== 'function') {
                app.consoleout('### Defining JSON.parse');
                JSON.parse = function (text, reviver) {
                    var j;

                    function walk(holder, key) {
                        var k;
                        var v;
                        var value = holder[key];
                        if (value && typeof value === 'object') {
                            for (k in value) {
                                if (Object.prototype.hasOwnProperty.call(value, k)) {
                                    v = walk(value, k);
                                    if (v !== undefined) {
                                        value[k] = v;
                                    } else {
                                        delete value[k];
                                    }
                                }
                            }
                        }
                        return reviver.call(holder, key, value);
                    }

                    text = String(text);
                    rx_dangerous.lastIndex = 0;
                    if (rx_dangerous.test(text)) {
                        text = text.replace(rx_dangerous, function (a) {
                            return '\\u' +
                                ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
                        });
                    }

                    if (
                        rx_one.test(
                            text
                                .replace(rx_two, '@')
                                .replace(rx_three, ']')
                                .replace(rx_four, '')
                        )
                    ) {

                        j = eval('(' + text + ')');

                        return typeof reviver === 'function'
                            ? walk({'': j}, '')
                            : j;
                    }

                    throw new SyntaxError('JSON.parse');
                };
            }
        }());
    }
}


///////////////////////////////////////////////////////////////////////////////////////////////

//
// Functions
//

function getOverset(story)
{
    wwlog(INFO, "      Action: Get Overset");

    var textContainers = story.textContainers;
    var lastFrame = textContainers[textContainers.length - 1];
    // overset is easy: if last frame has overset:
    if( textContainers.length > 0 && lastFrame.overflows )
    {
        wwlog(INFO, "      Info: Story has overset");
        // Overset, calculate number of lines by checking #overset chars
        // and number dividing chars/lines of non-overset

        // we get characters via texts to be Smart Layout compatible:
        var texts = lastFrame.texts;
        if( texts.length > 0 )
        {
            if (  texts[0].characters.length > 0 )
            {
                // Last frame contains some text, any character that is beyond the last character of this frame is overset text.
                wwlog(INFO, "      Info: Last frame contains some text");
                lastText = texts[texts.length-1];
                lastChar = lastText.characters[lastText.characters.length-1];
                numOversetChars = story.characters.length - lastChar.index-1;

                lines = getTotalLines( textContainers );

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
                var frameCount = textContainers.length;
                if ( frameCount > 1 )
                {
                    // There are previous threaded frames before the last frame (that contains no text). Any character that
                    // is beyond that previous frame (index of frameCount -2) is overset text.
                    lastFrame = textContainers[ frameCount - 2];
                    lastText = texts[texts.length-1];
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
        var itemLink = story.itemLink;
        if( itemLink )
        {
            if (itemLink.status == LinkStatus.linkMissing)
            {
                wwlog(ERROR, "      Error: link missing " + itemLink.name);
                //	basedocpath = myDoc.filePath;
                //	newpath = basedocpath + ":__" + story.itemLink.name;
                //	wwlog("Fix link to: " + newpath);
                //	story.itemLink.relink(newpath);
                //	story.itemLink.update();
            }
        }
        actualLines = getTotalLines( textContainers );
        try {
            // Record the index of the last character + 1. This will
            // be the index of the first character to remove
            var endBefore = story.length;

            var texts = story.texts;
            if( texts.length > 0 )
            {
                // Always insert dummy paragraph after the last paragraph.
                var nLastParagraph = (texts[0].paragraphs.length - 1);
                if( nLastParagraph >= 0 ) { // Bugfix: Avoid reference error for BZ#11869
                    // Add a new paragraph with a space (' ') so last inline object
                    // does not get removed.
                    texts[0].paragraphs[nLastParagraph].insertionPoints[-1].contents += '\r ';
                }
            }

            // Work around for EN-88022/EN-88126: Remember paragraph composer, reset it, and set it again.
            var lastParagraphStyle = story.insertionPoints[-1].appliedParagraphStyle;
            var origComposer = lastParagraphStyle.composer;
            var bChangedComposer = false;
            if (origComposer.indexOf("World") != -1 )
            {
                lastParagraphStyle.composer = "Adobe Paragraph Composer";
                bChangedComposer = true;
            }
            // end of first part of work around for EN-88002/EN-88126

            // Fill the story with dummy text
            fillWithDummyText( story, textContainers );

            // Determine the number of lines of underset
            maxLines = getTotalLines( textContainers );
            underset = maxLines - actualLines;

            // Remove the text added by the fill. Don't remove the end of story character!
            var endAfter = story.length-1;
            var charsToRemove = story.characters.itemByRange(endBefore, endAfter);
            charsToRemove.remove();

            // Revert paragraph composer, if it was changed (EN-88002/EN-88126).
            if ( bChangedComposer )
            {
                lastParagraphStyle.composer = origComposer;
            }
            // end of second part of work around for EN-88002/EN-88126
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
function fillWithDummyText( story, textContainers )
{
    wwlog(INFO, "      Action: Fill with dummy text");
    wwlog(INFO, "      DEBUG: textContainers.length = " + textContainers.length);
    var paragraphs = story.paragraphs;
    wwlog(INFO, "      DEBUG: paragraphs.length = " + paragraphs.length);

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
    while( textContainers.length > 0 && !textContainers[textContainers.length-1].overflows )
    {
        // Apply the pragraphstyle with cleared overrides to the end of the content.
        story.insertionPoints[-1].applyParagraphStyle( lastParagraphStyle, true );

        var target = story;

        if( paragraphs.length > 0 )
        {
            target = paragraphs.lastItem();
        }

        target.contents += dummyText;
    }

    wwlog(INFO, "	   Action: End filling");
}


function getTotalLines( textContainers )
{
    // story.lines does not give actual lines, but lines up till what has been composed.
    // this typically included a piece of overset, so get actual lines different way:
    lines = 0;
    wwlog(INFO, "      Action: Get total lines");
    try {
        var textContainersLength = textContainers.length;
        for( var tf = 0; tf < textContainersLength; ++tf ) {
            fram = textContainers[tf];
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

/**
 * Detect version of Smart Connection plug-ins and pass back integer array in format [major, minor, patch]
 */
function getPluginVersion ()
{
    var pluginVersion;
    var oProducts = app.products;
    var oProductsLen = oProducts.length;

    // walk through all installed products
    for( var i = 0; i < oProductsLen; i++ )
    {
        with( oProducts.item(i) ) // expose props: name, version and activationState
        {
            // v10.2.6 DAILY build 1020
            var versionString = version;
            // versionString = "8.3.20";

            var parsedVersion = versionString.split('.');
            if ( parsedVersion.length > 2 )
            {
                var majorStr = parsedVersion[0];
                // get rid of the 'v' and convert to int
                if ( majorStr[0] == 'v' )
                {
                    majorStr = majorStr.substr(1, majorStr.length);
                }
                var major = parseInt( majorStr );
                var minor = parseInt( parsedVersion[1] );
                // get rid of ' build ...' and convert to int
                var parsedPatch = parsedVersion[2].split(' ');
                var patch = parseInt( parsedPatch[0] );
                if ( !isNaN(major) && !isNaN(minor) && !isNaN(patch) )
                {
                    return [ major, minor, patch];
                }
            }
        }
    }
    return [0,0,0];
}

/**
 * Find out whether to use the old scripting method to find copyfit information, or to rely on the data coming from the scripting call to Smart Connection.
 * Smart Connection >= 10.2.6 and >= 11.0.5 contain the updated copyfit calculation.
 */
function copyfitBySC(version)
{
    var bCopyFitBySC = false;

    // version > 10.2.5
    if ( version[0] == 10 && ( (version[1] == 2 && version[2] > 5) || (version[1] > 2) ) )
    {
        bCopyFitBySC = true;
    }
    // version > 11.0.4
    else if ( version[0] == 11 && ( (version[1] == 0 && version[2] > 4) || (version[1] > 0) ) )
    {
        bCopyFitBySC = true;
    }
    else if ( version [0] > 11 )
    {
        bCopyFitBySC = true;
    }

    return bCopyFitBySC;
}


/**
 * Find out whether to save the layout in this script because of performed object operations.
 * Smart Connection > 10.2.7 and > 11.0.5 will save the processed object operations
 */
function saveRequired(version)
{
    var returnValue = true;

    if ( version[0] == 10 && ( (version[1] == 2 && version[2] > 7) || (version[1] > 2) ) ) {
        // version > 10.2.7
        returnValue = false;
    } else if ( version[0] == 11 && ( (version[1] == 0 && version[2] > 5) || (version[1] > 0) ) ) {
        // version > 11.0.5
        returnValue = false;
    }
    else if ( version [0] > 11 ) {
        // version >= 12.0
        returnValue = false;
    }

    return returnValue;
}

// checks if item is in array ( like PHP )
function in_array (arr, el)
{
    arrLen = arr.length;
    for (var i = 0; i < arrLen; i++) {
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
    // TODO make an array with the values and join them together in the end for a better performance
}

function isLogEnabled() {
    return (DEBUGLEVEL >= LOG_FILE);
}

function wwlog( logmode, strLogText ) {
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
    if ( isLogEnabled() ) {
        logLines.push(["[", getDateShort(), "] ", strLogText].join(""));
        app.wwlog( "WebEdit", LogLevelOptions.INFO, strLogText );
    }
}

function logSystemInfo () {
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

function getPageName( oPage ) {
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
var logLines = [];

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
    installJSON();

    var loginWatch = new StopWatch();
    var oversetWatch = new StopWatch();
    var openfileWatch = new StopWatch();
    var genPagesWatch = new StopWatch();
    var frameIterWatch = new StopWatch();
    var cleanupWatch = new StopWatch();
    var totalsWatch = new StopWatch();
    var shouldLog = isLogEnabled();

    var pluginVersion = getPluginVersion();

    // Keep an array of log strings that should be returned to the caller
    var composeData = new Array();

    try {
        totalsWatch.start();
        wwlog( CONSOLE, ">>> Start IDPreview.js");
        var workspacePath = File(dumpfile).parent;

        if (shouldLog) {
            logSystemInfo();

            wwlog( INFO, "Param: editionId: [" + editionId + "]");
            wwlog( INFO, "Param: editionName: [" + encodeURIComponent(editionName) + "]"); // BZ#7341 (encode edition)
            wwlog( INFO, "Param: previewfile: [" + previewfile + "]");
            wwlog( INFO, "Param: templatefile: [" + templatefile + "]");
            wwlog( INFO, "Param: previewType: [" + previewType + "]");
            wwlog( INFO, "Param: getRelations: [" + getRelations + "]");

            wwlog( INFO, "Param: dumpfile: [" + dumpfile + "]");
            wwlog( INFO, "Param: exportType: [" + exportType + "]");

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

            wwlog( INFO, "Workspace folder: [" + workspacePath + "]");
        }

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
            var lookupForGuidsOfChangedStories = {};
            var guidsOfChangedStories = guidsOfChangedStoriesCsv.split(",");
            var guidsOfChangedStoriesLength = guidsOfChangedStories.length;
            for (var z = 0; z < guidsOfChangedStoriesLength; z++) {
                lookupForGuidsOfChangedStories[guidsOfChangedStories[z]] = 1;
            }
            var guidsOfChangedStoriesCsvLength = guidsOfChangedStoriesCsv.length;

            // return width / height properties in 'points' unit
            myDoc.viewPreferences.horizontalMeasurementUnits = MeasurementUnits.points;
            myDoc.viewPreferences.verticalMeasurementUnits = MeasurementUnits.points;
            if ( editionName ){ // edition name is used to activate edition
                var activeEdition = myDoc.activeEdition;
                if (shouldLog) {
                    wwlog(INFO, "Current edition: [" + encodeURIComponent(activeEdition) +"]"); // BZ#7341 (encode edition)
                }
                if ( activeEdition != editionName && typeof(activeEdition) != "undefined" ) {
                    if (shouldLog) {
                        wwlog(INFO, "Activate edition: [" + encodeURIComponent(editionName) + "]"); // BZ#7341 (encode edition)
                    }
                    myDoc.activeEdition = editionName;
                }
            }
            wwlog( CONSOLE, "Action: Get Compose Data...");
            var lookupPages = {}; // lookup object for the pages key = page.id, value = 1 or 0 / undefined
            var arrUnlockedStories = new Array(); // collect stories that are unlocked by us (and so require restore lock)

            composeData.push( '<?xml version="1.0" encoding="UTF-8"?>\n' ); // EN-86609
            composeData.push( '<textcompose>\n' );
            composeData.push( '\t<context ' +
                'editionid="' + editionId + '" ' +
                'exporttype="' + exportType + '" ' +
                'previewtype="' + previewType + '">\n'
            );
            if( layoutID ) {
                composeData.push( '\t\t<layout ' +
                    'id="' + layoutID + '" ' +
                    'name="' + htmlEntities( myDoc.entMetaData.get( "Core_Name" )) + '" ' +
                    'version="' + myDoc.entMetaData.get("Version") + '" />\n'
                );
            }
            composeData.push( '\t</context>\n' );

            composeData.push( '\t<stories>\n' );
            frameIterWatch.start();
            try {
                // Walk through the list of articles in the opened document to find (based on the ID)
                // the article we're editing in WebEditor.
                var mas = myDoc.managedArticles;
                var masLen = mas.length;
                var isCopyfitBySC = copyfitBySC(pluginVersion);
                wwlog(INFO, "#articles=" + masLen );
                for( var artIdx = 0; artIdx < masLen; ++artIdx ) {
                    wwlog(INFO, "  article #" + artIdx );
                    var ma = mas.item(artIdx);
                    var md = ma.entMetaData;
                    if( !md.has( "Core_ID" ) )
                        continue;

                    var curId = md.get( "Core_ID" );
                    if( in_array( articleIDS, curId ) ) {
                        wwlog(INFO, "  Found article (curId="+curId+").");
                        // Got the article. Now walk through the components to find the pages. The
                        // list of components consists of stories and pageitems
                        var comps = ma.components;
                        var compsLen = comps.length;
                        wwlog(INFO, "  Article #comps=" + compsLen );

                        for( var j = 0; j < compsLen; ++j ) {
                            var comp = comps[j];
                            var compGuid = comp.guid;
                            wwlog(INFO, "    -- Component " + j + " --" );
                            // Now build a list of pages the components of the article run through.
                            // comp can be a PageItem derived class or a Story. Check here for story otherwise
                            // it's a PageItem derived item.
                            wwlog(INFO, "    Type=" + comp + " (" + j + ")" );
                            // myComp = new StopWatch();
                            // myComp.start();
                            if( comp instanceof Story ) {
                                if( guidsOfChangedStoriesCsvLength > 0 && // empty means: update all
                                    lookupForGuidsOfChangedStories[compGuid] == undefined) {
                                    wwlog(INFO, "    Skipping unchanged story: " + compGuid );
                                }
                                else {
                                    if( guidsOfChangedStoriesCsvLength == 0 ) {
                                        wwlog(INFO, "    Processing story: " + compGuid );
                                    } else {
                                        wwlog(INFO, "    Processing changed story: " + compGuid );
                                    }
                                    var tfs = comp.textContainers;
                                    if( tfs == undefined )
                                        continue;

                                    var tfsLen = tfs.length;
                                    wwlog(INFO, "    # text frames:   " + tfsLen);

                                    oversetWatch.start();
                                    // Only calculate Overset, no Underset. We will retrieve Underset from the frameData.
                                    var fitInfo;
                                    if ( isCopyfitBySC ) {
                                        wwlog( INFO, "Recomposing story just before copyfit calculation" );
                                        comp.recompose();
                                        wwlog( INFO, "getting overset/underset from frame data; " );
                                        var frameData = tfs[tfsLen - 1].frameData;
                                        var frameDataObjects  = eval("(" + frameData + ")");
                                        var frameDataObj = frameDataObjects[frameDataObjects.length - 1];

                                        oversetLines = frameDataObj.OversetLines;
                                        oversetChars = frameDataObj.OversetChars;
                                        wwlog( INFO, "      Info: text frame #" + tfIdx + "; frameIdx = " + frameIdx + "; OversetLines = " + oversetLines );
                                        if ( oversetLines > 0 ) {
                                            fitInfo = ['overset', oversetLines, oversetChars ];
                                        }
                                        else if ( oversetLines < 0 ) {
                                            fitInfo = ['underset', -oversetLines, 0 ];
                                        }
                                        else {
                                            fitInfo = ['copyfit', 0, 0 ];
                                        }
                                    }
                                    else {
                                        wwlog( INFO, "calculating overset/underset in script; " );
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

                                        fitInfo = getOverset(comp);
                                    }

                                    wwlog( INFO, "calculated overset/underset; fitInfo = " + fitInfo );
                                    oversetWatch.stop();

                                    composeData.push( '\t\t<story guid="' + compGuid + '" label="' + escape(comp.storyTitle) +
                                        '" words="' + comp.words.length + '" chars="' + comp.characters.length +
                                        '" lines="' + comp.lines.length + '" paras="' + comp.paragraphs.length +
                                        '" type="' + fitInfo[0] + '" value="' + fitInfo[1] + '" length="' + fitInfo[2] + '">\n'); // escape(): BZ#27285/BZ#26670

                                    var lookupStoryPages = {};
                                    var arrPageNames = new Array();
                                    composeData.push( '\t\t\t<textframes>\n');
                                    try
                                    {
                                        for( var tfIdx = 0; tfIdx < tfsLen; ++tfIdx )
                                        {
                                            wwlog( INFO, "      Info: text frame #" + tfIdx );
                                            var tfsObj = tfs[tfIdx];
                                            var oPage = tfsObj.parentPage;
                                            if (oPage) {
                                                var frameData = tfsObj.frameData; // Since SC CS4 v7.3.4 build 295/SC CS5 v7.3.4 build 293 and above
                                                var idArticleIdsCsv = "";
                                                var tfsObjIDids = tfsObj.allIndesignArticleIds;
                                                if( typeof( tfsObjIDids ) != "undefined" ) { // Since SC 10.2
                                                    idArticleIdsCsv = tfsObjIDids.join(',');
                                                }
                                                var frameHasTiles = false;
                                                var frameDataObjects  = eval("(" + frameData + ")");
                                                var obj_len = frameDataObjects.length;
                                                for( var frameIdx = 0; frameIdx < obj_len; frameIdx++ ) {
                                                    var frameDataObject = frameDataObjects[frameIdx];
                                                    composeData.push( '\t\t\t\t<textframe guid="' + frameDataObject.ElementID + '" frameid="' + tfsObj.id + '" ' +
                                                        'frameorder="' + frameDataObject.FrameOrder + '" pagesequence="' + frameDataObject.PageSequence + '" ' +
                                                        'pagenr="' + escape(frameDataObject.PageNumber) + '" layer="' + escape(frameDataObject.Layer) + '" ' +
                                                        'ypos="' + frameDataObject.Top + '" xpos="' + frameDataObject.Left + '" ' +
                                                        'width="' + frameDataObject.Width + '" height="' + frameDataObject.Height + '" ' +
                                                        'idarticleids="' + idArticleIdsCsv + '" ' +
                                                        'splineid="' + tfsObj.id + '" frametype="text" ' );
                                                    // Enterprise 7.6 adds support for placement tiles which break up a placement into multiple tiles that lie
                                                    // across page boundaries. This enables the Server and in the end Content Station to display articles that
                                                    // lie across pages (spread support). A tile is bound to a page via a pagesequence. Smart Connection adds
                                                    // the tiles to the Placement object (see WSDL) and thus also to the frameData JSON. The Tiles property
                                                    // is an optional parameter: it only exists when Smart Connection 7.6+ is used and when the placement
                                                    // crosses the page boundaries.
                                                    if( "Tiles" in frameDataObject ) {
                                                        frameHasTiles = true;
                                                        var fdoTilesLength = frameDataObject.Tiles.length;
                                                        composeData.push( '>\n\t\t\t\t\t<tiles>\n');
                                                        for( var tileIdx = 0; tileIdx < fdoTilesLength; ++tileIdx ) {
                                                            var fdoTilesDataObj = frameDataObject.Tiles[tileIdx];
                                                            composeData.push( '\t\t\t\t\t\t<tile pagesequence="' + fdoTilesDataObj.PageSequence +
                                                                '" ypos="' + fdoTilesDataObj.Top + '" xpos="' + fdoTilesDataObj.Left +
                                                                '" width="' + fdoTilesDataObj.Width + '" height="' + fdoTilesDataObj.Height + '"/>\n');
                                                        }
                                                        composeData.push( '\t\t\t\t\t</tiles>\n\t\t\t\t</textframe>\n');
                                                    } else {
                                                        // No tiles, just close the textframe element
                                                        composeData.push( '/>\n');
                                                    }
                                                } // escape(): BZ#27285/BZ#26670
                                                // Note: 'pagesequence' and 'guid' attributes are redundant, but still there to serve Web Editor.

                                                if (shouldLog && lookupStoryPages[oPage.id] == undefined) {
                                                    arrPageNames.push( getPageName( oPage ) );
                                                    lookupStoryPages[oPage.id] = 1;
                                                }
                                                lookupPages[oPage.id] = 1;

                                                // v7.6 feature: In Spread Preview mode, when the article
                                                // text frame is placed on left page (of the spread),
                                                // also include the right page (and vice versa).
                                                // For Single Page preview mode, when there are tiles, the
                                                // sibling spread page needs to be included as well.
                                                // For example a 'head' stretched over the whole spread.
                                                if( previewType == 'spread' || frameHasTiles ) {
                                                    if( oPage.side == PageSideOptions.leftHand || oPage.side == PageSideOptions.rightHand ) {
                                                        var oPageParent = oPage.parent;
                                                        if( typeof( oPageParent ) != "undefined" && oPageParent instanceof Spread ) {
                                                            var oSpreadPages = oPageParent.pages;
                                                            var len = oSpreadPages.count();
                                                            for( var iSpreadPage = 0; iSpreadPage < len; ++iSpreadPage ) {
                                                                var oSpreadPage = oSpreadPages.item( iSpreadPage );
                                                                if( oSpreadPage.id != oPage.id ) {
                                                                    if (shouldLog) {
                                                                        wwlog(INFO, "    Including other page (#" + getPageName( oSpreadPage ) + ") of the spread " +
                                                                        "(than the article textframe is placed on) to support spread preview mode." );
                                                                        if (lookupStoryPages[oSpreadPage.id] == undefined) {
                                                                            arrPageNames.push( getPageName( oSpreadPage ) );
                                                                            lookupStoryPages[oSpreadPage.id] = 1;
                                                                        }
                                                                    }
                                                                    lookupPages[oSpreadPage.id] = 1;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        if (shouldLog) {
                                            wwlog(INFO, "    Story placed on page(s) : " + arrPageNames.sort(sortNumber).join(','));
                                        }
                                    }
                                    catch (e)
                                    {
                                        wwlog( ERROR, "    Error #5: " + e.name + " - " + e.message + " Source: IDPreview.js#" + e.line );
                                    }
                                    composeData.push( '\t\t\t</textframes>\n');
                                    composeData.push( '\t\t</story>\n' );
                                }
                            }
                            else { // e.g. graphic frame
                                if( guidsOfChangedStoriesCsvLength > 0 && // empty means: update all
                                    lookupForGuidsOfChangedStories[compGuid] == undefined) {
                                    wwlog(INFO, "    Skipping unchanged graphic frame: " + compGuid );
                                }
                                else {
                                    var lookupStoryPages = {};
                                    var arrPageNames = new Array();
                                    var oPage = comp.parentPage;
                                    if( oPage ) {
                                        if( lookupPages[oPage.id] == undefined) {
                                            if( guidsOfChangedStoriesCsv.length == 0 ) {
                                                lookupPages[oPage.id] = 1;
                                                if (shouldLog) {
                                                    wwlog(INFO, "    Including page for non-story page item because of full refresh. Page " + getPageName( oPage ) );
                                                    // Store the current page into lookupStoryPages & arrPageNames
                                                    if (lookupStoryPages[oPage.id] == undefined) {
                                                        arrPageNames.push( getPageName(oPage) );
                                                        lookupStoryPages[oPage.id] = 1;
                                                    }
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
                                    var frameDataObjLen = frameDataObj.length;
                                    for( var frameIdx = 0; frameIdx < frameDataObjLen; frameIdx++ ) {
                                        if( "Tiles" in frameDataObj[frameIdx] ) {
                                            wwlog(INFO, "    We have Tiles " );
                                            frameHasTiles = true;
                                            break;
                                        }
                                    }
                                    // On spread view or if we have tiles collect related pages
                                    if( previewType == 'spread' || frameHasTiles ) {
                                        if( oPage.side == PageSideOptions.leftHand || oPage.side == PageSideOptions.rightHand ) {
                                            var oPageParent = oPage.parent;
                                            if( typeof( oPageParent ) != "undefined" && oPageParent instanceof Spread ) {
                                                var oSpreadPages = oPageParent.pages;
                                                var spreadPagesLen = oSpreadPages.count();
                                                for( var iSpreadPage = 0; iSpreadPage < spreadPagesLen; ++iSpreadPage ) {
                                                    var oSpreadPage = oSpreadPages.item( iSpreadPage );
                                                    if( oSpreadPage.id != oPage.id ) {
                                                        if (shouldLog) {
                                                            wwlog(INFO, "    Including other page (#" + getPageName( oSpreadPage ) + ") of the spread " +
                                                                "(than the article graphic frame is placed on) to support spread preview mode." );
                                                            if (lookupStoryPages[oSpreadPage.id] == undefined ) {
                                                                arrPageNames.push( getPageName( oSpreadPage ) );
                                                                lookupStoryPages[oSpreadPage.id] = 1;
                                                            }
                                                        }
                                                        lookupPages[oSpreadPage.id] = 1;
                                                    }
                                                }
                                            }
                                        }
                                        if (shouldLog) {
                                            wwlog(INFO, "    Graphic frame placed on page(s) : " + arrPageNames.sort(sortNumber).join(','));
                                        }
                                    }
                                }
                            }
                            // myComp.stop();
                            // wwlog( CONSOLE, "--- Component took: [" + myComp.getDuration() + "] sec");
                            // wwlog( CONSOLE, "-------------------------------------------------------");
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
            var unlockedStoriesLen = arrUnlockedStories.length;
            for( var l = 0; l < unlockedStoriesLen; l++ ) {
                arrUnlockedStories[l].textLock = true;
            }
            composeData.push( '\t</stories>\n' );
            frameIterWatch.stop();
            // wwlog( CONSOLE, "--- frameIterWatch took: [" + frameIterWatch.getDuration() + "] sec");

            // Export file
            genPagesWatch.start();
            var arrSortPages = new Array();
            var arrSortPageObjects = new Array();

            composeData.push( '\t<pages>\n' );
            try
            {
                // sort all pages of found array by walking through pages ( must be pagesequence )
                var myDocPages = myDoc.pages;
                var myDocPagesLen = myDocPages.length;
                var myDocPrefPageWidth = myDoc.documentPreferences.pageWidth;
                var myDocPrefPageHeight = myDoc.documentPreferences.pageHeight;
                for( var pgIdx = 0; pgIdx < myDocPagesLen; pgIdx++ )
                {
                    var oPage = myDocPages[pgIdx];
                    if ( lookupPages[oPage.id] == 1 ) {
                        var section = oPage.appliedSection;
                        var pageSectionName = section ? section.name : '';
                        if ( oPage.name.substr(0,pageSectionName.length) == pageSectionName ) {
                            // section name is allready in pagename ( sections/numbering -> tickbox 'Include prefix when numbering pages' )
                            arrSortPages.push(oPage.name);
                        }
                        else {
                            arrSortPages.push( getPageName(oPage) );
                        }
                        arrSortPageObjects.push( { key:pgIdx, value:oPage } );
                        var finalPageName = oPage.name;
                        var pageOrder = section.pageNumberStart + pgIdx;

                        composeData.push( '\t\t<page side="' + pageSide2Text(oPage.side) +
                            '" name="' + escape(finalPageName) + '" sequence="' + (pgIdx+1) +
                            '" width="' + myDocPrefPageWidth + '" height="' + myDocPrefPageHeight +
                            '" order= "' + pageOrder  + '"/>\n' );
                    }
                }
            }
            catch( e )
            {
                wwlog( ERROR, "Error #2.2: '" + e.name + " - " + e.message + "' Source: IDPreview.js line#" + e.line );
            }
            composeData.push( '\t</pages>\n' );

            // Since 9.7 request SC for the Object->Relations, Relation->Placements and the Object->Placements
            // because those may change due to ObjectOperation processing. Note that those may NOT be
            // stored in the DB yet, but reside in the layout in the editor workspace only.
            if (getRelations == 'true') {
              var rels = myDoc.entWorkflow.relations;
              if (typeof(rels) != "undefined" ) {
                  // remove the xml version processing instruction because the relations will be concatenated into our dump file
                  rels = rels.replace('<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>', '');
                  // TODO update this into a regular expression in case the <xml> string changes in the future
                  composeData.push( '\t<layout>' + ( rels ) + '</layout>\n' );
              }
            }

            composeData.push( '</textcompose>\n' );

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
                    var pageObjectsLen = arrSortPageObjects.length;
                    for( var i = 0; i < pageObjectsLen; ++i ) {
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
            // Save to reflect performed object operations in the locally stored layout so that these
            // are not performed over and over again for succeeding previews.
            // This should not be done for articles that are not placed on a layout.
            // SC 10.2.8+ and 11.0.7+ contain changes that no longer require this save.
            if( isSC10plus && !templatefile && saveRequired(pluginVersion) ) { // SC10+ && article is placed && save needed (<10.2.8+ and <11.0.7+)
                wwlog( INFO, "Saving document [" + myDoc.fullName + "]" );
                myDoc.save( myDoc.fullName );
            }
        }
        catch(e)
        {
            wwlog( ERROR, "Error #6.1: " + e.name + " - " + e.message + " Source: IDPreview.js#" + e.line );
        }
        try
        {
            // Close all documents without saving.
            while( app.documents.length > 0 ) {
                wwlog( INFO, "Closing document [" + app.documents.item(0).fullName + "]" );
                app.documents.item(0).close( SaveOptions.NO );
            }
        }
        catch(e)
        {
            wwlog( ERROR, "Error #3.2: " + e.name + " - " + e.message + " Source: IDPreview.js#" + e.line );
        }

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
        {
            wwlog( ERROR, "Error #4: " + e.name + " - " + e.message + " Source: IDPreview.js#" + e.line );
        }

        if( isSC10plus ) {
            app.disableGeneratingPreview(); // this also clears app.setArticleFileArray()
        } else { // SC8 (and SC9)
            app.generatingPreview = false;
        }

        cleanupWatch.stop();
        totalsWatch.stop();
        wwlog( CONSOLE, "<<< Ready, IDPreview took [" + totalsWatch.getDuration() + "] sec");

        if (shouldLog) {
            wwlog(INFO, "Performance: Duration login: [" + loginWatch.getDuration() + "] sec");
            wwlog(INFO, "Performance: Duration opening document: [" + openfileWatch.getDuration() + "] sec");
            wwlog(INFO, "Performance: Duration text frame iteration: [" + frameIterWatch.getDuration() + "] sec, which includes overset calculation: [" + oversetWatch.getDuration() + "] sec");
            wwlog(INFO, "Performance: Duration PDF/preview generation: [" + genPagesWatch.getDuration() + "] sec");
            wwlog(INFO, "Performance: Duration cleanup and logout: [" + cleanupWatch.getDuration() + "] sec");
            wwlog(INFO, "Performance: Total execution time: [" + totalsWatch.getDuration() + "] sec");
        }
        // wwlog(INFO, "Performance: 1: [" + tmp1.getDuration() + "] sec");
        // wwlog(INFO, "Performance: 2: [" + tmp2.getDuration() + "] sec");
        // wwlog(INFO, "Performance: 3: [" + tmp3.getDuration() + "] sec");
    }

    // Return the xml file to the caller
    return JSON.stringify({
        result: {
            composeData: composeData.join('')
        },
        log: logLines.join('\n')
    }, null, '  ');
}

app.doScript( main, ScriptLanguage.JAVASCRIPT, null, UndoModes.FAST_ENTIRE_SCRIPT, "Generate Preview" );
