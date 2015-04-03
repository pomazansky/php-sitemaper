<?php

namespace PhpSitemaper;

use Silex\Application;

require_once 'vendor/autoload.php';

$app = new Application();

$app['debug'] = true;

$app->get('/', '\\PhpSitemaper\\Controllers\\SitemapController::indexAction');

$app->post('/', '\\PhpSitemaper\\Controllers\\SitemapController::generateAction');

$app->run();
