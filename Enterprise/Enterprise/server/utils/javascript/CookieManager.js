/**
 * Cookie Manager for web browser applications. </br>
 * Allows to get, set and delete cookies stored at web browser. <br/>
 * 
 * @package 	SCEnterprise
 * @subpackage 	WebApps
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * Constructor
 */
function CookieManager()
{
}

/**
 * Retrieve the current value of a cookie. <br/>
 *
 * @param nameCookie string  Name of the cookie. <br/>
 * @return string            Current value of the cookie. <br/>
 */
CookieManager.prototype.getCookie = function( nameCookie ) 
{
	var arg = nameCookie + "=";
	var alen = arg.length;
	var clen = document.cookie.length;
	var i = 0;
	while (i < clen) {
		var j = i + alen;
		if (document.cookie.substring(i, j) == arg)
			return getCookieVal (j);
		i = document.cookie.indexOf(" ", i) + 1;
		if (i == 0) break;
	}
	
	return null;

	function getCookieVal(offset) 
	{
		var endstr = document.cookie.indexOf (";", offset);
		if (endstr == -1)
			endstr = document.cookie.length;
		return unescape(document.cookie.substring(offset, endstr));
	}
}

/**
 * Set a new value for a cookie. <br/>
 *
 * @param nameCookie string  Name of the cookie. <br/>
 * @param valueCookie string Value to be set for the cookie <br/>
 * @param expires Date       Optional. Time when the cookie should expire. Default scope is current session. <br/>
 * @param path string        Optional. Path to store cookie at. Default empty. <br/>
 * @param domain string      Optional. Domain to store cookie at. Default empty. <br/>
 * @param secure boolean     Optional. Whether or not to store cookie secure. Default false <br/>
 */
CookieManager.prototype.setCookie = function( nameCookie, valueCookie, expires, path, domain, secure )
{
	var newCookie = nameCookie + "=" + escape(valueCookie) +
		((expires == null) ? "" : ("; expires=" + expires.toGMTString())) +
		((path == null) ? "" : ("; path=" + path)) +
		((domain == null) ? "" : ("; domain=" + domain)) +
		((secure == true) ? "; secure" : "");
	document.cookie = newCookie;
}

/**
 * Delete a cookie. This is actually done by browser as soon as the current session ends. <br/>
 *
 * @param nameCookie string  Name of the cookie. <br/>
 */
CookieManager.prototype.deleteCookie = function( nameCookie ) 
{
	var exp = new Date();
	exp.setTime( exp.getTime() - 1800000 );
	var valueCookie = CookieManager.prototype.getCookie( nameCookie );
	document.cookie = nameCookie + "=" + escape(valueCookie) + "; expires=" + exp.toGMTString();
}
