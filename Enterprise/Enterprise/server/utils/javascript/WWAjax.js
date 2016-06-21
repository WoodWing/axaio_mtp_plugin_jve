function WWAjax(){};

WWAjax.formParamsToUrl = function( theForm ) 
{
	var url = "";
	for( i = 0; i < theForm.elements.length; i++ ) {
		elem = theForm.elements[i];
		// debug first posted content frame:
		//if( elem.name == "contentframe0" ) {
		//	alert( elem.name + ": " + encodeURIComponent(elem.value) );
		//}
		// [EKL@v4.2] encodeURI() does not encode ampersand (&), but encodeURIComponent() does.
		//            Else you loose all content typed after the ampersand!
		url += elem.name + '=' + encodeURIComponent(elem.value) + "&";
	}
	if( url.length > 0 ) {
		url = url.substr(0, url.length - 1); // remove last "&"
	}
	return url;
}

WWAjax.doHttpRequest = function(url, callbackFn, mimeType, returnXml, postData, async)
{
	var httpReq = false;
	if (window.XMLHttpRequest) { // Mozilla, Safari
		httpReq = new XMLHttpRequest();
		if (httpReq.overrideMimeType) {
			httpReq.overrideMimeType( mimeType );
		}
	} else if (window.ActiveXObject) { // IE
		try {
			httpReq = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				httpReq = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {}
		}
	}

	if (httpReq) {
		httpReq.onreadystatechange = function() {
			if (httpReq.readyState == 4) {
				//alert( httpReq.getAllResponseHeaders() );
				if( callbackFn != null ) {
					if (returnXml == true) {
						var xmlResult = httpReq.responseXML;
						if(!xmlResult || !xmlResult.documentElement ) { // IE, Opera
							xmlResult = httpReq.responseText;
							xmlResult = xmlResult.replace(/>/g, ">\n"); // make xml readable
						} else { // FF
							var rootNodeName = xmlResult.documentElement.nodeName; 
							if(rootNodeName == "parsererror") {
								xmlResult = httpReq.responseText;
								xmlResult = xmlResult.replace(/>/g, ">\n"); // make xml readable
							}
						}
						eval(callbackFn + '(xmlResult, httpReq.status)');
					} else {
						eval(callbackFn + '(httpReq.responseText, httpReq.status)');
					}
				}
			}
		}
		if( postData == null ) {
			httpReq.open('GET', url, async);
			httpReq.send(null);
		} else {
			httpReq.open('POST', url, async);
			httpReq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			httpReq.setRequestHeader("Content-length", postData.length);
			httpReq.setRequestHeader("Connection", "close");
			httpReq.send(postData);
		}
	}
}
