<?php

namespace demo\middlewares;

use yii\web\Request;

class Authenticate
{
    public $tokenParam = 'token';

    public function handle(Request $request)
    {
        //TODO
        return true;
    }


}