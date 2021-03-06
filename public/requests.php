<?php
require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php';
require_once INCL_DIR . 'user_functions.php';
require_once INCL_DIR . 'html_functions.php';
check_user_status();
global $CURUSER, $site_config;

$lang = load_language('global');
$stdhead = [
    'css' => [
        get_file('upload_css'),
    ],
];
$stdfoot = [
    'js' => [
        get_file('requests_js'),
    ],
];
$HTMLOUT = $count2 = '';
if ($CURUSER['class'] < UC_POWER_USER) {
    stderr('Error!', 'Sorry, power user and up only!');
}
//=== possible stuff to be $_GETting lol
$id = (isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0));
$comment_id = (isset($_GET['comment_id']) ? intval($_GET['comment_id']) : (isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0));
$category = (isset($_GET['category']) ? intval($_GET['category']) : (isset($_POST['category']) ? intval($_POST['category']) : 0));
$requested_by_id = isset($_GET['requested_by_id']) ? intval($_GET['requested_by_id']) : 0;
$vote = isset($_POST['vote']) ? intval($_POST['vote']) : 0;
$posted_action = strip_tags((isset($_GET['action']) ? htmlsafechars($_GET['action']) : (isset($_POST['action']) ? htmlsafechars($_POST['action']) : '')));
//===========================================================================================//
//==================================    let them vote on it!    ==========================================//
//===========================================================================================//
//=== add all possible actions here and check them to be sure they are ok
$valid_actions = [
    'add_new_request',
    'delete_request',
    'edit_request',
    'request_details',
    'vote',
    'add_comment',
    'edit_comment',
    'delete_comment',
];
//=== check posted action, and if no action was posted, show the default page
$action = (in_array($posted_action, $valid_actions) ? $posted_action : 'default');
//=== top menu :D
$top_menu = '<p><a class="altlink" href="requests.php">view requests</a> || <a class="altlink" href="requests.php?action=add_new_request">new request</a></p>';
switch ($action) {
    case 'vote':
        //=== kill if nasty
        if (!isset($id) || !is_valid_id($id) || !isset($vote) || !is_valid_id($vote)) {
            stderr('USER ERROR', 'Bad id / bad vote');
        }
        //=== see if they voted yet
        $res_did_they_vote = sql_query('SELECT vote FROM request_votes WHERE user_id = ' . sqlesc($CURUSER['id']) . ' AND request_id = ' . sqlesc($id));
        $row_did_they_vote = mysqli_fetch_row($res_did_they_vote);
        if ($row_did_they_vote[0] == '') {
            $yes_or_no = ($vote == 1 ? 'yes' : 'no');
            sql_query('INSERT INTO request_votes (request_id, user_id, vote) VALUES (' . sqlesc($id) . ', ' . sqlesc($CURUSER['id']) . ', ' . sqlesc($yes_or_no) . ')');
            sql_query('UPDATE requests SET ' . ($yes_or_no == 'yes' ? 'vote_yes_count = vote_yes_count + 1' : 'vote_no_count = vote_no_count + 1') . ' WHERE id = ' . sqlesc($id));
            header('Location: /requests.php?action=request_details&voted=1&id=' . sqlesc($id));
            die();
        } else {
            stderr('USER ERROR', 'You have voted on this request before.');
        }
        break;
    //===========================================================================================//
    //=======================    the default page listing all the requests w/ pager         ===============================//
    //===========================================================================================//

    case 'default':
        require_once INCL_DIR . 'bbcode_functions.php';
        require_once INCL_DIR . 'pager_new.php';
        //=== get stuff for the pager
        $count_query = sql_query('SELECT COUNT(id) FROM requests');
        $count_arr = mysqli_fetch_row($count_query);
        $count = $count_arr[0];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
        $perpage = isset($_GET['perpage']) ? (int)$_GET['perpage'] : 20;
        list($menu, $LIMIT) = pager_new($count, $perpage, $page, 'requests.php?' . ($perpage == 20 ? '' : '&amp;perpage=' . $perpage));
        $main_query_res = sql_query('SELECT r.id AS request_id, r.request_name, r.category, r.added, r.requested_by_user_id, r.filled_by_user_id, r.filled_torrent_id, r.vote_yes_count, r.vote_no_count, r.comments, u.id, u.username, u.warned, u.suspended, u.enabled, u.donor, u.class, u.leechwarn, u.chatpost, u.pirate, u.king,
c.id AS cat_id, c.name AS cat_name, c.image AS cat_image FROM requests AS r LEFT JOIN categories AS c ON r.category = c.id LEFT JOIN users AS u ON r.requested_by_user_id = u.id ORDER BY r.added DESC ' . $LIMIT);
        if ($count = 0) {
            stderr('Error!', 'Sorry, there are no current requests!');
        }
        $HTMLOUT .= (isset($_GET['new']) ? '<h1>Request Added!</h1>' : '') . (isset($_GET['request_deleted']) ? '<h1>Request Deleted!</h1>' : '') . $top_menu . '' . $menu . '<br>';
        $HTMLOUT .= '<table class="table table-bordered table-striped">
    <tr>
        <td>Type</td>
        <td>Name</td>
        <td>Added</td>
        <td>Comm</td>
        <td>Votes</td>
        <td>Requested By</td>
        <td>Filled</td>
    </tr>';
        while ($main_query_arr = mysqli_fetch_assoc($main_query_res)) {
            //=======change colors
            $HTMLOUT .= '
    <tr>
        <td><img src="' . $site_config['pic_base_url'] . 'caticons/' . get_categorie_icons() . '/' . htmlsafechars($main_query_arr['cat_image'], ENT_QUOTES) . '" alt="' . htmlsafechars($main_query_arr['cat_name'], ENT_QUOTES) . '" /></td>
        <td><a class="altlink" href="requests.php?action=request_details&amp;id=' . (int)$main_query_arr['request_id'] . '">' . htmlsafechars($main_query_arr['request_name'], ENT_QUOTES) . '</a></td>
        <td>' . get_date($main_query_arr['added'], 'LONG') . '</td>
        <td>' . number_format($main_query_arr['comments']) . '</td>
        <td>yes: ' . number_format($main_query_arr['vote_yes_count']) . '<br>
        no: ' . number_format($main_query_arr['vote_no_count']) . '</td>
        <td>' . format_username($main_query_arr) . '</td>
        <td>' . ($main_query_arr['filled_by_user_id'] > 0 ? '<a href="details.php?id=' . (int)$main_query_arr['filled_torrent_id'] . '" title="go to torrent page!!!"><span>yes!</span></a>' : '<span>no</span>') . '</td>
    </tr>';
        }
        $HTMLOUT .= '</table>';
        $HTMLOUT .= '' . $menu . '<br>';
        echo stdhead('Requests', true, $stdhead) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        break;
    //===========================================================================================//
    //==============================the details page for the request! ========================================//
    //===========================================================================================//

    case 'request_details':
        require_once INCL_DIR . 'bbcode_functions.php';
        require_once INCL_DIR . 'pager_new.php';
        //=== kill if nasty
        if (!isset($id) || !is_valid_id($id)) {
            stderr('USER ERROR', 'Bad id');
        }
        $res = sql_query('SELECT r.id AS request_id, r.request_name, r.category, r.added, r.requested_by_user_id, r.filled_by_user_id, r.filled_torrent_id, r.vote_yes_count,
                            r.vote_no_count, r.image, r.link, r.description, r.comments,
                            u.id, u.username, u.warned, u.suspended, u.enabled, u.donor, u.class, u.uploaded, u.downloaded, u.leechwarn, u.chatpost, u.pirate, u.king,
                            c.name AS cat_name, c.image AS cat_image
                            FROM requests AS r
                            LEFT JOIN categories AS c ON r.category = c.id
                            LEFT JOIN users AS u ON r.requested_by_user_id = u.id
                            WHERE r.id = ' . sqlesc($id));
        $arr = mysqli_fetch_assoc($res);
        //=== see if they voted yet
        $res_did_they_vote = sql_query('SELECT vote FROM request_votes WHERE user_id = ' . sqlesc($CURUSER['id']) . ' AND request_id = ' . sqlesc($id));
        $row_did_they_vote = mysqli_fetch_row($res_did_they_vote);
        if ($row_did_they_vote[0] == '') {
            $vote_yes = '<form method="post" action="requests.php">
                    <input type="hidden" name="action" value="vote" />
                    <input type="hidden" name="id" value="' . $id . '" />
                    <input type="hidden" name="vote" value="1" />
                    <input type="submit" class="button is-small" value="vote yes!" />
                    </form> ~ you will be notified when this request is filled.';
            $vote_no = '<form method="post" action="requests.php">
                    <input type="hidden" name="action" value="vote" />
                    <input type="hidden" name="id" value="' . $id . '" />
                    <input type="hidden" name="vote" value="2" />
                    <input type="submit" class="button is-small" value="vote no!" />
                    </form> ~ you are being a stick in the mud.';
            $your_vote_was = '';
        } else {
            $vote_yes = '';
            $vote_no = '';
            $your_vote_was = ' your vote: ' . $row_did_they_vote[0] . ' ';
        }
        //=== start page
        $HTMLOUT .= (isset($_GET['voted']) ? '<h1>vote added</h1>' : '') . (isset($_GET['comment_deleted']) ? '<h1>comment deleted</h1>' : '') . $top_menu . '
  <table class="table table-bordered table-striped">
  <tr>
  <td colspan="2"><h1>' . htmlsafechars($arr['request_name'], ENT_QUOTES) . ($CURUSER['class'] < UC_STAFF ? '' : ' [ <a href="requests.php?action=edit_request&amp;id=' . $id . '">edit</a> ]
  [ <a href="requests.php?action=delete_request&amp;id=' . $id . '">delete</a> ]') . '</h1></td>
  </tr>
  <tr>
  <td>image:</td>
  <td><img src="' . strip_tags($arr['image']) . '" alt="image" /></td>
  </tr>
  <tr>
  <td>description:</td>
  <td>' . format_comment($arr['description']) . '</td>
  </tr>
  <tr>
  <td>category:</td>
  <td><img src="' . $site_config['pic_base_url'] . 'caticons/' . get_categorie_icons() . '/' . htmlsafechars($arr['cat_image'], ENT_QUOTES) . '" alt="' . htmlsafechars($arr['cat_name'], ENT_QUOTES) . '" /></td>
  </tr>
  <tr>
  <td>link:</td>
  <td><a class="altlink" href="' . htmlsafechars($arr['link'], ENT_QUOTES) . '"  target="_blank">' . htmlsafechars($arr['link'], ENT_QUOTES) . '</a></td>
  </tr>
  <tr>
  <td>votes:</td>
  <td>
  <span>yes: ' . number_format($arr['vote_yes_count']) . '</span> ' . $vote_yes . '<br>
  <span>no: ' . number_format($arr['vote_no_count']) . '</span> ' . $vote_no . '<br> ' . $your_vote_was . '</td>
  </tr>
  <tr>
  <td>requested by:</td>
  <td>' . format_username($arr) . ' [ ' . get_user_class_name($arr['class']) . ' ]
  ratio: ' . member_ratio($arr['uploaded'], $site_config['ratio_free'] ? '0' : $arr['downloaded']) . get_user_ratio_image($arr['uploaded'], ($site_config['ratio_free'] ? '1' : $arr['downloaded'])) . '</td>
  </tr>' . ($arr['filled_torrent_id'] > 0 ? '<tr>
  <td>filled:</td>
  <td><a class="altlink" href="details.php?id=' . $arr['filled_torrent_id'] . '">yes, click to view torrent!</a></td>
  </tr>' : '') . '
  <tr>
  <td>Report Request</td>
  <td><form action="report.php?type=Request&amp;id=' . $id . '" method="post">
  <input type="submit" class="button_med" value="Report This Request" />
  For breaking the <a class="altlink" href="rules.php">rules</a></form></td>
  </tr>
  </table>';
        $HTMLOUT .= '<h1>Comments for ' . htmlsafechars($arr['request_name'], ENT_QUOTES) . '</h1><p><a name="startcomments"></a></p>';
        $commentbar = '<p><a class="index" href="requests.php?action=add_comment&amp;id=' . $id . '">Add a comment</a></p>';
        $count = (int)$arr['comments'];
        if (!$count) {
            $HTMLOUT .= '<h2>No comments yet</h2>';
        } else {
            //=== get stuff for the pager
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
            $perpage = isset($_GET['perpage']) ? (int)$_GET['perpage'] : 20;
            list($menu, $LIMIT) = pager_new($count, $perpage, $page, 'requests.php?action=request_details&amp;id=' . $id, ($perpage == 20 ? '' : '&amp;perpage=' . $perpage) . '#comments');
            $subres = sql_query('SELECT c.request, c.id AS comment_id, c.text, c.added, c.editedby, c.editedat, u.id, u.username, u.warned, u.suspended, u.enabled, u.donor, u.class, u.avatar, u.offensive_avatar, u.leechwarn, u.chatpost, u.pirate, u.king, u.title FROM comments AS c LEFT JOIN users AS u ON c.user = u.id WHERE c.request = ' . sqlesc($id) . ' ORDER BY c.id ' . $LIMIT) or sqlerr(__FILE__, __LINE__);
            $allrows = [];
            while ($subrow = mysqli_fetch_assoc($subres)) {
                $allrows[] = $subrow;
            }
            $HTMLOUT .= $commentbar . '<a name="comments"></a>';
            $HTMLOUT .= ($count > $perpage) ? '' . $menu . '<br>' : '<br>';
            $HTMLOUT .= comment_table($allrows);
            $HTMLOUT .= ($count > $perpage) ? '' . $menu . '<br>' : '<br>';
        }
        $HTMLOUT .= $commentbar;
        echo stdhead('Request details for: ' . htmlsafechars($arr['request_name'], ENT_QUOTES), true, $stdhead) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        break;
    //===========================================================================================//
    //====================================    add new request      ========================================//
    //===========================================================================================//

    case 'add_new_request':
        require_once INCL_DIR . 'bbcode_functions.php';
        $request_name = strip_tags(isset($_POST['request_name']) ? trim($_POST['request_name']) : '');
        $image = strip_tags(isset($_POST['image']) ? trim($_POST['image']) : '');
        $body = (isset($_POST['body']) ? trim($_POST['body']) : '');
        $link = strip_tags(isset($_POST['link']) ? trim($_POST['link']) : '');
        //=== do the cat list :D
        $category_drop_down = '<select name="category" class="required"><option class="body" value="">Select Request Category</option>';
        $cats = genrelist();
        foreach ($cats as $row) {
            $category_drop_down .= '<option class="body" value="' . (int)$row['id'] . '"' . ($category == $row['id'] ? ' selected' : '') . '>' . htmlsafechars($row['name']) . '</option>';
        }
        $category_drop_down .= '</select>';
        if (isset($_POST['category'])) {
            $cat_res = sql_query('SELECT id AS cat_id, name AS cat_name, image AS cat_image FROM categories WHERE id = ' . $category);
            $cat_arr = mysqli_fetch_assoc($cat_res);
            $cat_image = htmlsafechars($cat_arr['cat_image'], ENT_QUOTES);
            $cat_name = htmlsafechars($cat_arr['cat_name'], ENT_QUOTES);
        }
        //=== start page
        $HTMLOUT .= '<table class="table table-bordered table-striped">
   <tr>
   <td class="embedded"><h1>New Request</h1>' . $top_menu . '
   <form method="post" action="requests.php?action=add_new_request" name="request_form" id="request_form">
    <table class="table table-bordered table-striped">
    <tbody>
    <tr>
    <td colspan="2"><h1>Making a Request</h1></td>
    </tr>
    <tr>
    <td colspan="2">Before you make an request, <a class="altlink" href="search.php">Search</a>
    to be sure it has not yet been requested, offered, or uploaded!<br><br>Be sure to fill in all fields!</td>
    </tr>
    <tr>
    <td>name:</td>
    <td><input type="text" name="request_name" value="' . htmlsafechars($request_name, ENT_QUOTES) . '" class="required" /></td>
    </tr>
    <tr>
    <td>image:</td>
    <td><input type="text" name="image" value="' . htmlsafechars($image, ENT_QUOTES) . '" class="required" /></td>
    </tr>
    <tr>
    <td>link:</td>
    <td><input type="text" name="link" value="' . htmlsafechars($link, ENT_QUOTES) . '" class="required" /></td>
    </tr>
    <tr>
    <td>category:</td>
    <td>' . $category_drop_down . '</td>
    </tr>
    <tr>
    <td>description:</td>
    <td>' . BBcode($body) . '</td>
    </tr>
    <tr>
    <td colspan="2">
    <input type="submit" name="button" class="button is-small" value="Submit" /></td>
    </tr>
    </tbody>
    </table></form>
     </td></tr></table><br>';
        echo stdhead('Add new request.', true, $stdhead) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        break;
    //===========================================================================================//
    //====================================      delete  request      ========================================//
    //===========================================================================================//

    case 'delete_request':
        if (!isset($id) || !is_valid_id($id)) {
            stderr('Error', 'Bad ID.');
        }
        $res = sql_query('SELECT request_name, requested_by_user_id FROM requests WHERE id =' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
        $arr = mysqli_fetch_assoc($res);
        if (!$arr) {
            stderr('Error', 'Invalid ID.');
        }
        if ($arr['requested_by_user_id'] !== $CURUSER['id'] && $CURUSER['class'] < UC_STAFF) {
            stderr('Error', 'Permission denied.');
        }
        if (!isset($_GET['do_it'])) {
            stderr('Sanity check...', 'are you sure you would like to delete the request <b>"' . htmlsafechars($arr['request_name'], ENT_QUOTES) . '"</b>? If so click
        <a class="altlink" href="requests.php?action=delete_request&amp;id=' . $id . '&amp;do_it=666" >HERE</a>.');
        } else {
            sql_query('DELETE FROM requests WHERE id=' . sqlesc($id));
            sql_query('DELETE FROM request_votes WHERE request_id =' . sqlesc($id));
            sql_query('DELETE FROM comments WHERE request =' . sqlesc($id));
            header('Location: /requests.php?request_deleted=1');
            die();
        }
        echo stdhead('Delete Request.', true, $stdhead) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        break;
    //===========================================================================================//
    //====================================          edit request      ========================================//
    //===========================================================================================//

    case 'edit_request':
        require_once INCL_DIR . 'bbcode_functions.php';
        if (!isset($id) || !is_valid_id($id)) {
            stderr('Error', 'Bad ID.');
        }
        $edit_res = sql_query('SELECT request_name, image, description, category, requested_by_user_id, filled_by_user_id, filled_torrent_id, link FROM requests WHERE id =' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
        $edit_arr = mysqli_fetch_assoc($edit_res);
        if ($CURUSER['class'] < UC_STAFF && $CURUSER['id'] !== $edit_arr['requested_by_user_id']) {
            stderr('Error!', 'This is not your request to edit!');
        }
        $filled_by = '';
        if ($edit_arr['filled_by_user_id'] > 0) {
            $filled_by_res = sql_query('SELECT id, username, warned, suspended, enabled, leechwarn, chatpost, pirate, king, donor, class FROM users WHERE id =' . sqlesc($edit_arr['filled_by_user_id'])) or sqlerr(__FILE__, __LINE__);
            $filled_by_arr = mysqli_fetch_assoc($edit_res);
            $filled_by = 'this request was filled by ' . format_username($filled_by_arr);
        }
        $request_name = strip_tags(isset($_POST['request_name']) ? trim($_POST['request_name']) : $edit_arr['request_name']);
        $image = strip_tags(isset($_POST['image']) ? trim($_POST['image']) : $edit_arr['image']);
        $body = (isset($_POST['body']) ? trim($_POST['body']) : $edit_arr['description']);
        $link = strip_tags(isset($_POST['link']) ? trim($_POST['link']) : $edit_arr['link']);
        $category = (isset($_POST['category']) ? intval($_POST['category']) : $edit_arr['category']);
        //=== do the cat list :D
        $category_drop_down = '<select name="category" class="required"><option class="body" value="">Select Request Category</option>';
        $cats = genrelist();
        foreach ($cats as $row) {
            $category_drop_down .= '<option class="body" value="' . (int)$row['id'] . '"' . ($category == $row['id'] ? ' selected"' : '') . '>' . htmlsafechars($row['name'], ENT_QUOTES) . '</option>';
        }
        $category_drop_down .= '</select>';
        $cat_res = sql_query('SELECT id AS cat_id, name AS cat_name, image AS cat_image FROM categories WHERE id = ' . sqlesc($category));
        $cat_arr = mysqli_fetch_assoc($cat_res);
        $cat_image = htmlsafechars($cat_arr['cat_image'], ENT_QUOTES);
        $cat_name = htmlsafechars($cat_arr['cat_name'], ENT_QUOTES);
        //=== start page
        $HTMLOUT .= '<table class="table table-bordered table-striped">
   <tr>
   <td class="embedded">
   <h1>Edit Request</h1>' . $top_menu . '
   <form method="post" action="requests.php?action=edit_request" name="request_form" id="request_form">
   <input type="hidden" name="id" value="' . $id . '" />
   <table class="table table-bordered table-striped">
   <tr>
   <td colspan="2"><h1>Edit Request</h1></td>
   </tr>
   <tr>
   <td colspan="2">Be sure to fill in all fields!</td>
   </tr>
   <tr>
   <td>name:</td>
   <td><input type="text" name="request_name" value="' . htmlsafechars($request_name, ENT_QUOTES) . '" class="required" /></td>
   </tr>
   <tr>
   <td>image:</td>
   <td><input type="text" name="image" value="' . htmlsafechars($image, ENT_QUOTES) . '" class="required" /></td>
   </tr>
   <tr>
   <td>link:</td>
   <td><input type="text" name="link" value="' . htmlsafechars($link, ENT_QUOTES) . '" class="required" /></td>
   </tr>
   <tr>
   <td>category:</td>
   <td>' . $category_drop_down . '</td>
   </tr>
   <tr>
   <td>description:</td>
   <td>' . BBcode($body) . '</td>
   </tr>' . ($edit_arr['filled_by_user_id'] == 0 ? '' : '
   <tr>
   <td>filled:</td>
   <td>' . $filled_by . ' <input type="checkbox" name="filled_by" value="1"' . (isset($_POST['filled_by']) ? ' "checked"' : '') . ' /> check this box to re-set this request. [ removes filled by ]  </td>
   </tr>') . '
   <tr>
   <td colspan="2">
   <input type="submit" name="button" class="button is-small" value="Edit" /></td>
   </tr>
   </table></form>
    </td></tr></table><br>';
        echo stdhead('Edit request.', true, $stdhead) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        break;
    //===========================================================================================//
    //====================================    add comment          ========================================//
    //===========================================================================================//

    case 'add_comment':
        require_once INCL_DIR . 'bbcode_functions.php';
        require_once INCL_DIR . 'pager_new.php';
        //=== kill if nasty
        if (!isset($id) || !is_valid_id($id)) {
            stderr('USER ERROR', 'Bad id');
        }
        $res = sql_query('SELECT request_name FROM requests WHERE id = ' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
        $arr = mysqli_fetch_assoc($res);
        if (!$arr) {
            stderr('Error', 'No request with that ID.');
        }
        if (isset($_POST['button']) && $_POST['button'] == 'Save') {
            $body = trim($_POST['body']);
            if (!$body) {
                stderr('Error', 'Comment body cannot be empty!');
            }
            sql_query('INSERT INTO comments (user, request, added, text, ori_text) VALUES (' . sqlesc($CURUSER['id']) . ', ' . sqlesc($id) . ', ' . TIME_NOW . ', ' . sqlesc($body) . ',' . sqlesc($body) . ')');
            $newid = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS['___mysqli_ston']))) ? false : $___mysqli_res);
            sql_query('UPDATE requests SET comments = comments + 1 WHERE id = ' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
            header('Location: /requests.php?action=request_details&id=' . $id . '&viewcomm=' . $newid . '#comm' . $newid);
            die();
        }
        $body = htmlsafechars((isset($_POST['body']) ? $_POST['body'] : ''));
        $HTMLOUT .= $top_menu . '<form method="post" action="requests.php?action=add_comment">
    <input type="hidden" name="id" value="' . $id . '"/>';
        $res = sql_query('SELECT c.request, c.id AS comment_id, c.text, c.added, c.editedby, c.editedat,
                                u.id, u.username, u.warned, u.suspended, u.enabled, u.donor, u.class, u.avatar, u.offensive_avatar, u.title, u.leechwarn, u.chatpost, u.pirate,  u.king FROM comments AS c LEFT JOIN users AS u ON c.user = u.id WHERE request = ' . sqlesc($id) . ' ORDER BY c.id DESC LIMIT 5');
        $allrows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $allrows[] = $row;
        }
        if (count($allrows)) {
            $HTMLOUT .= '<h2>Most recent comments, in reverse order</h2>';
            $HTMLOUT .= comment_table($allrows);
        }
        echo stdhead('Add a comment to "' . $arr['request_name'] . '"', true, $stdhead) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        break;
    //===========================================================================================//
    //==================================    edit comment    =============================================//
    //===========================================================================================//

    case 'edit_comment':
        require_once INCL_DIR . 'bbcode_functions.php';
        if (!isset($comment_id) || !is_valid_id($comment_id)) {
            stderr('Error', 'Bad ID.');
        }
        $res = sql_query('SELECT c.*, r.request_name FROM comments AS c LEFT JOIN requests AS r ON c.request = r.id WHERE c.id=' . sqlesc($comment_id)) or sqlerr(__FILE__, __LINE__);
        $arr = mysqli_fetch_assoc($res);
        if (!$arr) {
            stderr('Error', 'Invalid ID.');
        }
        if ($arr['user'] != $CURUSER['id'] && $CURUSER['class'] < UC_STAFF) {
            stderr('Error', 'Permission denied.');
        }
        $body = htmlsafechars((isset($_POST['body']) ? $_POST['body'] : $arr['text']));
        if (isset($_POST['button']) && $_POST['button'] == 'Edit') {
            if ($body == '') {
                stderr('Error', 'Comment body cannot be empty!');
            }
            sql_query('UPDATE comments SET text=' . sqlesc($body) . ', editedat=' . TIME_NOW . ', editedby=' . sqlesc($CURUSER['id']) . ' WHERE id=' . sqlesc($comment_id)) or sqlerr(__FILE__, __LINE__);
            header('Location: /requests.php?action=request_details&id=' . $id . '&viewcomm=' . $comment_id . '#comm' . $comment_id);
            die();
        }
        if ($CURUSER['id'] == $arr['user']) {
            $avatar = avatar_stuff($CURUSER);
        } else {
            $res_user = sql_query('SELECT avatar, offensive_avatar, view_offensive_avatar FROM users WHERE id=' . $arr['user']) or sqlerr(__FILE__, __LINE__);
            $arr_user = mysqli_fetch_assoc($res_user);
            $avatar = avatar_stuff($arr_user);
        }
        $HTMLOUT .= $top_menu . '<form method="post" action="requests.php?action=edit_comment">
    <input type="hidden" name="id" value="' . $arr['request'] . '"/>
    <input type="hidden" name="comment_id" value="' . $comment_id . '"/>
     ' . (isset($_POST['button']) && $_POST['button'] == 'Preview' ? '<table class="table table-bordered table-striped">
    <tr>
    <td colspan="2"><h1>Preview</h1></td>
    </tr>
     <tr>
    <td>' . $avatar . '</td>
    <td>' . format_comment($body) . '</td>
    </tr></table><br>' : '') . '
    <table class="table table-bordered table-striped">
     <tr>
    <td colspan="2"><h1>Edit comment to "' . htmlsafechars($arr['request_name'], ENT_QUOTES) . '"</h1></td>
    </tr>
     <tr>
    <td><b>Comment:</b></td><td>' . BBcode($body) . '</td>
    </tr>
     <tr>
    <td colspan="2">
    <input name="button" type="submit" class="button is-small" value="Edit" /></td>
    </tr>
     </table></form>';
        echo stdhead('Edit comment to "' . $arr['request_name'] . '"', true, $stdhead) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        break;
    //===========================================================================================//
    //==================================    delete comment    =============================================//
    //===========================================================================================//

    case 'delete_comment':
        if (!isset($comment_id) || !is_valid_id($comment_id)) {
            stderr('Error', 'Bad ID.');
        }
        $res = sql_query('SELECT user, request FROM comments WHERE id=' . $comment_id) or sqlerr(__FILE__, __LINE__);
        $arr = mysqli_fetch_assoc($res);
        if (!$arr) {
            stderr('Error', 'Invalid ID.');
        }
        if ($arr['user'] != $CURUSER['id'] && $CURUSER['class'] < UC_STAFF) {
            stderr('Error', 'Permission denied.');
        }
        if (!isset($_GET['do_it'])) {
            stderr('Sanity check...', 'are you sure you would like to delete this comment? If so click <a class="altlink" href="requests.php?action=delete_comment&amp;id=' . (int)$arr['request'] . '&amp;comment_id=' . $comment_id . '&amp;do_it=666" >HERE</a>.');
        } else {
            sql_query('DELETE FROM comments WHERE id=' . sqlesc($comment_id));
            sql_query('UPDATE requests SET comments = comments - 1 WHERE id = ' . sqlesc($arr['request']));
            header('Location: /requests.php?action=request_details&id=' . $id . '&comment_deleted=1');
            die();
        }
        break;
} //=== end all actions / switch
//=== functions n' stuff \o/
/**
 * @param $rows
 *
 * @return string
 */
function comment_table($rows)
{
    $count2 = '';
    global $CURUSER, $site_config;
    $comment_table = '<table class="table table-bordered table-striped">
    <tr>
    <td class="three">';
    foreach ($rows as $row) {
        //=======change colors
        $text = format_comment($row['text']);
        if ($row['editedby']) {
            $res_user = sql_query('SELECT username FROM users WHERE id=' . sqlesc($row['editedby'])) or sqlerr(__FILE__, __LINE__);
            $arr_user = mysqli_fetch_assoc($res_user);
            $text .= '<p>Last edited by <a href="userdetails.php?id=' . (int)$row['editedby'] . '"><b>' . htmlsafechars($arr_user['username']) . '</b></a> at ' . get_date($row['editedat'], 'DATE') . '</p>';
        }
        $top_comment_stuff = $row['comment_id'] . ' by ' . (isset($row['username']) ? format_username($row) . ($row['title'] !== '' ? ' [ ' . htmlsafechars($row['title']) . ' ] ' : ' [ ' . get_user_class_name($row['class']) . ' ]  ') : ' M.I.A. ') . get_date($row['added'], '') . ($row['id'] == $CURUSER['id'] || $CURUSER['class'] >= UC_STAFF ? '
     - [<a href="requests.php?action=edit_comment&amp;id=' . (int)$row['request'] . '&amp;comment_id=' . (int)$row['comment_id'] . '">Edit</a>]' : '') . ($CURUSER['class'] >= UC_STAFF ? '
     - [<a href="requests.php?action=delete_comment&amp;id=' . (int)$row['request'] . '&amp;comment_id=' . (int)$row['comment_id'] . '">Delete</a>]' : '') . ($row['editedby'] && $CURUSER['class'] >= UC_STAFF ? '
     - [<a href="comment.php?action=vieworiginal&amp;cid=' . (int)$row['id'] . '">View original</a>]' : '') . '
    - [<a href="report.php?type=Request_Comment&amp;id_2=' . (int)$row['request'] . '&amp;id=' . (int)$row['comment_id'] . '">Report</a>]';
        $comment_table .= '
    <table class="table table-bordered table-striped">
    <tr>
    <td colspan="2"># ' . $top_comment_stuff . '</td>
    </tr>
    <tr>
    <td>' . avatar_stuff($row) . '</td>
    <td>' . $text . '</td>
    </tr>
    </table><br>';
    }
    $comment_table .= '</td></tr></table>';

    return $comment_table;
}
