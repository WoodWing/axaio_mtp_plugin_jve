try
{
	var doc = app.documents.item(0);
	// Access the document’s metadata
	var md = doc.entMetaData;
	var mdName = md.get("Core_Name" );
	alert( "*** Sample Enterprise Script ***\n*** After Open Article (InCopy)) ***\n*** Article = " + mdName + " ***" );
}
catch(E)
{
	alert( "ERROR: " + E.name + "\n\n" + E.message + "\n\nFound on line " + E.line );
}
