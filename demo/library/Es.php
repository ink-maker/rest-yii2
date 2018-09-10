<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/8/16
 * Time: 上午11:41
 */

namespace demo\library;

use Elasticsearch\ClientBuilder;

class Es
{

    const RETRIES = 2;

    private static $client = null;

    public static function getInstance()
    {
        if (isset(self::$client))
            return self::$client;

        $esConf = \Yii::$app->components['elasticsearch'];
        $params = [
            'hosts' => $esConf['hostname'],
            'retries' => self::RETRIES,
            'handler' => ClientBuilder::singleHandler()
        ];
        self::$client = ClientBuilder::fromConfig($params);
    }

    public function __construct()
    {
        self::getInstance();
    }

    /**
     * @param $index
     * @param $type
     * @param array $keyword
     * @param int $page
     * @param int $pageSize
     * @return array
     */

    public function search($index, $type, array $keyword, $page = 0, $pageSize = 10) {
        $params = [
            'index' => $index,
            'type' => $type,
            'from' => $page,
            'size' => $pageSize,
            'body' => [
                'query' => [
                    'match' => $keyword
                ]
            ]
        ];

        $ret = self::$client->search($params);

        $return = [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $ret['hits']['total'],
            'list' => $ret['hits']['total'] ? array_column($ret['hits']['hits'], '_source') : []
        ];

        return $return;
    }






}