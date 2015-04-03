<?php

namespace PhpSitemaper;

use Silex\Application;
use Silex\Provider\TwigServiceProvider;

require_once 'vendor/autoload.php';

$app = new Application();

$app['debug'] = true;

$app->get('/', '\\PhpSitemaper\\Controllers\\SitemapController::indexAction');

$app->post('/', '\\PhpSitemaper\\Controllers\\SitemapController::generateAction');

$app->register(new TwigServiceProvider(), [
    'twig.path' => __DIR__.'/src/templates',
    'twig.options' => [
        'cache' => 'cache/twig',
        'template_path' => 'src/templates/'
    ]
]);

$app->run();
