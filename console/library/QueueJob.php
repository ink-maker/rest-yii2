<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/6/20
 * Time: 上午11:45
 */
namespace app\library;
use Pheanstalk\Pheanstalk;

class QueueJob
{
    public static $beanstalk;

    public static function init()
    {

        if (!isset(self::$beanstalk)) {
            $beanstalkConf = \Yii::$app->components['beanstalk'];
            self::$beanstalk = new Pheanstalk($beanstalkConf['hostname'], $beanstalkConf['port']);
        }
    }

    public static function add($channel, $value, $delayTime = 0, $priority = 0, $reservedTimeout = 3)
    {
        self::init();
        $id = self::$beanstalk->useTube($channel)->put(json_encode($value), $priority, $delayTime, $reservedTimeout);
        //获取任务
//        $job = self::$beanstalk->peek($id);
        //查看任务状态
        //print_r(self::$beanstalk->statsJob($job));
    }

    public static function reserve($channel, $processTime = 3)
    {
        self::init();
        $job = self::$beanstalk->watch($channel)
            ->ignore('default')
            ->reserve($processTime);

        $jobData = null;
        if ($job) {
            $jobData = $job->getData();
            self::$beanstalk->delete($job);
        }

        return $jobData;
    }

}