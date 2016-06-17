<!--
	function keepSessionAlive()
	{
		var d = new Date();
		var src = 'keepsessionalive.php?t=' + d.valueOf(); //milliseconds since 1970 to force a refresh
		document.images.keepsessionalive.src = src;
		setTimeout( "keepSessionAlive()", 1000*60*5 ); //every 5 minutes
	}
	keepSessionAlive();
//-->