<?php

define('CONFIG_PATH', __DIR__);
define('WEB_PATH', dirname(__DIR__));

defined('SQL_MAP') or define('SQL_MAP', CONFIG_PATH.'/sqlmap/');
defined('SHARE') or define('SHARE', CONFIG_PATH.'/share/');
defined('CONSTANT') or define('CONSTANT', CONFIG_PATH.'/constant/');
defined('CACHE') or define('CACHE', CONFIG_PATH.'/cache/');
defined('QUEUE') or define('QUEUE', CONFIG_PATH.'/queue/');

