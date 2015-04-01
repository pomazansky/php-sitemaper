<?php

namespace PhpSitemaper\Fetchers;

/**
 * Interface for multi-threaded HTTP(S) client
 *
 * Interface IFetcher
 * @package Sitemap\Fetchers
 */
interface IMultiFetcher
{
    /**
     * Sets base URL
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl);

}
