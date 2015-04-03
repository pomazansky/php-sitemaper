<?php

namespace PhpSitemaper\Controllers;

use PhpSitemaper\Exporters\XmlWriterAdapter;
use PhpSitemaper\Fetchers\GuzzleAdapter;
use PhpSitemaper\Parsers\NokogiriAdapter;
use PhpSitemaper\SitemapConfig;
use PhpSitemaper\SitemapGenerator;
use PhpSitemaper\Stat;
use PhpSitemaper\Views\View;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SitemapController
 * @package Sitemap\Controllers
 */
class SitemapController implements ControllerProviderInterface
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
        $view = new View();
        return new Response($view->renderIndex($app));
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
        $app['session']->set('sessionId', $sessionId);

        /**
         * Creates sitemap object and sets basic params
         */
        $sitemap = new SitemapGenerator();
        $sitemap->setConfig(new SitemapConfig([
            'parseLevel' => $request->get('parseLevel'),
            'changeFreq' => $request->get('changeFreq'),
            'lastMod' => $request->get('lastMod'),
            'priority' => $request->get('priority'),
            'gzip' => $request->get('gzip')
        ]));
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
        $view = new View();
        return new Response($view->renderResult($app, $sitemap));
    }

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', '\\PhpSitemaper\\Controllers\\SitemapController::indexAction');

        $factory->post('/', '\\PhpSitemaper\\Controllers\\SitemapController::generateAction');

        return $factory;
    }
}
