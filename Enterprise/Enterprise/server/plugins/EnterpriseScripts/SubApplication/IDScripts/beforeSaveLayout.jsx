try
{
	alert( "*** Sample Enterprise Script ***\n*** Before Save Layout (InDesign)) ***" );
}
catch(E)
{
	alert( "ERROR: " + E.name + "\n\n" + E.message + "\n\nFound on line " + E.line );
}
