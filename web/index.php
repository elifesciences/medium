<?php

require_once '../vendor/autoload.php';

use eLife\Medium\Kernel;

$app = Kernel::create([
    'debug' => true,
    'validate' => true,
]);

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => '../logs/error.log',
    'monolog.level' => 'error',
));

$app->run();
