#!/usr/bin/env php
<?php
/**
 * Yii console bootstrap file.
 */
$debug = get_cfg_var('sld.DEBUG');
$env = get_cfg_var('sld.RUN_MODE');
defined('YII_DEBUG') or define('YII_DEBUG', $debug);
defined('YII_ENV') or define('YII_ENV', $env);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/demo/config/bootstrap.php';

$config = require __DIR__ . '/console/config/'.$env.'Console.php';

$application = new yii\console\Application($config);
$exitCode = $application->run();
exit($exitCode);
