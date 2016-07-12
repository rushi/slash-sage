<?php
require __DIR__ . '/vendor/autoload.php';

$settings = require __DIR__ . '/config/config.php';
$app = new \Slim\App($settings);

require_once __DIR__ . '/config/dependencies.php';

require_once __DIR__ . '/src/routes.php';

$app->run();
