<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/4/3
 * Time: 下午8:58
 */
namespace demo\controllers;

use demo\library\BizException;
use demo\library\Log;
use Yii;
use yii\db\Exception;
use yii\web\Controller;

class MyController extends Controller
{
    protected $request;
    protected $route;
    protected $env;

    public function init()
    {
        parent::init();
        $this->request = Yii::$app->request;
        $this->route = $this->module->requestedRoute;
        $this->excuteMiddleware();
    }

    private function excuteMiddleware()
    {

        $middleConf = require SHARE.'middleware.php';
        if (!$middleConf)
            return null;


        list($module, $controller, $action) = explode('/', $this->route);

        $exceptArr = $middleConf[$module]['controller'];
        if (in_array($controller, array_keys($exceptArr))) {
            if (!$exceptArr[$controller])
                return null;
            if (in_array($action, $exceptArr[$controller]))
                return null;
        }

        if (in_array($controller, $exceptArr) || in_array($action, $exceptArr))
            return null;

        $moduleRules = $middleConf[$module]['rules'];

        try {
            foreach ($moduleRules as $v) {
                call_user_func(array(new $v, 'handle'), $this->request);
            }

        } catch (Exception $e) {
            return $this->r($e->getCode(),  $e->getMessage(), null );
        }

    }

    protected function get($name, $default = null)
    {
        $input = $this->request->get($name);

        return isset($input) ? trim($input) : $default;
    }

    protected function post($name, $default = null)
    {
        $input = $this->request->post($name);

        return isset($input) ? trim($input) : $default;
    }

    protected function _execute($param, $jsonp = false, $callback = 'callback')
    {
        list($module, $controller, $action) = explode('/', $this->route);

        $action = stripos($action, '-') ?
            array_reduce(explode('-',$action), function($v1, $v2) {
            return ucfirst($v1).ucfirst($v2);}) : $action;

        $className = "\\demo\\models\\".$module."\\service\\".$controller."\\".ucfirst($action);

        $service = new $className();
        $result = null;
        try{
            $result = $service->doAction($param);

        }catch( \Exception $e) {

            //todo errorCode <99999 为系统异常 需要报警
            Log::error($module, 'code:'.$e->getCode().',msg:'.$e->getMessage().',params:'.json_encode($param));

            return $this->r($e->getCode(),  $e->getMessage(), $result );
        }

        return $this->r( 0,  'success', $result );
    }

    public function r($code, $msg, $data)
    {
        if( ! is_array($data) ) {
            $newData['result'] = $data;
            $data = $newData;
        }

        $res = [
            'code' => intval($code),
            'msg'  => $msg,
            'data' => $data,
        ];

        return $this->asJson($res);

    }

    public function middleware()
    {
        $usefulMiddleware = [];
        $middlewareConfig = require Yii::$app->basePath."/share/middleware.php";

        $middlewareArr = $middlewareConfig ? array_keys($middlewareConfig) : [];
        list($module, $controller, $action) = explode('/', $this->route);

        foreach ($middlewareArr as $middleware)
        {
            $className = "demo\\middlewares\\".ucfirst($middleware);
            if(!class_exists($className)){
                continue;
            }

            if(isset($middlewareConfig[$middleware]['only']) && is_array($middlewareConfig[$middleware]['only']))
            {
                $inOnly = false;
                foreach ($middlewareConfig[$middleware]['only'] as $path)
                {
                    $pathArr = explode("/",$path);
                    if(array($module) == $pathArr || array($module,$controller) == $pathArr || array($module,$controller,$action) == $pathArr) {
                        $inOnly = true;
                    }
                }
                if($inOnly == false)
                    continue;
            }

            if(isset($middlewareConfig[$middleware]['except']) && is_array($middlewareConfig[$middleware]['except']))
            {
                foreach ($middlewareConfig[$middleware]['except'] as $path)
                {
                    $pathArr = explode("/",$path);
                    if(array($module) == $pathArr || array($module,$controller) == $pathArr || array($module,$controller,$action) == $pathArr){
                        continue 2;
                    }
                }
            }

            //记录用到的中间件，方便调适
            $usefulMiddleware[] = $middleware;
            try{
                $obj = new $className();
                if(!$obj instanceof MiddlewareImp){
                    continue;
                }
                $obj->handle($this->request);
            }catch (MidException $e){
                $this->r($e->getCode(),  $e->getMessage(), [] )->send();
                exit;
            }
        }
        return;
    }
}