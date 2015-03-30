<?php

namespace PhpSitemaper\Views;

use PhpSitemaper\SitemapGenerator;

/**
 * Главный класс Вида
 *
 * Class SitemapView
 * @package Sitemap\Views
 */
class SitemapView extends View
{
    /**
     * Метод отображения главной формы
     */
    public function renderIndex()
    {
        $this->template = $this->twig->loadTemplate('urlForm.twig');
        echo $this->template->render([
            'template_path' => 'src/templates/twig/',
            'title' => 'Sitemap Generator'
        ]);
    }

    /**
     * Метод отображения страницы результатов
     * @param SitemapGenerator $sitemap
     */
    public function renderResult(SitemapGenerator $sitemap)
    {
        $this->template = $this->twig->loadTemplate('result.twig');
        echo $this->template->render([
            'template_path' => 'src/templates/twig/',
            'title' => 'Sitemap Generator',
            'baseUrl' => $sitemap->getBaseUrl(),
            'linksCount' => $sitemap->getPagesCount(),
            'sitemapFiles' => $sitemap->getSitemapFiles(),
            'sitemapIndexFile' => $sitemap->getSitemapIndexFile()
        ]);
    }
}