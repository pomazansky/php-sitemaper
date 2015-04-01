<?php

namespace PhpSitemaper\Exporters;

/**
 * Sitemap XML exporter with XMLWriter
 *
 * Class ExporterXmlWriter
 * @package Sitemap\Exporters
 */
class XmlWriterAdapter implements ExporterInterface
{
    /**
     * Base URL
     *
     * @var string
     */
    private $baseUrl;

    /**
     * XMLWriter object
     *
     * @var \XMLWriter
     */
    private $writer;

    /**
     * Initializes XML document
     */
    public function __construct()
    {
        $this->writer = new \XMLWriter();
    }

    /**
     * Sets export file path
     *
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->writer->openURI($filename);
    }

    /**
     * Sets params and initializes document
     *
     * @param string $mode
     */
    public function startDocument($mode = 'sitemap')
    {
        $this->writer->setIndent(true);
        $this->writer->startDocument('1.0', 'UTF-8');

        switch ($mode) {
            case 'sitemapindex' :
                $this->writer->startElement('sitemapindex');
                break;
            case 'sitemap':
            default :
                $this->writer->startElement('urlset');
        }

        /**
         * Add XSD scheme link for validation
         */
        $this->writer->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        $this->writer->writeAttribute('xsi:schemaLocation',
            "http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/{$mode}.xsd");

        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    }

    /**
     * Sets base URL
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Adds URL with optional params to XML document
     *
     * @param string $loc
     * @param null|string $lastMod
     * @param null|string $changeFreq
     * @param null|float $priority
     */
    public function attachUrl($loc, $lastMod = null, $changeFreq = null, $priority = null)
    {
        $this->writer->startElement('url');

        $this->writer->writeElement('loc', $this->baseUrl . $loc);

        if ($lastMod !== null) {
            $this->writer->writeElement('lastmod', $lastMod);
        }

        if ($changeFreq !== null) {
            $this->writer->writeElement('changefreq', $changeFreq);
        }

        if ($priority !== null) {
            $this->writer->writeElement('priority', number_format($priority, 2));
        }

        $this->writer->endElement();
    }

    /**
     * Adds Sitemap file URL to Sitemap Index
     *
     * @param $loc
     * @param string $lastMod
     */
    public function attachSitemap($loc, $lastMod)
    {
        $this->writer->startElement('sitemap');

        $this->writer->writeElement('loc', $this->baseUrl . $loc);

        $this->writer->writeElement('lastmod', $lastMod);

        $this->writer->endElement();
    }

    /**
     * Saves XML to file
     */
    public function save()
    {
        $this->writer->endElement();
        $this->writer->endDocument();

        $this->writer->flush();

        return true;
    }
}
