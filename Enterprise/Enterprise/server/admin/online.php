<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure('admin');
$tpl = HtmlDocument::loadTemplate( 'online.htm' );

require_once( BASEDIR . '/server/dbclasses/DBTicket.class.php' );
$user = DBTicket::checkTicket( $ticket );

// get all users from DB
$users = array();
$dbDriver = DBDriverFactory::gen();
$db = $dbDriver->tablename("users");
$sql = "SELECT `fullname`, `user` from $db";
$sth = $dbDriver->query($sql);
if (!$sth) exit;
while (($row = $dbDriver->fetch($sth))) {
	$users[$row['user']] = $row['fullname'];
}

// get all tickets sorted on the full name from the DB
$db = $dbDriver->tablename("tickets");
$userTable = $dbDriver->tablename("users");
$sql = "SELECT `ticketid`, `usr`, `clientname`, `clientip`, `appname`, `appversion`, `expire`, `logon` FROM $db, $userTable WHERE `usr` = `user` ORDER BY `fullname`";
$sth = $dbDriver->query($sql);
if (!$sth) exit;

// build tablerows with the results
$txt = '';
while (($row = $dbDriver->fetch($sth))) {
	$txt .= '<tr bgcolor="#DDDDDD">';
        $txt .= '<td class="text"><input type="checkbox" class="logOutUsers" name="logOutUsers[]" value="'  . formvar($row['ticketid']) . '"></td>';
        $txt .= '<td class="text">';
            $row['usr'] = strtolower($row['usr']);
            $users = array_change_key_case($users);
            if (array_key_exists($row['usr'], $users)) {
                $txt .= formvar($users[$row['usr']]);
            } else {
                $txt .= '&nbsp;';
            }
        $txt .= '</td>';

        // Client name not used, so don't show:
        //$txt .= '<td class="text">'.formvar($row['clientname']).'</td>';

        // Calculate the idle time of a user for a certain application
        $expire = strtotime($row['expire']);
        $expireOffset = DBTicket::getExpireTime( $row['appname']);

        $idleTime = time() - ( $expire - $expireOffset );

        $txt .= '<td class="text">'.formvar($row['clientip']).'</td>';
        $txt .= '<td class="text">'.formvar($row['appname']).'</td>';
        $txt .= '<td class="text">'.formvar($row['appversion']).'</td>';
        $txt .= '<td class="text">'.formvar(timeConverter($row['logon'])).'</td>';
        $txt .= '<td class="text">'.formvar(timeConverter($row['expire'])).'</td>';
        $txt .= '<td class="text">'.formvar( formatIdleTime($idleTime) ).'</td>';
	$txt .= '</tr>';
}

$tpl = str_replace ('<!--CONTENT-->', $txt, $tpl);
print HtmlDocument::buildDocument($tpl);

function timeConverter($val) {
	$val_array = preg_split('/[T]/', $val);	
	$date_array = preg_split('/[-]/', $val_array['0']);
	$date_formated = $date_array[2] . "-" . $date_array[1] . "-" . $date_array[0];
	return $date_formated . " " . $val_array['1'];
}

/**
 * Formats a give duration in seconds into a short readable (and localized) time indication
 * that is used to format the Idle Time column on the Online Users admin page.
 *
 * There are two units returned only, either "X days, Y hours" or "X hours, Y minutes".
 * When X or Y is zero, the whole unit is left out, e.g. when Y==0 it becomes: "X days".
 * Seconds are round up to minutes. When more than one hour, it is round up to 5 minutes.
 * When more than one day, it is round up to hours.
 *
 * @param integer $seconds Duration in time.
 * @return string Readable time indication.
 */
function formatIdleTime( $seconds )
{
    // Support for negative seconds.
    $sign = '';
    $absSeconds = abs($seconds);
    if( $absSeconds != $seconds ) {
        $seconds = $absSeconds;
        $sign = '-';
    }

    // Round up the seconds to minutes, 5 minutes or hours.
    if( $seconds >= 86400 ) { // more than one day?
        $seconds = round( $seconds / 3600 ) * 3600; // round per 1 hour
    } else {
        if( $seconds >= 3600 ) { // more than one hour?
            $seconds = round( $seconds / 300 ) * 300; // round per 5 minutes
        } else {
            $seconds = round( $seconds / 60 ) * 60; // round per minute
        }
    }

    // When given time span is one day or more, we want days and hours only.
    // When less than one day, we want hours and minutes only.
    if( $seconds >= 86400 ) { // more than one day?
        $units = array ( 'd' => 86400, 'h' => 3600 ); // days, hours
    } else { // less than one day?
        $units = array ( 'h' => 3600, 'i' => 60 ); // hours, minutes
    }

    // Compose the formatted duration string.
    // For example: "5 hours, 35 minutes" or "3 days, 4 hours"
    $retVal = '';
    $separator = '';
    foreach( $units as $unit => $value ) {
        $number = floor( $seconds / $value );
        if( $number > 0 ) {
            $retVal .= $separator . $sign . $number . ' ' . localizeTimeUnit( $unit, $number != 1 );
            $seconds %= $value;
            $separator = ', ';
        }
    }

    // If less than one minute, round it to return 1 or 0 minutes.
    if( !$retVal ) {
        $minutes = round( $seconds / 60 );
        if( $minutes == 0 ) {
            $sign = '';
        }
        $retVal = $sign . $minutes.' '.localizeTimeUnit( 'i', $minutes != 1 );
    }
    return $retVal;
}

/**
 * Localizes a given time unit, respecting on the user language.
 *
 * The following units are supported and localized as follows (e.g. to enUS):
 *    'd' => day or days
 *    'h' => hour or hours
 *    'i' => minute or minutes
 *
 * @param string $unit Time unit: 'd', 'h' or 'i'
 * @param boolean $plural TRUE to localize unit in plural, FALSE to singular
 * @return string Localized time unit.
 */
function localizeTimeUnit( $unit, $plural )
{
    switch( $unit ) {
        case 'd':
            if( $plural ) {
                $localized = BizResources::localize('TIME_DAYS');
            } else {
                $localized = BizResources::localize('TIME_DAY');
            }
            break;
        case 'h':
            if( $plural ) {
                $localized = BizResources::localize('TIME_HOURS');
            } else {
                $localized = BizResources::localize('TIME_HOUR');
            }
            break;
        case 'i':
            if( $plural ) {
                $localized = BizResources::localize('TIME_MINUTES');
            } else {
                $localized = BizResources::localize('TIME_MINUTE');
            }
            break;
    }
    return $localized;
}

?>