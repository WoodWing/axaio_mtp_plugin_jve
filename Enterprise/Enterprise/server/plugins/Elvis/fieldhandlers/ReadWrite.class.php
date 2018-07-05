<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_FieldHandlers_ReadWrite
{
	/** @var string $lvsFieldName */
	public $lvsFieldName;
	/** @var boolean $multiValue */
	protected $multiValue;
	/** @var string $dataType */
	protected $dataType;
	/** @var string $entMetadataCategory */
	protected $entMetadataCategory;
	/** @var PropertyInfo $property */
	protected $property;
	/** @var bool $customProperty */
	protected $customProperty = false;
	/** @var int brand Id */
	protected $brandId = 0;

	/**
	 * @param string $lvsFieldName Elvis metadata field name
	 * @param boolean $multiValue TRUE if field has multiple values, FALSE otherwise.
	 * @param string $dataType String representation of the Elvis field's data type.
	 * @param string $entPropertyName Enterprise metadata property name.
	 * @param integer $brandId
	 */
	public function __construct( $lvsFieldName, $multiValue, $dataType, $entPropertyName, $brandId = 0 )
	{
		$this->lvsFieldName = $lvsFieldName;
		$this->multiValue = $multiValue;
		$this->dataType = $dataType;

		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		if( BizProperty::isCustomPropertyName( $entPropertyName ) ) {
			$this->entMetadataCategory = 'ExtraMetaData';
			$this->customProperty = true;
			$entPropertyName = strtoupper( $entPropertyName );

			// TODO: Jikes, this causes a lot of DB calls!
			$propType = BizProperty::getCustomPropertyType( $entPropertyName, $brandId );
			
			$this->property = new PropertyInfo( $entPropertyName, null, $this->entMetadataCategory, $propType );
		} else {
			$metadataPaths = BizProperty::getMetaDataPaths();
			if (array_key_exists($entPropertyName,$metadataPaths)) {
				$metadataPath = $metadataPaths[ $entPropertyName ];
				$this->entMetadataCategory = substr( $metadataPath, 0, strpos( $metadataPath, '->' ) );
				$propertyInfos = BizProperty::getPropertyInfos();
				$this->property = $propertyInfos[ $entPropertyName ];
			} else {
				if( LogHandler::debugMode() ) { // for developers/integrators only
					LogHandler::Log( __METHOD__ , 'ERROR', "Property $entPropertyName not found in metadataPaths: ".print_r($metadataPaths,1));
				}
			}
		}
	}

	/**
	 * Reads field from Elvis and maps it to Enterprise
	 *
	 * @param MetaData $entMetadata
	 * @param mixed[] $elvisMetadata
	 */
	public function read( $entMetadata, $elvisMetadata )
	{
		$propertyName = $this->property->Name;
		$enterpriseValue = $this->getEnterpriseValue( $elvisMetadata );
		if( $this->customProperty ) {
			// Jay, we've got a custom property, let's do some complex stuff
			if( is_null( $enterpriseValue ) ) {
				$enterpriseValue = array();
			} else if( !$this->multiValue ) {
				$enterpriseValue = array( $enterpriseValue );
			}

			foreach( $entMetadata->{$this->entMetadataCategory} as $extraMetaData ) {
				if( $extraMetaData->Property == $propertyName ) {
					// Existing field found, let's update it and get the hell outta here
					$extraMetaData->Values = $enterpriseValue;
					return;
				}
			}

			// Property doesn't exist yet, just fill in the blanks
			$extraMetaData = new ExtraMetaData( $propertyName, $enterpriseValue );
			$entMetadata->{$this->entMetadataCategory}[] = $extraMetaData;
		} else {
			// Standard property, nice and easy
			$entMetadata->{$this->entMetadataCategory}->{$propertyName} = $enterpriseValue;
		}
	}

	/**
	 * Maps field from Enterprise and writes it to Elvis
	 *
	 * @param MetaData|mixed $entMetadataOrValue Either metadata or value
	 * @param mixed[] $elvisMetadata Elvis metadata to be filled
	 */
	public function write( $entMetadataOrValue, &$elvisMetadata )
	{
		$propertyName = $this->property->Name;
		$enterpriseValue = null;
		if( $entMetadataOrValue instanceof MetaData ) {
			if( $this->customProperty ) {
				// Jay, we've got a custom property, let's do some complex stuff
				foreach( $entMetadataOrValue->{$this->entMetadataCategory} as $extraMetaData ) {
					if( $extraMetaData->Property == $propertyName ) {
						// Field found, let's take a break
						$enterpriseValue = $extraMetaData->Values;
						break;
					}
				}
			} else {
				// Standard property, nice and easy
				$enterpriseValue = $entMetadataOrValue->{$this->entMetadataCategory}->{$this->property->Name};
			}
		} else {
			$enterpriseValue = $this->getFirstMetaDataValue( $entMetadataOrValue );
		}
		$elvisMetadata[ $this->lvsFieldName ] = $this->getElvisMetadataValue( $enterpriseValue );
	}

	// Reusable functions
	/**
	 * Helper function to get the list of values from a metaDataValue.
	 *
	 * @param MetaDataValue $metaDataValue Metadata value structure of which the first value needs to be retrieved
	 * @return mixed The metadata value
	 */
	private function getFirstMetaDataValue( $metaDataValue )
	{
		if( !is_null( $metaDataValue->Values ) ) {
			return $metaDataValue->Values[0];
		} else {
			return $metaDataValue->PropertyValues[0]->Value;
		}
	}

	/**
	 * Converts the Enterprise value to Elvis value
	 *
	 * @param string|string[] $enterpriseValue
	 * @return mixed
	 * @throws BizException
	 */
	protected function getElvisMetadataValue( $enterpriseValue )
	{
		if( is_array( $enterpriseValue ) ) {
			$enterpriseValue = implode( ",", $enterpriseValue );
		}
		switch( $this->dataType ) {
			case 'boolean':
				return (boolean)$enterpriseValue;
			case 'datetime':
				return (string)$enterpriseValue; //assuming its a formatted string like 'Y-m-d\TH:i:s'
			case 'number':
				return (int)$enterpriseValue;
			case 'decimal':
				return (double)$enterpriseValue;
			case 'text':
				return (string)$enterpriseValue;
			default:
				$message = "Unable to cast to unknown Elvis data type ".$this->dataType." for field ".$this->property->Name;
				throw new BizException( 'ERR_ERROR', 'Server', $message );
		}
	}

	/**
	 * Retrieve the desired value from Elvis to be populated at Enterprise side.
	 *
	 * @param array $elvisMetadata MetaData value retrieved from Elvis.
	 * @return mixed
	 */
	protected function getEnterpriseValue( $elvisMetadata )
	{
		if( is_null( $this->lvsFieldName ) || !isset( $elvisMetadata[ $this->lvsFieldName ] ) ) {
			$elvisValue = null;
		} else {
			$elvisValue = $elvisMetadata[ $this->lvsFieldName ];
		}

		if( $this->multiValue ) {
			$values = array();
			// Parse array values
			if( isset( $elvisValue ) ) {
				foreach( $elvisValue as $val ) {
					$entVal = $this->getEnterpriseMetadataValue( $val );
					if( !is_null( $entVal ) ) {
						$values[] = $entVal;
					}
				}
			}
			if( $this->property->Type == 'multistring' || $this->property->Type == 'multilist' ) {
				// Enterprise is expecting an Array of Strings as well (see Keywords/tags)
				return $values;
			}
			$multiValueString = implode( ',', $values );
			return $multiValueString;
		}

		return $this->getEnterpriseMetadataValue( $elvisValue );
	}

	/**
	 * Converts the Elvis value to Enterprise value.
	 *
	 * In case when the Enterprise property is of type
	 * 'list', 'multilist', 'multistring', 'multiline' or 'string',
	 * extra checks are done on the value and illegals characters ( if found ) will be removed.
	 * See UtfString::removeIllegalUnicodeCharacters().
	 *
	 * @param mixed $elvisValue
	 * @return mixed Datatype requested by Enterprise
	 * @throws BizException
	 */
	protected function getEnterpriseMetadataValue( $elvisValue )
	{
		if( is_null( $elvisValue ) && ( $this->property->Type == 'date' || $this->property->Type == 'datetime' ) ) {
			return null;
		}

		//This code relies on Elvis values to be casted to the Enterprise type
		//If the basic types between Elvis / Enterprise do not match
		//create a special field handler for it
		switch( $this->property->Type ) {
			case 'date':
				return date( 'Y-m-d', $elvisValue );
			case 'datetime':
				return date( 'Y-m-d\TH:i:s', $elvisValue );
			case 'list':
			case 'multilist':
			case 'multistring':
			case 'multiline':
			case 'string':
				require_once BASEDIR.'/server/utils/UtfString.class.php';
				return UtfString::removeIllegalUnicodeCharacters( strval( $elvisValue ) );
			case 'bool':
			case 'boolean':
				return $this->toBoolean( $elvisValue );
			case 'unsignedInt':
			case 'int':
				return (int)$elvisValue;
			case 'double':
				return (double)$elvisValue;

			case 'ObjectType':
			case 'Publication':
			case 'Category':
			case 'Issue':
			case 'Section':
			case 'ArrayOfEdition':
				$message = "Casting to Enterprise data type ".$this->property->Type." for field ".$this->property->Name." is not supported by Elvis Content Source.";
				throw new BizException( 'ERR_ERROR', 'Server', $message );
			default:
				$message = "Unable to cast to unknown Enterprise data type ".$this->property->Type." for field ".$this->property->Name;
				throw new BizException( 'ERR_ERROR', 'Server', $message );
		}
	}

	private function toBoolean( $value )
	{
		if( $value ) {
			return strtolower( $value ) == "true" || $value == "1" || strtolower( $value ) == "yes";
		}
		return false;
	}

	/**
	 * Returns the Id of the brand for which the mapping is applicable.
	 *
	 * If the mapping is applicable for all brands the Id = 0.
	 *
	 * @return int Brand Id
	 */
	public function mappedToBrand(  )
	{
		return $this->brandId;
	}
}