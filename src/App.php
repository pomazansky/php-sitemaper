<?php

namespace PhpSitemaper;

use PhpSitemaper\Views\View;

/**
 * Application supporting class
 *
 * Class App
 * @package Sitemap
 */
class App
{
    /**
     * Static instance
     *
     * @var App
     */
    private static $instance;

    /**
     * Defines constructor as protected
     */
    protected function __construct()
    {
    }

    /**
     * Makes object coping impossible
     */
    protected function __clone()
    {
    }

    /**
     * Starts support function
     */
    public static function start()
    {
        ob_start();
        session_start();

        set_exception_handler([__CLASS__, 'exceptionHandler']);
    }

    /**
     * Returns self static instance
     *
     * @return App
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Exception Handler
     *
     * @param \Exception $e
     */
    public static function exceptionHandler(\Exception $e)
    {
        file_put_contents('log/exception.log',
            time() . ' ' . $e->getFile() . ' @ line : ' . $e->getLine() . ' thrown ' . $e->getMessage() . "\n",
            FILE_APPEND);
        $view = new View();
        $view->renderError($e->getMessage());
    }
}
