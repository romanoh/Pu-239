<?php
require_once INCL_DIR . 'user_functions.php';
require_once CLASS_DIR . 'class_check.php';
global $lang;

$lang = array_merge($lang, load_language('ad_mysql_stats'));
class_check(UC_MAX);
$GLOBALS['byteUnits'] = [
    'Bytes',
    'KB',
    'MB',
    'GB',
    'TB',
    'PB',
    'EB',
];
$day_of_week = [
    'Sun',
    'Mon',
    'Tue',
    'Wed',
    'Thu',
    'Fri',
    'Sat',
];
$month = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];
// See http://www.php.net/manual/en/function.strftime.php to define the
// variable below
$datefmt = '%B %d, %Y at %I:%M %p';
$timespanfmt = '%s days, %s hours, %s minutes and %s seconds';
////////////////// FUNCTION LIST /////////////////////////
/**
 * @param     $value
 * @param int $limes
 * @param int $comma
 *
 * @return array
 */
function byteformat($value, $limes = 2, $comma = 0)
{
    $dh = pow(10, $comma);
    $li = pow(10, $limes);
    $return_value = $value;
    $unit = $GLOBALS['byteUnits'][0];
    for ($d = 6, $ex = 15; $d >= 1; $d--, $ex -= 3) {
        if (isset($GLOBALS['byteUnits'][ $d ]) && $value >= $li * pow(10, $ex)) {
            $value = round($value / (pow(1024, $d) / $dh)) / $dh;
            $unit = $GLOBALS['byteUnits'][ $d ];
            break 1;
        } // end if
    } // end for
    if ($unit != $GLOBALS['byteUnits'][0]) {
        $return_value = number_format($value, $comma, '.', ',');
    } else {
        $return_value = number_format($value, 0, '.', ',');
    }

    return [
        $return_value,
        $unit,
    ];
} // end of the 'formatByteDown' function
/**
 * @param $seconds
 *
 * @return string
 */
function timespanFormat($seconds)
{
    global $lang;
    $return_string = '';
    $days = floor($seconds / 86400);
    if ($days > 0) {
        $seconds -= $days * 86400;
    }
    $hours = floor($seconds / 3600);
    if ($days > 0 || $hours > 0) {
        $seconds -= $hours * 3600;
    }
    $minutes = floor($seconds / 60);
    if ($days > 0 || $hours > 0 || $minutes > 0) {
        $seconds -= $minutes * 60;
    }

    return (string)$days . $lang['mysql_stats_days'] . (string)$hours . $lang['mysql_stats_hours'] . (string)$minutes . $lang['mysql_stats_minutes'] . (string)$seconds . $lang['mysql_stats_seconds'];
}

/**
 * @param int    $timestamp
 * @param string $format
 *
 * @return string
 */
function localisedDate($timestamp = -1, $format = '')
{
    global $datefmt, $month, $day_of_week;
    if ($format == '') {
        $format = $datefmt;
    }
    if ($timestamp == -1) {
        $timestamp = time();
    }
    $date = preg_replace('@%[aA]@', $day_of_week[ (int)strftime('%w', $timestamp) ], $format);
    $date = preg_replace('@%[bB]@', $month[ (int)strftime('%m', $timestamp) - 1 ], $date);

    return strftime($date, $timestamp);
} // end of the 'localisedDate()' function
////////////////////// END FUNCTION LIST /////////////////////////////////////
$HTMLOUT = '';
$HTMLOUT .= "<h2>{$lang['mysql_stats_status']}</h2>";
//$res = @mysql_query('SHOW STATUS') or sqlerr(__FILE__,__LINE__);
$res = @sql_query('SHOW GLOBAL STATUS') or sqlerr(__FILE__, __LINE__);
while ($row = mysqli_fetch_row($res)) {
    $serverStatus[ $row[0] ] = $row[1];
}
@((mysqli_free_result($res) || (is_object($res) && (get_class($res) == 'mysqli_result'))) ? true : false);
unset($res);
unset($row);
$res = @sql_query('SELECT UNIX_TIMESTAMP() - ' . $serverStatus['Uptime']);
$row = mysqli_fetch_row($res);
$HTMLOUT .= "<table class='torrenttable' border='1'>
      <tr>
        <td>{$lang['mysql_stats_server']}" . timespanFormat($serverStatus['Uptime']) . $lang['mysql_stats_started'] . localisedDate($row[0]) . '

        </td>
      </tr>
      </table><br>';
((mysqli_free_result($res) || (is_object($res) && (get_class($res) == 'mysqli_result'))) ? true : false);
unset($res);
unset($row);
//Get query statistics
$queryStats = [];
$tmp_array = $serverStatus;
foreach ($tmp_array as $name => $value) {
    if (substr($name, 0, 4) == 'Com_') {
        $queryStats[ str_replace('_', ' ', substr($name, 4)) ] = $value;
        unset($serverStatus[ $name ]);
    }
}
unset($tmp_array);
$TRAFFIC_STATS = '';
$TRAFFIC_STATS_HEAD = "<!-- Server Traffic -->
        <b>{$lang['mysql_stats_traffic_per_hour']}</b>{$lang['mysql_stats_tables']}";
$TRAFFIC_STATS .= "<table class='torrenttable' width='100%' border='0'>
            <tr>
                <td colspan='3' bgcolor='grey'>{$lang['mysql_stats_traffic_per_hour']}</td>
            </tr>
            <tr>
                <td>{$lang['mysql_stats_received']}</td>
                <td >&#160;" . join(' ', byteformat($serverStatus['Bytes_received'])) . "&#160;</td>
                <td >&#160;" . join(' ', byteformat($serverStatus['Bytes_received'] * 3600 / $serverStatus['Uptime'])) . "&#160;</td>
            </tr>
            <tr>
                <td>{$lang['mysql_stats_sent']}</td>
                <td >&#160;" . join(' ', byteformat($serverStatus['Bytes_sent'])) . "&#160;</td>
                <td >&#160;" . join(' ', byteformat($serverStatus['Bytes_sent'] * 3600 / $serverStatus['Uptime'])) . "&#160;</td>
            </tr>
            <tr>
                <td bgcolor='grey'>&{$lang['mysql_stats_total']}</td>
                <td bgcolor='grey'>&#160;" . join(' ', byteformat($serverStatus['Bytes_received'] + $serverStatus['Bytes_sent'])) . "&#160;</td>
                <td bgcolor='grey'>&#160;" . join(' ', byteformat(($serverStatus['Bytes_received'] + $serverStatus['Bytes_sent']) * 3600 / $serverStatus['Uptime'])) . '&#160;</td>
            </tr>
        </table>';
$TRAFFIC_STATS2 = "<table class='torrenttable' width='100%' border='0'>
        <tr>
            <td colspan='4' bgcolor='grey'>{$lang['mysql_stats_connection_per_hour']}</td>
        </tr>
        <tr>
            <td>{$lang['mysql_stats_failed']}</td>
            <td >&#160;" . number_format($serverStatus['Aborted_connects'], 0, '.', ',') . "&#160;</td>
            <td >&#160;" . number_format(($serverStatus['Aborted_connects'] * 3600 / $serverStatus['Uptime']), 2, '.', ',') . "&#160;</td>
            <td >&#160;" . (($serverStatus['Connections'] > 0) ? number_format(($serverStatus['Aborted_connects'] * 100 / $serverStatus['Connections']), 2, '.', ',') . '&#160;%' : '---' . '&#160;') . "</td>
        </tr>
        <tr>
            <td>{$lang['mysql_stats_aborted']}</td>
            <td >&#160;" . number_format($serverStatus['Aborted_clients'], 0, '.', ',') . "&#160;</td>
            <td >&#160;" . number_format(($serverStatus['Aborted_clients'] * 3600 / $serverStatus['Uptime']), 2, '.', ',') . "&#160;</td>
            <td >&#160;" . (($serverStatus['Connections'] > 0) ? number_format(($serverStatus['Aborted_clients'] * 100 / $serverStatus['Connections']), 2, '.', ',') . '&#160;%' : '---') . "&#160;</td>
        </tr>
        <tr>
            <td bgcolor='grey'>{$lang['mysql_stats_total']}</td>
            <td bgcolor='grey'>&#160;" . number_format($serverStatus['Connections'], 0, '.', ',') . "&#160;</td>
            <td bgcolor='grey'>&#160;" . number_format(($serverStatus['Connections'] * 3600 / $serverStatus['Uptime']), 2, '.', ',') . "&#160;</td>
            <td bgcolor='grey'>&#160;" . number_format(100, 2, '.', ',') . '&#160;%&#160;</td>
        </tr>
    </table>';
$QUERY_STATS = '';
$QUERY_STATS .= "<!-- Queries -->
    <b>{$lang['mysql_stats_query']}</b>{$lang['mysql_stats_since']}" . number_format($serverStatus['Questions'], 0, '.', ',') . "{$lang['mysql_stats_querys']}<br>

    <table class='torrenttable' width='100%' border='0'>
        <tr>
            <td bgcolor='grey'>{$lang['mysql_stats_total']}</td>
            <td bgcolor='grey'>{$lang['mysql_stats_per_hour']}</td>
            <td bgcolor='grey'>{$lang['mysql_stats_per_minute']}</td>
            <td bgcolor='grey'>{$lang['mysql_stats_per_seconds']}</td>
        </tr>
        <tr>
            <td >&#160;" . number_format($serverStatus['Questions'], 0, '.', ',') . "&#160;</td>
            <td >&#160;" . number_format(($serverStatus['Questions'] * 3600 / $serverStatus['Uptime']), 2, '.', ',') . "&#160;</td>
            <td >&#160;" . number_format(($serverStatus['Questions'] * 60 / $serverStatus['Uptime']), 2, '.', ',') . "&#160;</td>
            <td >&#160;" . number_format(($serverStatus['Questions'] / $serverStatus['Uptime']), 2, '.', ',') . '&#160;</td>
        </tr>
    </table><br>';
$QUERY_STATS .= "<table class='torrenttable' width='100%' border='0'>
        <tr>
            <td colspan='2' bgcolor='grey'>{$lang['mysql_stats_query_type']}</td>
            <td bgcolor='grey'>{$lang['mysql_stats_per_hour']};</td>
            <td bgcolor='grey'>&#160;%&#160;</td>
        </tr>";
$useBgcolorOne = true;
$countRows = 0;
foreach ($queryStats as $name => $value) {
    // For the percentage column, use Questions - Connections, because
    // the number of connections is not an item of the Query types
    // but is included in Questions. Then the total of the percentages is 100.
    $QUERY_STATS .= '<tr>
          <td>&#160;' . htmlsafechars($name) . "&#160;</td>
          <td >&#160;" . number_format($value, 0, '.', ',') . "&#160;</td>
          <td >&#160;" . number_format(($value * 3600 / $serverStatus['Uptime']), 2, '.', ',') . "&#160;</td>
          <td >&#160;" . number_format(($value * 100 / ($serverStatus['Questions'] - $serverStatus['Connections'])), 2, '.', ',') . '&#160;%&#160;</td>
      </tr>';
}
unset($countRows);
unset($useBgcolorOne);
$QUERY_STATS .= '</table>';
//Unset used variables
unset($serverStatus['Aborted_clients']);
unset($serverStatus['Aborted_connects']);
unset($serverStatus['Bytes_received']);
unset($serverStatus['Bytes_sent']);
unset($serverStatus['Connections']);
unset($serverStatus['Questions']);
unset($serverStatus['Uptime']);
$STATUS_TABLE = '';
if (!empty($serverStatus)) {
    $STATUS_TABLE .= "<!-- Other status variables -->
          <b>{$lang['mysql_stats_more']}</b><br>
          
      <table class='torrenttable' border='0' width='100%'>
          <tr>
              <td bgcolor='grey'>{$lang['mysql_stats_variable']}</td>
              <td bgcolor='grey'>{$lang['mysql_stats_value']}</td>
          </tr>";
    $useBgcolorOne = true;
    $countRows = 0;
    foreach ($serverStatus as $name => $value) {
        $STATUS_TABLE .= '<tr>
            <td>&#160;' . htmlsafechars(str_replace('_', ' ', $name)) . "&#160;</td>
            <td >&#160;" . htmlsafechars($value) . '&#160;</td>
        </tr>';
    }
    unset($useBgcolorOne);
    $STATUS_TABLE .= '</table>';
}
$HTMLOUT .= "<table class='torrenttable' width='80%' cellpadding='4px'>
    <tr>
      <td colspan='2' class='colhead'>$TRAFFIC_STATS_HEAD</td>
    </tr>
    <tr>
      <td>$TRAFFIC_STATS</td><td>$TRAFFIC_STATS2</td>
    </tr>
    <tr>
      <td width='50%'>$QUERY_STATS</td><td >$STATUS_TABLE</td>
    </tr>
    </table>";
echo stdhead($lang['mysql_stats_stdhead']) . $HTMLOUT . stdfoot();
