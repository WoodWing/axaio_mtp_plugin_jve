try
{
	alert( "*** Sample Enterprise Script ***\n*** Before Save Article (InCopy)) ***" );
}
catch(E)
{
	alert( "ERROR: " + E.name + "\n\n" + E.message + "\n\nFound on line " + E.line );
}
