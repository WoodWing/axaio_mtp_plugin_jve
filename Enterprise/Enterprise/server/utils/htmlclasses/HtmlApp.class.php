<?php

require_once BASEDIR . '/server/utils/htmlclasses/HtmlBase.class.php';

class HtmlApp extends HtmlBase
{
	public $Ticket;
	public $User;
	public $Title;
	public $MainForm;
	public $InReport;

	/**
	 * Constructor
	 *
	 * @param string $name
	 * @param string $title
	 */
	public function __construct($name, $title = '')
	{
		if (isset(self::$Application))
		{
			die('Application allready constructed -> There can only be one instance of an application');
		}
		self::$Application = $this;
		HtmlBase::__construct(null, $name);
		$this->Title = $title;
		$this->MainForm = null;
		$this->InReport = false;
	}
	
	public function run()
	{
		try {
			$err = '';
			$this->MainForm->createFields();
			$error = $this->MainForm->execAction();
			$result = $this->MainForm->drawHeader() . "\n";
			$this->MainForm->fetchData();
			$result .= $this->drawBody() . "\n";
			$result .= $this->MainForm->drawBody() . "\n";

			print HtmlDocument::buildDocument($result);
			// This error is not fatal, therefore the page was successfully drawn and loaded,
			// we just show the error to the end-user.
			if( $error ) {
				print '<script language="javascript">alert("'.$error.'");</script>';
			}

		} catch( BizException $e ) {
			// BizException is caught and it is not recoverable, we cannot continue drawing the page.
			// Here, we set the flag (hasError) to be true so that the MainForm is aware that error has
			// occurred, this will enable MainForm to decide what page to draw (e.g draw the page as if
			// it is being loaded for the first time / draw the page with error shown and etc).
			$this->MainForm->setError();
			$err = "onLoad='javascript:alert(\"". $e->getMessage() ."\")'";

			// Calling the MainForm to draw the page again.
			$this->MainForm->createFields();
			//$error = $this->MainForm->execAction(); // This is not called anymore since the operation above failed, action will not be executed.
			$result = $this->MainForm->drawHeader() . "\n";
			$this->MainForm->fetchData();
			$result .= $this->drawBody() . "\n";
			$result .= $this->MainForm->drawBody() . "\n";

			require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
			print HtmlDocument::buildDocument( $result, true, $err );
		}
	}

	public function drawHeader()
	{
		$result = $this->MainForm->drawHeader();
		return $result;
	}
	
	public function drawBody()
	{
		return '';   
	}
}