<?php

namespace PhpSitemaper\Exporters;

use PhpSitemaper\ResourceEntry;

/**
 * Sitemap XML exporter interface
 *
 * Interface ISitemapExporter
 * @package Sitemap\Exporters
 */
interface ExporterInterface
{
    /**
     * Sets export file path
     *
     * @param string $filename
     */
    public function setFilename($filename);

    /**
     * Sets base URL
     *
     * @param $baseUrl
     */
    public function setBaseUrl($baseUrl);

    /**
     * Sets params and initializes document
     *
     * @param string $mode
     */
    public function startDocument($mode = 'sitemap');

    /**
     * Adds URL with optional params to XML document
     *
     * @param ResourceEntry $resource
     * @return
     */
    public function attachUrl(ResourceEntry $resource);

    /**
     * Adds Sitemap file URL to Sitemap Index
     *
     * @param $loc
     * @param string $lastMod
     */
    public function attachSitemap($loc, $lastMod);

    /**
     * Saves XML to file
     */
    public function save();
}
