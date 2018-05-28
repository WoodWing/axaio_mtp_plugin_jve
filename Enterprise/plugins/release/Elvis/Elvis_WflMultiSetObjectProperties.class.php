<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Mulitple Set Object Properties workflow web service.
 * Called when an end-user changes properties for a selection of objects (typically using CS).
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflMultiSetObjectProperties_EnterpriseConnector.class.php';

class Elvis_WflMultiSetObjectProperties extends WflMultiSetObjectProperties_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	/**
	 * @var AdmStatus[] Old status configurations per object id.
	 */
	private $oldStatuses = null;

	final public function runBefore( WflMultiSetObjectPropertiesRequest &$req )
	{
		// Find out if the state property changed and retrieve current statuses if this is the case
		$statePropertyChanged = false;
		foreach( $req->MetaData as $MetaDataValue ) {
			if( $MetaDataValue->Property == 'State' || $MetaDataValue->Property == 'StateId' ) {
				$statePropertyChanged = true;
				break;
			}
		}

		if( $statePropertyChanged ) {
			require_once __DIR__.'/util/ElvisObjectUtils.class.php';
			$this->oldStatuses = ElvisObjectUtils::getObjectsStatuses( ElvisObjectUtils::filterRelevantIdsFromObjectIds( $req->IDs ) );
		}
	} 

	final public function runAfter( WflMultiSetObjectPropertiesRequest $req, WflMultiSetObjectPropertiesResponse &$resp )
	{
		if( !is_null( $this->oldStatuses ) ) {
			require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';

			$changedObjectIds = array();
			// Find out if the state property changed and keep the target status name
			$targetStatusName = null;
			foreach( $req->MetaData as $MetaDataValue ) {
				if( $MetaDataValue->Property == 'State' ) {
					if( isset( $MetaDataValue->PropertyValues[0]->Value ) ) {
						$targetStatusName = $MetaDataValue->PropertyValues[0]->Value;
					}
					break;
				} else if( $MetaDataValue->Property == 'StateId' ) {
					if( isset( $MetaDataValue->PropertyValues[0]->Value ) ) {
						$stateId = $MetaDataValue->PropertyValues[0]->Value;
						if( $stateId != '' ) {
							$targetStatusName = BizAdmStatus::getStatusWithId( $MetaDataValue->PropertyValues[0]->Value )->Name;
						} else {
							$targetStatusName = '';
						}
					}
					break;
				}
			}

			// Detect objects changing from archived to non-archived statuses
			foreach( $this->oldStatuses as $objId => $curStatusCfg ) {
				if( empty( $targetStatusName ) && isset($curStatusCfg->NextStatus->Id) ) {
					$objTarStatusName = BizAdmStatus::getStatusWithId( $curStatusCfg->NextStatus->Id )->Name;
				} else {
					$objTarStatusName = $targetStatusName;
				}
				if( ElvisObjectUtils::statusChangedToUnarchived( $curStatusCfg->Name, $objTarStatusName ) ) {
					$changedObjectIds[] = $objId;
				}
			}

			// Update Elvis with new shadow relations of un-archived objects (if any)
			if( !empty( $changedObjectIds ) ) {
				require_once __DIR__.'/logic/ElvisUpdateManager.class.php';
				ElvisUpdateManager::sendUpdateObjectsByIds( $changedObjectIds, null );
			}
		}
	} 
	
	// Not called.
	final public function runOverruled( WflMultiSetObjectPropertiesRequest $req )
	{
	} 
}
