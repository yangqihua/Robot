<?php

namespace app;

require __DIR__ . '/../vendor/autoload.php';

use app\handlers\MainHandler;

//$result = MainHandler::request('购物','12','0');

//var_dump($result);

$str = '{"code":200,"data":"@杨启华 \n\n你好1""}';
$result = json_decode($str, true);
var_dump(json_last_error());
if (json_last_error() !== JSON_ERROR_NONE || !$result) {
    var_dump(['code' => 500, 'data' => 'json解析出错']);
} else {
    var_dump($result);
}
