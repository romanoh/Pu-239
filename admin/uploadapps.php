<?php
require_once INCL_DIR . 'user_functions.php';
require_once INCL_DIR . 'pager_functions.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $CURUSER, $site_config, $cache, $lang;

$lang = array_merge($lang, load_language('uploadapps'));
$possible_actions = [
    'show',
    'viewapp',
    'acceptapp',
    'rejectapp',
    'takeappdelete',
    'app',
];
$action = (isset($_GET['action']) ? htmlsafechars($_GET['action']) : '');
if (!in_array($action, $possible_actions)) {
    stderr($lang['uploadapps_error'], $lang['uploadapps_ruffian']);
}
$HTMLOUT = $where = $where1 = '';
//== View applications
if ($action == 'app' || $action == 'show') {
    if ($action == 'show') {
        $hide = "[<a href='{$site_config['baseurl']}/staffpanel.php?tool=uploadapps&amp;action=app'>{$lang['uploadapps_hide']}</a>]";
        $where = "WHERE status = 'accepted' OR status = 'rejected'";
        $where1 = "WHERE uploadapp.status = 'accepted' OR uploadapp.status = 'rejected'";
    } else {
        $hide = "[<a href='{$site_config['baseurl']}/staffpanel.php?tool=uploadapps&amp;action=show'>{$lang['uploadapps_show']}</a>]";
        $where = "WHERE status = 'pending'";
        $where1 = "WHERE uploadapp.status = 'pending'";
    }
    $res = sql_query("SELECT COUNT(id) FROM uploadapp $where") or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_row($res);
    $count = $row[0];
    $perpage = 15;
    $pager = pager($perpage, $count, '.staffpanel.php?tool=uploadapps&amp;.');
    $HTMLOUT .= "<h1>{$lang['uploadapps_applications']}</h1>";
    if ($count == 0) {
        $HTMLOUT .= "<table class='main' width='850' border='0' cellspacing='0' cellpadding='0'><tr><td class='embedded'>
        <div><font class='small'>{$hide}</font></div></td></tr></table>
        <table width='100%' border='1' cellspacing='0' cellpadding='5'><tr><td>
        <div>{$lang['uploadapps_noapps']}</div>
        </td></tr></table>";
    } else {
        $HTMLOUT .= "<form method='post' action='staffpanel.php?tool=uploadapps&amp;action=takeappdelete'>";
        if ($count > $perpage) {
            $HTMLOUT .= $pager['pagertop'];
        }
        $HTMLOUT .= "<table class='main' width='850' border='0' cellspacing='0' cellpadding='0'><tr><td class='embedded'>
        <div><font class='small'>{$hide}</font></div>
        <table width='100%' border='1' cellspacing='0' cellpadding='5'>
        <tr>
        <td class='colhead'>{$lang['uploadapps_applied']}</td>
        <td class='colhead'>{$lang['uploadapps_application']}</td>
        <td class='colhead'>{$lang['uploadapps_username']}</td>
        <td class='colhead'>{$lang['uploadapps_joined']}</td>
        <td class='colhead'>{$lang['uploadapps_class']}</td>
        <td class='colhead'>{$lang['uploadapps_upped']}</td>
        <td class='colhead'>{$lang['uploadapps_ratio']}</td>
        <td class='colhead'>{$lang['uploadapps_status']}</td>
        <td class='colhead'>{$lang['uploadapps_delete']}</td>
        </tr>\n";
        $res = sql_query("SELECT uploadapp.*, users.id AS uid, users.username, users.class, users.added, users.uploaded, users.downloaded FROM uploadapp INNER JOIN users on uploadapp.userid = users.id $where1 " . $pager['limit']) or sqlerr(__FILE__, __LINE__);
        while ($arr = mysqli_fetch_assoc($res)) {
            if ($arr['status'] == 'accepted') {
                $status = "<font color='green'>{$lang['uploadapps_accepted']}</font>";
            } elseif ($arr['status'] == 'rejected') {
                $status = "<font color='red'>{$lang['uploadapps_rejected']}</font>";
            } else {
                $status = "<font color='blue'>{$lang['uploadapps_pending']}</font>";
            }
            $membertime = get_date($arr['added'], '', 0, 1);
            $elapsed = get_date($arr['applied'], '', 0, 1);
            $HTMLOUT .= "<tr>
            <td>{$elapsed}</td>
            <td><a href='staffpanel.php?tool=uploadapps&amp;action=viewapp&amp;id=" . (int)$arr['id'] . "'>{$lang['uploadapps_viewapp']}</a></td>
            <td><a href='{$site_config['baseurl']}/userdetails.php?id=" . (int)$arr['uid'] . "'>" . htmlsafechars($arr['username']) . "</a></td>
            <td>{$membertime}</td>
            <td>" . get_user_class_name($arr['class']) . '</td>
            <td>' . mksize($arr['uploaded']) . '</td>
            <td>' . member_ratio($arr['uploaded'], $site_config['ratio_free'] ? '0' : $arr['downloaded']) . "</td>
            <td>{$status}</td>
            <td><input type=\"checkbox\" name=\"deleteapp[]\" value=\"" . (int)$arr['id'] . "\" /></td>
            </tr>\n";
        }
        $HTMLOUT .= "</table>
        <div><input type='submit' value='Delete' /></div>
        </td></tr></table></form>\n";
        if ($count > $perpage) {
            $HTMLOUT .= $pager['pagerbottom'];
        }
    }
}
//== View application
if ($action == 'viewapp') {
    $id = (int)$_GET['id'];
    $res = sql_query('SELECT uploadapp.*, users.id AS uid, users.username, users.class, users.added, users.uploaded, users.downloaded FROM uploadapp INNER JOIN users ON uploadapp.userid = users.id WHERE uploadapp.id=' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
    $arr = mysqli_fetch_assoc($res);
    $membertime = get_date($arr['added'], '', 0, 1);
    $elapsed = get_date($arr['applied'], '', 0, 1);
    $HTMLOUT .= "<h1>Uploader application</h1>
    <table width='750' border='1' cellspacing='0' cellpadding='5'>
    <tr>
    <td class='rowhead' width='25%'>{$lang['uploadapps_username1']} </td><td><a href='{$site_config['baseurl']}/userdetails.php?id=" . (int)$arr['uid'] . "'>" . htmlsafechars($arr['username']) . "</a></td>
    </tr>
    <tr>
    <td class='rowhead'>{$lang['uploadapps_joined']} </td><td>" . htmlsafechars($membertime) . "</td>
    </tr>
    <tr>
    <td class='rowhead'>{$lang['uploadapps_upped1']} </td><td>" . htmlsafechars(mksize($arr['uploaded'])) . '</td>
    </tr>
    ' . ($site_config['ratio_free'] ? '' : "<tr>
    <td class='rowhead'>{$lang['uploadapps_downed']} </td><td>" . htmlsafechars(mksize($arr['downloaded'])) . '</td>
    </tr>') . "
    <tr>
    <td class='rowhead'>{$lang['uploadapps_ratio1']} </td><td>" . member_ratio($arr['uploaded'], $site_config['ratio_free'] ? '0' : $arr['downloaded']) . "</td>
    </tr>
    <tr>
    <td class='rowhead'>{$lang['uploadapps_connectable']} </td><td>" . htmlsafechars($arr['connectable']) . "</td>
    </tr>
    <tr>
    <td class='rowhead'>{$lang['uploadapps_class1']} </td><td>" . get_user_class_name($arr['class']) . "</td>
    </tr>
    <tr>
    <td class='rowhead'>{$lang['uploadapps_applied1']} </td><td>" . htmlsafechars($elapsed) . "</td>
    </tr>
    <tr>
    <td class='rowhead'>{$lang['uploadapps_upspeed']} </td><td>" . htmlsafechars($arr['speed']) . "</td>
    </tr>
    <tr>
    <td class='rowhead'>{$lang['uploadapps_offer']} </td><td>" . htmlsafechars($arr['offer']) . "</td>
    </tr>
    <tr>
    <td class='rowhead'>{$lang['uploadapps_why']} </td><td>" . htmlsafechars($arr['reason']) . "</td>
    </tr>
    <tr>
    <td class='rowhead'>{$lang['uploadapps_uploader']} </td><td>" . htmlsafechars($arr['sites']) . '</td>
    </tr>';
    if ($arr['sitenames'] != '') {
        $HTMLOUT .= "<tr><td class='rowhead'>{$lang['uploadapps_sites']} </td><td>" . htmlsafechars($arr['sitenames']) . "</td></tr>
    <tr><td class='rowhead'>{$lang['uploadapps_axx']} </td><td>" . htmlsafechars($arr['scene']) . "</td></tr>
    <tr><td colspan='2'>{$lang['uploadapps_create']} <b>" . htmlsafechars($arr['creating']) . "</b><br>{$lang['uploadapps_seeding']} <b>" . htmlsafechars($arr['seeding']) . '</b></td></tr>';
    }
    if ($arr['status'] == 'pending') {
        $HTMLOUT .= "<tr><td colspan='2'><form method='post' action='staffpanel.php?tool=uploadapps&amp;action=acceptapp'><input name='id' type='hidden' value='" . (int)$arr['id'] . "' /><b>{$lang['uploadapps_note']}</b><br><input type='text' name='note' size='40' /> <input type='submit' value='{$lang['uploadapps_accept']}' style='height: 20px' /></form><br><form method='post' action='staffpanel.php?tool=uploadapps&amp;action=rejectapp'><input name='id' type='hidden' value='" . (int)$arr['id'] . "' /><b>{$lang['uploadapps_reason']}</b><br><input type='text' name='reason' size='40' /> <input type='submit' value='{$lang['uploadapps_reject']}' style='height: 20px' /></form></td></tr></table>";
    } else {
        $HTMLOUT .= "<tr><td colspan='2'>{$lang['uploadapps_application']} " . ($arr['status'] == 'accepted' ? 'accepted' : 'rejected') . ' by <b>' . htmlsafechars($arr['moderator']) . "</b><br>{$lang['uploadapps_comm']}" . htmlsafechars($arr['comment']) . "</td></tr></table>
    <div><a href='{$site_config['baseurl']}/staffpanel.php?tool=uploadapps&amp;action=app'>{$lang['uploadapps_return']}</a></div>";
    }
}
//== Accept application
if ($action == 'acceptapp') {
    $id = (int)$_POST['id'];
    if (!is_valid_id($id)) {
        stderr($lang['uploadapps_error'], $lang['uploadapps_noid']);
    }
    $res = sql_query("SELECT uploadapp.id, users.username, users.modcomment, users.id AS uid FROM uploadapp INNER JOIN users on uploadapp.userid = users.id WHERE uploadapp.id = $id") or sqlerr(__FILE__, __LINE__);
    $arr = mysqli_fetch_assoc($res);
    $note = htmlsafechars($_POST['note']);
    $subject = sqlesc($lang['uploadapps_subject']);
    $msg = sqlesc("{$lang['uploadapps_msg']}\n\n{$lang['uploadapps_msg_note']} $note");
    $msg1 = sqlesc("{$lang['uploadapps_msg_user']} [url={$site_config['baseurl']}/userdetails.php?id=" . (int)$arr['uid'] . "][b]{$arr['username']}[/b][/url] {$lang['uploadapps_msg_been']} {$CURUSER['username']}.");
    $modcomment = get_date(TIME_NOW, 'DATE', 1) . $lang['uploadapps_modcomment'] . $CURUSER['username'] . '.' . ($arr['modcomment'] != '' ? "\n" : '') . "{$arr['modcomment']}";
    $dt = TIME_NOW;
    sql_query("UPDATE uploadapp SET status = 'accepted', comment = " . sqlesc($note) . ', moderator = ' . sqlesc($CURUSER['username']) . ' WHERE id=' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
    sql_query('UPDATE users SET class = ' . UC_UPLOADER . ', modcomment = ' . sqlesc($modcomment) . ' WHERE id=' . sqlesc($arr['uid']) . ' AND class < ' . UC_STAFF) or sqlerr(__FILE__, __LINE__);
    $cache->update_row('user_stats_' . $arr['uid'], [
        'modcomment' => $modcomment,
    ], $site_config['expires']['user_stats']);
    $cache->update_row('user' . $arr['uid'], [
        'class' => 3,
    ], $site_config['expires']['user_cache']);
    sql_query('INSERT INTO messages(sender, receiver, added, msg, subject, poster) VALUES(0, ' . sqlesc($arr['uid']) . ", $dt, $msg, $subject, 0)") or sqlerr(__FILE__, __LINE__);
    $cache->increment('inbox_' . $arr['uid']);
    $subres = sql_query('SELECT id FROM users WHERE class >= ' . UC_STAFF) or sqlerr(__FILE__, __LINE__);
    while ($subarr = mysqli_fetch_assoc($subres)) {
        sql_query('INSERT INTO messages(sender, receiver, added, msg, subject, poster) VALUES(0, ' . sqlesc($subarr['id']) . ", $dt, $msg1, $subject, 0)") or sqlerr(__FILE__, __LINE__);
    }
    $cache->increment('inbox_' . $subarr['id']);
    $cache->delete('new_uploadapp_');
    stderr($lang['uploadapps_app_accepted'], "{$lang['uploadapps_app_msg']} {$lang['uploadapps_app_click']} <a href='{$site_config['baseurl']}/staffpanel.php?tool=uploadapps&amp;action=app'><b>{$lang['uploadapps_app_here']}</b></a> {$lang['uploadapps_app_return']}");
}
//== Reject application
if ($action == 'rejectapp') {
    $id = (int)$_POST['id'];
    if (!is_valid_id($id)) {
        stderr($lang['uploadapps_error'], $lang['uploadapps_no_up']);
    }
    $res = sql_query('SELECT uploadapp.id, users.id AS uid FROM uploadapp INNER JOIN users ON uploadapp.userid = users.id WHERE uploadapp.id=' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
    $arr = mysqli_fetch_assoc($res);
    $reason = htmlsafechars($_POST['reason']);
    $subject = sqlesc($lang['uploadapps_subject']);
    $msg = sqlesc("{$lang['uploadapps_rej_no']}\n\n{$lang['uploadapps_rej_reason']} $reason");
    $dt = TIME_NOW;
    sql_query("UPDATE uploadapp SET status = 'rejected', comment = " . sqlesc($reason) . ', moderator = ' . sqlesc($CURUSER['username']) . ' WHERE id=' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
    sql_query("INSERT INTO messages(sender, receiver, added, msg, subject, poster) VALUES(0, {$arr['uid']}, $dt, $msg, $subject, 0)") or sqlerr(__FILE__, __LINE__);
    $cache->delete('new_uploadapp_');
    stderr($lang['uploadapps_app_rej'], "{$lang['uploadapps_app_rejbeen']} {$lang['uploadapps_app_click']} <a href='{$site_config['baseurl']}/staffpanel.php?tool=uploadapps&amp;action=app'><b>{$lang['uploadapps_app_here']}</b></a>{$lang['uploadapps_app_return']}");
}
//== Delete applications
if ($action == 'takeappdelete') {
    if (empty($_POST['deleteapp'])) {
        stderr($lang['uploadapps_silly'], $lang['uploadapps_twix']);
    } else {
        sql_query('DELETE FROM uploadapp WHERE id IN (' . join(',', $_POST['deleteapp']) . ') ') or sqlerr(__FILE__, __LINE__);
        $cache->delete('new_uploadapp_');
        stderr($lang['uploadapps_deleted'], "{$lang['uploadapps_deletedsuc']} {$lang['uploadapps_app_click']} <a href='{$site_config['baseurl']}/staffpanel.php?tool=uploadapps&amp;action=app'><b>{$lang['uploadapps_app_here']}</b></a>{$lang['uploadapps_app_return']}");
    }
}
echo stdhead($lang['uploadapps_stdhead']) . $HTMLOUT . stdfoot();
