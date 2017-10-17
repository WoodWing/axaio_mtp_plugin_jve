<?php
/**
 * @package 	Enterprise
 * @subpackage 	Test
 * @since 		v6.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * This script is STRICTLY for development and test purposes.
 * It will send invalid broadcast messages to test if InDesign/InCopy don't crash. Put this script in your Enterprise 
 * installation directory "Enterprise" or change the path to "config.php" below.
 */
require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR . '/server/smartevent.php';

main();

function showTestFunctions ()
{
	print "<h1>InDesign or InCopy must be started and you have to be logged in</h1>\n";
	$all_functions = get_defined_functions();
	foreach ($all_functions['user'] as $userFunction) {
		if (strpos($userFunction, 'test') === 0) {
			print '<a href="?test=' . $userFunction . '">' . $userFunction . '</a>' . "<br />\n";
		}
	}
}

function main ()
{
	$eventPort = BizSettings::getFeatureValue('EventPort');
	if ($eventPort == 8093) {
		print "Please set your EventPort to something else than 8093.\n";
		exit();
	}
	$testFunction = strval(@$_REQUEST['test']);
	if (function_exists($testFunction)) {
		header('Content-type: text/plain');
		print "EventPort = $eventPort\n";
		call_user_func($testFunction);
	} else {
		showTestFunctions();
	}
}

class testSmartEvent
{
	public $EventVersion;
	public $Action;
	public $Type;
	public $Reserved;
	public $flds;

	function __construct ($action, $type = EVENTTYPE_SYSTEM, $version = EVENTVERSION)
	{
		$this->Action = $action;
		$this->Type = $type;
		$this->EventVersion = $version;
		$this->Reserved = 0;
	}

	function _addfield ($var, $val)
	{
		if (isset($val)) // ignore unset, but accept empty values
			$this->flds[$var] = $val;
	}

	// override fire
	function fire ($fieldLengthAdjust = 0)
	{
		$eventPort = BizSettings::getFeatureValue('EventPort');
		if (isset($eventPort)) {
			// create message
			$mess = pack("CCCC", $this->EventVersion, $this->Action, $this->Type, $this->Reserved);
			if ($this->flds) {
				foreach ($this->flds as $var => $val) {
					$fldmess = pack("n", mb_strlen($var, 'UTF-8') + $fieldLengthAdjust) . $var;
					$fldmess .= pack("n", strlen($val)) . $val;
					if (mb_strlen($mess, 'UTF-8') + mb_strlen($fldmess, 'UTF-8') > EVENT_MAXSIZE) {
						$fldmess = mb_strcut($fldmess, 0, EVENT_MAXSIZE, 'UTF-8');
						break;
					}
					$mess .= $fldmess;
				}
			}
			
			if (BizSettings::isFeatureEnabled('MulticastGroup')) {
				// let transmitter do the multicast for us (PHP 4.3.x does not support multicasting)
				$m = new SCEntMessenger(MC_MEDIATOR_ADDRESS, MC_MEDIATOR_PORT);
			} else {
				// broadcast it
				$m = new SCEntMessenger(EVENT_BROADCAST, $eventPort);
				$m->enable_broadcast();
			}
			print "message = " . array_shift(unpack('H*', $mess)) . "\n";
			$m->send($mess);
			$m->destroy();
		}
	}
}

function test_invalidUTF8 ()
{
	print "Send invalid UTF8\n";
	$event = new testSmartEvent(EVENT_CREATEOBJECT);
	$event->_addfield('ID', '1');
	$hex = '0102805050';
	$name = pack('H*', $hex);
	$event->_addfield('Name', $name);
	$event->fire();
}

function test_invalidFormat ()
{
	print "Send invalid format\n";
	$event = new testSmartEvent(EVENT_CREATEOBJECT, EVENTTYPE_SYSTEM, 2);
	$event->_addfield('ID', '1');
	$event->_addfield('Name', 'test name');
	$event->fire();
}

function test_invalidType ()
{
	print "Send invalid type\n";
	$event = new testSmartEvent(EVENT_CREATEOBJECT, 60);
	$event->EventVersion = 2;
	$event->_addfield('ID', '1');
	$event->_addfield('Name', 'test name');
	$event->fire();
}

function test_invalidReserved ()
{
	print "Send invalid reserved byte\n";
	$event = new testSmartEvent(EVENT_CREATEOBJECT);
	$event->Reserved = 60;
	$event->_addfield('ID', '1');
	$event->_addfield('Name', 'test name');
	$event->fire();
}

function test_invalidFieldLength ()
{
	print "Send too short field length\n";
	$event = new testSmartEvent(EVENT_CREATEOBJECT);
	$event->Reserved = 60;
	$event->_addfield('ID', '1');
	$event->_addfield('Name', 'test name');
	$event->fire(- 2);
	print "Send too long field length\n";
	$event = new testSmartEvent(EVENT_CREATEOBJECT);
	$event->Reserved = 60;
	$event->_addfield('ID', '1');
	$event->_addfield('Name', 'test name');
	$event->fire(+ 2);
}

function test_MultiSetPropertiesMessaging()
{
	// Construct the data.
	print "Send Ids and data\n";

	$data = array();
	$data['Type'] = 'Article';
	$data['Name'] = 'The new name for the changed objects.';
	$data['PublicationId'] = '12345';
	$data['IssueIds'] = '6841580,1729186,6069264,2165955,6236471,4932890,2410248,5413973,3869512,3815649,6844936,2467315,7361847';
	$data['EditionIds'] = '2629895,6462100,6817544,1032569,7095271';
	$data['SectionId'] = '112';
	$data['StateId'] = '2';
	$data['RouteTo'] = 'WoodWing';
	$data['LockedBy'] = 'WoodWing';
	$data['Version'] = '12';
	$data['UserId'] = 125690;
	$data['largerVar'] = 'BeginLorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean vehicula sem '
		. 'nibh, id elementum purus condimentum et. Cum sociis natoque penatibus et magnis dis parturient montes, '
		. 'nascetur ridiculus mus. Morbi eget iaculis ipsum. Nulla porttitor et urna sit amet lobortis. Nulla End';
	$data['OldRouteTo'] = 'WoodWing2';
	$data['LargeFieldThatWontFit'] = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean vehicula sem '
		. 'nibh, id elementum purus condimentum et. Cum sociis natoque penatibus et magnis dis parturient montes, '
		. 'nascetur ridiculus mus. Morbi eget iaculis ipsum. Nulla porttitor et urna sit amet lobortis. Nulla '
		. 'facilisi. Curabitur vulputate faucibus diam et mattis. Vivamus sagittis nisi massa, sed porttitor dolor '
		. 'congue quis. Nam eu est facilisis, elementum nibh eu, blandit tortor. In eu nibh vitae lorem feugiat '
		. 'aliquet. Sed blandit erat ante, id mattis dolor porta at. Sed et leo cursus, ultricies arcu sit amet, '
		. 'rhoncus dolor. Nullam interdum sodales metus, vitae mollis velit consequat sit amet. Nam at arcu eu magna '
		. 'cursus egestas vitae quis ante. Aliquam a rhoncus nibh. Sed ac felis sed augue bibendum malesuada sit amet '
		. 'et dolor. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Mauris '
		. 'non purus neque. Ut consectetur porta quam vel eleifend. Donec vulputate placerat eleifend. Duis posuere ac'
		. 'enim nec amet.';
	$data['largerVar2'] = 'cursus egestas vitae quis ante. Aliquam a rhoncus nibh. Sed ac felis sed augue bibendum '
		. 'et dolor. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Mauris '
		. 'non purus neque. Ut consectetur porta quam vel eleifend. Donec vulputate placerat eleifend. Duis posuere ac'
		. 'enim nec amet. malesuada sit amet ';

	$ids = '5902010,2547455,6841580,1729186,6069264,2165955,6236471,4932890,2410248,5413973,3869512,3815649,6844936,'
		. '2467315,7361847,2629895,6462100,6817544,1032569,7095271,4060891,5465062,9634910,7951115,2086969,9145481,'
		. '8923750,5958104,6251968,4873009,9680162,6719876,2412836,5673097,1298068,9358399,6642175,6726862,9085572,'
		. '8753388,8444796,4434063,7777722,4021657,6402001,2096685,3243705,2725505,7252212,8120938,1347444,5967230,'
		. '5007742,7952147,6446520,9734941,2021126,5000865,2308192,4606031,1792673,8278349,3698729,6316378,1030392,'
		. '1339376,6650206,1455433,8341085,8245687,5112092,8138749,1815980,6390916,3504933,5015176,4536987,6958986,'
		. '1648348,8455803,6858737,9447408,7116293,2723791,8620203,7906971,1503103,2138462,5535094,9133574,1662253,'
		. '8796086,9698686,8743030,5760742,9331689,3066904,2383574,5304066,9749253,9355285,1464061,7151342,1568296,'
		. '5335731,4571226,2902191,3965405,4620158,9393742,3017089,3979615,2357576,6399231,1471938,9661879,8851317,'
		. '1961188,9520764,2511904,2676484,8030239,6741181,1273719,2671926,8527377,6172151,6546129,3794905,8934712,'
		. '6968082,8054606,1186172,7640482,2993221,9321626,6869311,9528804,7907266,1798362,1487239,2213483,7102133,'
		. '3976609,7077321,8332462,7980468,1381047,3818832,8000339,8899046,5805680,6271675,7248849,8723816,7457612,'
		. '6788971,3144070,3537728,5105027,4306660,5709267,6101603,7286860,1513242,4214153,1473353,3946200,7255280,'
		. '5937886,7770933,2307861,8790908,5337997,9568083,3445832,7482040,2073658,9714084,9174946,1461965,3096667,'
		. '8195750,4670570,1600509,1384069,1578292,7935282,6413345,3656553,9094158,2687202,4276426,4014375,7170058,'
		. '7883363,6660520,7074710,9819790,6859390,1207749,7065410,7332769,4312921,5094069,9086460,2498773,2200703,'
		. '3848924,4884335,5914646,3859387,6164536,8164155,9582965,6132105,7156869,6834097,1914807,2169800,6537578,'
		. '6281964,3347994,1326945,5427224,8246851,7523040,9250028,5551892,3318244,2409787,6703850,8228504,5632527,'
		. '1889623,3815295,1949618,1190527,9388941,7893683,6385232,5813994,7961279,5386045,6641553,9562948,4267443,'
		. '4185094,2456851,5433077,9562948,4267443,4185094,2456851,5433077,9562948,4267443,4185094,2456851,5433077,'
		. '9562948,4267443,4185094,2456851,5433077,9562948,4267443,4185094,2456851,5433077,9562948,4267443,4185094,'
		. '2456851,5433077';
	$ids = explode( ',', $ids );

	// Create / Fire a new request.
	new smartevent_setPropertiesForMultipleObjects($ids, $data);
}
