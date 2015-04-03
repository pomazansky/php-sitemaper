<?php

namespace PhpSitemaper\Views;

use PhpSitemaper\SitemapGenerator;
use Silex\Application;

/**
 * Basic View class
 *
 * Class View
 * @package Sitemap
 */
class View
{
    /**
     * Renders main form
     *
     * @param Application $app
     * @return string
     */
    public function renderIndex(Application $app)
    {
        return $app['twig']->render('urlForm.twig', [
            'template_path' => 'src/templates/',
            'title' => 'Sitemap Generator'
        ]);
    }

    /**
     * Renders result page
     *
     * @param Application $app
     * @param SitemapGenerator $sitemap
     * @return string
     */
    public function renderResult(Application $app, SitemapGenerator $sitemap)
    {
        return $app['twig']->render('result.twig', [
            'template_path' => 'src/templates/',
            'title' => 'Sitemap Generator',
            'baseUrl' => $sitemap->getBaseUrl(),
            'linksCount' => $sitemap->getResourcesCount(),
            'sitemapFiles' => $sitemap->getSitemapFiles(),
            'sitemapIndexFile' => $sitemap->getSitemapIndexFile()
        ]);
    }

    /**
     * Renders 404 error
     *
     * @param Application $app
     * @return string
     */
    public function render404(Application $app)
    {
        return $app['twig']->render('error.twig', [
            'title' => '404 Error: Page not found',
            'template_path' => 'src/templates/',
            'msg' => '',
            'headling' => '404 Error: Page not found'
            ]);
    }

    /**
     * Renders Error page
     *
     * @param Application $app
     * @param string $msg
     * @return string
     */
    public function renderError(Application $app, $msg = '')
    {
        return $app['twig']->render('error.twig', [
            'title' => 'We are sorry, but an error happened',
            'template_path' => 'src/templates/',
            'msg' => $msg ?: 'We are trying our best to fix it. Please, come back later.',
            'headling' => 'We are sorry, but an error happened'
        ]);
    }
}
