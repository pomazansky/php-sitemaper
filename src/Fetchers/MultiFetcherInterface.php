<?php

namespace PhpSitemaper\Fetchers;

use GuzzleHttp\Event\CompleteEvent;

/**
 * Interface for multi-threaded HTTP(S) client
 *
 * Interface IFetcher
 * @package Sitemap\Fetchers
 */
interface MultiFetcherInterface
{
    /**
     * Sets base URL
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl);

    /**
     * Does multiple HTTP HEAD requests
     *
     * @param array $urls
     * @param callable $complete
     */
    public function headPool(array $urls, callable $complete);

    /**
     * Callback method for HTTP HEAD request success
     *
     * @param CompleteEvent $event
     */
    public function onHeadComplete(CompleteEvent $event);

    /**
     * Does multiple HTTP GET requests
     *
     * @param array $urls
     * @param callable $complete
     */
    public function getPool(array $urls, callable $complete);

    /**
     * Callback for HTTP GET request success
     *
     * @param CompleteEvent $event
     */
    public function onGetComplete(CompleteEvent $event);
}
