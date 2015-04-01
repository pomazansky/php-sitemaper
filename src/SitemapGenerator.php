<?php

namespace PhpSitemaper;

use PhpSitemaper\Exporters\ISitemapExporter;
use PhpSitemaper\Fetchers\IMultiFetcher;
use PhpSitemaper\Parsers\IParser;

/**
 * Class SitemapGenerator
 * @package src
 */
class SitemapGenerator
{

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
     * Array of enlisted pages
     *
     * @var Resource[]
     */
    private $resources = [];

    /**
     * Array of pages to fetch
     *
     * @var array
     */
    private $fetchQueue;

    /**
     * Object of HTTP client
     *
     * @var IMultiFetcher
     */
    private $fetcher;

    /**
     * HTML parser object
     *
     * @var IParser
     */
    private $parser;

    /**
     * XML exporter object
     *
     * @var ISitemapExporter
     */
    private $exporter;

    /**
     * Nesting level counter
     *
     * @var int
     */
    private $i;

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
     * @param IMultiFetcher $fetcher
     */
    public function setFetcher(IMultiFetcher $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    /**
     * Sets HTML parser object
     *
     * @param IParser $parser
     */
    public function setParser(IParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Sets XML parser object
     *
     * @param ISitemapExporter $exporter
     */
    public function setExporter(ISitemapExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    /**
     * Callback for first touching (retrieving only server response headers)
     *
     * @param string $url
     * @param array $headers
     */
    public function touchResource($url, $headers)
    {
        $resource = new Resource($url, $this->config, $this->i);
        $resource->setHeaders($headers);

        $this->stats->oneScanned($this->i);

        if (!$resource->isValidContent()) {
            return;
        }

        $this->resources[$resource->getUrl()] = $resource;

        $this->stats->oneAdded($this->i);

        if ($this->i < $this->config->parseLevel && $resource->isHtml()) {
            $this->addToGetQueue($url);
        }
    }

    /**
     * Page parsing and adding retrieved URLs to queue
     *
     * @param string $url
     * @param string $html
     */
    public function parseResource($url, $html)
    {
        $this->parser->setHtml($html);

        $parsedLinks = $this->parser->parse();
        $filteredLinks = $this->filterLinks($parsedLinks, $url);

        foreach ($filteredLinks as $link) {
            $this->addToHeadQueue($link);
        }
    }

    /**
     * Filters founded links due to Sitemap specs
     *
     * @param array $parsedLinks
     * @param string $currentUrl
     * @return array
     */
    private function filterLinks(array $parsedLinks = [], $currentUrl)
    {
        $filteredLinks = [];

        foreach ($parsedLinks as $link) {

            $url = parse_url($link);

            if (!empty($url['path']) && strpos($url['path'], './') !== false) {
                $url['path'] = $this->reformatPath($url['path'], $currentUrl);
            }

            $baseUrl = $this->getBaseUrlParts();

            if (!empty($url['scheme']) && $url['scheme'] !== $baseUrl['scheme']) {
                continue;
            }

            if (!empty($url['host']) && $url['host'] !== $baseUrl['host']) {
                continue;
            }

            if (!empty($url['port']) && $url['port'] !== $baseUrl['port']) {
                continue;
            }

            $path = !empty($url['path']) ? $url['path'] : '/';
            $query = !empty($url['query']) ? "?{$url['query']}" : '';
            $fragment = /*isset($url['fragment']) ? "#{$url['fragment']}" :*/
                '';

            $filteredLinks[] = "$path$query$fragment";
        }

        return array_unique($filteredLinks);
    }

    /**
     * Transforms relative URL to absolute
     *
     * @param string $pathStr
     * @param string $basePathStr
     * @return string
     */
    public function reformatPath($pathStr, $basePathStr)
    {

        $basePathStr = trim($basePathStr, '/');

        $path = explode('/', $pathStr);
        $newPath = [];

        $basePath = explode('/', $basePathStr);

        foreach ($path as $dir) {
            if ($dir === '.') {
                continue;
            }
            if ($dir === '..') {
                array_pop($basePath);
                continue;
            }
            $newPath[] = $dir;
        }

        $newPath = array_merge($basePath, $newPath);
        $newPath = '/' . implode('/', $newPath);

        return $newPath;
    }

    /**
     * Return base URL as array of elements
     *
     * @return array
     */
    public function getBaseUrlParts()
    {
        return $this->baseUrl;
    }

    /**
     * Adds URL to HEAD queue
     *
     * @param string $url
     */
    public function addToHeadQueue($url)
    {
        if (!array_key_exists($url, $this->resources) && !in_array($url, $this->fetchQueue['head'][$this->i + 1],
                true)
        ) {
            $this->fetchQueue['head'][$this->i + 1][] = $url;
        }
    }

    /**
     * Adds URL to GET queue
     *
     * @param string $url
     */
    public function addToGetQueue($url)
    {

        if (!in_array($url, $this->fetchQueue['get'][$this->i],
                true) && !in_array($url, $this->fetchQueue['get'][$this->i + 1], true)
        ) {
            $this->fetchQueue['get'][$this->i][] = $url;
        }

    }

    /**
     * Sitemap generation execution
     */
    public function execute()
    {
        $this->fetchQueue['head'][0][] = '/';
        $this->fetchQueue['get'][0] = [];

        $this->stats->setStart(microtime(true));
        $this->stats->newLevel(0);
        $this->stats->inQueue(0, 1);

        $this->fetcher->setBaseUrl($this->getBaseUrl());

        for ($this->i = 0; $this->i < $this->config->parseLevel; $this->i++) {
            $this->fetchQueue['head'][$this->i + 1] = [];
            $this->fetchQueue['get'][$this->i + 1] = [];

            $this->stats->newLevel($this->i);
            $this->stats->inQueue($this->i, count($this->fetchQueue['head'][$this->i]));

            $this->fetcher->headPool($this->fetchQueue['head'][$this->i], [$this, 'touchResource']);
            $this->fetcher->getPool($this->fetchQueue['get'][$this->i], [$this, 'parseResource']);

            if (count($this->fetchQueue['head'][$this->i + 1]) === 0) {
                break;
            }
        }
        $this->stats->setEnd();

        $this->export();
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

            $this->exporter->attachUrl($resource->getUrl(), $resource->getLastMod(), $resource->getChangeFreq(),
                $resource->getPriority());

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
        return $this->getBaseUrlParts()['host'] . '-' . time() . "-{$mode}.xml";
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

    /**
     * Generates process id
     *
     * @return string
     */
    public static function genId()
    {
        return time() . '-' . uniqid();
    }
}