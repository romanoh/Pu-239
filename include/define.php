<?php

define('TIME_NOW', time());

define('INCL_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('ROOT_DIR', realpath(INCL_DIR . '..' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
define('ADMIN_DIR', ROOT_DIR . 'admin' . DIRECTORY_SEPARATOR);
define('FORUM_DIR', ROOT_DIR . 'forums' . DIRECTORY_SEPARATOR);
define('PM_DIR', ROOT_DIR . 'pm_system' . DIRECTORY_SEPARATOR);
define('PIMP_DIR', ROOT_DIR . 'PimpMyLog' . DIRECTORY_SEPARATOR);
define('CACHE_DIR', ROOT_DIR . 'cache' . DIRECTORY_SEPARATOR);
define('MODS_DIR', ROOT_DIR . 'mods' . DIRECTORY_SEPARATOR);
define('LANG_DIR', ROOT_DIR . 'lang' . DIRECTORY_SEPARATOR);
define('TEMPLATE_DIR', ROOT_DIR . 'templates' . DIRECTORY_SEPARATOR);
define('BLOCK_DIR', ROOT_DIR . 'blocks' . DIRECTORY_SEPARATOR);
define('IMDB_DIR', ROOT_DIR . 'imdb' . DIRECTORY_SEPARATOR);
define('CLASS_DIR', INCL_DIR . 'class' . DIRECTORY_SEPARATOR);
define('CLEAN_DIR', INCL_DIR . 'cleanup' . DIRECTORY_SEPARATOR);
define('PUBLIC_DIR', ROOT_DIR . 'public' . DIRECTORY_SEPARATOR);
define('IMAGES_DIR', PUBLIC_DIR . 'images' . DIRECTORY_SEPARATOR);
define('VENDOR_DIR', ROOT_DIR . 'vendor' . DIRECTORY_SEPARATOR);
define('DATABASE_DIR', ROOT_DIR . 'database' . DIRECTORY_SEPARATOR);
define('BITBUCKET_DIR', ROOT_DIR . 'bucket' . DIRECTORY_SEPARATOR);
define('AVATAR_DIR', BITBUCKET_DIR . 'avatar' . DIRECTORY_SEPARATOR);
define('SQLERROR_LOGS_DIR', ROOT_DIR . 'sql_error' . DIRECTORY_SEPARATOR);

define('SQL_DEBUG', true);
define('SQL_LOGGING', false);
define('IP_LOGGING', true);
define('XBT_TRACKER', false);
define('REQUIRE_CONNECTABLE', false);
define('SOCKET', true);
