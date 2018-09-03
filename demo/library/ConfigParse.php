<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/4/8
 * Time: 下午3:36
 */

namespace demo\library;


use yii\db\Exception;

class ConfigParse
{
    public static function parseSql($sid, $column = '',$autoReplace=true)
    {
        $pos = strrpos($sid, '.');
        if (false === $pos) {
            throw new Exception('no such sql id');
        }
        $filePath = SQL_MAP.str_replace('.', '/', substr($sid, 0, $pos));

        $sqlMap = require $filePath.'.php';

        $key = substr($sid, $pos+1);
        $sql = $sqlMap[$key];

        return $column && $autoReplace ? str_replace('#COLUMN#', $column, $sql) : $sql;
    }

    public static function parseCache($configKey, $key)
    {
        $pos = strrpos($configKey, '.');
        if (false === $pos) {
            throw new Exception('no such cache id');
        }

        $filePath = CACHE.str_replace('.', '/', substr($configKey, 0, $pos));

        $cacheConf = require $filePath.'.php';

        $processKey = $cacheConf[substr($configKey, $pos+1)];

        $processKey['key'] = sprintf($processKey['key'], $key);

        return $processKey;
    }

    public static function parseQueue($configKey)
    {
        $pos = strrpos($configKey, '.');
        if (false === $pos) {
            throw new Exception('no such queue id');
        }

        $filePath = QUEUE.str_replace('.', '/', substr($configKey, 0, $pos));

        $queueConf = require $filePath.'.php';

        return $queueConf[substr($configKey, $pos+1)];
    }
}