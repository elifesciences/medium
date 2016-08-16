<?php

require_once '../vendor/autoload.php';

use eLife\Medium\Kernel;

$app = Kernel::create();
$app['debug'] = false;
$app->run();
