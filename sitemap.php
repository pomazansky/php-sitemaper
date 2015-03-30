#!/usr/bin/env php
<?php

/**
 * Снятие ограничения на время выполнение
 */
set_time_limit(0);

/**
 * Подргрузка автозагрузчика Composer
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Используем компонент Symfony Console
 */
use Symfony\Component\Console\Application;

/**
 * Создаем экземляр класс Application, подключаем класс команды
 * и запускаем приложение
 */
$app = new Application('Sitemap Generator');
$app->add(new \PhpSitemaper\SitemapCommand());
$app->run();
