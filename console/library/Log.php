<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/8/16
 * Time: 上午11:41
 */

namespace app\library;


class Log
{
    const APPLICATION = 'demo';
    const TRACE_LEVEL = 2;
    const CHANNEL_WRITE_LOG = 'log/write_log';
    const CHANNEL_SEND_WARNING = 'log/send_warning';


    public static function info($category, $message, $trace = false)
    {
        self::preProcess($category, $message, 'INFO', $trace);
    }

    public static function warning($category, $message, $trace = false)
    {
        self::preProcess($category, $message, 'WARNING', $trace);
    }

    public static function error($category, $message, $trace = false)
    {

        self::preProcess($category, $message, 'ERROR', $trace);
    }

    public static function preProcess($category, $message, $level, $trace)
    {


        $data = [];
        $data['time'] = date('Y-m-d H:i:s');
        $data['msg'] = $message;
        $data['ip'] = self::getRemoteIP();
        $data['trace'] = $trace;
        $data['level'] = $level;
        $text = self::formatMessage($data);

        $jobData = [
            'application' => self::APPLICATION,
            'text' => $text,
            'module' => $category
        ];
        QueueJob::add(self::CHANNEL_WRITE_LOG, $jobData);
        QueueJob::add(self::CHANNEL_SEND_WARNING, $jobData);
    }

    private static function formatMessage($data)
    {

        $text = $data['time'].' '.$data['ip'].' '.$data['level'].' '.$data['msg']."\r\n";
        if ($data['trace']) {
            $count = 0;
            $ts = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_pop($ts); // remove the last trace since it would be the entry script, not very useful
            foreach ($ts as $trace) {
                if (isset($trace['file'], $trace['line']) && strpos($trace['file'], YII2_PATH) !== 0) {
                    unset($trace['object'], $trace['args']);
                    $text .= 'in '.$trace['file'].':'.$trace['line']."\r\n";
                    $traces[] = $trace;
                    if (++$count >= self::TRACE_LEVEL) {
                        break;
                    }
                }
            }
        }

        return $text;
    }

    private static function getRemoteIP()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

}