<?php

namespace PhpSitemaper;

use PhpSitemaper\Controllers\SitemapController;
use Silex\Application;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;

require_once 'vendor/autoload.php';

$app = new Application();

$app['debug'] = true;

$app->mount('/', new SitemapController());

$app->register(new SessionServiceProvider());
$app->register(new TwigServiceProvider(), [
    'twig.path' => __DIR__.'/src/templates',
    'twig.options' => [
        'cache' => 'cache/twig',
        'template_path' => 'src/templates/'
    ]
]);

$app->run();
