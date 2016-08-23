<?php

require_once __DIR__.'/../vendor/autoload.php';

use eLife\Medium\Kernel;

$app = Kernel::create([
    'debug' => true,
    'validate' => true,
    'ttl' => 0,
]);
$app['debug'] = true;

$app->run();
