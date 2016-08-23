<?php

require_once '../vendor/autoload.php';

use eLife\Medium\Kernel;

$app = Kernel::create([
    'debug' => true,
    'validate' => true,
]);
$app->run();
