/**
 * Common Utils. Collection of handy functions that are used by other JavaScript modules. </br>
 * 
 * @package 	SCEnterprise
 * @subpackage 	WebApps
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 *  Returns an XML document HTML format. The returned HTML can be added to the document <br/>
 *  to show its structure for debugging purposes. XML elements are indented nicely. <br/>
 *
 * @param doc object   XML DOM tree to be formatted.
 * @return string      Well formatted HTML representation of the XML tree.
 */
function debugXML( doc, html ) 
{
	var xmlRet = "";
	if(doc.hasChildNodes()) 
	{
		if( html == true )
			xmlRet += '<ul><li>&lt;'+doc.tagName;
		else
			xmlRet += '<'+doc.tagName;
		var attr = doc.attributes;
		if(attr) for(var j=0; j<attr.length; j++) 
		{
			xmlRet += ' ' + attr[j].name + "=\"" + attr[j].value + "\"";
		}
		xmlRet += '>';
		if(doc.childNodes) for(var i=0; i<doc.childNodes.length; i++) 
		{
			xmlRet += debugXML( doc.childNodes[i], html );
		}
		if( html == true )
			xmlRet += '&lt;/'+doc.tagName+'></li></ul>';
		else
			xmlRet += '</'+doc.tagName+'>';
	}
	else 
	{
		if( doc.nodeValue )
			xmlRet += doc.nodeValue;
	}
	return xmlRet;
}

/**
 * Inserts HTML fragment to the current document. <br/>
 * The insertAdjacentHTML function is already present for IE, however there is no <br/>
 * such function for FireFox. The function below extends(!) the HTMLElement for the current <br/>
 * browser to let FireFox work the same as IE. See also {@link doInsertAdjacentHTML()}. <br/>
 *
 * @param where string   Where to insert: beforeBegin, afterBegin, beforeEnd or afterEnd <br/>
 * @param htmlstr string The HTML fragment to insert to the document.body. <br/>
 */
if(typeof HTMLElement!="undefined" && ! 
	 HTMLElement.prototype.insertAdjacentElement){ 
	HTMLElement.prototype.insertAdjacentHTML = function( where, htmlstr ) 
	{
		var range = document.createRange();
		range.setStartBefore( document.body.lastChild );
		var docFrag = range.createContextualFragment(htmlstr);
		
		switch( where ) {
			case 'beforeBegin':
				this.parentNode.insertBefore(docFrag, this);
				break;
			case 'afterBegin':
				this.insertBefore(docFrag, this.firstChild);
				break;
			case 'beforeEnd':
				this.appendChild(docFrag);
				break;
			case 'afterEnd':
				this.parentNode.insertBefore(docFrag, this.nextSibling);
				break;
		}
	}
}

/**
 * Does the same as insertAdjacentHTML, but works also for browsers that do not have insertAdjacentHTML
 * function that even can not be added (extended) as done in {@link insertAdjacentHTML()}.
 * Safari is such browser for which insertAdjacentHTML can not be added. <br/>
 * This function needs to be called (for all browsers!) instead of insertAdjacentHTML! <br/>
 */
function doInsertAdjacentHTML( elem, where, htmlstr )
{
	if( elem.insertAdjacentHTML ) { // IE, MOZ
		elem.insertAdjacentHTML( where, htmlstr );
	} else if( typeof( document.body.innerHTML ) != 'undefined' ) { // Safari
		switch( where ) {
			case 'beforeBegin':
			case 'afterBegin':
				elem.innerHTML = htmlstr + elem.innerHTML;
				break;
			case 'beforeEnd':
			case 'afterEnd':
				elem.innerHTML = elem.innerHTML + htmlstr;
				break;
		}
	} else {
		//FAILURE, nothing works
	}
}

/**
 * IE's clientX and clientY measurements were sometimes a couple pixels out. 
 * It turns out this is because IE's clientX and clientY measurements start from (2,2) in 
 * standards mode, and (0,0) in quirks mode.
 * IE stores this offset in its document.documentElement.clientLeft and  
 * document.documentElement.clientTop properties. This code should  
 * calculate the correct cursor position in all current browsers:
 *
 * @param Event e         Mouse click event
 * @return Cursor object with x and y members
 */
function getPosition(e) 
{
     e = e || window.event;
     var cursor = {x:0, y:0};
     if (e.pageX || e.pageY) {
         cursor.x = e.pageX;
         cursor.y = e.pageY;
     }
     else {
         cursor.x = e.clientX +
             (document.documentElement.scrollLeft ||
             document.body.scrollLeft) -
             document.documentElement.clientLeft;
         cursor.y = e.clientY +
             (document.documentElement.scrollTop ||
             document.body.scrollTop) -
             document.documentElement.clientTop;
     }
     return cursor;
}

/**
 * Raises error dialog showing details of the given Error object (r)
 */
function alertError( r )
{
	var msg = r.message + " (" + r.number + ")";
	/* // ONLY FOR DEBUGGING 
	if( r.detail ) {
		msg += "\n\n" + r.detail;
	}
	if( r.stack ) {
		msg += "\n\n" + r.stack;
	}*/
	alert( msg );
}

/**
 * Returns the full URL path of the root of Enterprise installation. <br/>
 *
 * @return string    The URL including end-slash, e.g. "http://localhost/scenterprise/".
 */
function getBaseDir()
{
	var url = document.URL;
	// try to find scenterprise root folder as safe as possible
	var lastSlash = url.lastIndexOf('/server/');
	if (lastSlash < 0) lastSlash = url.lastIndexOf('/config/');
	//if (lastSlash < 0) lastSlash = url.lastIndexOf('/'); // let's not do this (pretty unsafe)
	if (lastSlash < 0) {
		alert( "getBaseDir: SCEnterprise base folder not found!" );
		return null;
	} else {
		return url.substr( 0, lastSlash+1 ); 
	}
}

/**
 * Parses the key-value parameters of a given URL.
 * Assumed is that all parameters are escaped (since they are unescaped here).
 * Parameters without value are suported too; their value will be set null.
 *
 * @param string q Query URL parameters starting with "?"
 */
function WebPageQuery(q) 
{
	if(q.length > 1) {
		this.q = q.substring(1, q.length); // skip the "?" char
	} else {
		this.q = null;
	}
	this.keyValues = new Array();
	if( this.q != null && this.q.length > 0 ) {
		var keyvals = this.q.split("&");
		for(var i=0; i < keyvals.length; i++) {
			var keyval = keyvals[i].split("=");
			if( keyval.length > 1 ) { // key-value
				this.keyValues[ keyval[0] ] = unescape(keyval[1]);
			} else { // just the key (no value)
				this.keyValues[ keyval[0] ] = null;
			}
		}
	}
	
	this.getKeyValues = function() 
	{ 
		return this.keyValues; 
	}
}

/**
 * Add the DOMParser for IE6/IE7/IE8.
 * Note that FF/Safari/IE9+ ship their own DOMParser (so nothing gets added here).
 *
 * @since v7.0.10 To be prepared for IE9 that ships its own.
 */
if( typeof(DOMParser) == 'undefined' ) {
	DOMParser = function() {}
	DOMParser.prototype.parseFromString = function(str, contentType) {
		if( typeof(ActiveXObject) != 'undefined' ) {
			var xmlDom;
			try { // First try to get MS XML v3, else fall back at v2
				xmlDom = new ActiveXObject('MSXML2.DOMDocument'); // v3
			} catch (ex) { // v3 (suppress error to try v2)
				xmlDom = new ActiveXObject('Microsoft.XmlDom'); // v2 (same as 'MSXML.DomDocument')
			} 
			xmlDom.async = false;
			xmlDom.loadXML(str);
			// BZ#21511: Commented out; Do not raise error since that may disturb production
			//if( xmlDom.parseError != '0' ) {
			//	alert( xmlDom.parseError.reason );
			//}
			return xmlDom;
		}
	}
}
