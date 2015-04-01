<?php

namespace PhpSitemaper;

/**
 * Composer Autoload
 */
require_once 'vendor/autoload.php';

App::start();

/**
 * Initializing router and defining routes
 */
$router = Router::getInstance();

$router->add('get', '/', ['sitemap']);
$router->add('post', '/',['sitemap','generate']);


/**
 * Router execution
 */
$router->execute();
