<?php
require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php';
require_once INCL_DIR . 'user_functions.php';
check_user_status();
global $CURUSER, $site_config;

$res = sql_query('SELECT COUNT(*) FROM topics WHERE user_id = ' . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
$arr = mysqli_fetch_row($res);
$forumtopics = $arr['0'];
sql_query('UPDATE usersachiev SET forumtopics =' . sqlesc($forumtopics) . ' WHERE userid = ' . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
setSessionVar('is-success', "Your forum topics count has been updated! [{$forumtopics}]");
header("Location: {$site_config['baseurl']}/achievementhistory.php?id={$CURUSER['id']}");
