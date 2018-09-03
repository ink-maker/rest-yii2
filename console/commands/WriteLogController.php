<?php


namespace app\commands;

use yii\console\Controller;
use app\library\QueueJob;

class WriteLogController extends Controller
{
    const CHANNEL = 'log/write_log';

    private  $logFile = '';
    private static $dirMode = 0775;
    private static $fileMode;
    private static $enableRotation = true;
    private static $maxFileSize = 10240; // in KB
    private static $maxLogFiles = 5;
    private static $rotateByCopy = true;

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
                $this->process(json_decode($data, true));
            }
        }

        return true;
    }

    private function process($data)
    {
        $text = $data['text'];
        echo WEB_PATH;
        if (YII_ENV == 'dev') {
            $fileDir = \Yii::$app->getRuntimePath() . '/logs/';
        } else {
            $fileDir = '/home/wwwlogs/';
        }
        $this->logFile = $fileDir.$data['application'].'/'.$data['module'].'.log';

        $this->writeLog($text);
    }


    private function writeLog($text)
    {
        $logPath = dirname($this->logFile);

        self::createDirectory($logPath, self::$dirMode, true);

        if (($fp = @fopen($this->logFile, 'a')) === false) {
            throw new \Exception("Unable to append to log file: {self::logFile}");
        }
        @flock($fp, LOCK_EX);
        if (self::$enableRotation) {
            clearstatcache();
        }
        if (self::$enableRotation && @filesize($this->logFile) > self::$maxFileSize * 1024) {
            self::rotateFiles();
            @flock($fp, LOCK_UN);
            @fclose($fp);
            $writeResult = @file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
            if ($writeResult === false) {
                $error = error_get_last();
                throw new \Exception("Unable to export log through file!: {$error['message']}");
            }
            $textSize = strlen($text);
            if ($writeResult < $textSize) {
                throw new \Exception("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
            }
        } else {
            $writeResult = @fwrite($fp, $text);
            if ($writeResult === false) {
                $error = error_get_last();
                throw new \Exception("Unable to export log through file!: {$error['message']}");
            }
            $textSize = strlen($text);
            if ($writeResult < $textSize) {
                throw new \Exception("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
            }
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
        if (self::$fileMode !== null) {
            @chmod($this->logFile, self::$fileMode);
        }
    }

    private function rotateFiles()
    {
        $file = $this->logFile;
        for ($i = self::$maxLogFiles; $i >= 0; --$i) {

            $rotateFile = $file . ($i === 0 ? '' : '.' . $i);
            if (is_file($rotateFile)) {

                if ($i === self::$maxLogFiles) {
                    @unlink($rotateFile);
                    continue;
                }
                $newFile = $this->logFile . '.' . ($i + 1);
                self::$rotateByCopy ? self::rotateByCopy($rotateFile, $newFile) : self::rotateByRename($rotateFile, $newFile);
                if ($i === 0) {
                    self::clearLogFile($rotateFile);
                }
            }
        }
    }

    private function rotateByCopy($rotateFile, $newFile)
    {
        @copy($rotateFile, $newFile);
        if (self::$fileMode !== null) {
            @chmod($newFile, self::$fileMode);
        }
    }

    private function rotateByRename($rotateFile, $newFile)
    {
        @rename($rotateFile, $newFile);
    }

    private function clearLogFile($rotateFile)
    {
        if ($filePointer = @fopen($rotateFile, 'a')) {
            @ftruncate($filePointer, 0);
            @fclose($filePointer);
        }
    }

    private static function createDirectory($path, $mode = 0775, $recursive = true)
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);

        if ($recursive && !is_dir($parentDir) && $parentDir !== $path) {
            static::createDirectory($parentDir, $mode, true);
        }
        try {
            if (!mkdir($path, $mode)) {
                return false;
            }
        } catch (\Exception $e) {
            if (!is_dir($path)) {
                throw new \Exception("Failed to create directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
            }
        }
        try {
            return chmod($path, $mode);
        } catch (\Exception $e) {
            throw new \Exception("Failed to change permissions for directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
        }
    }

}