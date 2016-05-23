<?php
/**
 * @package Enterprise
 * @subpackage DataClasses
 * @since v9.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Enterprise Event Info data class definition.
 * Events are stored in the smart_serverjobs table.
 */

class EnterpriseEventInfo
{
	// >>> DB fields taken from smart_serverjobs table.
	public $EventId; // Unique identifier of the event record. (A GUID).
	public $EventTime; // When workflow event took place. Format is datetime as used in WSDLs. Use the UTC (GMT) timezone specification (Z) and for the fraction of seconds (.s) to specify microseconds (6 digits). Format: yyyy-mm-ddThh:mm:ss.ssssss+Z
	public $ActingUser; // The full name of workflow user who did the workflow action.
	public $OperationType; // create*, update or delete ( restore and copy actions result into create operations.)
	public $PluginEventInfo; //An array of plugins (keyed by their name) of which each row contains an array consisting of necessary plugin event information.
	// <<<
}
