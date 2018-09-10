<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/4/8
 * Time: 上午11:29
 */

namespace demo\models\user\service\index;

use demo\library\Es;

class TestEs
{


    public function execute($data)
    {
//        $params = [
//            'index' => 'user',
//            'type' => 'user',
//            'id' => '14'
//        ];

        return (new Es())->search('user', 'user', ['nickname' => $data['key']]);
    }

}