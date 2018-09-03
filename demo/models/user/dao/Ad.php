<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/4/8
 * Time: 下午2:34
 */

namespace demo\models\user\dao;

use demo\models\BaseDao;

class Ad extends BaseDao
{

    private $_column = 'id, type, assetUrl, linkUrl, sort, title, `desc`, creator, updater, status, createdAt, 
                       updatedAt, deletedAt';

    public function init()
    {
        $this->column = $this->_column;
        parent::init();
    }

    public function getById($id)
    {
        $params = ['id' => $id];

        $result = $this->queryExecute('user.ad.sql_get_by_id', $params)->queryOne();
        return $result;
    }

    public function updateTitleById($id)
    {
        $params = ['id' => $id, 'title' => 'test'];
        $ret = $this->writeExecute('user.ad.sql_update_by_id', $params);

        return $ret;
    }

}