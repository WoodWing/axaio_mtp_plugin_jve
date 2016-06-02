<!--
function geto(name)
{
	var d = document;
	if ( d.all )
		return d.all[ name ];
	else
		return d.getElementById( name );
}
function conError( str1, str2 )
{
	var o = geto( 'test');
	if ( o )
		o.innerHTML = "<img src='images/red.gif' width='10' height='10'> " + str1 + "<br><input type='button' name='retrybutton' value='" + str2 + "' onClick='retry();'>";
}
function conSuccess( str )
{
	var o = geto( 'test');
	if ( o )
		o.innerHTML = "<img src='images/green.gif' width='10' height='10'> " + str;
	try {
		var f = document.forms.theForm;
		f.access.value = 1; //skip the extra page to test the internet access
	}
	catch( e )
	{
	}
}
//-->