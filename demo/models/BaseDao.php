<?php
/***
 * @author charlie
 *
 */

namespace demo\models;

use demo\library\ConfigParse;
use yii;
use yii\db\Command;

class BaseDao extends Command
{

    protected $column;

    /*****
     * @param string $sid
     * @param $params
     * @return $this
     * @add  queryOne
     * @add  queryAll
     */
    protected function queryExecute($sid, array $params)
    {
        return Yii::$app->db->createCommand(ConfigParse::parseSql($sid, $this->column))
                            ->bindValues($params);
    }

    /***
     * @param string $sid
     * @param array $params
     * @return int
     */
    protected function writeExecute($sid, array $params)
    {
        return Yii::$app->db->createCommand(ConfigParse::parseSql($sid))
            ->bindValues($params)
            ->execute();
    }

    /***
     * @param string $table
     * @param array $data
     * @return $this
     */
    protected function insertBatch($table, array $data)
    {
        return Yii::$app->db->createCommand()
            ->batchInsert($table, $this->column, $data);
    }


}