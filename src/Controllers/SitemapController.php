<?php

namespace PhpSitemaper\Controllers;

use PhpSitemaper\Exporters\XmlWriterAdapter;
use PhpSitemaper\Fetchers\GuzzleAdapter;
use PhpSitemaper\Parsers\NokogiriAdapter;
use PhpSitemaper\SitemapConfig;
use PhpSitemaper\SitemapGenerator;
use PhpSitemaper\Stat;
use PhpSitemaper\Views\SitemapView;

/**
 * Class SitemapController
 * @package Sitemap\Controllers
 */
class SitemapController
{
    /**
     * Default action. Creates View for rendering main form
     */
    public function indexAction()
    {
        $view = new SitemapView();
        $view->renderIndex();
    }

    /**
     * Sitemap generation action
     */
    public function generateAction()
    {
        /**
         * Generates process id
         */
        $id = SitemapGenerator::genId();
        $_SESSION['id'] = $id;

        /**
         * Creates sitemap object and sets basic params
         */
        $sitemap = new SitemapGenerator();
        $sitemap->setConfig(new SitemapConfig($_POST));
        $sitemap->setBaseUrl($_POST['url']);

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
        $sitemap->setStats(new Stat($id));

        /**
         * Starts generation
         */
        $sitemap->execute();

        /**
         * Creates View and renders results
         */
        $view = new SitemapView();
        $view->renderResult($sitemap);
    }
}