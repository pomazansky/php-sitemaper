<?php

namespace PhpSitemaper;

use PhpSitemaper\Views\View;

/**
 * Вспомогательный класс приложения
 *
 * Class App
 * @package Sitemap
 */
class App
{
    /**
     * Экземпляр собственного класс
     *
     * @var App
     */
    private static $instance;

    /**
     * Метод запрещает создавать объекты класса извне в соответствии с паттерном Singleton
     */
    protected function __construct()
    {

    }

    /**
     * Метод запуска вспомогательных инструментов
     */
    public static function start()
    {
        ob_start();
        session_start();

        set_exception_handler([__CLASS__, 'exceptionHandler']);
    }

    /**
     * Возвращает единственный экземпляр объекта своего класс по паттерну Singleton
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
     * Обработчик Исключений
     *
     * @param \Exception $e
     */
    public static function exceptionHandler(\Exception $e)
    {
        file_put_contents('log/exception.log',
            time() . ' ' . $e->getFile() . ' @ line : ' . $e->getLine() . ' thrown ' . $e->getMessage() . "\n",
            FILE_APPEND);
        $view = new View();
        $view->renderError($msg = $e->getMessage());
    }

    /**
     * Метод запрещает копировать объекты класса извне в соответствии с паттерном Singleton
     */
    protected function __clone()
    {
    }
}