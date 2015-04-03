<?php

namespace PhpSitemaper;

use PhpSitemaper\Exporters\ExporterInterface;
use PhpSitemaper\Fetchers\MultiFetcherInterface;
use PhpSitemaper\Parsers\ParserInterface;

/**
 * Class SitemapGenerator
 * @package PhpSitemaper
 */
class SitemapGenerator
{

    /**
     * Array of enlisted pages
     *
     * @var \PhpSitemaper\ResourceEntry[]
     */
    private $resources = [];

    /**
     * Sitemap Index file path
     *
     * @var string|null
     */
    private $sitemapIndexFile;

    /**
     * Array of Sitemap files
     *
     * @var array
     */
    private $sitemapFiles = [];
    /**
     * Sitemap generation config object
     *
     * @var SitemapConfig
     */
    private $config;

    /**
     * Base URL
     *
     * @var array
     */
    private $baseUrl;

    /**
     * Object of HTTP client
     *
     * @var MultiFetcherInterface
     */
    private $fetcher;

    /**
     * HTML parser object
     *
     * @var ParserInterface
     */
    private $parser;

    /**
     * XML exporter object
     *
     * @var ExporterInterface
     */
    private $exporter;

    /**
     * Statistic gethering object
     *
     * @var Stat
     */
    private $stats;

    /**
     * Initialized default config
     */
    public function __construct()
    {
        $this->config = new SitemapConfig();
    }

    /**
     * Generates process id
     *
     * @return string
     */
    public static function genSessionId()
    {
        return time() . '-' . uniqid();
    }

    /**
     * Sets config
     *
     * @param SitemapConfig $config
     */
    public function setConfig(SitemapConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Sets HTTP client object
     *
     * @param MultiFetcherInterface $fetcher
     */
    public function setFetcher(MultiFetcherInterface $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    /**
     * Sets HTML parser object
     *
     * @param ParserInterface $parser
     */
    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Sets XML parser object
     *
     * @param ExporterInterface $exporter
     */
    public function setExporter(ExporterInterface $exporter)
    {
        $this->exporter = $exporter;
    }

    /**
     * Return base URL as string
     *
     * @return string
     */
    public function getBaseUrl()
    {
        $scheme = $this->baseUrl['scheme'] . '://';
        $host = $this->baseUrl['host'];
        $port = !empty($this->baseUrl['port']) ? ':' . $this->baseUrl['port'] : '';
        $path = $this->baseUrl['path'];

        return "$scheme$host$port$path";
    }

    /**
     * Sets base URL
     *
     * @param string $url
     */
    public function setBaseUrl($url)
    {
        $url = parse_url($url);

        if (empty($url['host']) && empty($url['scheme']) && !empty($url['path'])) {
            $url['host'] = $url['path'];
            $url['scheme'] = 'http';
            unset($url['path']);
        };

        $scheme = $url['scheme'] === 'https' ? 'https' : 'http';
        $host = $url['host'];
        $port = !empty($url['port']) ? ':' . $url['port'] : '';
        $path = '';

        $this->baseUrl = ['scheme' => $scheme, 'host' => $host, 'port' => $port, 'path' => $path];
    }

    /**
     * Exports URLs to XML files
     */
    private function export()
    {
        $writtenUrls = 0;
        $currentFileIndex = 0;

        $this->sitemapFiles[] = $this->genSitemapFilename();
        $this->exporter->setBaseUrl($this->getBaseUrl());
        $this->exporter->setFilename('download/' . $this->sitemapFiles[0]);
        $this->exporter->startDocument();

        foreach ($this->resources as $resource) {
            $this->exporter->attachUrl($resource);

            $writtenUrls++;
            if ($writtenUrls === 50000 || filesize('download/' . $this->sitemapFiles[$currentFileIndex]) > 10484000) {
                $this->exporter->save();
                $currentFileIndex++;
                $writtenUrls = 0;
                $filename = $this->genSitemapFilename();
                $this->sitemapFiles[] = $filename;
                $this->exporter->setFilename('download/' . $filename);
                $this->exporter->startDocument();
            }

        }

        $this->exporter->save();

        if ($this->config->gzip) {
            foreach ($this->sitemapFiles as &$filenameGZ) {
                $this->gzFile('download/' . $filenameGZ);
                $filenameGZ .= '.gz';
            }
            unset($filenameGZ);
        }

        if (count($this->sitemapFiles) > 1) {
            $this->sitemapIndexFile = $this->genSitemapFilename('sitemapindex');
            $this->exporter->setFilename($this->sitemapIndexFile);
            $this->exporter->startDocument('sitemapindex');

            foreach ($this->sitemapFiles as $filename) {
                $this->exporter->attachSitemap($this->getBaseUrl() . '/' . $filename, date(DATE_W3C, time()));
            }

            $this->exporter->save();

            if ($this->config->gzip) {
                $this->gzFile('download/' . $this->sitemapIndexFile);
                $this->sitemapIndexFile .= '.gz';
            }
        }

    }

    /**
     * Generates Sitemap filename
     *
     * @param string $mode
     * @return string
     */
    private function genSitemapFilename($mode = 'sitemap')
    {
        return $this->baseUrl['host'] . '-' . time() . "-{$mode}.xml";
    }

    /**
     * Gzips file and deletes source
     *
     * @param string $sourceFile
     */
    private function gzFile($sourceFile)
    {
        file_put_contents("compress.zlib://$sourceFile" . '.gz', file_get_contents($sourceFile));
        unlink($sourceFile);
    }

    /**
     * Returns enlisted URLs count
     *
     * @return int
     */
    public function getResourcesCount()
    {
        return count($this->resources);
    }

    /**
     * Returns Sitemap files list
     *
     * @return array
     */
    public function getSitemapFiles()
    {
        return $this->sitemapFiles;
    }

    /**
     * Return Sitemap Index filename
     *
     * @return null|string
     */
    public function getSitemapIndexFile()
    {
        return $this->sitemapIndexFile;
    }

    /**
     * Sets statistic gethering object
     *
     * @param Stat $stats
     */
    public function setStats(Stat $stats)
    {
        $this->stats = $stats;
    }

    public function execute()
    {
        $crawler = new Crawler($this);
        $crawler->setConfig($this->config);
        $crawler->setBaseUrl($this->baseUrl);
        $crawler->setFetcher($this->fetcher);
        $crawler->setParser($this->parser);
        $crawler->setStats($this->stats);

        $crawler->execute();
        $this->resources = $crawler->getResources();
        $this->export();
    }
}
