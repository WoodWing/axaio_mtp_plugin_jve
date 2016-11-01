<?php
/**
 * @package     Enterprise
 * @subpackage  MaintenanceMode
 * @since       v10.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Admin web application to configure this plugin.
 */

require_once BASEDIR.'/server/utils/htmlclasses/EnterpriseWebApp.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';


class MaintenanceMode_Configure_EnterpriseWebApp extends EnterpriseWebApp
{
	const PLUGIN_UNIQUE_NAME = 'MaintenanceMode';
	const INFO = 1;
	const WARNING = 2;
	const ERROR = 3;
	/** @var boolean The disable log in is activated. */
	private $disableLogIn = false;
	/** @var string Message to show user during log in. */
	private $disableLogInMessage = '';
	/** @var string Date/Time (ISO format) from which the log in is disabled. */
	private $disableFromDateTimeISO = '';
	/** @var string Time from which the log in is disabled. */
	private $message = '';
	/** @var string Html body. */
	private $htmlBody = '';

	public function isEmbedded()
	{
		return true;
	}

	public function getAccessType()
	{
		return 'admin';
	}

	public function getTitle()
	{
		return 'Maintenance Mode';
	}

	public function getHtmlBody()
	{
		$htmlTemplateFile = dirname( __FILE__ ).'/MaintenanceMode_Configure_Template.htm';
		$this->htmlBody = HtmlDocument::loadTemplate( $htmlTemplateFile );

		if( $this->isSubmitted() ) {
			$this->readAndSaveSettings();
		} else {
			$this->getPreviousSettings();
		}

		$this->updateHtmlBody();
		return $this->htmlBody;
	}

	private function isSubmitted()
	{
		if( isset( $_POST['save'] ) && $_POST['save'] == 'Save' ) {
			return true;
		}

		return false;
	}

	private function readAndSaveSettings()
	{
		$this->readInput();
		$this->validateDateSetting();
		if( $this->saveConfig() ) {
			$this->message .= $this->markupMsg( BizResources::localize( 'WORDPRESS_SAVE_COMPLETED' ), self::INFO );
		} else {
			$this->message .= $this->markupMsg( BizResources::localize( 'MaintenanceMode.SAVE_FAILED' ), self::ERROR );
		}
	}

	private function readInput()
	{
		if( isset( $_POST['disableloginmessage'] ) ) {
			$this->disableLogInMessage = $_POST['disableloginmessage'];
		}
		$disableFromDate = '';
		if( isset( $_POST['disablefrom_date'] ) ) {
			$disableFromDate = $_POST['disablefrom_date'];
		}
		$disableFromTime = '00:00';
		if( isset( $_POST['disablefrom_time'] ) && !empty( $_POST['disablefrom_time'] ) ) {
			$disableFromTime = $_POST['disablefrom_time'];
		}
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		$this->disableFromDateTimeISO = DateTimeFunctions::validDate( $disableFromDate.$disableFromTime.':00' );
		if( isset( $_POST['disablelogin'] ) && $_POST['disablelogin'] == 'on' ) {
			$this->disableLogIn = true;
		} else {
			$this->disableLogIn = false;
		}
	}

	private function validateDateSetting()
	{
		$disabledFromTimeStamp = strtotime( $this->disableFromDateTimeISO );
		if( $this->disableLogIn ) {
			if( $disabledFromTimeStamp ) {
				$dateFormatted = strftime( '%c', $disabledFromTimeStamp );
				if( time() >= $disabledFromTimeStamp ) {
					$dateFormatted = strftime( '%c', $disabledFromTimeStamp );
					$this->message .= $this->markupMsg( BizResources::localize( 'MaintenanceMode.LOGIN_DISABLE_FROM_DIRECT', false, array( $dateFormatted ) ), self::WARNING );
				} else {
					$this->message .= $this->markupMsg( BizResources::localize( 'MaintenanceMode.LOGIN_DISABLE_FROM_DATE', false, array( $dateFormatted ) ), self::INFO );
				}
			} else {
				$this->message .= $this->markupMsg( BizResources::localize( 'INVALID_DATE' ), self::ERROR );
			}
		}
	}

	private function getPreviousSettings()
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		$serializedSettings = DBConfig::getValue( self::PLUGIN_UNIQUE_NAME.'_settings' );

		if( $serializedSettings ) {
			$settings = unserialize( $serializedSettings );
			$this->disableLogIn = $settings['disableLogIn'];
			$this->disableFromDateTimeISO = $settings['disableFromDateTimeISO'];
			$this->disableLogInMessage = $settings['disableLogInMessage'];
		} else {
			$this->setDefaults();
		}
	}

	private function setDefaults()
	{
		$this->disableLogInMessage = 'Enter a message';
		$this->disableFromDateTimeISO = '';
		$this->disableLogIn = false;
	}

	private function saveConfig()
	{
		$settings = array(
			'disableLogIn' => $this->disableLogIn,
			'disableLogInMessage' => $this->disableLogInMessage,
			'disableFromDateTimeISO' => $this->disableFromDateTimeISO,
		);

		$serializedSettings = serialize( $settings );
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		$result = DBConfig::storeValue( self::PLUGIN_UNIQUE_NAME.'_settings', $serializedSettings );

		return $result;
	}

	private function updateHtmlBody()
	{
		$checked = $this->disableLogIn ? 'checked' : '';
		$disableLogIn = inputvar( 'disablelogin', $checked, 'checkbox', null, true );
		$disableFromDateTime = inputvar( 'disablefrom', $this->disableFromDateTimeISO, 'datetime', null, true );
		$disableLogInMessage = inputvar( 'disableloginmessage', $this->disableLogInMessage, null );

		$this->htmlBody = str_replace( '<!--DISABLE_LOGIN_FIELD-->', BizResources::localize( 'MaintenanceMode.LOGIN_DISABLE_FIELD' ), $this->htmlBody );
		$this->htmlBody = str_replace( '<!--DISABLE_LOGIN-->', $disableLogIn, $this->htmlBody );
		$this->htmlBody = str_replace( '<!--DISABLE_FROM_FIELD-->', BizResources::localize( 'MaintenanceMode.LOGIN_DISABLE_DATE_FIELD' ), $this->htmlBody );
		$this->htmlBody = str_replace( '<!--DISABLE_FROM_DATE_TIME-->', $disableFromDateTime, $this->htmlBody );
		$this->htmlBody = str_replace( '<!--DISABLE_MESSAGE_FIELD-->', BizResources::localize( 'LIC_MESSAGE' ), $this->htmlBody );
		$this->htmlBody = str_replace( '<!--DISABLE_MESSAGE-->', $disableLogInMessage, $this->htmlBody );
		$this->htmlBody = str_replace( '<!--MESSAGES-->', $this->message, $this->htmlBody );
	}

	/**
	 * Composes a HTML fragment to display an info text or error message.
	 *
	 * @param string $msg Message to display
	 * @param int $type , INFO = green, WARN = orange, ERROR = red
	 * @return string HTML fragment that contains the text (marked up)
	 */
	private function markupMsg( $msg, $type )
	{
		if( $msg ) {
			if( $type == self::ERROR ) {
				$msg = '<p style="color:#ff0000">'.$msg.'</p></b>';
			} elseif( $type == self::INFO ) {
				$msg = '<p style="color:#01a701">'.$msg.'</p></b>';
			} else {
				$msg = '<p style="color:#d17304">'.$msg.'</p></b>';
			}
		}

		return $msg;
	}
}
