<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once __DIR__.'/../../AbstractRemoteObject.php';

class ElvisPlacement extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.operation.Placement';
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		require_once __DIR__.'/../../../logic/ElvisAMFClient.php';
		if( ElvisAMFClient::getInterfaceVersion() >= 2 ) {
			$name = 'ElvisPlacement_v2';
		} else {
			$name = parent::getName();
		}
		return $name;
	}

	/** @var Page $page */
	public $page;

	/** @var double $top */
	public $top;

	/** @var double $left */
	public $left;

	/** @var double $width */
	public $width;

	/** @var double $height */
	public $height;

	/** @var boolean $onPasteBoard */
	public $onPasteBoard;

	/** @var boolean $onMasterPage */
	public $onMasterPage;

	/** @var ElvisEntityDescriptor[] $editions */
	public $editions;
}

/**
 * Adds the $widget property to the ElvisPlacement data class in backwards compatible manner.
 *
 * This property is introduced since Elvis 5.18 and Enterprise 10.1.1. If both versions are
 * matching or newer, the interface version is set to v2 and this data class is used instead.
 *
 * @since 10.1.1
 */
class ElvisPlacement_v2 extends ElvisPlacement
{
	/** @var ElvisEntityDescriptor $widget */
	public $widget;
}

class ElvisPlacementFactory
{
	/**
	 * Creates an elvis placement object.
	 *
	 * @return ElvisPlacement|ElvisPlacement_v2
	 */
	public static function create()
	{
		$className = ElvisPlacement::getName();
		return new $className;
	}
}