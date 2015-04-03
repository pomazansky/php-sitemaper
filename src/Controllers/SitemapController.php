<?php

namespace PhpSitemaper\Controllers;

use PhpSitemaper\Exporters\XmlWriterAdapter;
use PhpSitemaper\Fetchers\GuzzleAdapter;
use PhpSitemaper\Parsers\NokogiriAdapter;
use PhpSitemaper\SitemapConfig;
use PhpSitemaper\SitemapGenerator;
use PhpSitemaper\Stat;
use PhpSitemaper\Views\SitemapView;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SitemapController
 * @package Sitemap\Controllers
 */
class SitemapController
{

    /**
     * Default action. Creates View for rendering main form
     *
     * @param Request $request
     * @param Application $app
     * @return string
     */
    public function indexAction(Request $request, Application $app)
    {
        $view = new SitemapView();
        return $view->renderIndex();
    }

    /**
     * Sitemap generation action
     *
     * @param Request $request
     * @param Application $app
     * @return string
     */
    public function generateAction(Request $request, Application $app)
    {
        /**
         * Generates process id
         */
        $sessionId = SitemapGenerator::genSessionId();
        $_SESSION['sessionId'] = $sessionId;

        /**
         * Creates sitemap object and sets basic params
         */
        $sitemap = new SitemapGenerator();
        $sitemap->setConfig(new SitemapConfig($_POST));
        $sitemap->setBaseUrl($request->get('url'));

        /**
         * Sets fetcher - HTTP(S) client
         */
        $sitemap->setFetcher(new GuzzleAdapter());

        /**
         * Sets HTML parsing module
         */
        $sitemap->setParser(new NokogiriAdapter());

        /**
         * Sets XML exporting module
         */
        $sitemap->setExporter(new XmlWriterAdapter());

        /**
         * Sets statistics gethering module
         */
        $sitemap->setStats(new Stat($sessionId));

        /**
         * Starts generation
         */
        $sitemap->execute();

        /**
         * Creates View and renders results
         */
        $view = new SitemapView();
        return $view->renderResult($sitemap);
    }
}
