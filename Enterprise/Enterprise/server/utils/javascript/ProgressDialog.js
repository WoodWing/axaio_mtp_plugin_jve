/**
 * Progress Dialog. </br>
 * Adds a floating dialog to the HTML document with progress message. <br/>
 * The dialog can be shown or hidden at any time. <br/>
 * Used to let user wait for logon requests to complete. <br/>
 * 
 * @package 	SCEnterprise
 * @subpackage 	WebApps
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * Constructor
 */
function ProgressDialog() 
{
	DialogBase.apply( this, arguments ); // base class constructor
}

ProgressDialog.prototype = new DialogBase(); // inherit from base class

/**
 * Adds a floating dialog to the HTML document with progress message. <br/>
 * Must be called after document.body is created! <br/>
 * 
 * @param message string   The message to display in the progress dialog. <br/>
 */
ProgressDialog.prototype.build = function( message )
{
	var sBaseDir = getBaseDir();
	DialogBase.prototype.buildDialog( "divProgressDlg", '\
				<table id="divProgressDialogBody" cellpadding="7" border="0" width="300px">\
					<tr><td><p>' + message + '</p></td>\
						<td><img src="'+sBaseDir+'config/images/wwloader.gif" border="0"/><td/>\
					</tr>\
				</table>\
	');
}

/**
 * Shows the floating dialog. Must be called after {@link build()}. <br/>
 */
ProgressDialog.prototype.show = function()
{
	DialogBase.prototype.showDialog( "divProgressDlg" );
	if( document.all ) { // IE only
		document.getElementById( "divProgressDlg" ).style.width = parseInt( document.getElementById( "divProgressDialogBody" ).width ) + 24 + "px";
	}
}	

/**
 * Hides the floating dialog. Should be called after {@link show()}. <br/>
 */
ProgressDialog.prototype.hide = function()
{
	DialogBase.prototype.hideDialog( "divProgressDlg" );
}	
