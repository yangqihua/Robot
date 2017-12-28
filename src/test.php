<?php

namespace app;

require __DIR__.'/../vendor/autoload.php';

use app\handlers\MainHandler;

$result = MainHandler::request('购物','12','0');

var_dump($result);