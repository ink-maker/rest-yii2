<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/4/8
 * Time: 上午11:29
 */

namespace demo\models\user\service\index;

class Update
{


    public function doAction($data)
    {
        $indexBo = new \demo\models\user\bo\Index();

        $return = $indexBo->updateTitle($data['id']);

        return $return;
    }

}