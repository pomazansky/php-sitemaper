<?php

namespace PhpSitemaper;

//error_reporting(E_ALL);

/**
 * Подгрузка автозагрузчика Composer
 */
require_once 'vendor/autoload.php';

App::start();

/**
 * Инициализация Роутера и определение маршрутов
 */
$router = Router::getInstance();

$router->add('get', '/', ['sitemap']);
$router->add('post', '/',['sitemap','generate']);


/**
 * Запуск роутера
 */
$router->execute();
