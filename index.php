<?php

namespace PhpSitemaper;

use PhpSitemaper\Controllers\SitemapController;
use Silex\Application;

require_once 'vendor/autoload.php';

$app = new Application();

$app['debug'] = true;

$app->get('/', function(){
    $controller = new SitemapController();
    $controller->indexAction();
    return true;
});

$app->post('/', function(){
   $controller = new SitemapController();
    $controller->generateAction();
    return true;
});

$app->run();
