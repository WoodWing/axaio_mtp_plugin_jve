<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v10.1
 * @copyright  WoodWing Software bv. All Rights Reserved.

 * Converts Elvis resolutionX to Enterprise Dpi by using Elvis resolutionUnit.
 *
 * When resolutionUnit is set to 3 it means the resolutionX should be intepreted as pixels/centimeter.
 * However, the Enterprise Dpi is always in pixels/inch and so the Elvis resolutionX requires conversion
 * while mapping resolutionX into Dpi.
 */

require_once 'ReadOnlyFieldHandler.class.php';

class ResolutionFieldHandler extends ReadOnlyFieldHandler
{
	/**
	 * @inheritdoc
	 */
	public function read( $entMetadata, $elvisMetadata )
	{
		// Nothing to be done if the resolution isn't set in Elvis. (Note: ResolutionX can't be null.)
		if( isset( $elvisMetadata[ $this->lvsFieldName ] ) ) {
			$elvisResolutionX = $elvisMetadata[ $this->lvsFieldName ];
			if( $elvisResolutionX ) {

				// ResolutionUnit is not a mandatory field in Elvis. (Note: ResolutionUnit can't be null.)
				if( isset( $elvisMetadata['resolutionUnit'] ) ) {
					if( $elvisMetadata['resolutionUnit'] == 3 ) {
						$elvisResolutionX *= 2.54; // centimeters to inches
					}
				}
				$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} = $elvisResolutionX;
			}
		}
	}
}
