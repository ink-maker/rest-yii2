<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/4/8
 * Time: 下午2:03
 */

namespace demo\models\user\bo;


use demo\library\Cache;
use demo\models\BaseBo;
use demo\models\user\dao\Ad;

class Index extends BaseBo
{

    public function getUser($id, $name = 'test')
    {
        $key = 'laosun';
        $configKey = 'user.ad.user_ad_login';

        $cacheCli = new Cache();

        $cacheCli->set($configKey, $key, 'hello,cache');

        $aa = $cacheCli->get($configKey, $key);
        var_dump($aa);exit;

        return (new Ad())->getById($id);
    }

    public function updateTitle($id)
    {
        return (new Ad())->updateTitleById($id);
    }
}