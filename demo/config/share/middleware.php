<?php
/**
 * Created by PhpStorm.
 * User: charlie
 * Date: 2018/4/12
 * Time: 下午2:02
 */

return [
    'Authenticate'=>[
        //'only'=>['customer'],
        'except'=>['customer','ad/index','user/index/index']

    ],
    'CheckAge'=>[
        'only'=>['user/index/test','customer/index','ad']
    ],
    /*'FilterIP'=>[

    ]*/
    'user' => [
        'rules' => [
            demo\middlewares\Authenticate::class
        ],
        'controller' => [ //不想施加规则的controller和action
            'index' => [],
            'demo' => [] //表示全部不加
        ]
    ]
];