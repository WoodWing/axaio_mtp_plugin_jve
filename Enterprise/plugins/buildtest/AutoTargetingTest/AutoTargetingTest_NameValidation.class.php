<?php
require_once BASEDIR . '/server/interfaces/plugins/connectors/NameValidation_EnterpriseConnector.class.php';

class AutoTargetingTest_NameValidation extends NameValidation_EnterpriseConnector
{
	public function validatePassword( $password )
	{
	}

	public function validateMetaDataAndTargets( $user, MetaData &$meta, &$targets )
	{
	}
	
	public function validateMetaDataInMultiMode( $user, MetaData $invokedMetaData, array &$changedMetaDataValues )
	{
	}
	
	/**
	 * When a printable object is added to a dossier, the core server targets it automatically to the print targets of the dossier.
	 * However, this behaviour can be overruled by the connector by implementing this function.
	 *
	 * @param string $user - acting user
	 * @param Relation $relation The object relation being created.
	 * @param string $parentType Object type of parent e.g. Layout, Dossier, etc
	 * @param string $childType Object type of child, e.g. Article, Image, etc.
	 * @param Target[] &$extraTargets List of targets returned by connector to automatically add to the relation. Empty when none.
	 * @return boolean Return true to let the core apply the auto targeting rule, else false.
	 */
	public function applyAutoTargetingRule( $user, Relation $relation, $parentType, $childType, &$extraTargets )
	{
		// Get all the possible targets
		require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
		$parentObjectTargets = BizTarget::getTargets($user, $relation->Parent);
		$retVal = true;

		// Add Facebook to the extraTargets var so it's added later on.
		foreach( $parentObjectTargets as $parentObjectTarget ){
			require_once BASEDIR . '/server/dbclasses/DBAdmPubChannel.class.php';
			$channel = DBAdmPubChannel::getPubChannelObj($parentObjectTarget->PubChannel->Id);
			if( $channel->Type == 'web' && $channel->PublishSystem == 'Facebook' ) {
				$extraTargets[] = $parentObjectTarget;
			}
		}

		// If the name of the article matches this name we don't want AutoTargeting enabled so we return a false.
		// This is needed to create the first scenario.
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		if ( DBObject::getObjectName( $relation->Child ) == 'AutoTargetingRule_TestArticleName1' ) {
			$retVal = false;
		}

		return $retVal;
	}

	public function getPrio() { return self::PRIO_DEFAULT; }
}
