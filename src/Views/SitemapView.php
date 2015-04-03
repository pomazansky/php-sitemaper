<?php

namespace PhpSitemaper\Views;

use PhpSitemaper\SitemapGenerator;

/**
 * Class SitemapView
 * @package Sitemap\Views
 */
class SitemapView extends View
{
    /**
     * Renders main form
     *
     * @return string
     */
    public function renderIndex()
    {
        $this->template = $this->twig->loadTemplate('urlForm.twig');
        return $this->template->render([
            'template_path' => 'src/templates/',
            'title' => 'Sitemap Generator'
        ]);
    }

    /**
     * Renders result page
     *
     * @param SitemapGenerator $sitemap
     * @return string
     */
    public function renderResult(SitemapGenerator $sitemap)
    {
        $this->template = $this->twig->loadTemplate('result.twig');
        return $this->template->render([
            'template_path' => 'src/templates/',
            'title' => 'Sitemap Generator',
            'baseUrl' => $sitemap->getBaseUrl(),
            'linksCount' => $sitemap->getResourcesCount(),
            'sitemapFiles' => $sitemap->getSitemapFiles(),
            'sitemapIndexFile' => $sitemap->getSitemapIndexFile()
        ]);
    }
}
