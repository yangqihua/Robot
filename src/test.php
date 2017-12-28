<?php

namespace app;

require __DIR__.'/../vendor/autoload.php';

use app\handlers\MainHandler;

$result = MainHandler::request('0','12','123');

print $result;