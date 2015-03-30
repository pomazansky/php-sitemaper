<?php

namespace PhpSitemaper\Views;

/**
 * Базовый класс Вида
 *
 * Class View
 * @package Sitemap
 */
class View
{
    /**
     * Объект шаблонизатора Twig
     *
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * Объект шаблона Twig
     *
     * @var
     */
    protected $template;

    /**
     * Инициализация шаблонизатора Twig
     */
    public function __construct()
    {
        $this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem('src/templates/twig'), [
            'debug' => true,
            'cache' => 'cache/twig'
        ]);
    }

    /**
     * Метод отображения страницы ошибки 404
     */
    public function render404()
    {
        $this->template = $this->twig->loadTemplate('error.twig');
        echo $this->template->render([
            'title' => '404 Error: Page not found',
            'template_path' => 'src/templates/twig/',
            'msg' => '',
            'headling' => '404 Error: Page not found'
        ]);
    }

    /**
     * Метод отображения страницы ошибки
     *
     * @param string $msg
     */
    public function renderError($msg = '')
    {
        $this->template = $this->twig->loadTemplate('error.twig');
        echo $this->template->render([
            'title' => 'We are sorry, but an error happened',
            'template_path' => 'src/templates/twig/',
            'msg' => $msg ?: 'We are trying our best to fix it. Please, come back later.',
            'headling' => 'We are sorry, but an error happened'
        ]);
    }
}