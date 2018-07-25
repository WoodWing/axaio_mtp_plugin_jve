<?php
/**
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflGetDialog2_EnterpriseConnector.class.php';
require_once BASEDIR .'/server/bizclasses/BizContentSource.class.php';
require_once dirname(__FILE__) . '/SmartArchive.class.php';

class SmartArchive_WflGetDialog2 extends WflGetDialog2_EnterpriseConnector
{
	final public function getPrio () {	return self::PRIO_DEFAULT; }
	final public function getRunMode () { return self::RUNMODE_BEFOREAFTER; }
	
	final public function runBefore( WflGetDialog2Request &$req )
	{
		$id = $req->MetaData['ID']->PropertyValues[0]->Value;
		if( BizContentSource::isAlienObject( $id ) ) { // any alien object?
			if( SmartArchive::isContentSourceID($id) ){
				//Raise Error when user tries to Copy objects(Copy to), 'Send To', 'Create dossier'
				
				if($req->Action == 'CopyTo' || $req->Action == 'SendTo' || $req->Action == 'Create'){
					throw new BizException( 'ERR_AUTHORIZATION', 'Client', '' );
				}
			}
		}
	}
	
	final public function runAfter( WflGetDialog2Request $req, WflGetDialog2Response &$resp )
	{
		$id = $req->MetaData['ID']->PropertyValues[0]->Value;

		if( BizContentSource::isAlienObject( $id ) ) { // any alien object?
			//Refer to email Subject: "Re: Set Properties - show read only"
			/*$externalObjId =*/ 
			if( SmartArchive::isContentSourceID($id) ){
			// Note: in the future we might need $externalObjId representing the archive object id

				// Make all properties read only
				if( $resp->Dialog){
					if( $resp->Dialog->Tabs){
						if( $resp->Dialog->Tabs[0]->Widgets){
							foreach( $resp->Dialog->Tabs[0]->Widgets as $widget){
								if( isset($widget->PropertyUsage->Editable) ) {
									$widget->PropertyUsage->Editable = false;
								}
								
							}
						}
					}
				}
			}
		}
	}

	final public function runOverruled( WflGetDialog2Request $req )
	{
		// not called
	}

}
