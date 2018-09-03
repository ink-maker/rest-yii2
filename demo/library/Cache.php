<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/4/25
 * Time: 上午11:41
 */

namespace demo\library;

use Predis\Client;

class Cache
{

    private static $client = null;

    public static function getInstance()
    {
        if (isset(self::$client))
            return self::$client;

        $redisConf = \Yii::$app->components['redis'];
        $redisServer = [
            'host' => $redisConf['hostname'],
            'port' => $redisConf['port'],
            'database' => $redisConf['database'],
        ];
        self::$client = new Client($redisServer);


    }
    public function __construct()
    {
        self::getInstance();
    }

    //获取指定 key 的值
    public function get($configKey, $key)
    {
        $cacheConf = ConfigParse::parseCache($configKey, $key);

        return self::$client->get($cacheConf['key']);
    }

    //设置指定 key 的值
    public function set($configKey, $key, $vaule = '')
    {
        $cacheConf = ConfigParse::parseCache($configKey, $key);

        if (!$vaule)
            return null;

        $vaule = is_array($vaule) ? json_encode($vaule) : $vaule;

        return self::$client->set($cacheConf['key'], $vaule, 'EX', $cacheConf['exp']);
    }

    //检查给定 key 是否存在
    public function exists($configKey, $key)
    {
        $cacheConf = ConfigParse::parseCache($configKey, $key);

        return self::$client->exists($cacheConf['key']);
    }

    //在 key 存在时删除 key
    public function del($configKey, $key)
    {
        $cacheConf = ConfigParse::parseCache($configKey, $key);

        return self::$client->del($cacheConf['key']);
    }

    //批量获取keys的值
    public function mget(array $keys)
    {
        if (!$keys)
            return null;

        $cacheConfArr = [];
        foreach ($keys as $v) {
            $cacheConfArr[] = ConfigParse::parseCache($v['configKey'], $v['key']);
        }

        $readKeys = array_keys($cacheConfArr, 'key');

        return self::$client->mget($readKeys);
    }

    //将 key 中储存的数字值增一
    public function incr($configKey, $key)
    {
        $cacheConf = ConfigParse::parseCache($configKey, $key);

        return self::$client->incr($cacheConf['key']);
    }

    //将 key 所储存的值加上给定的增量值（increment）
    public function incrBy($configKey, $key, $increment)
    {
        $cacheConf = ConfigParse::parseCache($configKey, $key);

        return self::$client->incrby($cacheConf['key'], $increment);
    }

    //将 key 所储存的值加上给定的浮点增量值（increment）
    public function incrByFloat($configKey, $key, $increment)
    {
        $cacheConf = ConfigParse::parseCache($configKey, $key);

        return self::$client->incrbyfloat($cacheConf['key'], $increment);
    }

    //将 key 中储存的数字值减一。
    public function decr($configKey, $key)
    {
        $cacheConf = ConfigParse::parseCache($configKey, $key);

        return self::$client->decr($cacheConf['key']);
    }

    //key 所储存的值减去给定的减量值（decrement）
    public function decrBy($configKey, $key, $increment)
    {
        $cacheConf = ConfigParse::parseCache($configKey, $key);

        return self::$client->decrby($cacheConf['key'], $increment);
    }

    //如果 key 已经存在并且是一个字符串， append 命令将 指定value 追加到改 key 原来的值（value）的末尾
    public function append($configKey, $key, $value)
    {
        $cacheConf = ConfigParse::parseCache($configKey, $key);

        return self::$client->append($cacheConf['key'], $value);
    }




}