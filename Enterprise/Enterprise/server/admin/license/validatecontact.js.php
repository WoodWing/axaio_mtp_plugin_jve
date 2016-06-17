<?php
	require_once dirname(__FILE__).'/../../../config/config.php';
	require_once BASEDIR . '/server/secure.php';
?>
<!--
function testField( field, str )
{
	if ( field.value.length == 0 )
	{
		alert( str );
		field.focus()
		return false;
	}	
	return true;
}

function validateContact( f )
{
	if ( !testField( f.name, '<?php echo addslashes(BizResources::localize("LIC_ENTER_NAME")); ?>' ))
		return false;
	if ( !testField( f.email, '<?php echo addslashes(BizResources::localize("LIC_ENTER_EMAIL")); ?>' ))
		return false;
	if ( !testField( f.address1, '<?php echo addslashes(BizResources::localize("LIC_ENTER_ADDRESS")); ?>' ))
		return false;
	if ( !testField( f.zip, '<?php echo addslashes(BizResources::localize("LIC_ENTER_ZIPCODE")); ?>' ))
		return false;
	if ( !testField( f.city, '<?php echo addslashes(BizResources::localize("LIC_ENTER_CITY")); ?>' ))
		return false;
	if ( f.country.selectedIndex == 0 )
	{
		alert('<?php echo addslashes(BizResources::localize("LIC_ENTER_COUNTRY")); ?>');
		f.country.focus();
		return false;
	}
	
	//Only test the very first time, OR in case it has been changed
	if ( ( f.orgemail.value.length == 0 ) ||
	     ( f.orgemail.value != f.email.value ) )
	{
		var confirmEmailStr = '<?php echo addslashes(BizResources::localize("LIC_CHECK_EMAIL")); ?>';
		confirmEmailStr = str_replace( '%1', f.email.value, confirmEmailStr );
		if ( !confirm( confirmEmailStr ))
		{
			f.email.focus();
			return false;
		}
	}
	
	return true;
}

function loadCountries( f, defaultcountry )
{
	var countries = Array( "Afghanistan","Albania","Algeria","American Samoa","Andorra","Angola","Anguilla","Antarctica","Antigua And Barbuda","Argentina","Armenia","Aruba","Australia","Austria","Azerbaijan","Bahamas, The","Bahrain","Bangladesh","Barbados","Belarus","Belgium","Belize","Benin","Bermuda","Bhutan","Bolivia","Bosnia and Herzegovina","Botswana","Bouvet Island","Brazil","British Indian Ocean Territory","Brunei","Bulgaria","Burkina Faso","Burundi","Cambodia","Cameroon","Canada","Cape Verde","Cayman Islands","Central African Republic","Chad","Chile","China","China (Hong Kong S.A.R.)","China (Macau S.A.R.)","Christmas Island","Cocos (Keeling) Islands","Colombia","Comoros","Congo","Congo, Democractic Republic of the","Cook Islands","Costa Rica","Cote D'Ivoire (Ivory Coast)","Croatia (Hrvatska)","Cuba","Cyprus","Czech Republic","Denmark","Djibouti","Dominica","Dominican Republic","East Timor","Ecuador","Egypt","El Salvador","Equatorial Guinea","Eritrea","Estonia","Ethiopia","Falkland Islands (Islas Malvinas)","Faroe Islands","Fiji Islands","Finland","France","French Guiana","French Polynesia","French Southern Territories","Gabon","Gambia, The","Georgia","Germany","Ghana","Gibraltar","Greece","Greenland","Grenada","Guadeloupe","Guam","Guatemala","Guinea","Guinea-Bissau","Guyana","Haiti","Heard and McDonald Islands","Honduras","Hungary","Iceland","India","Indonesia","Iran","Iraq","Ireland","Israel","Italy","Jamaica","Japan","Jordan","Kazakhstan","Kenya","Kiribati","Korea","Korea, North","Kuwait","Kyrgyzstan","Laos","Latvia","Lebanon","Lesotho","Liberia","Libya","Liechtenstein","Lithuania","Luxembourg","Macedonia, Former Yugoslav Republic of","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Marshall Islands","Martinique","Mauritania","Mauritius","Mayotte","Mexico","Micronesia","Moldova","Monaco","Mongolia","Montserrat","Morocco","Mozambique","Myanmar","Namibia","Nauru","Nepal","Netherlands Antilles","Netherlands, The","New Caledonia","New Zealand","Nicaragua","Niger","Nigeria","Niue","Norfolk Island","Northern Mariana Islands","Norway","Oman","Pakistan","Palau","Panama","Papua new Guinea","Paraguay","Peru","Philippines","Pitcairn Island","Poland","Portugal","Puerto Rico","Qatar","Reunion","Romania","Russia","Rwanda","Saint Helena","Saint Kitts And Nevis","Saint Lucia","Saint Pierre and Miquelon","Saint Vincent And The Grenadines","Samoa","San Marino","Sao Tome and Principe","Saudi Arabia","Senegal","Seychelles","Sierra Leone","Singapore","Slovakia","Slovenia","Solomon Islands","Somalia","South Africa","South Georgia And The South Sandwich Islands","Spain","Sri Lanka","Sudan","Suriname","Svalbard And Jan Mayen Islands","Swaziland","Sweden","Switzerland","Syria","Taiwan","Tajikistan","Tanzania","Thailand","Togo","Tokelau","Tonga","Trinidad And Tobago","Tunisia","Turkey","Turkmenistan","Turks And Caicos Islands","Tuvalu","Uganda","Ukraine","United Arab Emirates","United Kingdom","United States","United States Minor Outlying Islands","Uruguay","Uzbekistan","Vanuatu","Vatican City State (Holy See)","Venezuela","Vietnam","Virgin Islands (British)","Virgin Islands (US)","Wallis And Futuna Islands","Western Sahara","Yemen","Yugoslavia","Zambia","Zimbabwe" );
	var n = countries.length;
	var f = document.forms.theForm;
	var c = f.country;
	for ( var i=0; i<n; i++ ) {
		var cname = countries[ i ];
		c.options[ i+1 ] = new Option( cname );
		if ( cname == defaultcountry ) {
			c.options[ i+1 ].selected = true;
		}
	}
}
//-->