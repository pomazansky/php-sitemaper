<?php

namespace PhpSitemaper\Views;

/**
 * Basic View class
 *
 * Class View
 * @package Sitemap
 */
class View
{
    /**
     * Twig object
     *
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * Twig template object
     *
     * @var
     */
    protected $template;

    /**
     * Initializes Twig
     */
    public function __construct()
    {
        $this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem('src/templates'), [
            'debug' => true,
            'cache' => 'cache/twig'
        ]);
    }

    /**
     * Renders 404 error
     */
    public function render404()
    {
        $this->template = $this->twig->loadTemplate('error.twig');
        echo $this->template->render(
            [
            'title' => '404 Error: Page not found',
            'template_path' => 'src/templates/',
            'msg' => '',
            'headling' => '404 Error: Page not found'
            ]
        );
    }

    /**
     * Renders Error page
     *
     * @param string $msg
     */
    public function renderError($msg = '')
    {
        $this->template = $this->twig->loadTemplate('error.twig');
        echo $this->template->render([
            'title' => 'We are sorry, but an error happened',
            'template_path' => 'src/templates/',
            'msg' => $msg ?: 'We are trying our best to fix it. Please, come back later.',
            'headling' => 'We are sorry, but an error happened'
        ]);
    }
}
