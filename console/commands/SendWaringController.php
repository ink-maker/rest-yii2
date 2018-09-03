<?php


namespace app\commands;

use yii\console\Controller;
use app\library\QueueJob;

class SendWaringController extends Controller
{
    const CHANNEL = 'log/send_warning';

    public static $beanstalk;

    public function init()
    {
        parent::init();

    }

    public function actionRun()
    {

        while (true) {
            $data = QueueJob::reserve(self::CHANNEL);
            var_dump($data);
            if ($data) {
                //todo 发送邮件处理
                $this->process(json_decode($data, true));

            }
        }

        return true;
    }

    private function process($data)
    {



    }


}