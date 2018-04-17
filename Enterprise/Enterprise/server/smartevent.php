<?php
// constants
define ('EVENTVERSION',					1);
// EVENT_MAXSIZE is set to the size of the payload of a standard MTU 1500 ethernet frame.
// See http://en.wikipedia.org/wiki/Ethernet_frame
// EVENT_MAXSIZE is set to maximum 1024, exceed this value will cause Enterprise Agent crashed.
define ('EVENT_MAXSIZE',				1024);
define ('EVENT_BROADCAST',	'255.255.255.255');
//define ('EVENT_BROADCAST',		'127.0.0.1');			// test mode

define ('EVENT_LOGON',                                 1);
define ('EVENT_LOGOFF',                                2);
define ('EVENT_CREATEOBJECT',                          3);
define ('EVENT_DELETEOBJECT',                          4);
define ('EVENT_SAVEOBJECT',                            5);
define ('EVENT_SETOBJECTPROPERTIES',                   6);
define ('EVENT_SENDTO',                                7);
define ('EVENT_LOCKOBJECT',                            8);
define ('EVENT_UNLOCKOBJECT',                          9);
define ('EVENT_CREATEOBJECTRELATION',                 10);
define ('EVENT_DELETEOBJECTRELATION',                 11);
define ('EVENT_SENDMESSAGE',                          12);
define ('EVENT_UPDATEOBJECTRELATION',                 13);
define ('EVENT_DEADLINECHANGED',                      14);
define ('EVENT_DELETEMESSAGE',                        15);
define ('EVENT_ADDTOQUERY',                           16);  // v6.0 for future usage
define ('EVENT_REMOVEFROMQUERY',                      17);	// v6.0 for future usage
define ('EVENT_RELOGON',                              18);	// v6.0 only for customization
define ('EVENT_RESTOREVERSION',                       19);
define ('EVENT_CREATEOBJECTTARGET',                   20);
define ('EVENT_DELETEOBJECTTARGET',                   21);
define ('EVENT_UPDATEOBJECTTARGET',                   22);
define ('EVENT_RESTOREOBJECT',                        23);	// reserved for v8.0 feature
define ('EVENT_ISSUE_DOSSIER_REORDER_AT_PRODUCTION',  24);	// since v7.0.13 for Digital Magazine / Newsfeed
define ('EVENT_ISSUE_DOSSIER_REORDER_PUBLISHED',      25);	// since v7.5.0
define ('EVENT_PUBLISH_DOSSIER',                      26);	// since v7.5.0
define ('EVENT_UPDATE_DOSSIER',                       27);	// since v7.5.0
define ('EVENT_UNPUBLISH_DOSSIER',                    28);	// since v7.5.0
define ('EVENT_SET_PUBLISH_INFO_FOR_DOSSIER',         29);	// since v7.5.0
define ('EVENT_PUBLISH_ISSUE',                        30);	// since v7.5.0
define ('EVENT_UPDATE_ISSUE',                         31);	// since v7.5.0
define ('EVENT_UNPUBLISH_ISSUE',                      32);	// since v7.5.0
define ('EVENT_SET_PUBLISH_INFO_FOR_ISSUE',           33);	// since v7.5.0
define ('EVENT_CREATE_OBJECT_LABELS',                 34);	// since v9.1.0
define ('EVENT_UPDATE_OBJECT_LABELS',                 35);	// since v9.1.0
define ('EVENT_DELETE_OBJECT_LABELS',                 36);	// since v9.1.0
define ('EVENT_ADD_OBJECT_LABELS',                    37);	// since v9.1.0
define ('EVENT_REMOVE_OBJECT_LABELS',                 38);	// since v9.1.0
define ('EVENT_SET_PROPERTIES_FOR_MULTIPLE_OBJECTS',  39);  // since v9.2.0
define ('EVENT_CREATE_ISSUE',                         40);  // since v10.4.1
define ('EVENT_MODIFY_ISSUE',                         41);  // since v10.4.1
define ('EVENT_DELETE_ISSUE',                         42);  // since v10.4.1

define ('EVENT_DEBUG',                               255);

define ('EVENTTYPE_SYSTEM',                            1);
define ('EVENTTYPE_CLIENT',                            2);
define ('EVENTTYPE_USER',                              3);

class smartevent
{
	/** @var int $action Event action id. Value of one of the EVENT_ definitions listed above. */
	private $action;
	/** @var int $type Event type. Not used. Value of one of the EVENTTYPE_ definitions listed above. */
	private $type;
	/** @var string[] $flds Key-value map containing the fields for a message.  */
	private $flds = array();
	/** @var array $potentialLargeFields Key-value map containing the fields for a message that can be potentially large.  */
	private $potentialLargeFields = array();
	/** @var string $exchangeName Message exchange whereto the message must be published. */
	private $exchangeName = null;

	/**
	 * Constructor.
	 *
	 * @param int $action
	 * @param string|null $ticket
	 * @param int $type
	 */
	public function __construct( $action, $ticket = null, $type = EVENTTYPE_SYSTEM )
	{
		$this->action = $action;
		$this->type = $type;

		// Send the owner of the event (the ticket) as part of the message.
		// Instead of sending the ticket as is, which is considered a security
		// concern, the first 12 characters of the md5 hash are sent. Why MD5
		// and not the more secure sha256? MD5 is used elsewhere in the codebases
		// of the server and clients and is know to work the same in all these places.
		$algo = 'md5';
		if( $ticket && function_exists('hash') && in_array( $algo, hash_algos() ) ) {
			$hashed = substr( hash( $algo, $ticket ), 0, 12 );
			$this->addfield( 'Ticket', $hashed );
		}

		// Since 10.0.0 a message queue integration was added wherefore exchange names must be composed by the sub classes.
		// However, the re-logon event was not implemented in this module, but probably in use by customizations.
		// To make those work, the exchange name is composed at this point, in the parental class.
		if( $action == EVENT_RELOGON ) {
			$this->composeExchangeNameForSystem();
		}
	}

	/**
	 * This function adds a field to the broadcast message.
	 *
	 * When the isPotentialLargeField is set to true this field can be large in terms of values. The field will be appended
	 * to the end of the message. The 'shared' values of the message will be send every time, but the values of the
	 * potential large field will be split over several messages.
	 *
	 * @throws BizException If there are more than two fields or the value isn't an array when handling potentially large fields.
	 * @param string $var The name of the variable to be used for the field.
	 * @param mixed $val The value to be added for the field.
	 * @param bool $isPotentialLargeField Whether or not the field is a potentially large field. Default: false.
	 * @return void
	 */
	protected function addfield($var, $val, $isPotentialLargeField = false)
	{
		if ( $isPotentialLargeField && count($this->potentialLargeFields) >= 2 ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server',
				'A message can only have up to 2 excessive fields.' );
		}

		if( isset( $val ) ) { // Ignore unset, but accept empty values.
			if ( $isPotentialLargeField ) {
				if ( !is_array($val) ) {
					throw new BizException( 'ERR_INVALID_OPERATION', 'Server',
						'For potential large fields the value should be an array.' );
				}
				$this->potentialLargeFields[$var] = $val;
			} else {
				$this->flds[$var] = $val;
			}
		}
	}

	/**
	 * Add Enterprise object metadata to the event fields.
	 *
	 * @param Object $object Enterprise object
	 * @param bool $addModifier
	 * @param bool $addDeleter
	 */
	protected function addObjectFields($object, $addModifier=true, $addDeleter=false)
	{
		// Add object fields
		$id = $object->MetaData->BasicMetaData->ID;
		$this->addfield('ID', $id);
		$this->addfield('Name', $object->MetaData->BasicMetaData->Name);
		$this->addfield('Type', $object->MetaData->BasicMetaData->Type);
		$this->addfield('PublicationId', $object->MetaData->BasicMetaData->Publication->Id);
		$this->addfield('SectionId', $object->MetaData->BasicMetaData->Category->Id);
		$this->addfield('StateId', $object->MetaData->WorkflowMetaData->State->Id);
		$this->addfield('RouteTo', $object->MetaData->WorkflowMetaData->RouteTo);
		$this->addfield('LockedBy', $object->MetaData->WorkflowMetaData->LockedBy);
		if($addModifier){
			$this->addfield('Modified', $object->MetaData->WorkflowMetaData->Modified);
			$this->addfield('Modifier', $object->MetaData->WorkflowMetaData->Modifier);
		}
		if($addDeleter){
			$this->addfield('Deleter',$object->MetaData->WorkflowMetaData->Deletor);
			$this->addfield('Deleted',$object->MetaData->WorkflowMetaData->Deleted);
		}
		$this->addfield('Version', $object->MetaData->WorkflowMetaData->Version);
		$this->addfield('Format', $object->MetaData->ContentMetaData->Format);
		$this->addfield('Dimensions', $object->MetaData->ContentMetaData->Dimensions);

		// Resolve targets from parent relations (BZ#20453)
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
		$targets = $object->Targets;
		if( !is_array($targets) ) {
			$targets = array(); // Paranoid repair for the sake of robustness
		}
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		if( !BizContentSource::isAlienObject( $id ) ) { // Continue to get object relations if it is not alien object
			try {
				$rows = DBObjectRelation::getObjectRelations($id, 'parents');
				foreach ($rows as $row) {
					$targets = array_merge($targets, DBTarget::getTargetsbyObjectrelationId($row['id']));
				}
			} catch (BizException $e) {
			} // Let's never fail because of this (used for messaging only)
		}
		// Collect issue/edition ids from object targets and relational targets
		$issueids = array();
		$editionids = array();
		foreach ($targets as $target) {
			if( isset($target->Issue->Id) && $target->Issue->Id > 0 ) {
				$issueids[$target->Issue->Id] = true;
			}
			if ($target->Editions) {
				foreach ($target->Editions as $edition) {
					if( isset($edition->Id) && $edition->Id > 0 ) {
						$editionids[$edition->Id] = true;
					}
				}
			}
		}

		// Add target fields at the end of network package;
		// When too many targets for the package, they'll get truncated, which is ok.
		$this->addfield('IssueIds', implode(',',array_keys($issueids)));
		$this->addfield('EditionIds', implode(',',array_keys($editionids)));
	}

	/**
	 * Add channel id, issue id and edition ids from a target to the event fields.
	 *
	 * @param Target $target
	 */
	protected function addTargetFields($target)
	{
		$this->addfield('PubChannelId', $target->PubChannel->Id);
		$this->addfield('IssueId', $target->Issue->Id);
		$editionids = array();
		if ($target->Editions) {
			foreach ($target->Editions as $edition) {
				if (!in_array($edition->Id, $editionids)) {
					$editionids[] = $edition->Id;
				}
			}
		}
		$this->addfield('EditionIds', implode(',',$editionids));
	}

	/**
	 * Adds ids and names of given object labels to the event fields.
	 *
	 * @param ObjectLabel[] $labels
	 */
	protected function addObjectLabels( array $labels )
	{
		$msgLabels = array();
		foreach( $labels as $label ) {
			$msgLabels[] = $label->Id .chr(0x09).$label->Name; // Separated by a Tab character
		}
		$this->addfield( 'Labels', $msgLabels, true );
	}

	/**
	 * Sends the broadcast message.
	 *
	 * Generates and sends the requested message, the current $messageCompositionMethods are available:
	 *
	 * Default: (Triggered by passing $messageCompositionMethod as null) This is the default message composition method.
	 *   Messages are composed of key / value pairs for the flds and the potentialLargeFields being passed. Any field
	 *   that surpasses the message length is dropped from the message.
	 *
	 * 'CartessianEvenly': Use this message when you need to send messages about a large amount of objects with a large
	 *   amount of properties where you wish to ensure that all the messages being sent are complete and relay all the
	 *   data for all the ids.
	 *   This composition type generates a message where the header and fields are added normally,
	 *   the remaining space in the message however is cut up into two even parts. The method expects two
	 *   potentialLargeFields, the first contains an array of integers and represents the Ids for the broadcast message.
	 *   The second potentialLargeField should consist of an array of key / value pairs representing properties to be
	 *   sent in the message. For both the ids and the properties the underlying logic calculates the maximum amount of
	 *   values that will fit in the respective parts and then generates ids x properties messages (cartessian product)
	 *   which will be sent to clients.
	 *
	 * @param null|string $messageCompositionMethod Override flag to specify a method for sending a message.
	 * @return void
	 */
	public function fire( $messageCompositionMethod = null )
	{
		if( !SmartEventQueue::canFire() ) {
			LogHandler::Log('smartevent','DEBUG','postpone fire, action ID: '.$this->action );
			SmartEventQueue::addEvent( $this );
			return; // queue instead of direct fire
		}
		LogHandler::Log('smartevent','DEBUG','direct fire, action ID: '.$this->action );
		$eventPort = BizSettings::getFeatureValue('EventPort');
		if( isset( $eventPort ) )
		{
			// Compose the messages.
			switch ( $messageCompositionMethod ) {
				case 'CartessianEvenly' :
					$messages = $this->generateEvenlySpreadCartessianMessages();
					break;
				default :
					$messages = $this->generateDefaultMessages();
			}

			// Prepare to send the messages.
			if( BizSettings::isFeatureEnabled('MulticastGroup') ) {
				// let transmitter do the multicast for us (PHP 4.3.x does not support multicasting)
				$m = new SCEntMessenger( MC_MEDIATOR_ADDRESS, MC_MEDIATOR_PORT );
			}
			else
			{
				// broadcast it
				$m = new SCEntMessenger(EVENT_BROADCAST, $eventPort);
				$m->enable_broadcast();
			}
			// Send all the messages
			foreach ( $messages as $message ) {
				$m->send( $message );
			}
			$m->destroy();
		}

		// Publish the event to the message queue.
		if( $this->exchangeName ) {
			$headerFields = array( 'EventVersion' => EVENTVERSION, 'EventId' => $this->action );
			$messageFields = array_merge(
				$this->flds,
				$this->potentialLargeFields
			);
			require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
			BizMessageQueue::publishMessage( $this->exchangeName, $headerFields, $messageFields );
		}
	}

	/**
	 * Generates Messages using a Cartessian product.
	 *
	 * This method composes messages using a cartessian product. The following is expected
	 *
	 * - There are two PotentiallyLargeFields set:
	 *      - The first always contains a single array of ids. The key will be used as the variable name when broadcasting.
	 *      - The second contains properties in key => value format.
	 *
	 * The method generates the message header, adds any simple fields that are defined (usually only the ticket) and
	 * uses the remaining available space in the message divided evenly between the ids and the data that is to be sent.
	 * It then uses all these components to create id chunks x data chunks messages ensuring that all the data and all
	 * the properties are always sent to clients. Message composition generally looks like this:
	 *
	 * Message:
	 * |Variable      | 50% of the remaining size | 50% of the remaining size|
	 * =======================================================================
	 * |Header/Ticket | IDS                       | DATA                     |
	 * =======================================================================
	 *
	 * Through smart sorting the method attempts to keep the number of messages as small as possible.
	 *
	 * @return string[] An array of generated messages.
	 */
	private function generateEvenlySpreadCartessianMessages( )
	{
		$messages = array();

		// Potentially large fields can only have a single entry.
		if (count( $this->potentialLargeFields ) != 2) {
			LogHandler::Log('SmartEvent', 'ERROR', 'Evenly spread CartessianMessages must have two large fields, '
				. 'The first filled with the IDs, the second an array with the properties to send.');
		}

		// Generate the header (4 bytes).
		$messageHeaderSize = 0;
		$header = $this->generateMessageHeader( $messageHeaderSize );

		// Add any simple fields to the header of the message (these are added for each sub-message in the cartessian
		// product as well). Normally this only consists of the ticket. The extra fields are added in the same space as
		// the ticket, beware that adding a lot of data in this fashion impairs the remaining space for the ids / data.
		$fldData = '';
		if ( $this->flds ) foreach ($this->flds as $key => $value ) {
			$variableData = pack('n', strlen( $key ) ) . $key;
			$variableData .= pack('n', strlen( $value ) ). $value;
			$fldData .= $variableData;
		}
		// Add variables to the header and recalculate the length.
		$header .= $fldData;
		$messageHeaderSize = strlen( $header );

		// Determine the maximum size for the left over data part.
		$maxMessageSize = floor( ( EVENT_MAXSIZE - $messageHeaderSize ) / 2 );

		// Cut up the ids and data into correctly sized chunks.
		$labels = array_keys( $this->potentialLargeFields );
		$idLabel = reset( $labels ); // Get the label for the Id's field.
		$ids = reset( $this->potentialLargeFields ); // First item always contains the ids.
		$dataChunks = next( $this->potentialLargeFields ); // Second item always contains the data.
		reset( $this->potentialLargeFields ); // Set internal pointer back to start.

		// Split ids and data into evenly spaced chunks.
		$ids = $this->packIdsIntoChunks( $maxMessageSize, $idLabel, $ids );
		$sortedDataChunks = $this->packDataIntoChunks( $maxMessageSize, $dataChunks );

		// Generate the messages as the Cartessian product of ids and data:
		if ( $sortedDataChunks && $ids ) {

			// Compose messages as the cartessian product for each of the ID sets.
			foreach ( $ids as $idChunk ) {
				foreach ( $sortedDataChunks as $chunk ) {
					$message = $header . $idChunk . $chunk ;
					LogHandler::Log('SmartEvent', 'DEBUG', 'Generated message length: '
						. (strlen( $header ) + strlen( $idChunk ) + strlen( $chunk ) ) );
					$messages[] = $message;
				}
			}
		}

		return $messages;
	}

	/**
	 * Generates the header part of the message.
	 *
	 * This is a set number of fields as expected by the client:
	 *
	 * Format: Version number of the format.
	 * Event: Identifies the message, contains the event type.
	 * Type: Type of the message.
	 * Reserved: This field is reserved for future use.
	 *
	 * @param null $length Filled in with the header length after its composition.
	 * @return string The header string.
	 */
	private function generateMessageHeader( &$length = null )
	{
		// Generate the header and set the length.
		$header = pack('CCCC', EVENTVERSION, $this->action, $this->type, 0);
		$length = strlen($header );
		return $header;
	}

	/**
	 * Splits properties up into evenly spaced datachunks.
	 *
	 * Accepts an array of key / value pairs to be turned into message encoded variables and a maximum chunk size.
	 * The variables are message encoded and cut up into chunks of data, these chunks are returned in an array.
	 *
	 * @param int $maxChunkSizeInBytes The maximum size of the data chunk in bytes.
	 * @param array $data An array of key / value properties to be added to the message chunk.
	 * @return string[] An array of encoded message chunks sorted by index on the size of the message (high to low).
	 */
	private function packDataIntoChunks( $maxChunkSizeInBytes, array $data) 
	{
		// Pack the field data.
		$packedVariables = array();

		foreach ( $data as $name => $values ) {
			$length = 0;
			$packedData = $this->packField( $name, $values, $length );

			// Only use the packed field if it does not exceed the maximum allowed length.
			// If the max length is reached then the variable is left out of the packed values.
			if ($length <= $maxChunkSizeInBytes ) {
				$index = strval($length);
				if (!array_key_exists( $index, $packedVariables) ){
					$packedVariables[$index] = array();
				}
				$packedVariables[$index][] = $packedData;
			}
		}
		// Reverse sort on the key by numbers and flatten the array to make it easier to compose packages.
		krsort( $packedVariables, SORT_NUMERIC );
		$dataChunks = array();
		foreach ($packedVariables as $chunks) {
			foreach ( $chunks as $chunk ) {
				$dataChunks[] = $chunk;
			}
		}
		$sortedDataChunks = array();
		if ( $dataChunks ) {
		// Merge largest / smallest until we have a full data chunk.
			while ( count( $dataChunks ) > 0) {
				// shift off the first item as that is the largest item.
				$dataChunk = array_shift( $dataChunks );

				// add the smallest items if they are available.
				if (count( $dataChunks ) > 0 ){
					$full = false;
					while ( !$full ) {
						$nextLength = strlen( end( $dataChunks ) ) + strlen( $dataChunk );
						if ( $nextLength <= $maxChunkSizeInBytes ) {
							$dataChunk .= array_pop($dataChunks);

							if (count( $dataChunks ) == 0 ) {
								$full = true;
							}
						} else {
							$full = true;
						}
					}
				}
				$sortedDataChunks[] = $dataChunk;
			}
		}

		return $sortedDataChunks;
	}

	/**
	 * Takes an array of ids and splits them up into message chunks.
	 *
	 * This generates an array of strings, each containing message encoded data containing the maximum number of id's
	 * per string possible.
	 *
	 * @param int $maxChunkSizeInBytes The maximum size that each chunk may have.
	 * @param string $fieldName The identifier for the ids to be incorporated in the message.
	 * @param int[] $ids The ids to be cut up evenly into chunks.
	 * @return string[] Returns an array of generated message chunks containing ids.
	 */
	private function packIdsIntoChunks( $maxChunkSizeInBytes, $fieldName, array $ids )
	{
		$packedVariables = array();

		if ( $ids ) {
			// Format the field name for the chunk.
			$fieldNamePacked = pack('n', mb_strlen($fieldName, 'UTF-8')).$fieldName;

			// Calculate the length of fieldname to be reserverd in the chunk.
			$idKeyLength = strlen( $fieldNamePacked );

			// Calculate the number of bytes to reserve for the value length in the chunk.
			$maxValueLength = strlen( $maxChunkSizeInBytes - $idKeyLength );

			// Calculate the number of ID's that will fit in a chunk, taking the calculated values above into account.
			// we calculate the numbers back to bits (each number is going to be an unsigned integer (4 bytes), each
			// character in a Base64 encoded string represents 6 bits, therefore the base64 encoded string will take up
			// roughly one third more space than the original.
			$maxNumOfVariablesPerPack = floor( ( $maxChunkSizeInBytes - $idKeyLength - $maxValueLength ) / (4 * 8 / 6) );

			// Slice the Id's into even parts so we can encode them.
			$sliced = array_chunk( $ids, $maxNumOfVariablesPerPack );
			foreach ( $sliced as $slice ) {
				$uint32 = '';

				foreach( $slice as $id ) {
					$uint32 .= pack( 'V', $id );
				}
				$packed = base64_encode( $uint32 );
				// Format the value correctly for inclusion in the chunk.
				$packedVariables[] = $fieldNamePacked . pack('n', mb_strlen($packed, 'UTF-8')) . $packed;
			}
		}

		foreach ( $packedVariables as $p ) {
			LogHandler::Log( 'smartevent', 'DEBUG', strlen( $p ));
		}

		return $packedVariables;
	}

	/**
	 * Generates messages using the default method.
	 *
	 * Generates a list of messages using the default method of adding all the fields and potentialLarge fields that
	 * fit within the message(s).
	 *
	 * @return string[] An array of messages to be fired.
	 */
	private function generateDefaultMessages()
	{
		// Create the message(s).
		$mess = $this->generateMessageHeader();
		if ($this->flds){
			foreach($this->flds as $var => $val) {
				$fldmess = pack('n', mb_strlen($var, 'UTF-8')).$var;
				$fldmess .= pack('n', strlen($val)).$val;

				// Forget the whole(!) field and its value when it causes to exceed max package size.
				// It does not just cut the value since that would lead into corrupted
				// data at client side. Instead, it simply does not add the whole field (Field-value).
				// Even for large field values, such as DossierIds for EVENT_ISSUE_DOSSIER_REORDER_AT_PRODUCTION
				// it is wanted to leave out the whole field, or else clients get confused with
				// corrupted dossier ids (cut into half) and would miss the remaining dossier ids.
				// It does not bail out here completely when exceeded EVENT_MAXSIZE to give chance
				// to next (if any) field-value that could be smaller in data length and still fits into the EVENT_MAXSIZE.
				if( mb_strlen($mess, 'UTF-8') + mb_strlen($fldmess, 'UTF-8') <= EVENT_MAXSIZE ) {
					$mess .= $fldmess; // only add on when it still fits into permitted size of EVENT_MAXSIZE.
				}
			}
		}
		$messages = array();
		if ( !$this->potentialLargeFields ) {
			// When there are no potential large fields, the message is the only message to be added.
			$messages[] = $mess;
		} else {
			// Calculate how many bytes are left for the potential large fields.
			$bytesLeft = EVENT_MAXSIZE - mb_strlen($mess, 'UTF-8');
			if ( count($this->potentialLargeFields) == 1 ) {
				// For only one potential large field it is simple. Send the message for the number of times
				// the array can be chopped into pieces.
				foreach ( $this->potentialLargeFields as $var => $values ) {
					$fieldMessages = self::splitLargeList( $var, $values, $bytesLeft );
					if ( $fieldMessages ) foreach ( $fieldMessages as $fieldMessage ) {
						$messages[] = $mess. $fieldMessage;
					}
				}
			} else {
				$bytesLeftPerValue = floor($bytesLeft / 2);
				$first = true;
				$firstFieldName = null;
				$firstFieldValue = null;
				$firstByteLength = null;
				$firstFieldValues = array();
				$secondFieldValues = array();
				foreach ( $this->potentialLargeFields as $var => $values ) {
					if ( $first ) {
						$firstByteLength = 0;
						$fieldPacked = self::packField( $var, $values, $firstByteLength );
						// If the field isn't as big as the set limit we can use more space for the second value
						if ( $firstByteLength < $bytesLeftPerValue ) {
							$firstFieldValues = array( $fieldPacked );
							$bytesLeftPerValue = $bytesLeft - $firstByteLength;
						} else {
							$firstFieldValues = self::splitLargeList( $var, $values, $bytesLeftPerValue );
						}
						// Temporary save of the fields. Needed when the second field is smaller
						$firstFieldName = $var;
						$firstFieldValue = $values;
					} else {
						$secondByteLength = 0;
						$fieldPacked = self::packField( $var, $values, $secondByteLength );
						// If the field isn't as big as the set limit we can use more space for the first value when needed
						if ( $secondByteLength < $bytesLeftPerValue && $firstByteLength > $bytesLeftPerValue ) {
							$secondFieldValues = array( $fieldPacked );
							$bytesLeftPerValue = $bytesLeft - $secondByteLength;
							$firstFieldValues = self::splitLargeList( $firstFieldName, $firstFieldValue, $bytesLeftPerValue );
						} else {
							$secondFieldValues = self::splitLargeList( $var, $values, $bytesLeftPerValue );
						}

					}
					$first = false;
				}

				// Traverse through all the fields, make sure every combination of fields is send.
				foreach ( $firstFieldValues as $firstFieldValue ) {
					foreach ( $secondFieldValues as $secondFieldValue ) {
						$messages[] = $mess . $firstFieldValue . $secondFieldValue;
					}
				}
			}
		}
		return $messages;
	}

	/**
	 * Splits the given field values in arrays of packed string values that don't exceed the max length property.
	 *
	 * @param string $fieldName
	 * @param array $fieldValues
	 * @param integer $maxLength
	 * @return array
	 */
	private function splitLargeList( $fieldName, $fieldValues, $maxLength )
	{
		$retVal = array();
		$start = 0;
		$length = 1;
		$count = count( $fieldValues );
		$fieldPacked = null;
		while( $start + $length <= $count ) {
			// Get all the values of the array in the specified range
			$value = array_slice($fieldValues, $start, $length );
			$byteLength = 0;
			$fieldPacked = self::packField( $fieldName, $value, $byteLength );
			if( $byteLength > $maxLength ) {
				$value = array_slice($fieldValues, $start, $length - 1 );
				$fieldPacked = self::packField( $fieldName, $value, $byteLength );
				$retVal[] = $fieldPacked;
				$start += $length - 1;
				$length = 1;
				$fieldPacked = null;
			}
			$length++;
		}
		// It could be that there is still some packed field generated so save that one as well.
		if ( $fieldPacked ) {
			$retVal[] = $fieldPacked;
		}

		return $retVal;
	}

	/**
	 * Packs a field in the correct format.
	 *
	 * @param string $name The name of the field
	 * @param mixed $value normally a string but when an array the value is imploded and separated by a ,
	 * @param int $length when given the length of the packed field is returned.
	 * @return string
	 */
	private function packField( $name, $value, &$length = null )
	{
		if ( is_array($value) ) {
			$value = implode(',', $value);
		}
		$value = strval($value);

		$fieldPacked = pack('n', mb_strlen($name, 'UTF-8')).$name;
		$fieldPacked .= pack('n', strlen($value)).$value;

		if ( !is_null( $length ) ) {
			$length = mb_strlen($fieldPacked, 'UTF-8');
		}

		return $fieldPacked;
	}

	/**
	 * Determines the exchange name for system/session related messages (used to publish the message).
	 *
	 * The system needs to know in which exchange the message should be published. There is a message exchange at system level,
	 * per brand and per overrule issue. By calling this function, assumed is that the message should be published in
	 * the system exchange. Typically used for logon/logoff events.
	 */
	protected function composeExchangeNameForSystem()
	{
		// When the message queue integration is disabled, there is no need to resolved the exchange name.
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
		$this->exchangeName = null;
		if( !BizMessageQueue::isInstalled() ) {
			return;
		}

		// Determine the message exchange name.
		$this->exchangeName = BizMessageQueue::composeExchangeNameForSystem();
		LogHandler::Log( 'smartevent', 'INFO',	'Determined system exchange name '.$this->exchangeName );
	}

	/**
	 * Takes an object id to resolve brand/overrule issue and determines the exchange name (used to publish the message).
	 *
	 * The system needs to know to which exchange the message should be published. There is a message exchange at system level,
	 * per brand and per overrule issue. By calling this function, assumed is that the message should be published in
	 * either the brand or overrule issue exchange.
	 *
	 * In case of failure, the function bails out silently. No error is logged nor exception is thrown since system can
	 * operate properly in production without messages. Only a warning is given when bad param is provided since that is
	 * considered a programmatic error.
	 *
	 * @param integer $objectId
	 */
	protected function composeExchangeNameForObjectId( $objectId )
	{
		// When message queue integration is disabled, there is no need to resolved the exchange name.
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
		$this->exchangeName = null;
		if( !BizMessageQueue::isInstalled() ) {
			return;
		}

		// Warn and bail out when bad object id is provided.
		if( !$objectId ) {
			LogHandler::Log( 'smartevent', 'WARN', 'No object specfified. Message can not be published.' );
			return;
		}

		// For alien objects, events are not published to message exchanges. This is because the brand / overrule issue
		// can not be resolved and so the exchange name can not be determined. It is rather harmless not to publish messages
		// because an alien object should become a shadow object first before it can be adjusted.
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $objectId ) ) {
			LogHander::Log( 'smartevent', 'INFO', 'No event sent for alien object '.$objectId );
			return;
		}

		// Determine the message exchange name.
		require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
		$objectIdsIssueIds = DBIssue::getOverruleIssueIdsFromObjectIds( array( $objectId ) );
		if( array_key_exists( $objectId, $objectIdsIssueIds ) ) {
			$this->exchangeName = BizMessageQueue::composeExchangeNameForOverruleIssue( $objectIdsIssueIds[$objectId] );
			LogHandler::Log( 'smartevent', 'INFO',
				'Resolved exchange name '.$this->exchangeName.' from overrule issue '.$objectIdsIssueIds[$objectId].' for object id '.$objectId );
		} else {
			require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
			$pubId = DBObject::getObjectPublicationId( $objectId );
			if( !$pubId ) {
				$pubId = DBObject::getObjectPublicationId( $objectId, 'Trash' );
			}
			if( $pubId ) {
				$this->exchangeName = BizMessageQueue::composeExchangeNameForPublication( $pubId );
				LogHandler::Log( 'smartevent', 'INFO',
					'Resolved exchange name '.$this->exchangeName.' from publication '.$pubId.' for object id '.$objectId );
			} else {
				LogHandler::Log( 'smartevent', 'WARN',
					'Could not resolve publication id from object id '.$objectId.'. '.
					'Event message will not be published.' );
			}
		}
	}

	/**
	 * Same as composeExchangeNameForObjectId, but now fully relying on a given Object, without the help of DB.
	 *
	 * This is especially useful to fire events after an object is deleted from DB (but $object still in memory).
	 *
	 * @param Object $object The workflow object. Should have at least its MetaData and Targets members resolved.
	 */
	protected function composeExchangeNameForObject( $object )
	{
		// When message queue integration is disabled, there is no need to resolved the exchange name.
		require_once BASEDIR . '/server/bizclasses/BizMessageQueue.class.php';
		$this->exchangeName = null;
		if( !BizMessageQueue::isInstalled() ) {
			return;
		}

		// Warn and bail out when bad object id is provided.
		$objectId = $object->MetaData->BasicMetaData->ID;
		if( !$objectId ) {
			LogHandler::Log( 'smartevent', 'WARN', 'No object specfified. Message can not be published.' );
			return;
		}

		// For alien objects, events are not published into message exchanges. This is because the brand / overrule issue
		// can not be resolved and so the exchange name can not be determined. It is rather harmless not to publish messages
		// because an alien object should become a shadow object first before it can be adjusted.
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $objectId ) ) {
			LogHander::Log( 'smartevent', 'INFO', 'No event sent for alien object ' . $objectId );
			return;
		}

		$inOverruleIssue = count($object->Targets) == 1 && $object->Targets[0]->Issue->OverrulePublication;
		if( $inOverruleIssue ) {
			$issueId = $object->Targets[0]->Issue->Id;
			if( $issueId ) {
				$this->exchangeName = BizMessageQueue::composeExchangeNameForOverruleIssue( $issueId );
				LogHandler::Log( 'smartevent', 'INFO',
					'Resolved exchange name '.$this->exchangeName.' from overrule issue '.$issueId.' for object id '.$objectId );
			} else {
				LogHandler::Log( 'smartevent', 'WARN', 'No overrule issue id specified. Message can not be published.' );
			}
		} else {
			$pubId = $object->MetaData->BasicMetaData->Publication->Id;
			if( $pubId ) {
				$this->exchangeName = BizMessageQueue::composeExchangeNameForPublication( $pubId );
				LogHandler::Log( 'smartevent', 'INFO',
					'Resolved exchange name '.$this->exchangeName.' from publication '.$pubId.' for object id '.$objectId );
			} else {
				LogHandler::Log( 'smartevent', 'WARN', 'No publication id specified. Message can not be published.' );
			}
		}
	}

	/**
	 * Takes an issue id to resolve brand/overrule issue and determines the exchange name (used to publish the message).
	 *
	 * The system needs to know to which exchange the message should be published. There is a message exchange at system level,
	 * per brand and per overrule issue. By calling this function, assumed is that the message should be published in
	 * either the brand or overrule issue exchange.
	 *
	 * In case of failure, the function bails out silently. No error is logged nor exception is thrown since system can
	 * operate properly in production without messages. Only a warning is given when bad param is provided since that is
	 * considered a programmatic error.
	 *
	 * @param integer $issueId
	 */
	protected function composeExchangeNameForIssue( $issueId )
	{
		// When the message queue integration is disabled, there is no need to resolve the exchange name.
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
		$this->exchangeName = null;
		if( !BizMessageQueue::isInstalled() ) {
			return;
		}

		// Warn and bail out when bad issue id is provided.
		if( !$issueId ) {
			LogHandler::Log( 'smartevent', 'WARN', 'No issue specfified. Message can not be published.' );
			return;
		}

		// Determine the message exchange name.
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		if( DBIssue::isOverruleIssue( $issueId ) ) {
			$this->exchangeName = BizMessageQueue::composeExchangeNameForOverruleIssue( $issueId );
			LogHandler::Log( 'smartevent', 'INFO',
				'Resolved exchange name '.$this->exchangeName.' from overrule issue id '.$issueId );
		} else {
			$channelId = DBIssue::getChannelId( $issueId );
			if( $channelId ) {
				$pubId = DBChannel::getPublicationId( $channelId );
				if( $pubId ) {
					$this->exchangeName = BizMessageQueue::composeExchangeNameForPublication( $pubId );
					LogHandler::Log( 'smartevent', 'INFO',
						'Resolved exchange name '.$this->exchangeName.' from publication id '.$pubId );
				} else {
					LogHandler::Log( 'smartevent', 'WARN',
						'Could not resolve publication id from issue id '.$issueId.'. '.
						'Event message will not be published to exchange.' );
				}
			} else {
				LogHandler::Log( 'smartevent', 'WARN',
					'Could not resolve publication id from channel id '.$channelId.'. '.
					'Event message will not be published to exchange.' );
			}
		}
	}
}
///////////////////////////////////////////////////////////////////
// every event as a class
///////////////////////////////////////////////////////////////////
class smartevent_logon extends smartevent
{
	public function __construct( $ticket, $username, $fullname, $servername )
	{
		parent::__construct( EVENT_LOGON, $ticket );
		$this->composeExchangeNameForSystem();

		$this->addfield( 'UserID', $username );
		$this->addfield( 'FullName', $fullname );
		$this->addfield( 'Server', $servername );

		$this->fire();
	}
}

class smartevent_logoff extends smartevent
{
	public function __construct( $ticket, $username )
	{
		parent::__construct( EVENT_LOGOFF, $ticket );
		$this->composeExchangeNameForSystem();

		$this->addfield( 'UserID', $username );

		$this->fire();
	}
}

class smartevent_createobjectEx extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param string $longusername full username of the logged in user
	 * @param object $object all data is passed via $object now
	 */
	public function __construct( $ticket, $longusername, $object )
	{
		parent::__construct( EVENT_CREATEOBJECT, $ticket );
		$this->composeExchangeNameForObjectId( $object->MetaData->BasicMetaData->ID );

		$this->addfield( 'UserId', $longusername );
		$this->addObjectFields( $object );

		$this->fire();
	}
}

class smartevent_deleteobject extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param Object $object Object that is going to be deleted
	 * @param int $userId ID of the user.
	 * @param boolean $permanent True:Deleted Permanently; False:Deleted(Send to TrashCan)
	 */
	public function __construct( $ticket, $object, $userId, $permanent  )
	{
		parent::__construct( EVENT_DELETEOBJECT, $ticket );
		if( $permanent ) {
			$this->composeExchangeNameForObject( $object );
		} else {
			$this->composeExchangeNameForObjectId( $object->MetaData->BasicMetaData->ID );
		}

		$this->addObjectFields( $object, false/*addModifier*/, true/*$addDeletor*/ );
		$this->addfield( 'UserId', $userId );
		$permanentMessage = $permanent ? 'true' : 'false';
		$this->addfield( 'Permanent', $permanentMessage );

		$this->fire();
	}
}

class smartevent_restoreobject extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param Object $object Object that is restored
	 * @param int $userId ID of the user.
	 */
	public function __construct( $ticket, $object, $userId )
	{
		parent::__construct( EVENT_RESTOREOBJECT, $ticket );
		$this->composeExchangeNameForObjectId( $object->MetaData->BasicMetaData->ID );

		$this->addObjectFields( $object, true/*addModifier*/, true/*$addDeletor*/ );
		$this->addfield( 'UserId', $userId );

		$this->fire();
	}
}

class smartevent_saveobjectEx extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param string $longusername full username of the logged in user
	 * @param object $object all data is passed via $object now
	 * @param string $oldRouteTo The RouteTo property of the object before it got updated
	 */
	public function __construct( $ticket, $longusername, $object, $oldRouteTo )
	{
		parent::__construct( EVENT_SAVEOBJECT, $ticket );
		$this->composeExchangeNameForObjectId( $object->MetaData->BasicMetaData->ID );

		$this->addfield( 'UserId', $longusername );
		$this->addObjectFields( $object );
		$this->addfield( 'OldRouteTo', $oldRouteTo );

		$this->fire();
	}
}

class smartevent_setobjectpropertiesEx extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param string $longusername full username of the logged in user
	 * @param object $object all data is passed via $object now
	 * @param string $oldRouteTo The RouteTo property of the object before it got updated
	 */
	public function __construct( $ticket, $longusername, $object, $oldRouteTo )
	{
		parent::__construct( EVENT_SETOBJECTPROPERTIES, $ticket );
		$this->composeExchangeNameForObjectId( $object->MetaData->BasicMetaData->ID );

		$this->addfield( 'UserId', $longusername );
		$this->addObjectFields( $object );
		$this->addfield( 'OldRouteTo', $oldRouteTo );

		$this->fire();
	}
}

class smartevent_lockobject extends smartevent
{
	public function __construct( $ticket, $id, $user )
	{
		parent::__construct( EVENT_LOCKOBJECT, $ticket );
		$this->composeExchangeNameForObjectId( $id );

		$this->addfield( 'ID', $id );
		$this->addfield( 'LockedBy', $user );

		$this->fire();
	}
}

class smartevent_unlockobject extends smartevent
{
	public function __construct( $ticket, $id, $user, $bKeepLockForOffline, $routeto = null )
	{
		parent::__construct( EVENT_UNLOCKOBJECT, $ticket );
		$this->composeExchangeNameForObjectId( $id );

		$this->addfield( 'ID', $id );
		$this->addfield( 'LockedBy', $user ); // user will only be set when object is locked for offline
		$this->addfield( 'LockForOffline', $bKeepLockForOffline ? "true" : "false" );
		$this->addfield( 'RouteTo', $routeto );

		$this->fire();
	}
}

class smartevent_createobjectrelation extends smartevent
{
	public function __construct( $ticket, $child, $reltype, $parent, $name )
	{
		parent::__construct( EVENT_CREATEOBJECTRELATION, $ticket );
		$this->composeExchangeNameForObjectId( $parent );

		$this->addfield( 'Child', $child );
		$this->addfield( 'Type', $reltype );
		$this->addfield( 'Parent', $parent );
		$this->addfield( 'PlacedOn', $name );

		$this->fire();
	}
}

class smartevent_updateobjectrelation extends smartevent
{
	public function __construct( $ticket, $child, $reltype, $parent, $name )
	{
		parent::__construct( EVENT_UPDATEOBJECTRELATION, $ticket );
		$this->composeExchangeNameForObjectId( $parent );

		$this->addfield( 'Child', $child );
		$this->addfield( 'Type', $reltype );
		$this->addfield( 'Parent', $parent );
		$this->addfield( 'PlacedOn', $name );

		$this->fire();
	}
}

class smartevent_deleteobjectrelation extends smartevent
{
	public function __construct( $ticket, $child, $reltype, $parent, $name )
	{
		parent::__construct( EVENT_DELETEOBJECTRELATION, $ticket );
		$this->composeExchangeNameForObjectId( $parent );

		$this->addfield( 'Child', $child );
		$this->addfield( 'Type', $reltype );
		$this->addfield( 'Parent', $parent );
		$this->addfield( 'PlacedOn', $name );

		$this->fire();
	}
}

class smartevent_deadlinechanged extends smartevent
{
	public function __construct( $ticket, $id, $deadlinehard, $deadlinesoft )
	{
		parent::__construct( EVENT_DEADLINECHANGED, $ticket );
		$this->composeExchangeNameForObjectId( $id );

		$this->addfield( 'ID', $id );
		$this->addfield( 'DeadlineHard', $deadlinehard );
		$this->addfield( 'DeadlineSoft', $deadlinesoft );

		$this->fire();
	}
}

class smartevent_sendmessage extends smartevent
{
	public function __construct( $ticket, $message )
	{
		parent::__construct( EVENT_SENDMESSAGE, $ticket );
		if( $message->ObjectID ) {
			$this->composeExchangeNameForObjectId( $message->ObjectID );
		} else {
			$this->composeExchangeNameForSystem();
		}

		$this->addfield( 'UserID',       $message->UserID );
		$this->addfield( 'ObjectID',     $message->ObjectID );
		$this->addfield( 'MessageID',    $message->MessageID );
		$this->addfield( 'MessageType',  $message->MessageType );
		$this->addfield( 'MessageTypeDetail', $message->MessageTypeDetail );
		$this->addfield( 'TimeStamp',    $message->TimeStamp );
		$this->addfield( 'Message',      $message->Message );
		$this->addfield( 'MessageLevel', $message->MessageLevel );
		$this->addfield( 'FromUser',     $message->FromUser );
		// We do not send expiration, this is only meant for internal server purposes:
		//$this->addfield( 'Expiration', $message->Expiration );

		// Introduced since 8.0...
		$this->addfield( 'ThreadMessageID',  $message->ThreadMessageID );
		$this->addfield( 'ReplyToMessageID', $message->ReplyToMessageID );
		$this->addfield( 'MessageStatus',    $message->MessageStatus );
		$this->addfield( 'ObjectVersion',    $message->ObjectVersion );
		if( isset($message->IsRead) ) { // internal prop, but good to send (when known)
			$this->addfield( 'IsRead',       $message->IsRead ? 'true' : 'false' );
		}

		// Specific info for Sticky Notes...
		if( $message->StickyInfo ) {
			$this->addfield( 'AnchorX',  $message->StickyInfo->AnchorX );
			$this->addfield( 'AnchorY',  $message->StickyInfo->AnchorY );
			$this->addfield( 'Left',     $message->StickyInfo->Left );
			$this->addfield( 'Top',      $message->StickyInfo->Top );
			$this->addfield( 'Width',    $message->StickyInfo->Width );
			$this->addfield( 'Height',   $message->StickyInfo->Height );
			$this->addfield( 'Page',     $message->StickyInfo->Page );
			$this->addfield( 'Version',  $message->StickyInfo->Version );
			$this->addfield( 'Color',    $message->StickyInfo->Color );
			$this->addfield( 'PageSequence', $message->StickyInfo->PageSequence );
		}

		$this->fire();
	}
}

class smartevent_deletemessage extends smartevent
{
	/**
	 * Constructor.
	 *
	 * @param string $ticket
	 * @param int $messageId
	 * @param null|string $objectId [10.0.0] Only set for object messages, not for user messages.
	 */
	public function __construct( $ticket, $messageId, $objectId=null )
	{
		parent::__construct( EVENT_DELETEMESSAGE, $ticket );

		if( $objectId ) { // object message?
			$this->composeExchangeNameForObjectId( $objectId );
		} // else it is a user message for which the system event exchange should be used

		$this->addfield( 'MessageID', $messageId );

		$this->fire();
	}
}

class smartevent_restoreversion extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param string $fullUserName full username of the logged in user
	 * @param object $object all data is passed via $object
	 * @param string $oldRouteTo The RouteTo property of the object before it got updated
	 */
	public function __construct( $ticket, $fullUserName, $object, $oldRouteTo )
	{
		parent::__construct( EVENT_RESTOREVERSION, $ticket );
		$this->composeExchangeNameForObjectId( $object->MetaData->BasicMetaData->ID );

		$this->addfield( 'UserId', $fullUserName );
		$this->addObjectFields( $object );
		$this->addfield( 'OldRouteTo', $oldRouteTo );

		$this->fire();
	}
}

class smartevent_createobjecttarget extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param string $fullUserName full username of the logged in user
	 * @param int $objectId Enterprise object id
	 * @param Target $target
	 */
	public function __construct( $ticket, $fullUserName, $objectId, $target )
	{
		parent::__construct( EVENT_CREATEOBJECTTARGET, $ticket );
		$this->composeExchangeNameForObjectId( $objectId );

		$this->addfield( 'UserId', $fullUserName );
		$this->addfield( 'ID', $objectId );
		$this->addTargetFields( $target );

		$this->fire();
	}
}

class smartevent_deleteobjecttarget extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param string $fullUserName full username of the logged in user
	 * @param int $objectId Enterprise object id
	 * @param Target $target
	 */
	public function __construct( $ticket, $fullUserName, $objectId, $target )
	{
		parent::__construct( EVENT_DELETEOBJECTTARGET, $ticket );
		$this->composeExchangeNameForObjectId( $objectId );

		$this->addfield( 'UserId', $fullUserName );
		$this->addfield( 'ID', $objectId );
		$this->addTargetFields( $target );

		$this->fire();
	}
}

class smartevent_updateobjecttarget extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param string $fullUserName full username of the logged in user
	 * @param int $objectId Enterprise object id
	 * @param Target $target
	 */
	public function __construct( $ticket, $fullUserName, $objectId, $target )
	{
		parent::__construct( EVENT_UPDATEOBJECTTARGET, $ticket );
		$this->composeExchangeNameForObjectId( $objectId );

		$this->addfield( 'UserId', $fullUserName );
		$this->addfield( 'ID', $objectId );
		$this->addTargetFields( $target );

		$this->fire();
	}
}

class smartevent_issuereorder extends smartevent
{
	/**
	 * Event on dossier reordering (within an issue) at production.
	 *
	 * @param string $ticket ticket of the logged in user
	 * @param string $pubChannelType
	 * @param integer $issueId
	 * @param string $dossierIds Comma separated list of dossiers, base64 encoded, as ordered within the issue.
	 * @since v7.0.13 for Digital Magazine / Newsfeed
	 */
	public function __construct( $ticket, $pubChannelType, $issueId, $dossierIds )
	{
		parent::__construct( EVENT_ISSUE_DOSSIER_REORDER_AT_PRODUCTION, $ticket );
		$this->composeExchangeNameForIssue( $issueId );

		$this->addfield( 'PubChannelType', $pubChannelType );
		$this->addfield( 'IssueId', $issueId );
		$this->addfield( 'DossierIds', $dossierIds );
		// L> When there are too many dossiers to fit into 1K package, the DossierIds field is not sent at all.
		//    This is an indication to clients (like CS) to start polling issue orders instead (through AMF).

		$this->fire();
	}
}

class smartevent_issuereorderpublished extends smartevent
{
	/**
	 * Event on dossier reordering (within an issue) at publish system.
	 *
	 * @param string $ticket ticket of the logged in user
	 * @param string $pubChannelType
	 * @param PubPublishedIssue $publishedIssue
	 * @param string $dossierIds Comma separated list of dossiers, base64 encoded, as ordered within the issue.
	 * @since v7.5.0
	 */
	public function __construct( $ticket, $pubChannelType, $publishedIssue, $dossierIds )
	{
		parent::__construct( EVENT_ISSUE_DOSSIER_REORDER_PUBLISHED, $ticket );
		$this->composeExchangeNameForIssue( $publishedIssue->Target->IssueID );

		$this->addfield( 'PubChannelType', $pubChannelType );
		$this->addfield( 'PubChannelId', $publishedIssue->Target->PubChannelID );
		$this->addfield( 'IssueId', $publishedIssue->Target->IssueID );
		$this->addfield( 'EditionId', $publishedIssue->Target->EditionID );
		$this->addfield( 'DossierIds', $dossierIds );
		// L> When there are too many dossiers to fit into 1K package, the DossierIds field is not sent at all.
		//    This is an indication to clients (like CS) to start polling issue orders instead (through AMF).

		$this->fire();
	}
}

class smartevent_publishdossier extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param PubPublishedDossier $publishedDossier
	 * @param string $pubChannelType
	 * @since v7.5.0
	 */
	public function __construct( $ticket, $publishedDossier, $pubChannelType )
	{
		parent::__construct( EVENT_PUBLISH_DOSSIER, $ticket );
		$this->composeExchangeNameForObjectId( $publishedDossier->DossierID );

		$publishTarget = $publishedDossier->Target;
		$this->addfield( 'DossierId', $publishedDossier->DossierID );
		$this->addfield( 'PubChannelType', $pubChannelType );
		$this->addfield( 'PubChannelId', $publishTarget->PubChannelID );
		$this->addfield( 'IssueId', $publishTarget->IssueID );
		$this->addfield( 'EditionId', $publishTarget->EditionID );
		$this->addfield( 'PublishedDate', $publishedDossier->PublishedDate );
		if( isset( $publishedDossier->Fields ) ) foreach( $publishedDossier->Fields as $field ) {
			$this->addfield( $field->Key, $field->Values[0] );
		}

		$this->fire();
	}
}

class smartevent_updatedossier extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param PubPublishedDossier $publishedDossier
	 * @param string $pubChannelType
	 * @since v7.5.0
	 */
	public function __construct( $ticket, $publishedDossier, $pubChannelType )
	{
		parent::__construct( EVENT_UPDATE_DOSSIER, $ticket );
		$this->composeExchangeNameForObjectId( $publishedDossier->DossierID );

		$publishTarget = $publishedDossier->Target;
		$this->addfield( 'DossierId', $publishedDossier->DossierID );
		$this->addfield( 'PubChannelType', $pubChannelType );
		$this->addfield( 'PubChannelId', $publishTarget->PubChannelID );
		$this->addfield( 'IssueId', $publishTarget->IssueID );
		$this->addfield( 'EditionId', $publishTarget->EditionID );
		$this->addfield( 'PublishedDate', $publishedDossier->PublishedDate );
		if( isset( $publishedDossier->Fields ) ) foreach( $publishedDossier->Fields as $field ) {
			$this->addfield( $field->Key, $field->Values[0] );
		}

		$this->fire();
	}
}

class smartevent_unpublishdossier extends smartevent
{
	/**
	 * @param string $ticket ticket of the logged in user
	 * @param PubPublishedDossier $publishedDossier
	 * @param string $pubChannelType
	 * @since v7.5.0
	 */
	public function __construct( $ticket, $publishedDossier, $pubChannelType )
	{
		parent::__construct( EVENT_UNPUBLISH_DOSSIER, $ticket );
		$this->composeExchangeNameForObjectId( $publishedDossier->DossierID );

		$publishTarget = $publishedDossier->Target;
		$this->addfield( 'DossierId', $publishedDossier->DossierID );
		$this->addfield( 'PubChannelType', $pubChannelType );
		$this->addfield( 'PubChannelId', $publishTarget->PubChannelID );
		$this->addfield( 'IssueId', $publishTarget->IssueID );
		$this->addfield( 'EditionId', $publishTarget->EditionID );
		if( isset( $publishedDossier->Fields ) ) foreach( $publishedDossier->Fields as $field ) {
			$this->addfield( $field->Key, $field->Values[0] );
		}

		$this->fire();
	}
}

class smartevent_setpublishinfofordossier extends smartevent
{
	/**
	 * @param string $ticket Ticket for the logged in user.
	 * @param PubPublishedDossier $publishedDossier
	 * @param string $pubChannelType Publication channel type: e.g: 'print'
	 * @since v7.5.0
	 */
	public function __construct( $ticket, $publishedDossier, $pubChannelType )
	{
		parent::__construct( EVENT_SET_PUBLISH_INFO_FOR_DOSSIER, $ticket );
		$this->composeExchangeNameForObjectId( $publishedDossier->DossierID );

		$this->addfield( 'DossierId', $publishedDossier->DossierID );
		$this->addfield( 'PubChannelType', $pubChannelType );
		$this->addfield( 'PubChannelId', $publishedDossier->Target->PubChannelID );
		$this->addfield( 'IssueId', $publishedDossier->Target->IssueID );
		$this->addfield( 'EditionId', $publishedDossier->Target->EditionID );
		$this->addfield( 'PublishedDate', $publishedDossier->PublishedDate );
		if( isset( $publishedDossier->Fields ) ) foreach( $publishedDossier->Fields as $field ) {
			$this->addfield( $field->Key, $field->Values[0] );
		}

		$this->fire();
	}
}

class smartevent_publishissue extends smartevent
{
	/**
	 * @param string $ticket
	 * @param PubPublishedIssue $publishedIssue
	 * @param string $pubChannelType Publication channel type: e.g: 'print'
	 * @since v7.5.0
	 */
	public function __construct( $ticket, $publishedIssue, $pubChannelType  )
	{
		parent::__construct( EVENT_PUBLISH_ISSUE, $ticket );
		$this->composeExchangeNameForIssue( $publishedIssue->Target->IssueID );

		$this->addfield( 'PubChannelType', $pubChannelType );
		$this->addfield( 'PubChannelId', $publishedIssue->Target->PubChannelID );
		$this->addfield( 'IssueId', $publishedIssue->Target->IssueID );
		$this->addfield( 'EditionId', $publishedIssue->Target->EditionID );
		$this->addfield( 'Version', $publishedIssue->Version );
		$this->addfield( 'PublishedDate', $publishedIssue->PublishedDate );
		if( isset( $publishedIssue->Fields ) ) foreach( $publishedIssue->Fields as $field ) {
			$this->addfield( $field->Key, $field->Values[0] );
		}

		$this->fire();
	}
}

class smartevent_updateissue extends smartevent
{
	/**
	 * @param string $ticket
	 * @param PubPublishedIssue $publishedIssue
	 * @param string $pubChannelType Publication channel type: e.g: 'print'
	 * @since v7.5.0
	 */
	public function __construct( $ticket, $publishedIssue, $pubChannelType  )
	{
		parent::__construct( EVENT_UPDATE_ISSUE, $ticket );
		$this->composeExchangeNameForIssue( $publishedIssue->Target->IssueID );

		$this->addfield( 'PubChannelType', $pubChannelType );
		$this->addfield( 'PubChannelId', $publishedIssue->Target->PubChannelID );
		$this->addfield( 'IssueId', $publishedIssue->Target->IssueID );
		$this->addfield( 'EditionId', $publishedIssue->Target->EditionID );
		$this->addfield( 'Version', $publishedIssue->Version );
		$this->addfield( 'PublishedDate', $publishedIssue->PublishedDate );
		if( isset( $publishedIssue->Fields ) ) foreach( $publishedIssue->Fields as $field ) {
			$this->addfield( $field->Key, $field->Values[0] );
		}

		$this->fire();
	}
}

class smartevent_unpublishissue extends smartevent
{
	/**
	 * @param string $ticket
	 * @param PubPublishedIssue $publishedIssue
	 * @param string $pubChannelType Publication channel type: e.g: 'print'
	 * @since v7.5.0
	 */
	public function __construct( $ticket, $publishedIssue, $pubChannelType  )
	{
		parent::__construct( EVENT_UNPUBLISH_ISSUE, $ticket );
		$this->composeExchangeNameForIssue( $publishedIssue->Target->IssueID );

		$this->addfield( 'PubChannelType', $pubChannelType );
		$this->addfield( 'PubChannelId', $publishedIssue->Target->PubChannelID );
		$this->addfield( 'IssueId', $publishedIssue->Target->IssueID );
		$this->addfield( 'EditionId', $publishedIssue->Target->EditionID );
		$this->addfield( 'Version', $publishedIssue->Version );
		if( isset( $publishedIssue->Fields ) ) foreach( $publishedIssue->Fields as $field ) {
			$this->addfield( $field->Key, $field->Values[0] );
		}

		$this->fire();
	}
}

class smartevent_setpublishinfoforissue extends smartevent
{
	/**
	 * @param string $ticket Ticket for the logged in user.
	 * @param PubPublishedIssue $publishedIssue
	 * @param string $pubChannelType Publication channel type: e.g: 'print'
	 * @since v7.5.0
	 */
	public function __construct( $ticket, $publishedIssue, $pubChannelType )
	{
		parent::__construct( EVENT_SET_PUBLISH_INFO_FOR_ISSUE, $ticket );
		$this->composeExchangeNameForIssue( $publishedIssue->Target->IssueID );

		$this->addfield( 'PubChannelType', $pubChannelType );
		$this->addfield( 'PubChannelId', $publishedIssue->Target->PubChannelID );
		$this->addfield( 'IssueId', $publishedIssue->Target->IssueID );
		$this->addfield( 'EditionId', $publishedIssue->Target->EditionID );
		$this->addfield( 'Version', $publishedIssue->Version );
		$this->addfield( 'PublishedDate', $publishedIssue->PublishedDate );
		if( isset( $publishedIssue->Fields ) ) foreach( $publishedIssue->Fields as $field ) {
			$this->addfield( $field->Key, $field->Values[0] );
		}

		$this->fire();
	}
}

class smartevent_createobjectlabels extends smartevent
{
	/**
	 * @param string $objectId Dossier id or Dossier Template id.
	 * @param ObjectLabel[] $labels
	 * @since v9.1.0
	 */
	public function __construct( $objectId, array $labels )
	{
		parent::__construct( EVENT_CREATE_OBJECT_LABELS, BizSession::getTicket() );
		$this->composeExchangeNameForObjectId( $objectId );

		$this->addfield( 'ObjectId', $objectId );
		$this->addObjectLabels( $labels );

		$this->fire();
	}
}

class smartevent_updateobjectlabels extends smartevent
{
	/**
	 * @param string $objectId Dossier id or Dossier Template id.
	 * @param ObjectLabel[] $labels
	 * @since v9.1.0
	 */
	public function __construct( $objectId, array $labels )
	{
		parent::__construct( EVENT_UPDATE_OBJECT_LABELS, BizSession::getTicket() );
		$this->composeExchangeNameForObjectId( $objectId );

		$this->addfield( 'ObjectId', $objectId );
		$this->addObjectLabels( $labels );

		$this->fire();
	}
}

class smartevent_deleteobjectlabels extends smartevent
{
	/**
	 * @param string $objectId Dossier id or Dossier Template id.
	 * @param ObjectLabel[] $labels
	 * @since v9.1.0
	 */
	public function __construct( $objectId, array $labels )
	{
		parent::__construct( EVENT_DELETE_OBJECT_LABELS, BizSession::getTicket() );
		$this->composeExchangeNameForObjectId( $objectId );

		$this->addfield( 'ObjectId', $objectId );
		$this->addObjectLabels( $labels );

		$this->fire();
	}
}

class smartevent_addobjectlabels extends smartevent
{
	/**
	 * @param string $parentId Dossier id or Dossier Template id.
	 * @param array $childIds Ids of objects contained by the dosier ($parentId).
	 * @param ObjectLabel[] $labels Object labels to add.
	 * @since v9.1.0
	 */
	public function __construct( $parentId, array $childIds, array $labels )
	{
		parent::__construct( EVENT_ADD_OBJECT_LABELS, BizSession::getTicket() );
		$this->composeExchangeNameForObjectId( $parentId );

		$this->addfield( 'ParentId', $parentId );
		$this->addfield( 'ChildIds', $childIds, true );
		$this->addObjectLabels( $labels );

		$this->fire();
	}
}

class smartevent_removeobjectlabels extends smartevent
{
	/**
	 * @param string $parentId Dossier id or Dossier Template id.
	 * @param array $childIds Ids of objects contained by the dosier ($parentId).
	 * @param ObjectLabel[] $labels Object labels to remove.
	 * @since v9.1.0
	 */
	public function __construct( $parentId, array $childIds, array $labels )
	{
		parent::__construct( EVENT_REMOVE_OBJECT_LABELS, BizSession::getTicket() );
		$this->composeExchangeNameForObjectId( $parentId );

		$this->addfield( 'ParentId', $parentId );
		$this->addfield( 'ChildIds', $childIds, true );
		$this->addObjectLabels( $labels );

		$this->fire();
	}
}

class smartevent_setPropertiesForMultipleObjects extends smartevent
{
	/**
	 * Constructs a new notification Event for setting properties on multiple objects.
	 *
	 * Broadcasts messages using a cartessian product of the ids and the properties to notify the various
	 *
	 * @param int[] $ids Ids of the objects for which the properties were set.
	 * @param array $properties An array of key / value pairs containing the updated properties.
	 */
	public function __construct( array $ids, array $properties )
	{
		parent::__construct( EVENT_SET_PROPERTIES_FOR_MULTIPLE_OBJECTS, BizSession::getTicket() );
		$this->composeExchangeNameForObjectId( reset( $ids ) );

		$this->addfield( 'ObjectIds', $ids, true );
		$this->addfield( 'properties', $properties, true );

		$this->fire( 'CartessianEvenly' );
	}
}

class smartevent_debug extends smartevent
{
	public function __construct( $message )
	{
		parent::__construct( EVENT_DEBUG );
		$this->composeExchangeNameForSystem();

		$this->addfield( 'Time', date( "H:i:s" ) );
		$this->addfield( 'Message', $message );

		$this->fire();
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/**
 * SCEntMessenger uses a connectionless UDP socket to transmit binary to its destination.
 *
 * Example of use:
 *
 * $m = new SCEntMessenger();
 * $m->set_destination("192.168.1.5", 3890);
 * $m->send("just an example");
 *
 * Since it is connectionless, you can change the destination address/port at any time.
 * If you are having problems establishing communication, it may be due to a bad address,
 * improper setup of the IP routing table, or a problem on the other end.  When in doubt,
 * use tcpdump or ethereal to check that packets are indeed being transmitted.
 */
class SCEntMessenger 
{
	private $sock = null;
	private $address = '';
	private $port = 0;

	/** 
	 * Address is an IP address, given as a string.
	 * To convert a hostname to IP, use gethostbyname('www.example.com')
	 * You must also specify a port as an integer, typically $port is larger than 1024.
	 *
	 * @param string $address
	 * @param integer $port
	 */
	public function __construct( $address = '', $port = 0 ) 
	{
		$this->address = $address;
		$this->port = $port;

		$this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		//		if(($this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)) < 0) {
		//			$this->error("Could not create datagram socket.");
		//		}
	}

	/** 
	 * Destructor function, usually not needed, provided in case you want to free the socket.
	 */
	public function destroy() 
	{
		socket_close($this->sock);
	}

	/** 
	 * Enables broadcasting.
	 */
	public function enable_broadcast() 
	{
		socket_set_option($this->sock, SOL_SOCKET, SO_BROADCAST, 1);
		//		if(($ret = socket_set_option($this->sock, SOL_SOCKET, SO_BROADCAST, 1)) < 0) {
		//			$this->error("Failed to enable broadcast option.");
		//		}
	}

	/** 
	 * Disables broadcasting.
	 */
	public function disable_broadcast() 
	{
		socket_set_option($this->sock, SOL_SOCKET, SO_BROADCAST, 0);
		//		if(($ret = socket_set_option($this->sock, SOL_SOCKET, SO_BROADCAST, 0)) < 0) {
		//			$this->error("Failed to disable broadcast option.");
		//		}
	}

	/** 
	 * Address is an IP address, given as a string.
	 * To convert a hostname to IP, use gethostbyname('www.example.com')
	 * You must also specify a port as an integer, typically $port is larger than 1024.
	 *
	 * @param string $address
	 * @param integer $port
	 */
	public function set_destination( $address, $port ) 
	{
		$this->address = $address;
		$this->port = $port;
	}

	/**
	 * send() accepts either an OSCDatagram object or a binary string
	 *
	 * @param string $message
	 * @return integer|boolean The number of bytes sent to the remote host, or FALSE if an error occurred.
	 */
	public function send( $message ) 
	{
		if(is_object($message)) {
			$message = $message->get_binary();
		}
		LogHandler::Log('message', 'DEBUG', 'Message: '.$message.' Length: '.strlen($message));
		return socket_sendto($this->sock, $message, strlen($message), 0, $this->address, $this->port);
	}

	/**
	 * Report a fatal error.
	 *
	 * @param string $message
	 */
	public function error($message) 
	{
		trigger_error("SCEntMessenger Error: $message", E_USER_ERROR);
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/**
 * Global event queue that can be used to expose fireing smartevent objects.
 * The $queues member is a stack for future purposes; It can deal with nested queues.
 *
 * For example, SmartEventQueue is used in context of creating new article in new dossier. In that
 * case the server needs to wait broadcasting CreateObjectRelations event until the two CreateObjects
 * events are sent. Or else it will confuse clients with ids of objects they are not aware of yet (BZ#15317).
 */
class SmartEventQueue
{
	static private $queues = array(); // stack of queues of smartevent objects

	/**
	 * Creates new queue (and adds it to the stack).
	 * It stops direct fire mode, and starts recording events instead.
	 */
	static public function createQueue()
	{
		$newQueue = array( 'events' => array(), 'canFire' => false );
		//array_push( self::$queues, $newQueue );
		self::$queues[] = $newQueue;

		//LogHandler::Log('smartevent','DEBUG','createQueue, queue: ['.print_r(self::$queues,true).']' );
	}

	/**
	 * Starts direct fire mode, and does no more recording events.
	 */
	static public function startFire()
	{
		$topQueue = &self::getTopQueue();
		$topQueue['canFire'] = true;

		//LogHandler::Log('smartevent','DEBUG','startFire, queue: ['.print_r(self::$queues,true).']' );
	}

	/**
	 * Stops direct fire mode, and starts recording events instead.
	 */
	static public function stopFire()
	{
		$topQueue = &self::getTopQueue();
		$topQueue['canFire'] = false;

		//LogHandler::Log('smartevent','DEBUG','stopFire, queue: ['.print_r(self::$queues,true).']' );
	}

	/**
	 * Fire all events from the (top most) queue and removes that queue.
	 */
	static public function fireQueue()
	{
		self::startFire(); // make sure our fire gets through directly
		$topQueue = &self::getTopQueue();
		$events = $topQueue['events'];
		LogHandler::Log('smartevent','DEBUG','fireQueue takes out top most queue with '.count($events).' events.' );
		while( ($event = array_shift($events)) ) {
			$event->fire(); // Note: does call back our canFire() method!
		}
		array_pop( self::$queues ); // remove top most queue once all its events are fired!

		//LogHandler::Log('smartevent','DEBUG','fireQueue, queue: ['.print_r(self::$queues,true).']' );
	}

	/**
	 * Add event to the (top most) queue, typically to expose fire.
	 *
	 * @param smartevent $event
	 */
	static public function addEvent( smartevent $event )
	{
		$topQueue = &self::getTopQueue();
		//array_push( $topQueue['events'], $event );
		$topQueue['events'][] = $event;

		//LogHandler::Log('smartevent','DEBUG','addEvent, queue: ['.print_r(self::$queues,true).']' );
	}

	/**
	 * Tells if the (top most) queue is in fire mode (or if there is no queue created at all).
	 *
	 * @return boolean
	 */
	static public function canFire()
	{
		if( count(self::$queues) > 0 ) {
			$topQueue = &self::getTopQueue();
			return $topQueue['canFire'];
		}
		return true;
	}

	static private function &getTopQueue()
	{
		$keys = array_keys( self::$queues );
		$lastkey = end( $keys );
		return self::$queues[$lastkey];
	}
}