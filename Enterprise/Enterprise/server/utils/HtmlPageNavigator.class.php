<?php

/**
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 * 
 * Determines how to jump back to the previous physical HTML page, ignoring all submits on the current page.
 * For example, go from page A to B and then submit page B for a number of times.
 * So the history looks like A-B-B-B-B. Now pressing the back button of the browser whould still show page B.
 * The HtmlPageNavigator keeps track of the history and jumps back to page A at once.
 * 
 * Example:
 * 
 * $nav = new HtmlPageNavigator();
 * $html .= '<form action="'.$nav->GetURL().'" method="post">';
 * $html .= 'Hello World<br>';
 * $html .= $nav->GetBackIconButton();
 * $html .= '</form>';
 * print HtmlDocument::buildDocument( $html );
 * 
 */

class HtmlPageNavigator
{
	protected $thisurl = null;

	public function __construct()
	{
		$this->backcount = @$_REQUEST['BackCount'];
		if( $this->backcount == '' ) $this->backcount = 0;
		$this->backcount -= 1;
		$this->thisurl = $_SERVER['PHP_SELF'].'?BackCount='.$this->backcount;
	}

	public function GetBackIconButton()
	{
		$backtxt = BizResources::localize('ACT_BACK');
		$backimg = SERVERURL_ROOT.INETROOT.'/config/images/back_32.gif';
		return '<img src="'.$backimg.'" border=0 title="'.$backtxt.'" onclick="history.go('.$this->backcount.');return false;">';
	}

	public function GetBackPressButton()
	{
		$backtxt = BizResources::localize('ACT_BACK');
		return '<input type=submit value="'.$backtxt.'" title="'.$backtxt.'" onclick="history.go('.$this->backcount.');return false;"/>';
	}

	public function GetURL()
	{ 
		return $this->thisurl; 
	}
}