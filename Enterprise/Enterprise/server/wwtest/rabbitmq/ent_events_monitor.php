<?php
require_once '../../../config/config.php';

/**
 * Determines the acting client name or IP address.
 *
 * @return string
 */
function getClientName()
{
	$clientName = isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '';
	if( empty($clientName) ) {
		require_once BASEDIR . '/server/utils/UrlUtils.php';
		$clientName = WW_Utils_UrlUtils::getClientIP();
		// BZ#6359 Let's use ip since gethostbyaddr() could be extremely expensive!
	}
	return $clientName;
}
?>
<!DOCTYPE html>
<html>
<head>
	<script language="javascript" src="../../jquery/jquery-1.8.2.min.js"></script>
	<script language="javascript" src="../../utils/javascript/stomp.js"></script>
	<script language="javascript" src="../../utils/javascript/jquery.zend.jsonrpc.js"></script>
	
	<link href="main.css" rel="stylesheet" type="text/css"/>
	<title>RabbitMQ - Enterprise events monitor</title>
</head>
<body>
    <h1 id="docTitle"></h1>
    <p id="webSocketUrl">Connected through: </p>

    <div id="objRows">
        <h2>Events</h2>
    </div>

    <script>
	    $('#docTitle').append( document.title );

		// Configure entry point (URL) to workflow interface of Enterprise Server.
		var wflServerUrl = '<?php echo SERVERURL_ROOT.INETROOT.'/index.php?protocol=JSON' ?>';
		
		// Process all necessary URL parameters. If any of them is missing an error is thrown.
		var username = getUrlParameter('user');
		if( !username ) {
			throwError('Parameter "user" is missing from the URL. Fill in your Enterprise username.');
		}
		var password = getUrlParameter('pass');
		if( !password ) {
			throwError('Parameter "pass" is missing from the URL. Fill in your Enterprise password.');
		}
		var vhost = '<?php echo BizSession::getEnterpriseSystemId() ?>';

		try {
			// Create JSON proxy to Enterprise Server workflow interface
			wflServices = jQuery.Zend.jsonrpc({url: wflServerUrl});
		} catch( e ) {
			throwError('Could not connect to Enterprise Server.')
		}

		var resp = logOnEnterprise(username, password);
		var ticket = resp.Ticket;
		var connections = resp.MessageQueueConnections;

		// Get the STOMPWS messagequeue connection.
		var connection = null;
		if( resp.MessageQueueConnections ) {
			resp.MessageQueueConnections.forEach( function(con) {
				if( con.Instance == "RabbitMQ" && con.Protocol == "STOMPWS" ) {
					connection = con;
				}
			});
		}
		if( !connection ) {
			throwError('The RabbitMQ STOMPWS configuration is missing. Please check your settings in configserver.php.');
		}
		
		// Subscribe for each publication listed in the Logon response.
		if( resp.Publications ) {
			var webSocketUrl = connection.Url;
			var ws = new WebSocket( webSocketUrl );
			$('#webSocketUrl').append( webSocketUrl );
			var client = Stomp.over(ws);

			/**
			 * When connected to RabbitMQ, we subscribe to the system.events queue by default, followed by all 
			 * publication and overrule issue queues found in the logon response.
			 */
			var on_connect = function(x) {
				subscribeToQueue(resp.MessageQueue);
			};
			var on_error = function(frame) {
				console.log(frame);
				if (typeof frame === 'string' || frame instanceof String) {
					alert(frame);
				} else {
					alert(frame.body);
				}
			};
			client.connect(connection.User, password, on_connect, on_error, vhost);
		}

		/**
		 * Replaces any dots in a string with dashes in order to make the strings usable as HTML element ids.
		 * @param string
		 * @returns escaped string
		 */
		function escapeCSSNotation(string) {
			return string.replace( /\./g, "-" );
		}

		/**
		 * Throws an error. It outputs the error message both on the screen in an alert window and in the console.
		 * @param errorMessage
		 */
		function throwError(errorMessage) {
			alert(errorMessage);
			throw new Error(errorMessage);
		}

		/**
		 * Reads the value of an URL parameter. If the parameter is not given, it returns a false boolean value.
		 * @param paramKey Key value of the URL parameter that is requested.
		 * @returns string|false
		 */
		function getUrlParameter(paramKey) {
			var sPageURL = decodeURIComponent(window.location.search.substring(1)),
				sURLVariables = sPageURL.split('&'),
				sParameterName,
				i;

			for (i = 0; i < sURLVariables.length; i++) {
				sParameterName = sURLVariables[i].split('=');

				if (sParameterName[0] === paramKey) {
					return sParameterName[1] === undefined ? false : sParameterName[1];
				}
			}
		}

		/**
		 * Performs a log on to Enterprise Server, requesting MessageQueue information.
		 * 
		 * @param username
		 * @param password
		 * @returns object
		 */
		function logOnEnterprise(username, password) {
			try {
				var result = wflServices.LogOn({
					User: username,
					Password: password,
					ClientName: '<?php echo getClientName(); ?>',
					ClientAppName: document.title,
					ClientAppVersion: '<?php echo "v".SERVERVERSION ?>',
					RequestInfo: ['Publications', 'MessageQueueConnections']
				});
			} catch( e ) {
			}
			if( wflServices.error ) {
				throwError( 'Could not connect to Enterprise Server. ' + wflServices.error_message );
			} 
			return result;
		}

		/**
		 * Subscribe to a queue and update the webpage with the queue information and contents.
		 * @param queueName
		 */
		function subscribeToQueue(queueName) {
			/**
			 * Prints a message to the screen everytime one is received from RabbitMQ.
			 * @param object frame The complete RabbitMQ message.
			 */
			var subscriptionCallback = function( frame ) {
				var message = jQuery.parseJSON( frame.body );
				var data = message.EventData;
				var queueName = frame.headers.subscription;
				var escapedQueueName = escapeCSSNotation( queueName );

				var time = new Date($.now());
				$('#queue_'+escapedQueueName).append( '<tr><td>'+time+'</td><td>'+message.EventHeaders.EventId+'</td>'+
					'<td>'+JSON.stringify(data)+'</td><td>'+frame.headers.destination+'</td></tr>' );
				$('#nevents_'+escapedQueueName).text( parseInt($('#nevents_'+escapedQueueName).text(), 10) + 1 );
			}

			client.subscribe( '/queue/'+queueName, subscriptionCallback, { id: queueName } );

			var escapedQueueName = escapeCSSNotation(queueName);

			$('#objRows').append('<span>Message Queue: '+queueName+'</span>')
				.append(' -- # of events: <span id="nevents_'+escapedQueueName+'">0</span>')
				.append('<table id="queue_'+escapedQueueName+'"><tr><th>Time</th><th>Event id</th><th>Event data</th><th>Destination</th></tr></table>');
		}
	</script>
</body>
</html>