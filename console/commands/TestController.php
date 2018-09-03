<?php


namespace app\commands;

use yii\console\Controller;
use app\library\Log;
use app\library\BizException;

class TestController extends Controller
{

    public function init()
    {
        parent::init();

    }

    public function actionRun()
    {
        while (true) {

            $data = rand(0,5);
            sleep(3);
            var_dump($data);
            //TODO  process biz
            if ($data>3) {
                try {
                    $this->process($data);
                } catch (\Exception $e) {
                    Log::error('console', 'code:'.$e->getCode().','.'msg:'.$e->getMessage());
                }

            }
        }

        return true;
    }

    private function process($data)
    {
        throw new BizException(BizException::PARAM_ERROR);

    }


}