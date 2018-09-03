<?php

namespace demo\library;

class BizException extends \Exception
{


    // 10000~99999为系统异常预留
    //10010000开始，每隔10000一个功能模块
    //系统类型错误
    const INTER_ERROR = 10010000;
    const PARAM_ERROR = 10010001;
    const SYSTEM_NET_ERROR = 10010002;
    const OP_ERROR = 10010003;
    const OP_REMOTE = 10010004;
    const NOT_RIGHTS = 10010005;
    const CONFIG_NOT_EXIST = 10010006;


    //自定义信息
    const SELF_DEFINE = 10000000;

    public static $msgs = array(
        self::SELF_DEFINE => '',
        self::INTER_ERROR => '未知错误',
        self::PARAM_ERROR => '参数错误',
        self::SYSTEM_NET_ERROR => '网络错误',
        self::OP_ERROR => '操作失败',
        self::OP_REMOTE => '',
        self::NOT_RIGHTS => '无权限',
        self::CONFIG_NOT_EXIST => '配置文件不存在',
    );

    public function __construct($code, $param = "", $realCode=0){
        if( $realCode <= 0){
            $this->code = $code;
        }else {
            $this->code = $realCode;
            $this->message = $param;
            return;
        }

        $this->message = self::getErrorMsg( $code );
    }

    public static function getErrorMsg($errCode)
    {
        if (isset(self::$msgs[$errCode])) {
            return self::$msgs[$errCode];
        }else {
            return self::$msgs[self::INTER_ERROR];
        }
    }
}
