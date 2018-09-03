<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/4/8
 * Time: 上午11:29
 */

namespace demo\models\user\service\index;

use demo\library\BizException;
use demo\library\QueueJob;


class TestLog
{


    public function doAction($data)
    {
        throw new BizException(BizException::CONFIG_NOT_EXIST);


        return 'done!!!';
    }

}