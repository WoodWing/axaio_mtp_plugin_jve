/**
 * Dialog base class to be inherited by logon and workflow dialogs. </br>
 * It allows users to drag the dialog using mouse. <br/>
 * 
 * @package 	SCEnterprise
 * @subpackage 	WebApps
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * Constructor
 */
function DialogBase() 
{
}

/**
 * Logs on to Enterprise server through web app services <br/>
 *
 * @param elementToDrag object   The DIV element (HTML DOM) of the dialog frame. <br/>
 * @param event                  Current event fired to the elementToDrag. <br/>
 */
DialogBase.prototype.beginDrag = function( elementToDrag, event )
{
	var deltaX = event.clientX - parseInt(elementToDrag.style.left);
	var deltaY = event.clientY - parseInt(elementToDrag.style.top);
	if (document.addEventListener){
		document.addEventListener("mousemove", moveHandler, true);
		document.addEventListener("mouseup", upHandler, true);
	}
	else if (document.attachEvent){
		document.attachEvent("onmousemove", moveHandler);
		document.attachEvent("onmouseup", upHandler);
	}
	else {
		var oldmovehandler = document.onmousemove;
		var olduphandler = document.onmouseup;
		document.onmousemove = moveHandler;
		document.onmouseup = upHandler;
	}
	if (event.stopPropagation) event.stopPropagation();
	else event.cancelBubble = true;
	if (event.preventDefault) event.preventDefault();
	else event.returnValue = false;
	
	function moveHandler(e){
		if (!e) e = window.event;
		elementToDrag.style.left = (e.clientX - deltaX) + "px";
		elementToDrag.style.top = (e.clientY - deltaY) + "px";
		if (e.stopPropagation) e.stopPropagation();
		else e.cancelBubble = true;
	}
	
	function upHandler(e){
		if (!e) e = window.event;
		if (document.removeEventListener){
			document.removeEventListener("mouseup", upHandler, true);
			document.removeEventListener("mousemove", moveHandler, true);
		}
		else if (document.detachEvent){
			document.detachEvent("onmouseup", upHandler);
			document.detachEvent("onmousemove", moveHandler);
		}
		else {
			document.onmouseup = olduphandler;
			document.onmousemove = oldmovehandler;
		}
		if (e.stopPropagation) e.stopPropagation();
		else e.cancelBubble = true;
	}
}

/**
 * Adds a floating modal dialog to the HTML document model. <br/>
 * Must be called after document.body is created! <br/>
 *
 * @param dlgId string    An unique dialog ID.
 * @param dlgBody string  HTML fragment to be drawm in the dialog.
 */
DialogBase.prototype.buildDialog = function( dlgId, dlgBody )
{
	var sBaseDir = getBaseDir();
	var fullDlg = ' \
		<!-- background layer to block user input to underlaying HTML widgets -->\
		<div id="darkBackgroundLayer_'+dlgId+'" class="dlg_backlayer" >\
		</div>\
		\
		<div id="' + dlgId + '" style="position:absolute; z-index:10; left:350px; top:160px; display:none;" >\
			<div class="dlg_outerframe" >\
				<table cellSpacing="0" cellPadding="0" border="0">\
					<!-- dialog header -->\
					<tr onMouseDown="DialogBase.prototype.beginDrag(this.parentNode.parentNode.parentNode.parentNode, event);" style="cursor:pointer;">\
						<td width="17" height="15" valign="bottom" class="dlg_border" ><img height="15" src="'+sBaseDir+'config/images/border/dlg_lt.gif" width="17"/></td>\
						<td class="dlg_border" background="'+sBaseDir+'config/images/border/dlg_top.gif"><img height="1" src="'+sBaseDir+'config/images/border/dlg_pix.gif" width="1" border="0"/></td>\
						<td width="25" valign="bottom" class="dlg_border" ><img height="15" src="'+sBaseDir+'config/images/border/dlg_rt.gif" width="25"/></td>\
					</tr>\
					<tr onMouseDown="DialogBase.prototype.beginDrag(this.parentNode.parentNode.parentNode.parentNode, event);" style="cursor:pointer;">\
						<td rowspan="2" class="dlg_border" background="'+sBaseDir+'config/images/border/dlg_left.gif"></td>\
						<td align="center"></td>\
						<td rowspan="2" class="dlg_border" background="'+sBaseDir+'config/images/border/dlg_right.gif"></td>\
					</tr>\
					<!-- dialog body -->\
					<tr>\
						<td>' + dlgBody + '</td>\
					</tr>\
					<!-- dialog footer -->\
					<tr onMouseDown="DialogBase.prototype.beginDrag(this.parentNode.parentNode.parentNode.parentNode, event);" style="cursor:pointer;">\
						<td valign="top" height="24" width="17" class="dlg_border" ><img height="24" src="'+sBaseDir+'config/images/border/dlg_lb.gif" width="17"/></td>\
						<td class="dlg_border"  background="'+sBaseDir+'config/images/border/dlg_bottom.gif">&nbsp;</td>\
						<td valign="top" class="dlg_border" ><img height="24" src="'+sBaseDir+'config/images/border/dlg_rb.gif" width="25"/></td>\
					</tr>\
				</table>\
			</div>\
		</div>';
	doInsertAdjacentHTML( document.body, 'afterBegin', fullDlg );
	document.getElementById( "darkBackgroundLayer_" + dlgId ).style.display = "none";
}

/**
 * Removes the model dialog from the HTML document model. <br/>
 * Must be called after {@link buildDialog()}. <br/>
 *
 * @param dlgId string  An unique dialog ID.
 */
DialogBase.prototype.destroyDialog = function( dlgId )
{
	var dlg = document.getElementById( dlgId );
	dlg.innerHTML = "";
}

/**
 * Shows the modal dialog. <br/>
 * Must be called after {@link buildDialog()}. <br/>
 *
 * @param dlgId string  An unique dialog ID.
 */
DialogBase.prototype.showDialog = function( dlgId )
{
	document.getElementById( dlgId ).style.display = "";
	document.getElementById( "darkBackgroundLayer_" + dlgId ).style.display = "";
}

/**
 * Hides the modal dialog (without removing it from the HTML document model). <br/>
 * Must be called after {@link showDialog()}. <br/>
 *
 * @param dlgId string  An unique dialog ID.
 */
DialogBase.prototype.hideDialog = function( dlgId )
{
	document.getElementById( dlgId ).style.display = "none";
	document.getElementById( "darkBackgroundLayer_" + dlgId ).style.display = "none";
}
