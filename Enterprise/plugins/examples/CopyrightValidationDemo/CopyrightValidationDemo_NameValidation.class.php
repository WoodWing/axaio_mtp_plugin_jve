<?php
/****************************************************************************
   Copyright 2008-2014 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

require_once BASEDIR.'/server/interfaces/plugins/connectors/NameValidation_EnterpriseConnector.class.php';

class CopyrightValidationDemo_NameValidation extends NameValidation_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }

	/**
	 * See NameValidation_EnterpriseConnector.class.php for comments.
	 *
	 * @param string $pass The new password about to apply
	 * @return bool true to indicate the password is valid.
	 */
	final public function validatePassword( $pass )
	{
		$pass = $pass;
		// Password valid, continue validate with standard rules:
		return true;
	}

	/**
	 * See NameValidation_EnterpriseConnector.class.php for comments.
	 *
	 * @param string	$user - user setting the meta data
	 * @param MetaData	$meta - meta data to validate
	 * @param array		$targets - list of Target objects to validate
	 */
	final public function validateMetaDataAndTargets( $user, MetaData &$meta, &$targets )
	{
		$user = $user;
		$targets = $targets;

		if ( isset( $meta->RightsMetaData->Copyright ) ) {
			$id = $meta->BasicMetaData->ID;
			if( $meta->RightsMetaData->Copyright == 'Spam Software (c)' ) {
				// Don't allow setting Copyright to Spam software
				throw new BizException( 'ERR_UNABLE_SETPROPERTIES', 'Client',
					'It is not allowed to set the Copyright property to "Spam Software (c)"' . PHP_EOL . 'id=' . $id );
			}

			// Overwrite copyright metadata
			$meta->RightsMetaData->Copyright = 'Ham Software (c)';
		}
	}

	/**
	 * See NameValidation_EnterpriseConnector.class.php for comments.
	 *
	 * @param string	$user - user setting the meta data
	 * @param MetaData	$invokedMetaData MetaData containing essential properties of the tested object
	 * @param array		&$changedMetaDataValues Array of MetaDataValues, containing changed properties only that will be applied to all objects.
	 */
	public function validateMetaDataInMultiMode( $user, MetaData $invokedMetaData, array &$changedMetaDataValues )
	{
		$user = $user;

		$id = $invokedMetaData->BasicMetaData->ID;

		$copyrightMetadata = null;
		foreach( $changedMetaDataValues as $metaDataValue ) {
			if( $metaDataValue->Property == 'Copyright' ) {
				$copyrightMetadata = $metaDataValue;
				break;
			}
		}

		if( is_null( $copyrightMetadata ) ) {
			$copyrightMetadata = new MetaDataValue();
			$copyrightMetadata->Property = 'Copyright';
			$changedMetaDataValues[] = $copyrightMetadata;
		}

		if( !empty( $copyrightMetadata->PropertyValues ) ) {
			$value = reset( $copyrightMetadata->PropertyValues );
			if( $value->Value == 'Spam Software (c)' ) {
				// Don't allow setting Copyright to Spam software
				throw new BizException( 'ERR_UNABLE_SETPROPERTIES', 'Client',
					'It is not allowed to set the Copyright property to "Spam Software (c)"' . PHP_EOL . 'id=' . $id );
			}
		}

		// Overwrite copyright metadata
		$propValue = new PropertyValue();
		$propValue->Value = 'Ham Software (c)';
		$copyrightMetadata->PropertyValues = array( $propValue );
	}
}
