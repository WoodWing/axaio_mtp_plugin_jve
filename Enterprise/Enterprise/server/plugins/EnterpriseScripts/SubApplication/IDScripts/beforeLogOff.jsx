try{
	alert( "*** Sample Enterprise Script ***\n*** Before Log Off (InDesign)) ***" );
}
catch(E){
	alert( "ERROR: " + E.name + "\n\n" + E.message + "\n\nFound on line " + E.line );
}
