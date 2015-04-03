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
     * @var \PhpSitemaper\Resource[]
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
     * Nesting level counter
     *
     * @var int
     */
    private $currentLevel;

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
    public static function genId()
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
     * Callback for first touching (retrieving only server response headers)
     *
     * @param string $url
     * @param array $headers
     */
    public function touchResource($url, $headers)
    {
        $resource = new Resource($url, $this->config, $this->currentLevel);
        $resource->setHeaders($headers);

        $this->stats->oneScanned($this->currentLevel);

        if (!$resource->isValidContent()) {
            return;
        }

        $this->resources[$resource->getUrl()] = $resource;

        $this->stats->oneAdded($this->currentLevel);

        if ($this->currentLevel < $this->config->parseLevel && $resource->isHtml()) {
            $this->addToGetQueue($url);
        }
    }

    /**
     * Adds URL to GET queue
     *
     * @param string $url
     */
    public function addToGetQueue($url)
    {

        if (!in_array($url, $this->fetchQueue['get'][$this->currentLevel], true)
            && !in_array($url, $this->fetchQueue['get'][$this->currentLevel + 1], true)
        ) {
            $this->fetchQueue['get'][$this->currentLevel][] = $url;
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

        foreach ($filteredLinks as $url) {
            $this->addToHeadQueue($url);
        }
    }

    /**
     * Filters founded links due to Sitemap specs
     *
     * @param array $parsedLinks
     * @param string $currentUrl
     * @return array
     */
    private function filterLinks(array $parsedLinks, $currentUrl)
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
        if (!array_key_exists($url, $this->resources)
            && !in_array($url, $this->fetchQueue['head'][$this->currentLevel + 1], true)
        ) {
            $this->fetchQueue['head'][$this->currentLevel + 1][] = $url;
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

        for ($this->currentLevel = 0; $this->currentLevel < $this->config->parseLevel; $this->currentLevel++) {
            $this->fetchQueue['head'][$this->currentLevel + 1] = [];
            $this->fetchQueue['get'][$this->currentLevel + 1] = [];

            $this->stats->newLevel($this->currentLevel);
            $this->stats->inQueue($this->currentLevel, count($this->fetchQueue['head'][$this->currentLevel]));

            $this->fetcher->headPool($this->fetchQueue['head'][$this->currentLevel], [$this, 'touchResource']);
            $this->fetcher->getPool($this->fetchQueue['get'][$this->currentLevel], [$this, 'parseResource']);

            if (count($this->fetchQueue['head'][$this->currentLevel + 1]) === 0) {
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
            $this->exporter->attachUrl(
                $resource->getUrl(),
                $resource->getLastMod(),
                $resource->getChangeFreq(),
                $resource->getPriority()
            );

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
}
