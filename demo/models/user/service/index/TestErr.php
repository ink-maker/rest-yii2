<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/4/8
 * Time: 上午11:29
 */

namespace demo\models\user\service\index;

use demo\library\BizException;

class TestErr
{


    public function doAction($data)
    {
        echo 111;
        if (!isset($data['name']))
            throw new BizException(BizException::PARAM_ERROR);

        echo 222;
        return 'done!!!';
    }

}