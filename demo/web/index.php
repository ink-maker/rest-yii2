<?php
//echo phpinfo();
$debug = get_cfg_var('demo.DEBUG');
$env = get_cfg_var('demo.RUN_MODE');
//var_dump($env);exit;
defined('YII_DEBUG') or define('YII_DEBUG', $debug);
defined('YII_ENV') or define('YII_ENV', $env);

//YII_ENABLE_ERROR_HANDLER和YII_ENABLE_EXCEPTION_HANDLER为false

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../config/'.$env.'/main.php'
);

(new yii\web\Application($config))->run();
