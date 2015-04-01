#!/usr/bin/env php
<?php

/**
 * Setting infinite execution time
 */
set_time_limit(0);

/**
 * Composer Autoload
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Using Symfony Console
 */
use Symfony\Component\Console\Application;

/**
 * Creating new object of Application class
 */
$app = new Application('Sitemap Generator');

/**
 * Adding new command to application
 */
$app->add(new \PhpSitemaper\SitemapCommand());

/**
 * Running
 */
$app->run();
