<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v10.0.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class IdsAutomationServerJobFacade
{
	/**
	 * Function to create IdsAutomation jobs for an layout object.
	 *
	 * This function can be used by other plugins to easily create an IdsAutomation server job
	 * when needed. The object type and status id aren't mandatory, if known they can be passed
	 * to this function to optimize the call.
	 *
	 * @param integer $objId
	 * @param string|null $objType
	 * @param integer|null $stateId
	 * @return boolean|null Returns true if a job was created, otherwise false. Null is returned when the plugin isn't activated
	 */
	public static function createIdsAutomationJobsForLayout( $objId, $objType = null, $stateId = null ) {
		require_once dirname(__FILE__) . '/IdsAutomationUtils.class.php';
		if ( IdsAutomationUtils::isPluginActivated() ) {
			if (!$objType) {
				$objType = IdsAutomationUtils::getObjectType($objId);
			}
			if (!$stateId) {
				$stateId = IdsAutomationUtils::getStatusId($objId);
			}

			return IdsAutomationUtils::createIdsAutomationJobsForLayout($objId, $objType, $stateId);
		} else {
			LogHandler::Log('IdsAutomation', 'DEBUG', 'Skip creating IdsAutomation jobs since the plugin is deactivated.');
		}
	}

	/**
	 * Function to create IdsAutomation jobs for a layout on which the given object id is placed.
	 *
	 * This function can be used by other plugins to easily create an IdsAutomation server job
	 * when needed. The object type and status id aren't mandatory, if known they can be passed
	 * to this function to optimize the call.
	 *
	 * This call is used by the Content Station 10 plugin.
	 *
	 * @param integer $objId
	 * @param string|null $objType
	 * @param integer|null $stateId
	 * @return boolean|null Returns true if a job was created, otherwise false. Null is returned when the plugin isn't activated
	 */
	public static function createIdsAutomationJobsForPlacedObject( $objId, $objType = null, $stateId = null ) {
		require_once dirname(__FILE__) . '/IdsAutomationUtils.class.php';
		if ( IdsAutomationUtils::isPluginActivated() ) {
			if ( !$objType ) {
				$objType = IdsAutomationUtils::getObjectType($objId);
			}
			if ( !$stateId ) {
				$stateId = IdsAutomationUtils::getStatusId($objId);
			}

			return IdsAutomationUtils::createIdsAutomationJobsForPlacedObject($objId, $stateId, $objType);
		} else {
			LogHandler::Log('IdsAutomation', 'DEBUG', 'Skip creating IdsAutomation jobs since the plugin is deactivated.');
		}
	}
}