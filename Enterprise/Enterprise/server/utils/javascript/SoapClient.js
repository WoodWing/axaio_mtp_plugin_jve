/*****************************************************************************\

 Javascript "SOAP Client" library

 License: http://www.codeplex.com/JavaScriptSoapClient/Project/License.aspx
 
 @version: 2.3 - 2007.02.01 + WoodWing customizations - 2007.03.01
 @author: Matteo Casati - http://www.guru4.net/
 
\*****************************************************************************/

/* NOTE#001:
WoodWing: Found difference between Safari (v3) and other common browsers such as Mozilla and IE.
For common browser, the constructor of objects is "function <Name>", so for example, "function Array".
However, for Safari, the constructors are "function" not telling the class name!!!

That means this SoapClient module failed at many places, deriving the class name from constructor function, 
like:
   if(o.constructor.toString().indexOf("function Date()") > -1)
and:
   (/function\s+(\w*)\s*\(/ig).exec(o[p].constructor.toString());

That is why I have introduced getObjectType method for supported object types.
Also introduced getElementName method, which is now mandatory for all custom objects!

This has an advantage that the class name does not have to be the same as the element name
sent through SOAP. This is useful when the element name is a reserved keyword in JavaScript!

Here is how you can create a custom data object:
	function MyObject( id, name ) {
		this.ID = id;
		this.Name = name;
	}
	MyObject.prototype.getElementName = function() { return "TheObject"; }
	
This custom object then is send through SOAP as:
	<TheObject>
		<ID>...</ID>
		<Name>...</Name>
	</TheObject>

*/

// >>> WoodWing: See NOTE#001
Object.prototype.getObjectType = function() { return "object"; }
String.prototype.getObjectType = function() { return "string"; }
Array.prototype.getObjectType = function() { return "array"; }
Date.prototype.getObjectType = function() { return "date"; }
// <<<

function SOAPClientParameters()
{
	var _pl = new Array();
	this.add = function(name, value) 
	{
		_pl[name] = value; 
		return this; 
	}
	this.toXml = function()
	{
		// >>> WoodWing: Avoid duplicate code
		/*var xml = "";
		for(var p in _pl)
		{
			// >>> WoodWing: Handle nillables
			if( _pl[p] == null ) {
				xml += "<" + p + " xsi:nil=\"true\"/>";
			} else { // <<<
				switch(typeof(_pl[p])) 
				{
	                case "string":
	                case "number":
	                case "boolean":
	                case "object":
	                    xml += "<" + p + ">" + SOAPClientParameters._serialize(_pl[p]) + "</" + p + ">";
	                    break;
	                default:
	                    break;
	            }
            }
		}
		return xml;	*/
		return SOAPClientParameters._serialize( _pl );
		// <<<
	}
}

// >>> WoodWing: See NOTE#001
SOAPClientParameters.validObjectType = function( o )
{
	var type = typeof(o);
	switch( type ) {
		case "string":
		case "number":
		case "boolean":
			break;
		case "object":
			type = (o != null && o.getObjectType != "undefined") ? o.getObjectType() : "";
			switch( type ) {
				case "string":
				case "date":
				case "array":
					break;
				default:
					if( o.nodeType == 3 ) { // BZ#9283
						type = "text";
					} else {
						type = "object";
					}
			}
			break;
		default:
			type = ""; // unsupported
	}
	return type;
}
// <<<

SOAPClientParameters._serialize = function(o)
{
    var s = "";
    switch( SOAPClientParameters.validObjectType(o) ) // WoodWing removed: switch(typeof(o)) 
    {
        case "text":
        	s += o.nodeValue;
        	break;
        case "string":
            s += o.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;"); 
            break;
        case "number":
        case "boolean":
            s += o.toString(); 
            break;
        //case "object": // WoodWing: See NOTE#001
        case "date": // WoodWing: See NOTE#001, removed: if(o.constructor.toString().indexOf("function Date()") > -1)
                var year = o.getFullYear().toString();
                var month = (o.getMonth() + 1).toString(); month = (month.length == 1) ? "0" + month : month;
                var date = o.getDate().toString(); date = (date.length == 1) ? "0" + date : date;
                var hours = o.getHours().toString(); hours = (hours.length == 1) ? "0" + hours : hours;
                var minutes = o.getMinutes().toString(); minutes = (minutes.length == 1) ? "0" + minutes : minutes;
                var seconds = o.getSeconds().toString(); seconds = (seconds.length == 1) ? "0" + seconds : seconds;
                var milliseconds = o.getMilliseconds().toString();
                var tzminutes = Math.abs(o.getTimezoneOffset());
                var tzhours = 0;
                while(tzminutes >= 60)
                {
                    tzhours++;
                    tzminutes -= 60;
                }
                tzminutes = (tzminutes.toString().length == 1) ? "0" + tzminutes.toString() : tzminutes.toString();
                tzhours = (tzhours.toString().length == 1) ? "0" + tzhours.toString() : tzhours.toString();
                var timezone = ((o.getTimezoneOffset() < 0) ? "+" : "-") + tzhours + ":" + tzminutes;
                s += year + "-" + month + "-" + date + "T" + hours + ":" + minutes + ":" + seconds + "." + milliseconds + timezone;
            break;
            
        case "array": // WoodWing: See NOTE#001, removed: else if(o.constructor.toString().indexOf("function Array()") > -1)
                for(var p in o)
                {
                    if(!isNaN(p))   // linear array
                    {
                        /* >>> WoodWing: See NOTE#001
                        // WoodWing: Testing FF (v2.0.0.4) on Mac seems that RegExp.$1 is empty at 1st, 3rd, 5th, etc time calling!!
                        //(/function\s+(\w*)\s*\(/ig).exec(o[p].constructor.toString());
                        //var type = RegExp.$1;
                        var expStr = /function\s+(\w*)\s*\(/;  // w=[A-Za-z0-9_], s=[ \f\n\r\t\v]  
                        var expRes = expStr.exec( o[p].constructor.toString(), "ig" );
                        var type = expRes && expRes.length>0 ? expRes[1] : "";
                        switch(type)
                        {
                            case "":
                                type = typeof(o[p]); break; // WoodWing: "break" was missing!
                            case "String":
                                type = "string"; break;
                            case "Number":
                                type = "int"; break;
                            case "Boolean":
                                type = "bool"; break;
                            case "Date":
                                type = "DateTime"; break;
                        } */
                        var type = "";
                        switch( SOAPClientParameters.validObjectType( o[p] ) )
                        {
                            case "string":
                                type = "String"; break;
                            case "number":
                                type = "int"; break;
                            case "boolean":
                                type = "bool"; break;
                            case "date":
                                type = "DateTime"; break;
                            case "object":
                                type = o[p].getElementName(); break;
                            default: // use <item> for unsuported types
                                type = "item"; break;
                        }
                        // <<<
								if( o[p] == null ) { // >>> WoodWing: Handle nillables
									s += "<" + type + " xsi:nil=\"true\"/>";
								} else { // <<<
      	                  s += "<" + type + ">" + SOAPClientParameters._serialize(o[p]) + "</" + type + ">";
      	               }
                    }
                    else {    // associative array
                        if( typeof(o[p]) != "function" ) { // WoodWing: avoid object method names in SOAP!
									if( o[p] == null ) { // >>> WoodWing: Handle nillables
										s += "<" + p + " xsi:nil=\"true\"/>";
									} else { // <<<
										 s += "<" + p + ">" + SOAPClientParameters._serialize(o[p]) + "</" + p + ">";
									}
                        }
                    }
                }
            break;
            
        // >>> WoodWing: See NOTE#001
        //default: // Object or custom function
        case "object":
        // <<<
					for(var p in o) {
                  if( typeof(o[p]) != "function" ) { // WoodWing: avoid object method names in SOAP!
							if( o[p] == null ) { // >>> WoodWing: Handle nillables
								s += "<" + p + " xsi:nil=\"true\"/>";
							} else { // <<<
							   s += "<" + p + ">" + SOAPClientParameters._serialize(o[p]) + "</" + p + ">";
							}   
						}
					}
			// >>> WoodWing, See NOTE#001
			default: // function or unsupported type
				break;
			// <<<
    }
    return s;
}

function SOAPClient() {}

// WoodWing: Added 'service' param to choose one of the WSDL flavors dispatched by the access point (url)
// WoodWing: Added 'ns' param to pass name space which implies the wsdl should NOT be loaded/requested
SOAPClient.invoke = function(url, service, method, parameters, async, callback, ns)
{
	if(async)
		SOAPClient._loadWsdl(url, service, method, parameters, async, callback, ns);
	else
		return SOAPClient._loadWsdl(url, service, method, parameters, async, callback, ns);
}

// private: wsdl cache
SOAPClient_cacheWsdl = new Array();

// private: invoke async
// WoodWing: Added 'service' param to choose one of the WSDL flavors dispatched by the access point (url)
// WoodWing: Added 'ns' param to pass name space which implies the wsdl should NOT be loaded/requested
SOAPClient._loadWsdl = function(url, service, method, parameters, async, callback, ns)
{
	// load from cache? Add service with url, to have multiple wsdl cache in array
	var wsdl = SOAPClient_cacheWsdl[url+service];
	// if wsdl loaded from cache, then no need to reload the wsdl again
	if(wsdl) {
		return SOAPClient._sendSoapRequest(url, method, parameters, async, callback, wsdl);
		// BZ#21510 Removed the "?start_debug=1" param from URL
	}
	// get wsdl
	var xmlHttp = null;
	if( service != null ) { // WoodWing
		var serverVersion = '';
		if( AppSession.prototype.version ) {
			serverVersion = '&serverVersion=' + AppSession.prototype.version;
		}
		xmlHttp = SOAPClient._getXmlHttp();
		xmlHttp.open("GET", url + "?wsdl="+service+serverVersion, async); // WoodWing: See comments above

		if(async) 
		{
			xmlHttp.onreadystatechange = function() 
			{
				if(xmlHttp.readyState == 4) {
					SOAPClient._onLoadWsdl(url, method, parameters, async, callback, xmlHttp, ns, service);
				}
			}
		}
		xmlHttp.send(null);
		// >>> WoodWing: Throw error when WSDL could not be retrieved
		if( xmlHttp.status != 200 ) {
			o = new Error( xmlHttp.statusText );
			o.number = xmlHttp.status;
			
			if(callback)
				callback(o, null);
			if(!async)
				return o;
		// <<<
		} else if (!async) {
			return SOAPClient._onLoadWsdl(url, method, parameters, async, callback, xmlHttp, ns, service);
		}
	} else {
		return SOAPClient._onLoadWsdl(url, method, parameters, async, callback, xmlHttp, ns, service);
	}
}
SOAPClient._onLoadWsdl = function(url, method, parameters, async, callback, req, ns, service)
{
	var wsdl = null;
	if( req != null ) {
		wsdl = req.responseXML;
		SOAPClient_cacheWsdl[url+service] = wsdl;	// save a copy in cache
	}
	return SOAPClient._sendSoapRequest(url, method, parameters, async, callback, wsdl, ns);
}
SOAPClient._sendSoapRequest = function(url, method, parameters, async, callback, wsdl, ns)
{
	// get namespace
	if( ns == null ) { // WoodWing
		ns = (wsdl.documentElement.attributes["targetNamespace"] + "" == "undefined") ? wsdl.documentElement.attributes.getNamedItem("targetNamespace").nodeValue : wsdl.documentElement.attributes["targetNamespace"].value;
	}
	// build SOAP request
	var sr = 
				"<?xml version=\"1.0\" encoding=\"utf-8\"?>" +
				"<soap:Envelope " +
				"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " +
				"xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" " +
				"xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\" " +
				"xmlns:ns1=\""+ns+"\">" + // WoodWing: Declare ns at envelope level to simplify dispatching at access point (url)
				"<soap:Body>" +
				//"<" + method + " xmlns=\"" + ns + "\">" +
				"<ns1:" + method + ">" + // WoodWing: See comments above
				parameters.toXml() +
				//"</" + method + "></soap:Body></soap:Envelope>";
				"</ns1:" + method + "></soap:Body></soap:Envelope>"; // WoodWing: See comments above
	// send request
	var xmlHttp = SOAPClient._getXmlHttp();
	xmlHttp.open("POST", url, async);
	
	var soapaction = ((ns.lastIndexOf("/") != ns.length - 1) ? ns + "/" : ns) + method;
	xmlHttp.setRequestHeader("SOAPAction", soapaction);
	xmlHttp.setRequestHeader("Content-Type", "text/xml; charset=utf-8");
	if(async) 
	{
		xmlHttp.onreadystatechange = function() 
		{
			if(xmlHttp.readyState == 4) {
				SOAPClient._onSendSoapRequest(method, async, callback, wsdl, xmlHttp);
			}
		}
	}
	xmlHttp.send(sr);
	if (!async) {
		return SOAPClient._onSendSoapRequest(method, async, callback, wsdl, xmlHttp);
	}
}

SOAPClient._onSendSoapRequest = function(method, async, callback, wsdl, req) 
{
	var o = null;
	// >>> WoodWing: Derive reponse name from request name: LogOnRequest -> LogOnResponse
	//var nd = SOAPClient._getElementsByTagName(req.responseXML, method + "Result");
	//if(nd.length == 0) {
	//	nd = SOAPClient._getElementsByTagName(req.responseXML, "return");	// PHP web Service?
	//}
	var respMethod = method;
	var reqPos = respMethod.lastIndexOf( "Request" );
	if( reqPos != -1 ) {
		respMethod = respMethod.substr( 0, reqPos );
	}
	respMethod = respMethod + "Response";
	
	var nd = SOAPClient._getElementsByTagName( req.responseXML, respMethod );
	// <<<
	if(nd.length == 0)
	{
		if(req.responseXML.getElementsByTagName("faultcode").length > 0)
		{
			// >>> WoodWing: Fixed issue: Error object had undefined message property (tested with FF v1.5)
		    //if(async || callback)
		    //    o = new Error(500, req.responseXML.getElementsByTagName("faultstring")[0].childNodes[0].nodeValue);
			//else
			//    throw new Error(500, req.responseXML.getElementsByTagName("faultstring")[0].childNodes[0].nodeValue);			
		    o = new Error( req.responseXML.getElementsByTagName("faultstring")[0].childNodes[0].nodeValue );
		    var detail = req.responseXML.getElementsByTagName("detail");
		    if( detail && detail.length > 0 ) {
			    o.detail = detail[0].childNodes.length > 0 ? detail[0].childNodes[0].nodeValue : detail[0].nodeValue;
			 } else {
			    o.detail = "";
			 }
		    o.number = 500;
			// <<<
		}
		if(!o && req.status != 200) {
			// serious PHP errors might come back as text ( instead of XML )
		  o = new Error( req.statusText + '\n' + req.responseText );
			o.number = req.status;
			o.name = 'Error';
			if( req.responseText.indexOf("SCEntError_InvalidTicket") >= 0) { // ticket expired
				o.detail == "SCEntError_InvalidTicket"
			}
		}
		if(o && !async && !callback) throw o;
	}
	else if( wsdl != null ) { // WoodWing: allow having no wsdl
		o = SOAPClient._soapresult2object(nd[0], wsdl);
	} else {
		o = nd[0];
	}
	if(callback)
		callback(o, req.responseXML);
	if(!async)
		return o;
}
SOAPClient._soapresult2object = function(node, wsdl)
{
    var wsdlTypes = SOAPClient._getTypesFromWsdl(wsdl);
    return SOAPClient._node2object(node, wsdlTypes);
}
SOAPClient._node2object = function(node, wsdlTypes)
{
	// null node
	if(node == null)
		return null;
	// text node
	if(node.nodeType == 3 || node.nodeType == 4)
		return SOAPClient._extractValue(node, wsdlTypes);
	// leaf node
	if (node.childNodes.length == 1 && (node.childNodes[0].nodeType == 3 || node.childNodes[0].nodeType == 4))
		return SOAPClient._node2object(node.childNodes[0], wsdlTypes);
	var typeFromWsdl = SOAPClient._getTypeFromWsdl(node.nodeName, wsdlTypes).toLowerCase(); // WoodWing: Save to use twice
	var isarray = typeFromWsdl.indexOf("arrayof") != -1;
	// object node
	if(!isarray)
	{
		// >>> WoodWing: For large text (string) data (>4K), the node has text child nodes which requires 
		// concatenation instead of normal child node treament, or else the "obj['text'] = p;" statement 
		// will overwrite all collected texts and only keep the last collected...!
		if( typeFromWsdl == "xsd:string" ) {
			var s = "";
			for(var i = 0; i < node.childNodes.length; i++) {
				var value = (node.childNodes[i].nodeValue != null) ? node.childNodes[i].nodeValue + "" : "";
				s += value; // assume all nodes are text (as the WSDL says so)
			}
			return s;
		} else { // <<<
			var obj = null;
			if(node.hasChildNodes())
				obj = new Object();
			for(var i = 0; i < node.childNodes.length; i++)
			{
				var p = SOAPClient._node2object(node.childNodes[i], wsdlTypes);
				obj[node.childNodes[i].nodeName] = p;
			}
			return obj;
		}
	}
	// list node
	else
	{
		// create node ref
		var l = new Array();
		for(var i = 0; i < node.childNodes.length; i++)
			l[l.length] = SOAPClient._node2object(node.childNodes[i], wsdlTypes);
		return l;
	}
	return null;
}
SOAPClient._extractValue = function(node, wsdlTypes)
{
	var value = node.nodeValue;
	switch(SOAPClient._getTypeFromWsdl(node.parentNode.nodeName, wsdlTypes).toLowerCase())
	{
		default: // WoodWing: changed "s:" into "xsd:" namespace for all cases below
		case "xsd:string":			
			return (value != null) ? value + "" : "";
		case "xsd:boolean":
			return value + "" == "true";
		case "xsd:int":
		case "xsd:long":
			return (value != null) ? parseInt(value + "", 10) : 0;
		case "xsd:double":
			return (value != null) ? parseFloat(value + "") : 0;
		case "xsd:datetime":
			if(value == null)
				return null;
			else
			{
				value = value + "";
				value = value.substring(0, (value.lastIndexOf(".") == -1 ? value.length : value.lastIndexOf(".")));
				value = value.replace(/T/gi," ");
				value = value.replace(/-/gi,"/");
				var d = new Date();
				d.setTime(Date.parse(value));										
				return d;				
			}
	}
}
SOAPClient._getTypesFromWsdl = function(wsdl)
{
	var wsdlTypes = new Array();
	// IE
	var ell = wsdl.getElementsByTagName("xsd:element");	// WoodWing: changed "s:" into "xsd:" namespace
	var useNamedItem = true;
	// MOZ
	
	//alert(debugXML( wsdl, false ));
	
	if(ell.length == 0)
	{
		ell = wsdl.getElementsByTagName("element");	     
		useNamedItem = false;
	}
	for(var i = 0; i < ell.length; i++)
	{
		if(useNamedItem)
		{
			if(ell[i].attributes.getNamedItem("name") != null && ell[i].attributes.getNamedItem("type") != null) 
				wsdlTypes[ell[i].attributes.getNamedItem("name").nodeValue] = ell[i].attributes.getNamedItem("type").nodeValue;
		}	
		else
		{
			if(ell[i].attributes["name"] != null && ell[i].attributes["type"] != null)
				wsdlTypes[ell[i].attributes["name"].value] = ell[i].attributes["type"].value;
		}
	}
	return wsdlTypes;
}
SOAPClient._getTypeFromWsdl = function(elementname, wsdlTypes)
{
    var type = wsdlTypes[elementname] + "";
    return (type == "undefined") ? "" : type;
}
// private: utils
SOAPClient._getElementsByTagName = function(document, tagName)
{
	try
	{
		// trying to get node omitting any namespaces (latest versions of MSXML.XMLDocument)
		// Please ignore the FireFox Venkman JavaScript Debugger, document.setProperty is AVAILABLE in FireFox
		document.setProperty("SelectionLanguage", "XPath");   // WW fix
		return document.selectNodes(".//*[local-name()=\""+ tagName +"\"]");
	}
	catch (ex) {}
	// old XML parser support
	if (/Firefox[\/\s](\d+\.\d+)/.test(navigator.userAgent)){ //test for Firefox/x.x or Firefox x.x (ignoring remaining digits);
 		var ffversion=new Number(RegExp.$1) // capture x.x portion and store as a number
 		if (ffversion>=3) // Check whether it is FF version 3, if yes, then we must pass the namespace as well
 			return document.getElementsByTagName( 'ns1:' + tagName);
 	}
	return document.getElementsByTagName(tagName);
}
// private: xmlhttp factory
SOAPClient._getXmlHttp = function() 
{
	try
	{
		if(window.XMLHttpRequest) 
		{
			var req = new XMLHttpRequest();
			// some versions of Moz do not support the readyState property and the onreadystate event so we patch it!
			if(req.readyState == null) 
			{
				req.readyState = 1;
				req.addEventListener("load", 
									function() 
									{
										req.readyState = 4;
										if(typeof req.onreadystatechange == "function")
											req.onreadystatechange();
									},
									false);
			}
			return req;
		}
		if(window.ActiveXObject)  {
			return new ActiveXObject(SOAPClient._getXmlHttpProgID());
		}
	}
	catch (ex) {}
	throw new Error("Your browser does not support XmlHttp objects");
}
SOAPClient._getXmlHttpProgID = function()
{
	if(SOAPClient._getXmlHttpProgID.progid)
		return SOAPClient._getXmlHttpProgID.progid;
	// WoodWing: Added "MSXML2.XMLHTTP.6.0" or else for a soap error, a generic parse raises instead, 
	//   which happens under IE only: "Download of the specified resource has failed"
	var progids = ["MSXML2.XMLHTTP.7.0", "MSXML2.XMLHTTP.6.0", "Msxml2.XMLHTTP.5.0", "Msxml2.XMLHTTP.4.0", "MSXML2.XMLHTTP.3.0", "MSXML2.XMLHTTP", "Microsoft.XMLHTTP"];
	var o;
	for(var i = 0; i < progids.length; i++)
	{
		try
		{
			o = new ActiveXObject(progids[i]);
			return SOAPClient._getXmlHttpProgID.progid = progids[i];
		}
		catch (ex) {};
	}
	throw new Error("Could not find an installed XML parser");
}
