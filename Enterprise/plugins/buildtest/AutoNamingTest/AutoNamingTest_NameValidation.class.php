<?php
require_once BASEDIR . '/server/interfaces/plugins/connectors/NameValidation_EnterpriseConnector.class.php';

class AutoNamingTest_NameValidation extends NameValidation_EnterpriseConnector
{
	public function validatePassword( $password )
	{
		$password = $password; // keep code analyzer happy
	}

	public function validateMetaDataAndTargets( $user, MetaData &$meta, &$targets )
	{
		$user = $user; $meta = $meta; $targets = $targets; // keep code analyzer happy
	}
	
	public function validateMetaDataInMultiMode( $user, MetaData $invokedMetaData, array &$changedMetaDataValues )
	{
		$user = $user; $invokedMetaData = $invokedMetaData; $changedMetaDataValues = $changedMetaDataValues; // keep code analyzer happy
	}

	/**
	 * To inform the core how the connector wants the autonaming
	 * @param string $user user setting the meta data
	 * @param MeataData $metaData Metadata of the object.
	 * @param array Object targets
	 * @param array Relations of the object
	 * @return null|boolean Null if core should decide, true if autonaming must be applied, false if no autonaming must
	 * be applied.
	 */
	public function applyAutoNamingRule( $user, $metaData, $targets, $relations )
	{
		// Make analyzer happy
		$user = $user;
		$targets = $targets;
		$relations = $relations;

		$autoNaming = null;
		switch( $metaData->BasicMetaData->Name ) {
			case 'dossier_web_123':
			case 'dossier_mover_123':
				$autoNaming = true;
				break;
			case 'dossier_web_abc':
			case 'dossier_mover_abc':
				$autoNaming = false;
				break;
		}
		return $autoNaming;
	}

	public function applyAutoTargetingRule( $user, Relation $relation, $parentType, $childType, &$extraTargets )
	{
		$user = $user; $parentType = $parentType; $childType = $childType; // make code analyser happy
	}

	public function getPrio() { return self::PRIO_DEFAULT; }
}
